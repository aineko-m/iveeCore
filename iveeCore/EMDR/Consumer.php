<?php
/**
 * Consumer class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/Consumer.php
 *
 */

namespace iveeCore\EMDR;

/**
 * EMDR for IVEE Consumer
 * EmdrConsumer handles the incoming data stream from the relay and passes rowsets to either EmdrPriceUpdate or
 * EmdrHistoryUpdate.
 *
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/Consumer.php
 *
 */
class Consumer
{
    /**
     * @var \iveeCore\EMDR\Consumer $instance the singleton EmdrConsumer instance
     */
    protected static $instance;

    /**
     * @var array $trackedTypeIDs array holding the typeIDs => typeNames of items to track on the market
     */
    protected $trackedTypeIDs = array();

    /**
     * @var array $trackedMarketRegionIDs array holding the IDs of the market regions to be tracked
     */
    protected $trackedMarketRegionIDs = array();

    /**
     * @var array $regions regionID => regionName
     */
    protected $regions;

    /**
     * @var SDE $sde holds the SDE instance, for convenience
     */
    protected $sde;

    /**
     * @var ICache $cache holds a cache instance, if cache use is configured
     */
    protected $cache;

    /**
     * @var string $EmdrPriceUpdateClass holds the class name for EmdrPriceUpdate objects
     */
    protected $emdrPriceUpdateClass;

    /**
     * @var string $EmdrHistoryUpdateClass holds the class name for EmdrHistoryUpdate objects
     */
    protected $emdrHistoryUpdateClass;

    /**
     * Returns singleton instance
     *
     * @return \iveeCore\EMDR\Consumer
     */
    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static;
        return static::$instance;
    }

    /**
     * Constructor
     *
     * @return EmdrConsumer
     */
    protected function __construct()
    {
        if (VERBOSE)
            echo "Instantiating EmdrConsumer" . PHP_EOL . "Getting tracked item IDs... ";

        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');
        $this->sde = $sdeClass::instance();

        $defaultsClass = \iveeCore\Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        $cacheClass = \iveeCore\Config::getIveeClassName('Cache');
        $this->cache = $cacheClass::instance();

        //load IDs of items to track on market
        $res = $this->sde->query(
            "SELECT typeID, typeName
            FROM invTypes
            WHERE marketGroupID IS NOT NULL
            AND published = 1;"
        );
        while ($tmp = $res->fetch_array(MYSQL_NUM))
            $this->trackedTypeIDs[(int) $tmp[0]] = $tmp[1];

        if (VERBOSE)
            echo count($this->trackedTypeIDs)." found." . PHP_EOL;

        //load regionIDs
        $res = $this->sde->query(
            "SELECT regionID, regionName
            FROM mapRegions;"
        );
        while ($tmp = $res->fetch_array(MYSQL_NUM))
            $this->regions[(int) $tmp[0]] = $tmp[1];

        $this->trackedMarketRegionIDs = $defaults->getTrackedMarketRegionIDs();
        $this->emdrPriceUpdateClass   = \iveeCore\Config::getIveeClassName('EmdrPriceUpdater');
        $this->emdrHistoryUpdateClass = \iveeCore\Config::getIveeClassName('EmdrHistoryUpdater');
    }

    /**
     * Main work method.
     * Loops indefinitely unless interrupted. Note that the MySQL connection might time out if no relevant data is
     * received over longer periods, EvE downtimes, for instance.
     *
     * @return void
     */
    public function run()
    {
        if (VERBOSE)
            echo "Starting EMDR data stream" . PHP_EOL;

        //init ZMQ
        $context = new \ZMQContext;
        $subscriber = $context->getSocket(\ZMQ::SOCKET_SUB);

        //Connect to EMDR relay.
        $subscriber->connect(\iveeCore\Config::getEmdrRelayUrl());

        // Disable filtering.
        $subscriber->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");

        if (VERBOSE)
            echo "Processing EMDR data" . PHP_EOL;

        //main loop
        while (true)
            // Receive raw market JSON strings and un-serialize
            $this->handleMarketData(json_decode(gzuncompress($subscriber->recv())));
    }

    /**
     * Processes the received EMDR object.
     *
     * @param \stdClass $marketData the market data object received from EMDR
     *
     * @return void
     */
    protected function handleMarketData(\stdClass $marketData)
    {
        //loop over rowsets (typically just 1)
        foreach ($marketData->rowsets as $rowset) {
            $typeID = (int) $rowset->typeID;
            $regionID = (int) $rowset->regionID;
            $generatedAt = strtotime($rowset->generatedAt);

            //filter data
            if (!$this->filterData($typeID, $regionID, $generatedAt))
                continue;

            //get timestamps from cache or DB
            $timestamps = $this->getTimestamps($typeID, $regionID);

            //order data
            if ($marketData->resultType == 'orders')
                $this->handleOrderData($typeID, $regionID, $generatedAt, $rowset, $timestamps);

            //history data
            elseif ($marketData->resultType == 'history') {
                try {
                    $this->handleHistoryData($typeID, $regionID, $generatedAt, $rowset, $timestamps);
                } catch (\iveeCore\Exceptions\NoRelevantDataException $e) {
                    continue;
                }
            } else { //we received something else
                if (VERBOSE > 2)
                    echo '- skipping unknown data type ' . $marketData->resultType . PHP_EOL;
                continue;
            }

            //update and invalidate caches as necessary
            $this->updateCaches($typeID, $regionID, $timestamps);
        }
    }

    /**
     * Filters incoming data by type, region and generation date
     *
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @param int $generatedAt UNIX timestamp of the data generation date
     *
     * @return bool false if dataset is to be filtered
     */
    protected function filterData($typeID, $regionID, $generatedAt)
    {
        //skip data for non-tracked market regions
        if (!isset($this->trackedMarketRegionIDs[$regionID])) {
            if (VERBOSE > 2)
                echo '- skipping regionID=' . $regionID.', typeID=' . $typeID . PHP_EOL;
            return false;
        }

        //skip non-tracked items
        if (!isset($this->trackedTypeIDs[$typeID])) {
            if (VERBOSE > 2)
                echo '- skipping untracked typeID=' . $typeID . PHP_EOL;
            return false;
        }

        //skip data with impossible generation date. Make sure your systems clock is correct.
        if ($generatedAt > time()) {
            if (VERBOSE > 2)
                echo '- skipping data for typeID=' . $typeID . ', generated in the future: '
                    . date('Y-m-d H:i:s', $generatedAt) . PHP_EOL;
            return false;
        }

        //skip data with generation date older than 24h
        if (time() > $generatedAt + 24 * 3600) {
            if (VERBOSE > 2)
                echo '- skipping data for typeID=' . $typeID . ', data generated more than 24h ago: '
                    . date('Y-m-d H:i:s', $generatedAt) . PHP_EOL;
            return false;
        }
        return true;
    }

    /**
     * Gets timestamps for latest price data generation, history data generation and most recent date with history data.
     * Queries cache first if available and goes to the DB as fallback.
     *
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     *
     * @return \ArrayObject with two elements, carrying the UNIX timestamps
     */
    protected function getTimestamps($typeID, $regionID)
    {
        try {
            return $this->cache->getItem('emdrts_' . $regionID . '_' . $typeID);
        } catch (\iveeCore\Exceptions\KeyNotFoundInCacheException $e) {
            $orderTimestamps = $this->getTimestampsDB($typeID, $regionID);
            $this->cache->setItem($orderTimestamps, 'emdrts_' . $regionID . '_' . $typeID);
            return $orderTimestamps;
        }
    }

    /**
     * Gets timestamps for latest price data generation, history data generation and most recent date with history data
     * from the DB.
     *
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     *
     * @return \ArrayObject with two elements, carrying the UNIX timestamps
     */
    protected function getTimestampsDB($typeID, $regionID)
    {
        $timestamps = array();

        $res = $this->sde->query(
            "SELECT
            UNIX_TIMESTAMP(atp.lastPriceUpdate) as lastPriceDataTS,
            UNIX_TIMESTAMP(atp.lastHistUpdate) as lastHistDataTS
            FROM " . \iveeCore\Config::getIveeDbName() . ".iveeTrackedPrices as atp
            WHERE atp.typeID = " . (int) $typeID . " AND atp.regionID = " . (int) $regionID . ";"
        );

        if ($res->num_rows == 1) {
            $tmp = $res->fetch_assoc();
            $timestamps[0] = (int) (($tmp['lastPriceDataTS'] > 0) ? $tmp['lastPriceDataTS'] : 0);
            $timestamps[1] = (int) (($tmp['lastHistDataTS'] > 0)  ? $tmp['lastHistDataTS'] : 0);
        } else {
            $timestamps[0] = 0;
            $timestamps[1] = 0;
        }
        return new \ArrayObject($timestamps);
    }

    /**
     * Updates or invalidates cache entries after market data update
     *
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @param \ArrayObject $timestamps with two elements carrying the UNIX timestamps
     *
     * @return void
     */
    protected function updateCaches($typeID, $regionID, \ArrayObject $timestamps)
    {
        //update timestamps cache
        $this->cache->setItem($timestamps, 'emdrts_' . $regionID . '_' . $typeID);

        //invalidate RegionMarketData cache
        $regionMarketDataClass = \iveeCore\Config::getIveeClassName('RegionMarketData');
        $regionMarketDataClass::deleteFromCache(array($regionID . '_' . $typeID));
    }

    /**
     * Handles the processing and DB insertion of order data
     *
     * @param int $typeID of the item the data refers to
     * @param int $regionID of the region the data refers to
     * @param int $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param \stdClass $rowset the raw market data
     * @param \ArrayObject $timestamps with two elements carrying the UNIX timestamps
     *
     * @return void
     */
    protected function handleOrderData($typeID, $regionID, $generatedAt, \stdClass $rowset, \ArrayObject $timestamps)
    {
        //skip if data is older than newest generatedAt/priceUpdateTimestamp
        if ($generatedAt < $timestamps[0]) {
            if (VERBOSE > 2)
                echo '- skipping old price data for typeID=' . $typeID . ', generated at '
                    . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process price data
        $epu = new $this->emdrPriceUpdateClass(
            $typeID,
            $regionID,
            $generatedAt,
            $rowset->rows
        );
        //save data to DB
        $epu->insertIntoDB();

        //update order timestamp
        $timestamps[0] = $generatedAt;
    }

    /**
     * Handles the processing and DB insertion of history data
     *
     * @param int $typeID of the item the data refers to
     * @param int $regionID of the region the data refers to
     * @param int $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param \stdClass $rowset the raw market data
     * @param \ArrayObject $timestamps with two elements carrying the UNIX timestamps
     *
     * @return void
     * @throws NoRelevantDataException if no relevant data rows are given
     */
    protected function handleHistoryData($typeID, $regionID, $generatedAt, \stdClass $rowset, \ArrayObject $timestamps)
    {
        //skip if data is older than newest generatedAt/historyUpdateTimestamp
        if ($generatedAt < $timestamps[1]) {
            if (VERBOSE > 2)
                echo '- skipping old history data for typeID=' . $typeID . ', generated at '
                    . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process history data
        $ehu = new $this->emdrHistoryUpdateClass(
            $typeID,
            $regionID,
            $generatedAt,
            $rowset->rows
        );

        //insert data into DB
        $ehu->insertIntoDB();

        //update history data generation timestamp
        $timestamps[1] = $generatedAt;
    }

    /**
     * Gets the typeName for a market tracked TypeID
     *
     * @param int $typeID of the item to get the name for
     *
     * @return string
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the requested typeID is not found among the tracked
     * typeIDs
     */
    public function getTypeNameById($typeID)
    {
        if (isset($this->trackedTypeIDs[(int) $typeID]))
            return $this->trackedTypeIDs[(int) $typeID];
        else {
            $exceptionClass = \iveeCore\Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass((int) $typeID . ' not found among market tracked item IDs.');
        }
    }

    /**
     * Gets the regionName for a regionID
     *
     * @param int $regionID of the region to get the name for
     *
     * @return string
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the requested regionID is not found
     */
    public function getRegionNameById($regionID)
    {
        if (isset($this->regions[(int) $regionID]))
            return $this->regions[(int) $regionID];
        else {
            $exceptionClass = \iveeCore\Config::getIveeClassName('SystemIdNotFoundException');
            throw new $exceptionClass((int) $regionID . ' not found among region IDs.');
        }
    }
}
