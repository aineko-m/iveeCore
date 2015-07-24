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
use iveeCore\Config;

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
     * @var iveeCrest\EndpointHandler $endpointHandler instance to be used
     */
    protected $endpointHandler;

    /**
     * @var iveeCrest\MarketPricessor $marketProcessor instance to be used
     */
    protected $marketProcessor;

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
                . '-test       : Test the CREST connectivity by pulling data for the character' . PHP_EOL
                . '-industry   : Update system industry indices and global prices' . PHP_EOL
                . '-facilities : Update facilities (outposts)' . PHP_EOL
                . '-history    : Update market history for items in all configured regions' . PHP_EOL
                . '-prices     : Update market prices for items in all configured regions' . PHP_EOL
                . '-all        : Run all of the above updates' . PHP_EOL
                . '-s          : Silent operation' . PHP_EOL
                . 'Multiple options can be specified' . PHP_EOL;
            exit();
        }

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

        if ($all OR in_array('-industry', $args))
            $this->updateIndustry($verbose);

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
        } catch (Exception $ex) {
            echo "Test failed" . PHP_EOL
            . get_class($ex) . ': ' . $ex->getMessage();
            exit();
        }
    }

    /**
     * Performs solar system industry indices update and global market prices update
     *
     * @param bool $verbose whether info should be printed to console
     *
     * @return void
     */
    protected function updateIndustry($verbose)
    {
        //do system industry indices update
        $crestIndustrySystemsUpdaterClass = Config::getIveeClassName('CrestIndustrySystemsUpdater');
        $crestIndustrySystemsUpdaterClass::doUpdate($this->endpointHandler, $verbose);

        //do market prices update
        $crestMarketPricesUpdaterClass = Config::getIveeClassName('CrestMarketPricesUpdater');
        $crestMarketPricesUpdaterClass::doUpdate($this->endpointHandler, $verbose);
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
        $crestIndustryFacilitiesUpdaterClass = Config::getIveeClassName('CrestIndustryFacilitiesUpdater');
        $crestIndustryFacilitiesUpdaterClass::doUpdate($this->endpointHandler, $verbose);
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

        //history doesn't need to be updated more than once a day
        $cutoffTs = mktime(0, 0, 0);
        foreach ($regionIds as $regionId) {
            $idsToUpdate = static::getTypeIdsToUpdate($regionId, $cutoffTs, 'lastHistUpdate');
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
        $cutoffTs = time() - Config::getMaxPriceDataAge();
        foreach ($regionIds as $regionId) {
            $idsToUpdate = static::getTypeIdsToUpdate($regionId, $cutoffTs, 'lastPriceUpdate');
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
     *
     * @return array
     */
    protected static function getTypeIdsToUpdate($regionId, $cutoffTs, $dateColumn)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();
        
        //get the Ids that need updating
        $res = $sde->query(
            "SELECT typeID
            FROM invTypes
            WHERE marketGroupID IS NOT NULL
            AND published = 1
            AND typeID < 350000
            AND typeID NOT IN (
                SELECT typeID
                FROM " . Config::getIveeDbName() . ".trackedMarketData
                WHERE regionID = " . (int) $regionId . "
                AND " . $dateColumn . " > '" . date('Y-m-d H:i:s', $cutoffTs) . "'
            );"
        );
        $ret = array();
        while ($tmp = $res->fetch_array(MYSQL_NUM))
            $ret[] = (int) $tmp[0];
        return $ret;
    }
}
