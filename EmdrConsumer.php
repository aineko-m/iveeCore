<?php

/**
 * EMDR for IVEE Consumer
 * EmdrConsumer handles the incoming data stream from the relay and passes rowsets to either EmdrPriceUpdate or 
 * EmdrHistoryUpdate.
 * 
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/EmdrConsumer.php
 * @package iveeCore
 */
class EmdrConsumer {
    
    /**
     * @var EmdrConsumer $instance the singleton EmdrConsumer instance
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
     * @var int $defaultRegionID the default regionID
     */
    protected $defaultRegionID;
    
    /**
     * @var array $regions regionID => regionName
     */
    protected $regions;
    
    /**
     * @var SDE $sde holds the SDE instance, for convenience
     */
    protected $sde;
    
    /**
     * @var string $EmdrPriceUpdateClass holds the class name for EmdrPriceUpdate objects
     */
    protected $EmdrPriceUpdateClass;
    
    /**
     * @var string $EmdrHistoryUpdateClass holds the class name for EmdrHistoryUpdate objects
     */
    protected $EmdrHistoryUpdateClass;
    
    /**
     * Returns instance.
     * @return EmdrConsumer
     */
    public static function instance() {
        if (!isset(static::$instance))
            static::$instance = new static;

        return static::$instance;
    }
    
    /**
     * Constructor.
     * @return EmdrConsumer
     */
    protected function __construct(){
        if(VERBOSE) echo "Instantiating EmdrConsumer" . PHP_EOL 
            . "Getting tracked item IDs... ";

        $this->sde = SDE::instance();
        
        //load IDs of items to track on market
        $res = $this->sde->query(
            "SELECT typeID, typeName
            FROM invTypes 
            WHERE marketGroupID IS NOT NULL
            AND published = 1;"
        );
        while($tmp = $res->fetch_array(MYSQL_NUM)){
            $this->trackedTypeIDs[(int) $tmp[0]] = $tmp[1];
        }

        if(VERBOSE) echo count($this->trackedTypeIDs)." found." . PHP_EOL;
        
        //load regionIDs
        $res = $this->sde->query(
            "SELECT regionID, regionName
            FROM mapRegions;"
        );
        while($tmp = $res->fetch_array(MYSQL_NUM)){
            $this->regions[(int) $tmp[0]] = $tmp[1];
        }
        
        $this->trackedMarketRegionIDs = $this->sde->defaults->getTrackedMarketRegionIDs();
        $this->defaultRegionID        = $this->sde->defaults->getDefaultRegionID();
        $this->EmdrPriceUpdateClass   = iveeCoreConfig::getIveeClassName('EmdrPriceUpdate');
        $this->EmdrHistoryUpdateClass = iveeCoreConfig::getIveeClassName('EmdrHistoryUpdate');
    }
    
    /**
     * Main work method.
     * Loops indefinitely unless interrupted. Note that the MySQL connection might time out if no relevant data is 
     * received over longer periods, EvE downtimes, for instance.
     */
    public function run(){
        if(VERBOSE) echo "Starting EMDR data stream" . PHP_EOL;
        
        //init ZMQ
        $context = new ZMQContext();
        $subscriber = $context->getSocket(ZMQ::SOCKET_SUB);

        //Connect to EMDR relay.
        $subscriber->connect(iveeCoreConfig::getEmdrRelayUrl());

        // Disable filtering.
        $subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "");
        
        if(VERBOSE) echo "Processing EMDR data" . PHP_EOL;

        //main loop
        while (true) {
            // Receive raw market JSON strings and un-serialize
            $this->handleMarketData(json_decode(gzuncompress($subscriber->recv())));
        }
    }
    
    /**
     * Processes the received EMDR object.
     * @param stdClass $marketData the market data object received from EMDR
     */
    protected function handleMarketData(stdClass $marketData){
        //loop over rowsets (typically just 1)
        foreach($marketData->rowsets as $rowset){
            $typeID = (int) $rowset->typeID;
            $regionID = (int) $rowset->regionID;
            $generatedAt = strtotime($rowset->generatedAt);
            
            //filter data
            if(!$this->filterData($typeID, $regionID, $generatedAt))
                continue;

            //get timestamps from cache or DB
            $timestamps = $this->getTimestamps($typeID, $regionID);

            //order data
            if($marketData->resultType == 'orders'){
                $this->handleOrderData($typeID, $regionID, $generatedAt, $rowset, $timestamps);
            }
            //history data
            elseif($marketData->resultType == 'history'){
                try {
                    $this->handleHistoryData($typeID, $regionID, $generatedAt, $rowset, $timestamps);
                } catch(NoRelevantDataException $e){
                    continue;
                }
            }
            //we received something else
            else {
                if(VERBOSE > 2) echo '- skipping unknown data type ' . $marketData->resultType . PHP_EOL;
                continue;
            }

            //update and invalidate caches as necessary
            $this->updateCaches($typeID, $regionID, $timestamps);
        }
    }
    
    /**
     * Filters incoming data by type, region and generation date
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @param int $generatedAt UNIX timestamp of the data generation date
     * @return boolean false if dataset is to be filtered
     */
    protected function filterData($typeID, $regionID, $generatedAt){
        //skip data for non-tracked market regions
        if(!isset($this->trackedMarketRegionIDs[$regionID])){
            if(VERBOSE > 2) echo '- skipping regionID=' . $regionID.', typeID=' . $typeID . PHP_EOL;
            return false;
        }

        //skip non-tracked items
        if(!isset($this->trackedTypeIDs[$typeID])){
            if(VERBOSE > 2) echo '- skipping untracked typeID=' . $typeID . PHP_EOL;
            return false;
        }             

        //skip data with impossible generation date. Make sure your systems clock is correct.
        if($generatedAt > time()){
            if(VERBOSE > 2) 
                echo '- skipping data for typeID=' . $typeID . ', generated in the future: ' 
                    . date('Y-m-d H:i:s', $generatedAt) . PHP_EOL;
            return false;
        }

        //skip data with generation date older than 24h
        if(time() > $generatedAt + 24 * 3600){
            if(VERBOSE > 2) 
                echo '- skipping data for typeID=' . $typeID . ', data generated more than 24h ago: ' 
                    . date('Y-m-d H:i:s', $generatedAt) . PHP_EOL;
            return false;
        }
        
        return true;
    }
    
    /**
     * Gets timestamps for latest price data generation, history data generation and most recent date with history data.
     * Queries memcached first if available and goes to the DB as fallback.
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @return ArrayObject with two elements, carrying the UNIX timestamps
     */
    protected function getTimestamps($typeID, $regionID){
        if(iveeCoreConfig::getUseMemcached()){
            try {
                return $this->sde->getFromCache('emdrts_' . $regionID . '_' . $typeID);
            } catch(KeyNotFoundInMemcachedException $e){
                $orderTimestamps = $this->getTimestampsDB($typeID, $regionID);
                $this->sde->storeInCache($orderTimestamps, 'emdrts_' . $regionID . '_' . $typeID);
                return $orderTimestamps;
            }
        } else{
            return $this->getTimestampsDB($typeID, $regionID);
        }
    }
    
    /**
     * Gets timestamps for latest price data generation, history data generation and most recent date with history data
     * from the DB.
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @return ArrayObject with two elements, carrying the UNIX timestamps
     */
    protected function getTimestampsDB($typeID, $regionID){
        $timestamps = array();
        
        $res = $this->sde->query(
            "SELECT
            UNIX_TIMESTAMP(atp.lastPriceUpdate) as lastPriceDataTS, 
            UNIX_TIMESTAMP(atp.lastHistUpdate) as lastHistDataTS 
            FROM iveeTrackedPrices as atp 
            WHERE atp.typeID = " . (int) $typeID . " AND atp.regionID = " . (int) $regionID . ";"
        );
        
        if($res->num_rows == 1){
            $tmp = $res->fetch_assoc();
            $timestamps[0] = (int) (($tmp['lastPriceDataTS'] > 0) ? $tmp['lastPriceDataTS'] : 0);
            $timestamps[1] = (int) (($tmp['lastHistDataTS'] > 0)  ? $tmp['lastHistDataTS'] : 0);
        } else {
            $timestamps[0] = 0;
            $timestamps[1] = 0;
        }
        
        return new ArrayObject($timestamps);
    }
    
    /**
     * Updates or invalidates cache entries after market data update
     * @param int $typeID the ID of the item
     * @param int $regionID the ID of the region
     * @param ArrayObject with three elements, carrying the UNIX timestamps
     */
    protected function updateCaches($typeID, $regionID, ArrayObject $timestamps){
        if(iveeCoreConfig::getUseMemcached()){
            //update timestamps cache
            $this->sde->storeInCache($timestamps, 'emdrts_' . $regionID . '_' . $typeID);

            //invalidate the type cache for the item that was updated if its the default region
            if($regionID == $this->defaultRegionID)
                $this->sde->invalidateCache('type_' . $typeID);
        }
    }
    
    /**
     * Handles the processing and DB insertion of order data
     * @param int $typeID of the item the data refers to
     * @param int $regionID of the region the data refers to
     * @param int $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param stdClass $rowset the raw market data
     * @param ArrayObject $timestamps
     */
    protected function handleOrderData($typeID, $regionID, $generatedAt, stdClass $rowset, ArrayObject $timestamps){
        //skip if data is older than newest generatedAt/priceUpdateTimestamp
        if($generatedAt < $timestamps[0]){
            if(VERBOSE > 2) 
                echo '- skipping old price data for typeID=' . $typeID . ', generated at ' 
                    . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process price data
        $epu = new $this->EmdrPriceUpdateClass(
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
     * @param $typeID of the item the data refers to
     * @param $regionID of the region the data refers to
     * @param $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param stdClass $rowset the raw market data
     * @param ArrayObject $timestamps
     * @throws NoRelevantDataException if no relevant data rows are given
     */
    protected function handleHistoryData($typeID, $regionID, $generatedAt, stdClass $rowset, ArrayObject $timestamps){
        //skip if data is older than newest generatedAt/historyUpdateTimestamp
        if($generatedAt < $timestamps[1]){
            if(VERBOSE > 2) 
                echo '- skipping old history data for typeID=' . $typeID . ', generated at ' 
                    . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process history data
        $ehu = new $this->EmdrHistoryUpdateClass(
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
     * @param $typeID of the item to get the name for
     * @returns string typeName
     * @throws TypeIdNotFoundException if the requested typeID is not found among the tracked typeIDs
     */
    public function getTypeNameById($typeID){
        if(isset($this->trackedTypeIDs[(int)$typeID]))
            return $this->trackedTypeIDs[(int)$typeID];
        else
            throw new TypeIdNotFoundException((int)$typeID . ' not found among market tracked item IDs.');
    }
    
    /**
     * Gets the regionName for a regionID
     * @param $regionID of the region to get the name for
     * @returns string regionName
     * @throws TypeIdNotFoundException if the requested regionID is not found
     */
    public function getRegionNameById($regionID){
        if(isset($this->regions[(int)$regionID]))
            return $this->regions[(int)$regionID];
        else
            throw new TypeIdNotFoundException((int)$regionID . ' not found among region IDs.');
    }
}

?>