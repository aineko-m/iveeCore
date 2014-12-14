<?php
/**
 * EmdrHistoryUpdate class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/EmdrHistoryUpdate.php
 *
 */

namespace iveeCore\EMDR;

/**
 * EmdrHistoryUpdate handles market history data updates from EMDR
 *
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/EmdrHistoryUpdate.php
 *
 */
class HistoryUpdater
{
    /**
     * @var int $typeID of the item being updated
     */
    protected $typeID;

    /**
     * @var int $regionID of the region the data is from
     */
    protected $regionID;

    /**
     * @var int $generatedAt the unix timestamp of the datas generation
     */
    protected $generatedAt;

    /**
     * @var array $rows the history data rows
     */
    protected $rows;

    /**
     * Constructor
     *
     * @param int $typeID ID of item this history data is from
     * @param int $regionID ID of the region this history data is from
     * @param int $generatedAt unix timestamp of the data generation
     * @param array $rows the history data rows
     *
     * @return \iveeCore\EMDR\HistoryUpdater
     * @throws NoRelevantDataException if 0 relevant rows are given
     */
    public function __construct($typeID, $regionID, $generatedAt, array $rows)
    {
        if (count($rows) < 1) {
            $exceptionClass = \iveeCore\Config::getIveeClassName('NoRelevantDataException');
            throw new $exceptionClass("0 relevant history rows to process");
        }

        $this->typeID      = (int) $typeID;
        $this->regionID    = (int) $regionID;
        $this->generatedAt = (int) $generatedAt;
        $this->rows        = $rows;
    }

    /**
     * Inserts history data into the DB
     *
     * @return void
     */
    public function insertIntoDB()
    {
        $combinedSql = '';
        $latestDate = 0;
        $oldestDate = 9999999999;
        $existingDates = array();
        $sdeClass  = \iveeCore\Config::getIveeClassName('SDE');
        $emdrClass = \iveeCore\Config::getIveeClassName('EmdrConsumer');

        //find newest and oldest dates in data rows
        foreach ($this->rows as $day) {
            $rowdate = (int) strtotime($day[0]);

            //track newest date
            if ($rowdate > $latestDate) $latestDate = $rowdate;

            //track oldest date
            if ($rowdate < $oldestDate) $oldestDate = $rowdate;
        }

        //get dates with existing history data
        $res = $sdeClass::instance()->query(
            "SELECT UNIX_TIMESTAMP(date)
            FROM " . \iveeCore\Config::getIveeDbName() . ".iveePrices
            WHERE typeID = " . $this->typeID . "
            AND regionID = " . $this->regionID . "
            AND date <= '" . date('Y-m-d', $latestDate) . "'
            AND date >= '" . date('Y-m-d', $oldestDate) . "';"
        );
        while ($tmp = $res->fetch_array(MYSQL_NUM))
            $existingDates[(int) $tmp[0]] = 1;

        //iterate over data rows received from EMDR
        foreach ($this->rows as $day) {
            $rowdate = (int) strtotime($day[0]);

            //if row already exists
            if (isset($existingDates[$rowdate])) {
                //do update for 8 latest days of already existind dates, skip all other existing rows
                if ($rowdate + (8 * 24 * 3600) < $latestDate)
                    continue;

                $updateData = array(
                    'tx'   => (int) $day[1],
                    'vol'  => (int) $day[2],
                    'low'  => (float) $day[3],
                    'high' => (float) $day[4],
                    'avg'  => (float) $day[5]
                );

                $where = array(
                    'typeID'   => $this->typeID,
                    'regionID' => $this->regionID,
                    'date'     => date('Y-m-d', $rowdate),
                );

                //build update query
                $combinedSql .= $sdeClass::makeUpdateQuery(\iveeCore\Config::getIveeDbName() . '.iveePrices',
                    $updateData, $where);
            } else { // do insert for all missing data
                $insertData = array(
                    'typeID'   => $this->typeID,
                    'regionID' => $this->regionID,
                    'date'     => date('Y-m-d', $rowdate),
                    'tx'       => (int) $day[1],
                    'vol'      => (int) $day[2],
                    'low'      => (float) $day[3],
                    'high'     => (float) $day[4],
                    'avg'      => (float) $day[5]
                );

                //build insert query
                $combinedSql .= $sdeClass::makeUpsertQuery(\iveeCore\Config::getIveeDbName() . '.iveePrices',
                    $insertData);
            }

        }
        //add stored procedure call to complete the update
        $combinedSql .= "CALL " . \iveeCore\Config::getIveeDbName() . ".iveeCompleteHistoryUpdate(" . $this->typeID
            . ", " . $this->regionID . ", '" . date('Y-m-d H:i:s', $this->generatedAt) . "'); COMMIT;";

        //run all queries
        $sdeClass::instance()->multiQuery($combinedSql);

        if (VERBOSE) {
            $ec = $emdrClass::instance();
            echo "H: " . $ec->getTypeNameById($this->typeID) . ' (' . $this->typeID . '), '
                . $ec->getRegionNameById($this->regionID) . ' ('. $this->regionID . '), '
                . count($this->rows) . " days" . PHP_EOL;
        }
    }
}
