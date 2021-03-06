<?php
/**
 * MarketProcessor class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/MarketProcessor.php
 */

namespace iveeCore\CREST;

use iveeCore\Config;
use iveeCrest\Exceptions\CrestErrorException;
use iveeCrest\Responses\MarketOrderCollection;
use iveeCrest\Responses\MarketOrderCollectionSlim;
use iveeCrest\Responses\MarketTypeHistoryCollection;
use iveeCrest\Responses\Root;

/**
 * MarketProcessor interfaces with the iveeCrest classes to fetch market data and handles updates of the prices DB
 * table of iveeCore. It also offers methods for running batched updates to market history and prices, most useful for
 * running automatized update scripts.
 * Realistic price estimation is separated into it's own class for easy customization.
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/MarketProcessor.php
 */
class MarketProcessor
{
    /**
     * @var \iveeCore\SDE $sde instance for quicker access
     */
    protected static $sde;

    /**
     * @var array $marketTypes holds typeId => typeName
     */
    protected static $marketTypes = [];

    /**
     * @var array $regions holds regionId => regionName
     */
    protected static $regions = [];

    /**
     * @var \iveeCrest\Responses\Root $root the root CREST enpdoint response
     */
    protected $root;

    /**
     * @var bool $verboseBatch controls whether the batch update function should print info to the console
     */
    protected $verboseBatch = false;

    /**
     * @var string $sqlInsertBuffer used to buffer SQL queries for submitting + committing in batches
     */
    protected $sqlInsertBuffer = '';

    /**
     * @var int $lastCommitTs unix timestamp of the last DB commit
     */
    protected $lastCommitTs = 0;

    /**
     * Constructor.
     *
     * @param \iveeCrest\Responses\Root $root to be used
     */
    public function __construct(Root $root)
    {
        $this->root = $root;
        $sdeClass = Config::getIveeClassName('SDE');
        static::$sde = $sdeClass::instance();
    }

    /**
     * Gets the newest history data from CREST, also updating the DB in the process.
     *
     * @param int $typeId of the market type
     * @param int $regionId of the marget region
     * @param bool $cache whether the result of the CREST call should be cached. If another caching layer is present,
     * caching in this call should be disabled
     *
     * @return array holding the history values for the latest day
     */
    public function getNewestHistoryData($typeId, $regionId, $cache = true)
    {
        $ret = $this->processHistoryCollection(
            $this->root->getRegionCollection()->getRegion($regionId)->getMarketHistory($typeId, $cache)
        );
        $this->commitSql();
        return $ret;
    }

    /**
     * Runs the history batch update process for selected market types and regions. This is done with parallel async
     * CURL calls and DB submission + committing in batches.
     *
     * @param array $typeIds of the market types
     * @param array $regionIds of the marget regions
     * @param bool $verbose whether the items currently being updated should be printed to console
     *
     * @return void
     */
    public function runHistoryBatch(array $typeIds, array $regionIds, $verbose = false)
    {
        $this->verboseBatch = $verbose;
        $regionCollection = $this->root->getRegionCollection();
        foreach (array_unique($regionIds) as $regionId) {
            try {
                $regionCollection->getRegion($regionId)->getMultiMarketHistory(
                    $typeIds,
                    function (MarketTypeHistoryCollection $response) {
                        $this->processHistoryCollection($response);
                    },
                    null,
                    false
                );
            } catch (CrestErrorException $ex) {
                if ($verbose) {
                    echo "Skipping region due to too many CREST errors.\n";
                }
            }
        }

        $this->commitSql();
    }

    /**
     * Processes market history collection, updating the DB and returning the history values for the latest day.
     *
     * @param \iveeCrest\Responses\MarketTypeHistoryCollection $historyCollection to be processed
     *
     * @return array with the latest history values
     */
    protected function processHistoryCollection(MarketTypeHistoryCollection $historyCollection)
    {
        $sdeClass   = Config::getIveeClassName('SDE');
        $iveeDbName = Config::getIveeDbName();

        $data = $historyCollection->gather();
        $typeId = $historyCollection->getTypeId();
        $regionId = $historyCollection->getRegionId();

        //if no history is available in target region, return zeros (no low, high, avg)
        if (count($data) < 1) {
            $ret = array(
                'tx'   => 0,
                'vol'  => 0,
                'date' => mktime(0, 0, 0) - 24 * 3600 //if there was history data, it would be for the past day.
                //TODO: deal with DST change
            );
        } else {
            //find newest and oldest dates in data rows
            $latestDate = max(array_keys($data));
            $oldestDate = min(array_keys($data));

            //get dates with existing history data
            $existingDates = $this->getExistingHistoryDates($iveeDbName, $typeId, $regionId, $latestDate, $oldestDate);

            //iterate over data rows received from CREST
            foreach ($data as $dateTs => $day) {
                //we manually decide between update and insert instead of simply using an "INSERT ... ON DUPLICATE KEY
                //UPDATE" because it causes an autoincrement even when no insert happened, which can realistically
                //exhaust INT based DB Ids.

                //if row already exists
                if (isset($existingDates[$dateTs])) {
                    //do update for last day, skip all other existing rows
                    if ($dateTs < $latestDate) {
                        continue;
                    }

                    $updateData = array(
                        'tx'   => (int) $day->orderCount,
                        'vol'  => (int) $day->volume,
                        'low'  => (float) $day->lowPrice,
                        'high' => (float) $day->highPrice,
                        'avg'  => (float) $day->avgPrice
                    );

                    $where = array(
                        'typeID'   => $typeId,
                        'regionID' => $regionId,
                        'date'     => date('Y-m-d', $dateTs),
                    );

                    //build update query
                    $this->submitSql(
                        $sdeClass::makeUpdateQuery(
                            $iveeDbName . '.marketHistory',
                            $updateData,
                            $where
                        )
                    );

                    //keep latest date data for return
                    if ($dateTs == $latestDate) {
                        $ret = $updateData;
                        $ret['date'] = $dateTs;
                    }
                } else { // do insert for all missing data
                    $insertData = array(
                        'typeID'   => $typeId,
                        'regionID' => $regionId,
                        'date'     => date('Y-m-d', $dateTs),
                        'tx'       => (int) $day->orderCount,
                        'vol'      => (int) $day->volume,
                        'low'      => (float) $day->lowPrice,
                        'high'     => (float) $day->highPrice,
                        'avg'      => (float) $day->avgPrice
                    );

                    //build insert query
                    $this->submitSql(
                        $sdeClass::makeUpsertQuery(
                            $iveeDbName . '.marketHistory',
                            $insertData
                        )
                    );

                    //keep latest date data for return
                    if ($dateTs == $latestDate) {
                        $ret = $insertData;
                        $ret['date'] = $dateTs;
                    }
                }
            }
        }

        //add stored procedure call to complete the update
        $this->submitSql(
            "CALL " . $iveeDbName . ".completeHistoryUpdate(" . $typeId . ", "
            . $regionId . ", '" . date('Y-m-d H:i:s') . "');"
        );

        if ($this->verboseBatch) {
            static::printTypeAndRegion('H', $typeId, $regionId);
        }

        //TODO: Decide if we should invalidate caches or not.

        //return only the newest history data
        return $ret;
    }

    /**
     * Fetches the timestamps of the days for which history data is already available.
     *
     * @param string $iveeDbName the name of iveeCores DB
     * @param int $typeId of the type
     * @param int $regionId to be checked in
     * @param int $latestDate timestamp defining the most recent day for the range to be fetched
     * @param int $oldestDate timestamp defining the oldest day for the range to be fetched
     *
     * @return array with the timestamps as key
     */
    protected function getExistingHistoryDates($iveeDbName, $typeId, $regionId, $latestDate, $oldestDate)
    {
        $existingDates = [];
        $res = static::$sde->query(
            "SELECT UNIX_TIMESTAMP(date)
            FROM " . $iveeDbName . ".marketHistory
            WHERE typeID = " . $typeId . "
            AND regionID = " . $regionId . "
            AND date <= '" . date('Y-m-d', $latestDate) . "'
            AND date >= '" . date('Y-m-d', $oldestDate) . "';"
        );
        while ($tmp = $res->fetch_array(MYSQLI_NUM)) {
            $existingDates[(int) $tmp[0]] = 1;
        }

        return $existingDates;
    }

    /**
     * Gets the newest price data from CREST, also updating the DB in the process.
     *
     * @param int $typeId of the market type
     * @param int $regionId of the marget region
     *
     * @return array
     */
    public function getNewestPriceData($typeId, $regionId)
    {
        $ret = $this->processOrderCollection(
            $this->root->getRegionCollection()->getRegion($regionId)->getMarketOrders($typeId)
        );
        $this->commitSql();
        return $ret;
    }

    /**
     * Runs the price batch update process for selected market types and regions. This is done with parallel async CURL
     * calls and DB submission + committing in batches.
     *
     * @param array $typeIds of the market types
     * @param array $regionIds of the marget regions
     * @param bool $verbose whether the items currently being updated should be printed to console
     *
     * @return void
     */
    public function runPriceBatch(array $typeIds, array $regionIds, $verbose = false)
    {
        $this->verboseBatch = $verbose;
        $regionCollection = $this->root->getRegionCollection();
        foreach (array_unique($regionIds) as $regionId) {
            try {
                //only do "huge" update if configured and if a decent amount of types were requested
                if (Config::getHugePriceUpdateBatches() and count($typeIds) > 200) {
                    $this->processSlimOrderCollection(
                        $regionCollection->getRegion($regionId)->getAllMarketOrdersCollection(),
                        $typeIds
                    );
                } else {
                    $regionCollection->getRegion($regionId)->getMultiMarketOrders(
                        $typeIds,
                        function (MarketOrderCollection $response) {
                            $this->processOrderCollection($response);
                        },
                        null,
                        false
                    );
                }
            } catch (CrestErrorException $ex) {
                if ($verbose) {
                    echo "Skipping region due to too many CREST errors.\n";
                }
            }
        }
        $this->commitSql();
    }

    /**
     * Processes market order collection, calculating realistic prices and other values and doing the DB upsert.
     *
     * @param \iveeCrest\Responses\MarketOrderCollection $orderCollection with both buy and sell order items
     *
     * @return array with the calculated values
     */
    protected function processOrderCollection(MarketOrderCollection $orderCollection)
    {
        $typeId = $orderCollection->getTypeId();
        $regionId = $orderCollection->getRegionId();
        $priceEstimatorClass = Config::getIveeClassName('CrestPriceEstimator');
        $estimator = new $priceEstimatorClass($this);
        $priceData = $estimator->calcValues($orderCollection->gather(), $typeId, $regionId);

        //DB update or insert
        $this->upsertPriceDb($priceData, $typeId, $regionId);
        return $priceData;
    }

    /**
     * Processes "slim" market order collection with all region wide orders, calculating realistic prices and other
     * values and doing the DB upsert.
     *
     * @param \iveeCrest\Responses\MarketOrderCollectionSlim $orderCollectionSlim with all region orders
     * @param array $typeIds with all IDs that should be updated
     *
     * @return void
     */
    protected function processSlimOrderCollection(MarketOrderCollectionSlim $orderCollectionSlim, array $typeIds)
    {
        $regionId = $orderCollectionSlim->getRegionId();
        $priceEstimatorClass = Config::getIveeClassName('CrestPriceEstimator');
        $estimator = new $priceEstimatorClass($this);
        $allOrders = $orderCollectionSlim->gather();
        foreach ($typeIds as $typeId) {
            if (isset($allOrders[$typeId])) {
                $priceData = $estimator->calcValues($allOrders[$typeId], $typeId, $regionId);
            } else {
                $priceData = $estimator->calcValues([], $typeId, $regionId);
            }
            $this->upsertPriceDb($priceData, $typeId, $regionId);
        }
    }

    /**
     * Inserts or updates the pricing values in the DB.
     *
     * @param array $priceData with the values
     * @param int $typeId
     * @param int $regionId
     *
     * @return void
     */
    protected function upsertPriceDb(array $priceData, $typeId, $regionId)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        //clear columns that don't belong in this update
        if (isset($priceData['avgVol'])) {
            unset($priceData['avgVol']);
        }
        if (isset($priceData['avgTx'])) {
            unset($priceData['avgTx']);
        }
        if (isset($priceData['lastHistUpdate'])) {
            unset($priceData['lastHistUpdate']);
        }
        if (isset($priceData['lastPriceUpdate'])) {
            unset($priceData['lastPriceUpdate']);
        }

        if (count($priceData) > 0) {
            //check if row already exists
            $res = static::$sde->query(
                "SELECT regionID
                FROM " . Config::getIveeDbName() . ".marketPrices
                WHERE regionID = " . (int) $regionId . "
                AND typeID = " . (int) $typeId . "
                AND date = '" . date('Y-m-d', time()) . "';"
            );

            $where = array(
                'typeID'   => $typeId,
                'regionID' => $regionId,
                'date'     => date('Y-m-d', time())
            );

            //if row already exists
            if ($res->num_rows == 1) {
                //build update query
                $this->submitSql(
                    $sdeClass::makeUpdateQuery(Config::getIveeDbName() . '.marketPrices', $priceData, $where)
                );
            } else {
                //build insert query
                $this->submitSql(
                    $sdeClass::makeUpsertQuery(Config::getIveeDbName() . '.marketPrices', array_merge($priceData, $where))
                );
            }
        }

        //add stored procedure call to complete the update
        $this->submitSql(
            "CALL " . Config::getIveeDbName() . ".completePriceUpdate("
            . (int) $typeId . ", " . (int) $regionId . ", '" . date('Y-m-d H:i:s', time()) . "');"
        );

        if ($this->verboseBatch) {
            static::printTypeAndRegion('P', $typeId, $regionId);
        }

        //TODO: Decide if we should invalidate caches or not.
    }

    /**
     * Submits SQL query to buffer for deferred (batched) commit to DB every second.
     *
     * @param string $sql the SQL query
     *
     * @return void
     */
    protected function submitSql($sql)
    {
        //append sql to buffer
        if (isset($sql)) {
            $this->sqlInsertBuffer .= $sql;
        }

        //auto-commit after every second
        if (time() - $this->lastCommitTs >= 1) {
            $this->commitSql();
        }
    }

    /**
     * Sends buffered queries to DB and commits them, then clears the buffer.
     *
     * @return void
     */
    protected function commitSql()
    {
        $this->lastCommitTs = time();
        static::$sde->multiQuery($this->sqlInsertBuffer . 'COMMIT;');
        $this->sqlInsertBuffer = '';
    }

    /**
     * Prints a message and the type and region name to console for given IDs.
     *
     * @param string $msg to be printed
     * @param int $typeId of the type
     * @param int $regionId of the region
     *
     * @return void
     */
    protected static function printTypeAndRegion($msg, $typeId, $regionId)
    {
        if (count(static::$marketTypes) < 1) {
            static::loadNames();
        }

        echo $msg . ': ' . static::$regions[$regionId] . ' (' . $regionId . '), '
            . (isset(static::$marketTypes[$typeId]) ? static::$marketTypes[$typeId] : '-no name-')
            . ' (' . $typeId . ")\n";
    }

    /**
     * Loads the names of types and regions.
     *
     * @return void
     */
    protected static function loadNames()
    {
        //load IDs of items to track on market
        $typeRes = static::$sde->query(
            "SELECT typeID, typeName
            FROM invTypes
            WHERE (marketGroupID IS NOT NULL OR published = 1);"
        );
        while ($tmp = $typeRes->fetch_array(MYSQLI_NUM)) {
            static::$marketTypes[(int) $tmp[0]] = $tmp[1];
        }

        //load regionIDs
        $regionRes = static::$sde->query(
            "SELECT regionID, regionName
            FROM mapRegions;"
        );
        while ($tmp = $regionRes->fetch_array(MYSQLI_NUM)) {
            static::$regions[(int) $tmp[0]] = $tmp[1];
        }
    }
}
