<?php

/**
 * EMDR for IVEE client.
 * 
 * Connects to a relay, processes order and history data and stores it to iveeCore's DB tables.
 * 
 * Requires Zero-MQ and php-zmq binding. See README for build instructions.
 * 
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/emdr.php
 * @package iveeCore
 */

echo " _____ __  __ ____  ____     __              _____     _______ _____ 
| ____|  \/  |  _ \|  _ \   / _| ___  _ __  |_ _\ \   / / ____| ____|
|  _| | |\/| | | | | |_) | | |_ / _ \| '__|  | | \ \ / /|  _| |  _|  
| |___| |  | | |_| |  _ <  |  _| (_) | |     | |  \ V / | |___| |___ 
|_____|_|  |_|____/|_| \_\ |_|  \___/|_|    |___|  \_/  |_____|_____|\n";

error_reporting(E_ALL);
ini_set('display_errors', 'on');

DEFINE('VERBOSE', 1);

//include config from one directory above, via absolute path
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreConfig.php');

$ec = EmdrConsumer::instance();
$ec->run();

/**
 * EmdrConsumer handles the incoming data stream from the relay and passes rowsets to either EmdrPriceUpdate or 
 * EmdrHistoryUpdate.
 */
class EmdrConsumer {
    
    /**
     * @var EmdrConsumer $instance the singleton EmdrConsumer instance
     */
    protected static $instance;
    
    /**
     * @var array $priceUpdateTimestamps the last "generatedAt" timestamps for order data; typeID => unix_timestamp
     */
    protected $priceUpdateTimestamps = array();
    
    /**
     * @var array $historyUpdateTimestamps the last "generatedAt" timestamps for history data; typeID => unix_timestamp
     */
    protected $historyUpdateTimestamps = array();
    
    /**
     * @var array $latestPriceDates the last dates for which order data was received; 
     * typeID => unix_timestamp, rounded to days
     */
    protected $latestPriceDates = array();
    
    /**
     * @var array $latestPriceDates the last dates for which history data was received;
     * typeID => unix_timestamp, rounded to days
     */
    protected $latestHistDates = array();
    
    /**
     * Returns instance.
     * @return EmdrConsumer
     */
    public static function instance() {
        if (!isset(self::$instance))
            self::$instance = new EmdrConsumer;

        return self::$instance;
    }
    
    /**
     * Constructor.
     * @return EmdrConsumer
     */
    protected function __construct(){
        if(VERBOSE) echo "Instantiating EmdrConsumer" . PHP_EOL 
            . "Getting price and history update timestamps for tracked items... ";

        //get all ids of items to watch plus their respective update timestamps
        $res = SDE::instance()->query("SELECT 
            t.typeID, 
            UNIX_TIMESTAMP(atp.lastHistUpdate) as lhua, 
            UNIX_TIMESTAMP(atp.lastPriceUpdate) as lpua, 
            UNIX_TIMESTAMP(ah.date) as lhu, 
            UNIX_TIMESTAMP(ap.date) as lpu
            FROM invTypes t
            LEFT JOIN (
                SELECT 
                typeID, 
                newestHistData, 
                lastHistUpdate, 
                newestPriceData, 
                lastPriceUpdate 
                FROM iveeTrackedPrices WHERE regionID = " . (int) iveeCoreConfig::getDefaultRegionID() . "
            ) as atp ON atp.typeID = t.typeID
            LEFT JOIN iveePrices as ap ON ap.id = atp.newestPriceData
            LEFT JOIN iveePrices as ah ON ah.id = atp.newestHistData
            WHERE marketGroupID IS NOT NULL
            AND t.published = 1;"
        );
        
        while($tmp = $res->fetch_array(MYSQL_NUM)){
            $this->historyUpdateTimestamps[(int) $tmp[0]] = (int) (($tmp[1] > 0) ? $tmp[1] : 0);
            $this->priceUpdateTimestamps[(int) $tmp[0]]   = (int) (($tmp[2] > 0) ? $tmp[2] : 0);
            $this->latestHistDates[(int) $tmp[0]]         = (int) (($tmp[3] > 0) ? $tmp[3] : 0);
            $this->latestPriceDates[(int) $tmp[0]]        = (int) (($tmp[4] > 0) ? $tmp[4] : 0);
	} 
	$res->free();
        if(VERBOSE) echo count($this->priceUpdateTimestamps)." found." . PHP_EOL;
    }
    
    /**
     * Main work method.
     * Loops indefinitely unless interrupted.
     * Note that the MySQL connection might time out if no relevant data is received over longer periods, EvE downtimes,
     * for instance.
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
            // Receive raw market JSON strings and un-serialize a named array.
            $market_data = json_decode(gzuncompress($subscriber->recv()));

            //loop over rowsets (typically just 1)
            foreach($market_data->rowsets as $rowset){
                $typeID = (int) $rowset->typeID;
                $regionID = (int) $rowset->regionID;
                $generatedAt = strtotime($rowset->generatedAt);

                //skip data not from default region
                if($regionID != iveeCoreConfig::getDefaultRegionID()){
                    if(VERBOSE > 2) echo '- skipping regionID=' . $regionID.', typeID=' . $typeID . PHP_EOL;
                    continue;
                }

                //skip non-tracked items
                if(!isset($this->priceUpdateTimestamps[$typeID])){
                    if(VERBOSE > 2) echo '- skipping untracked typeID=' . $typeID . PHP_EOL;
                    continue;
                }             
 
                //skip data with impossible generation date. Make sure your systems clock is correct.
                if($generatedAt > time()){
                    if(VERBOSE > 2) 
                        echo '- skipping data for typeID=' . $typeID . ', generated in the future: ' . $rowset->generatedAt . PHP_EOL;
                    continue;
                }
                
                //order data
                if($market_data->resultType == 'orders')
                    $this->handleOrderData($typeID, $regionID, $generatedAt, $rowset);
                //history data
                elseif($market_data->resultType == 'history')
                    $this->handleHistoryData($typeID, $regionID, $generatedAt, $rowset);
                //we received something else
                else
                    if(VERBOSE > 2) echo '- skipping unknown data type ' . $market_data->resultType . PHP_EOL;
            }
        }
    }
    
    /**
     * Handles the processing and DB insertion of order data
     * @param $typeID of the item the data refers to
     * @param $regionID of the region the data refers to
     * @param $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param $rowset the raw market data
     */
    protected function handleOrderData($typeID, $regionID, $generatedAt, $rowset){
        //skip if data is older than newest generatedAt/priceUpdateTimestamp
        if($generatedAt < $this->priceUpdateTimestamps[$typeID]){
            if(VERBOSE > 2) 
                echo '- skipping old price data for typeID=' . $typeID . ', generated at ' . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process price data
        $epu = new EmdrPriceUpdate(
            $typeID, 
            $regionID, 
            $generatedAt,
            $rowset->rows
        );
        //save data to DB, get latest data date
        $dataDate = $epu->insertIntoDB();

        //update array with data generation timestamp
        $this->priceUpdateTimestamps[$typeID] = $generatedAt;

        //update array with latest data date
        $this->latestPriceDates[$typeID] = $dataDate;
    }
    
    /**
     * Handles the processing and DB insertion of history data
     * @param $typeID of the item the data refers to
     * @param $regionID of the region the data refers to
     * @param $generatedAt the unix timestamp of the moment the data set was generated at an uploader client
     * @param $rowset the raw market data
     */
    protected function handleHistoryData($typeID, $regionID, $generatedAt, $rowset){
        //skip if data is older than newest generatedAt/historyUpdateTimestamp
        if($generatedAt < $this->historyUpdateTimestamps[$typeID]){
            if(VERBOSE > 2) 
                echo '- skipping old history data for typeID=' . $typeID . ', generated at ' . $rowset->generatedAt . PHP_EOL;
            return;
        }

        //process history data
        try{
            $ehu = new EmdrHistoryUpdate(
                $typeID, 
                $regionID, 
                $generatedAt, 
                $this->latestHistDates[$typeID], 
                $rowset->rows
            );
        } catch(NoRelevantDataException $e){
            return;
        }
        $dataDate = $ehu->insertIntoDB();

        //update array with data generation timestamp
        $this->historyUpdateTimestamps[$typeID] = $generatedAt;

        //update array with latest data date
        $this->latestHistDates[$typeID] = $dataDate;
    }
}

/**
 * EmdrHistoryUpdate handles history data updates
 */
class EmdrHistoryUpdate {
    
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
     * @var array $rows the filtered history data rows
     */
    protected $rows;

    /**
     * Constructor.
     * @param int $typeID ID of item this history data is from
     * @param int $regionID ID of the region this history data is from
     * @param int $generatedAt unix timestamp of the data generation
     * @param int $latestUpdateDate unix timestamp of the last date to receive data update
     * @param array $rows the history data rows
     */
    public function __construct($typeID, $regionID, $generatedAt, $latestUpdateDate, $rows){
        $this->typeID     = $typeID;
        $this->regionID   = $regionID;
        $this->generatedAt= $generatedAt;
        
        //filter out old data
        foreach($rows as $k => $row){
            $rowdate = strtotime($row[0]);
            
            //process data up to 8 days old
            //this is done to help complete missing days of data when data arrives fragmented
            //and to avoid an averaging feedback (average calculated and stored for vol and tx over the last 7 days)
            if($rowdate + (8 * 24 * 3600) < $latestUpdateDate){
                //dereference old data
                unset($rows[$k]);
            }
        }
        $this->rows = $rows;
        if(count($this->rows) < 1)
            throw new NoRelevantDataException("0 relevant history rows to process");
    }
    
    /**
     * Inserts history data into the DB
     * @return int unix timestamp for the latest date (day granularity) for which data was inserted
     */
    public function insertIntoDB(){
        $combinedSql = '';
        $latestDate = 0;
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        
        foreach($this->rows as $day){
            $rowdate = (int) strtotime($day[0]);
            
            //track newest date
            if($rowdate > $latestDate) $latestDate = $rowdate;
            
            $insertData = array(
                'typeID'   => $this->typeID,
                'regionID' => $this->regionID,
                'date'     => "'".date('Y-m-d', $rowdate)."'",
                'tx'       => (int) $day[1],
                'vol'      => (int) $day[2],
                'low'      => (float) $day[3],
                'high'     => (float) $day[4],
                'avg'      => (float) $day[5]
            );
            
            $updateData = array(
                'tx'       => (int) $day[1],
                'vol'      => (int) $day[2],
                'low'      => (float) $day[3],
                'high'     => (float) $day[4],
                'avg'      => (float) $day[5]
            );

            //build upsert query
            $combinedSql .= $utilClass::makeUpsertQuery('iveePrices', $insertData, $updateData);
        }
        //call stored procedure to finish the update
        $combinedSql .= "CALL iveeCompleteHistoryUpdate(" . $this->typeID . ", " . $this->regionID 
            . ", '" . date('Y-m-d H:i:s', $this->generatedAt) . "'); COMMIT;";
        if(VERBOSE) echo "H: " . $this->typeID . ', ' . count($this->rows) . " days" . PHP_EOL;
        
        $sde = SDE::instance();
        
        //run all queries
        $sde->multiQuery($combinedSql);
        
        //invalidate the cache for the item that was updated
        $sde->invalidateCache('type_' . $this->typeID);
        return $latestDate;
    }
}

/**
 * EmdrPriceUpdate handles price/order data updates
 */
class EmdrPriceUpdate {
    
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
     * @var array $averages contains the averages for volume and transactions for the last 7 days
     */
    protected $averages;
    
    /**
     * @var float $sell the calculated sell price
     */
    protected $sell;
    
    /**
     * @var int $avgSell5OrderAge the average age in seconds for sell orders withing 5% of calculated sell price
     */
    protected $avgSell5OrderAge;
    
    /**
     * @var float $buy the calculated buy price
     */
    protected $buy;
    
    /**
     * @var int $avgBuy5OrderAge the average age in seconds for buy orders withing 5% of calculated buy price
     */
    protected $avgBuy5OrderAge;
    
    /**
     * @var int $demandIn5 the volume demanded by buy orders withing 5% of calculated buy price
     */
    protected $demandIn5;
    
    /**
     * @var int $supplyIn5 the volume available in sell orders withing 5% of calculated sell price
     */
    protected $supplyIn5;
    
    /**
     * Constructor.
     * @param int $typeID ID of item this price data is from
     * @param int $regionID ID of the region this price data is from
     * @param int $generatedAt unix timestamp of the data generation
     * @param array $orders the order data rows
     */
    public function __construct($typeID, $regionID, $generatedAt, $orders){
		
        $this->typeID      = $typeID;
        $this->regionID    = $regionID;
        $this->generatedAt = $generatedAt;
        
        //separate buy and sell orders
        $sdata = array();
        $bdata = array();
        foreach($orders as $order){
            if($order[6] == 1)
                $bdata[] = $order;
            else
                $sdata[] = $order;
        }
        
        //sort orders: sell ASC, buy DESC
        usort($bdata, array('EmdrPriceUpdate', 'cmp'));
        $bdata = array_reverse($bdata);
        usort($sdata, array('EmdrPriceUpdate', 'cmp'));
        
        //get average vol and tx from DB (= last history row) 
        //Look at stored procedure iveeCompleteHistoryUpdate() for details
        $res = SDE::instance()->query("
            SELECT 
            ap.vol, 
            ap.tx 
            FROM iveeTrackedPrices as atp
            JOIN iveePrices as ap ON  atp.newestHistData = ap.id
            WHERE atp.regionID = " . (int) $this->regionID . "
            AND atp.typeID = " . (int) $this->typeID . ";"
        );

        while($tmp = $res->fetch_row()){
            $this->averages['avgVol'] = (float)$tmp[0];
            $this->averages['avgTx']  = (float)$tmp[1];
        }
        //if vol or tx are below 1 unit, set to 1 unit for the purpose of calculations
        if(!isset($this->averages['avgVol']) OR $this->averages['avgVol'] < 1)
            $this->averages['avgVol'] = 1;

        if(!isset($this->averages['avgTx']) OR $this->averages['avgTx'] < 1)
            $this->averages['avgTx'] = 1;
        
        //estimate realistic prices
        $sellStats = self::getPriceStats($sdata, $this->averages, $this->generatedAt);
        $this->sell = $sellStats['avgOrderPrice'];
        $this->avgSell5OrderAge = $sellStats['avgOrderAge'];

        $buyStats = self::getPriceStats($bdata, $this->averages, $this->generatedAt);
        $this->buy = $buyStats['avgOrderPrice'];
        $this->avgBuy5OrderAge = $buyStats['avgOrderAge'];
        
        //get supply within 5% of calculated price
        if(isset($this->sell)){
            $this->supplyIn5 = 0;
            foreach($sdata as $sellorder){
                //if cut-off reached, break
                if($this->sell * 1.05 < $sellorder[0] ) break;
                $this->supplyIn5 += $sellorder[1];
            }
        } 

        //get demand within 5% of calculated price
        if(isset($this->buy)){
            $this->demandIn5 = 0;
            foreach($bdata as $buyorder){
                //skip orders with ludicrous minimum volume
                if($buyorder[5] > $this->averages['avgVol']) continue;
                //if cut-off sum reached, break
                if($this->buy * 0.95 > $buyorder[0] ) break;
                $this->demandIn5 += $buyorder[1];
            }
        } 
    }
    
    //helper function for sorting orders based on price
    protected static function cmp($a, $b){
        if($a[0] == $b[0]) return 0;
        return($a[0] > $b[0])? +1 : -1;
    }
    
    /**
     * Inserts price data into the DB
     */
    public function insertIntoDB(){
        if(isset($this->sell) OR isset($this->buy)){
            $utilClass = iveeCoreConfig::getIveeClassName('util');
            $updateData = array(
                'typeID' => $this->typeID,
                'regionID' => $this->regionID,
                'date' => "'" . date('Y-m-d', $this->generatedAt) . "'",
                'sell' => $this->sell,
                'buy'  => $this->buy,
                'avgSell5OrderAge' => $this->avgSell5OrderAge,
                'avgBuy5OrderAge' => $this->avgBuy5OrderAge,
                'demandIn5' => $this->demandIn5,
                'supplyIn5' => $this->supplyIn5
            );
            //build upsert query
            $sql = $utilClass::makeUpsertQuery('iveePrices', $updateData, $updateData);
            
            //call stored procedure to complete the update
            $sql .= "CALL iveeCompletePriceUpdate(" . $this->typeID . ", " . $this->regionID 
                . ", '" . date('Y-m-d H:i:s', $this->generatedAt) . "'); COMMIT;";

            if(VERBOSE) echo "P: " . $this->typeID . PHP_EOL;
            $sde = SDE::instance();
            
            //execute the combined queries
            $sde->multiQuery($sql);
            
            //invalidate the cache for the item that was updated
            $sde->invalidateCache('type_' . $this->typeID);
            return strtotime(date('Y-m-d', $this->generatedAt));
        } 
    }
    
    /**
     * Estimate realistic prices by calculating weighted average of orders equivalent to 5% of daily volume
     * @param array $odata the orders (buy or sell)
     * @param array with averages for volume and transactions
     * @param int $generatedAt the timestamp of data generation
     * @return array with estimated price and average order age
     */
    protected static function getPriceStats($odata, $averages, $generatedAt){
        if(count($odata) < 1) return NULL;
        
        $volcumula   = 0;
        $pricecumula = 0;
        $tscumula    = 0; //for average order age
        
        foreach($odata as $order){
            //skip orders with ludicrous minimum volume
            if($order[5] > $averages['avgVol']) continue;

            //accumulate volume for weighted averages
            $volcumula   += $order[1];
            //accumulate prices weighted with volume
            $pricecumula += $order[1] * $order[0];
            //accumulate order age in seconds weighted by volume
            $tscumula    += $order[1] * ($generatedAt - strtotime($order[7]));

            // if 5% cut-off reached, break
            if($volcumula >= $averages['avgVol']*0.05) break;
        }
        
        if($volcumula==0)$volcumula = 1;

        return array(
            //get the averages by dividing by the cumulated volume
            'avgOrderPrice' => $pricecumula/$volcumula,
            'avgOrderAge'   => (int)($tscumula/$volcumula)
        );
    }
}

?>