<?php
/**
 * IveeUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IveeUpdater.php
 */

namespace iveeCore\CREST;
use iveeCore\Config, iveeCrest\EndpointHandler;

/**
 * IveeUpdater provides the necessary functionality for running CREST updates from the CLI.
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IveeUpdater.php
 */
class IveeUpdater
{
    /**
     * @var \iveeCrest\EndpointHandler $endpointHandler instance to be used
     */
    protected $endpointHandler;

    /**
     * @var \iveeCrest\MarketPricessor $marketProcessor instance to be used
     */
    protected $marketProcessor;

    /**
     * @var \iveeCore\SDE $sde for DB connectivity
     */
    protected static $sde;

    /**
     * Starts the updating. Prints help to console if insufficient arguments are passed.
     *
     * @param array $args the script arguments
     * @param array $regionIds that should be updated
     *
     * @return void
     */
    public function run(array $args, array $regionIds)
    {
        if (count($args) < 2) {
            echo '== ' . Config::VERSION . ' Updater ==' . PHP_EOL
                . 'Available options:' . PHP_EOL
                . '-test         : Test the CREST connectivity by pulling data for the character' . PHP_EOL
                . '-indices      : Update system industry indices' . PHP_EOL
                . '-globalprices : Update global prices (average, adjusted)' . PHP_EOL
                . '-facilities   : Update facilities (outposts)' . PHP_EOL
                . '-history      : Update market history for items in all configured regions' . PHP_EOL
                . '-prices       : Update market prices for items in all configured regions' . PHP_EOL
                . '-all          : Run all of the above updates' . PHP_EOL
                . '-s            : Silent operation' . PHP_EOL
                . 'Multiple options can be specified' . PHP_EOL;
            exit();
        }

        $sdeClass = Config::getIveeClassName('SDE');
        static::$sde = $sdeClass::instance();

        if (in_array('-s', $args))
            $verbose = false;
        else
            $verbose = true;

        if (in_array('-all', $args))
            $all = true;
        else
            $all = false;

        //setup CREST Client and EndpointHandler
        $clientClass = Config::getIveeClassName('Client');
        $client = new $clientClass;
        $endpointHandlerClass = Config::getIveeClassName('EndpointHandler');
        $this->endpointHandler = new $endpointHandlerClass($client);

        if (in_array('-test', $args))
            $this->testCrest();

        if ($all OR in_array('-indices', $args))
            $this->updateIndices($verbose);

        if ($all OR in_array('-globalprices', $args))
            $this->updateGlobalPrices($verbose);

        if ($all OR in_array('-facilities', $args))
            $this->updateFacilities($verbose);

        if ($all OR in_array('-history', $args))
            $this->updateHistory($regionIds, $verbose);

        if ($all OR in_array('-prices', $args))
            $this->updatePrices($regionIds, $verbose);

        if ($verbose)
            echo 'Peak memory usage: ' . ceil(memory_get_peak_usage(true) / 1024) . 'KiB' . PHP_EOL;
    }

    /**
     * Tests the CREST connection and prints the output of the access token verify call.
     *
     * @return void
     */
    protected function testCrest()
    {
        try {
            $charData = $this->endpointHandler->verifyAccessToken();
            echo "Test OK" . PHP_EOL;
            print_r($charData);
        } catch (\Exception $ex) {
            echo "Test failed" . PHP_EOL
            . get_class($ex) . ': ' . $ex->getMessage();
            exit();
        }
    }

    /**
     * Performs solar system industry indices update
     *
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updateIndices($verbose)
    {
        //get most recent update date
        $res = static::$sde->query(
            "SELECT UNIX_TIMESTAMP(lastUpdate) as lastUpdateTs
            FROM " . Config::getIveeDbName() . ".trackedCrestUpdates
            WHERE name = 'industryIndices';"
        );

        if ($res->num_rows > 0)
            $lastUpdateTs = (int) $res->fetch_assoc()['lastUpdateTs'];
        else
            $lastUpdateTs = 0;

        //if roughly an hour passed, do the update
        //system indices are update at least once per hour on CCPs side
        if (time() - $lastUpdateTs > 3500) {
            //do system industry indices update
            $crestIndustryIndicesUpdaterClass = Config::getIveeClassName('CrestIndustryIndicesUpdater');
            $crestIndustryIndicesUpdaterClass::doUpdate($this->endpointHandler, $verbose);
            
            $sql = \iveeCore\SDE::makeUpsertQuery(
                Config::getIveeDbName() . '.trackedCrestUpdates',
                array(
                    'name' => 'industryIndices',
                    'lastUpdate' => date('Y-m-d H:i:s', time())
                ),
                array('lastUpdate' => date('Y-m-d H:i:s', time()))
            );
            static::$sde->multiQuery($sql . ' COMMIT;');
            
        } elseif($verbose)
            echo "System industry indices still up-to-date, skipping.\n";
    }

    /**
     * Performs global market prices update.
     * Although the values for the global prices are only recalculated on CCPs side once a week, it is not really
     * determined when, so we do one update per day.
     *
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updateGlobalPrices($verbose)
    {
        //get most recent update date
        $res = static::$sde->query(
            "SELECT UNIX_TIMESTAMP(date) as dateTs
            FROM " . Config::getIveeDbName() . ".globalPrices
            WHERE typeID = 34
            ORDER BY date DESC
            LIMIT 1;"
        )->fetch_assoc();

        //if a day passed, do update
        //values only change about once a week, but this can't be relied upon, so we do a daily update
        if ($res['dateTs'] + 24 * 3600 < time()) {
            //do global prices update
            $crestGlobalPricesUpdaterClass = Config::getIveeClassName('CrestGlobalPricesUpdater');
            $crestGlobalPricesUpdaterClass::doUpdate($this->endpointHandler, $verbose);
        } elseif ($verbose) {
            echo "Global prices still up-to-date, skipping\n";
        }
    }

    /**
     * Performs facilities (outposts) update
     *
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updateFacilities($verbose)
    {
        //get most recent update date
        $res = static::$sde->query(
            "SELECT UNIX_TIMESTAMP(lastUpdate) as lastUpdateTs
            FROM " . Config::getIveeDbName() . ".trackedCrestUpdates
            WHERE name = 'facilities';"
        );

        if ($res->num_rows > 0)
            $lastUpdateTs = (int) $res->fetch_assoc()['lastUpdateTs'];
        else
            $lastUpdateTs = 0;

        //if roughly three hours passed, do the update
        if (time() - $lastUpdateTs > 10000) {
            $crestFacilitiesUpdaterClass = Config::getIveeClassName('CrestFacilitiesUpdater');
            $crestFacilitiesUpdaterClass::doUpdate($this->endpointHandler, $verbose);

            $sql = \iveeCore\SDE::makeUpsertQuery(
                Config::getIveeDbName() . '.trackedCrestUpdates',
                array(
                    'name' => 'facilities',
                    'lastUpdate' => date('Y-m-d H:i:s', time())
                ),
                array('lastUpdate' => date('Y-m-d H:i:s', time()))
            );
            static::$sde->multiQuery($sql . ' COMMIT;');
        } elseif($verbose)
            echo "Facilities data still up-to-date, skipping.\n";
    }

    /**
     * Performs market history data update for specified regions, for all items that need updating.
     *
     * @param array $regionIds of the regions that should be updated
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updateHistory(array $regionIds, $verbose)
    {
        if (!isset($this->marketProcessor)) {
            $crestMarketProcessorClass = Config::getIveeClassName('CrestMarketProcessor');
            $this->marketProcessor = new $crestMarketProcessorClass($this->endpointHandler);
        }

        if ($verbose)
            echo get_called_class() . " starting market history update\n";

        //history doesn't need to be updated more than once a day
        $cutoffTs = mktime(0, 0, 0);
        foreach ($regionIds as $regionId) {
            $idsToUpdate = static::getTypeIdsToUpdate($regionId, $cutoffTs, 'lastHistUpdate', $this->endpointHandler);
            if (count($idsToUpdate) > 0) {
                if ($verbose)
                    echo 'Updating history data for ' . count($idsToUpdate) . ' market types in regionId=' . $regionId
                        . PHP_EOL;
                $this->marketProcessor->runHistoryBatch($idsToUpdate, array($regionId), $verbose);
            } else
                if ($verbose)
                    echo 'No history to update for market types in regionId=' . $regionId . PHP_EOL;
            
        }
    }

    /**
     * Performs market price data update for specified regions, for all items that need updating (depending on
     * maxPriceDataAge).
     *
     * @param array $regionIds of the regions that should be updated
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updatePrices(array $regionIds, $verbose)
    {
        if (!isset($this->marketProcessor)) {
            $crestMarketProcessorClass = Config::getIveeClassName('CrestMarketProcessor');
            $this->marketProcessor = new $crestMarketProcessorClass($this->endpointHandler);
        }

        if ($verbose)
            echo get_called_class() . " starting market prices update\n";

        //get the cutoff timestamp for determining what price data age is too old
        $cutoffTs = time() - Config::getMaxPriceDataAge();
        foreach ($regionIds as $regionId) {
            $idsToUpdate = static::getTypeIdsToUpdate($regionId, $cutoffTs, 'lastPriceUpdate', $this->endpointHandler);
            if (count($idsToUpdate) > 0) {
                if ($verbose)
                    echo 'Updating price data for ' . count($idsToUpdate) . ' market types in regionId=' . $regionId
                        . PHP_EOL;
                $this->marketProcessor->runPriceBatch($idsToUpdate, array($regionId), $verbose);
            } else
                if ($verbose)
                    echo 'No prices to update for market types in regionId=' . $regionId . PHP_EOL;
        }
    }

    /**
     * Fetches the typeIds of the items that need updating in a region
     *
     * @param int $regionId to be checked
     * @param int $cutoffTs the unix timestamp to be used to decide if data is too old
     * @param string $dateColumn the DB column to check the timestamp on, either 'lastHistUpdate' or 'lastPriceUpdate'
     * @param \iveeCrest\EndpointHandler $eph to be used
     *
     * @return array
     */
    protected static function getTypeIdsToUpdate($regionId, $cutoffTs, $dateColumn, EndpointHandler $eph)
    {
        //get matket typeIds from CREST
        $marketTypeIds = array_keys($eph->getMarketTypeHrefs());

        //get the subset Ids that need updating and are not Dust-only
        $res = static::$sde->query(
            "SELECT typeID
            FROM invTypes
            WHERE typeID IN (" . implode(', ', $marketTypeIds) . ")
            AND typeID < 350000
            AND typeID NOT IN (
                SELECT typeID
                FROM " . Config::getIveeDbName() . ".trackedMarketData
                WHERE regionID = " . (int) $regionId . "
                AND " . $dateColumn . " > '" . date('Y-m-d H:i:s', $cutoffTs) . "'
            )
            ORDER BY typeID ASC;"
        );
        $ret = [];
        while ($tmp = $res->fetch_array(MYSQL_NUM))
            $ret[] = (int) $tmp[0];
        return $ret;
    }
}