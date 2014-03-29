<?php

/**
 * Class for all items that can be sold on the market. 
 * Inheritance: Sellable -> Type.
 * 
 * Note that item objects from child classes are not necessarily sellable on the market.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Sellable.php
 * @package iveeCore
 */
class Sellable extends Type {

    /**
     * @var int the marketGroupID of this Type
     */
    protected $marketGroupID;
    
    /**
     * @var int unix timestamp of the date of the history data. Day granularity.
     */
    protected $priceDate;
    
    /**
     * @var float the realistic sell price as estimated in emdr.php for the default region
     */
    protected $sellPrice;
    
    /**
     * @var float the realistic buy price as estimated in emdr.php for the default region
     */
    protected $buyPrice;
    
    /**
     * @var int the volume available in sell orders within 5% of the sellPrice
     */
    protected $supplyIn5;
    
    /**
     * @var int the volume demanded by buy orders within 5% of the buyPrice
     */
    protected $demandIn5;
    
    /**
     * @var int average time passed since updates for sell orders within 5% of the sellPrice. A measure of competition.
     */
    protected $avgSell5OrderAge;
    
    /**
     * @var int average time passed since update for buy orders within 5% of the buyPrice. A measure of competition.
     */
    protected $avgBuy5OrderAge;
    
    /**
     * @var int unix timestamp of the date of the price data. Day granularity.
     */
    protected $histDate;
    
    /**
     * @var float the market volume of this type, averaged over the last 7 days
     */
    protected $avgVol;
    
    /**
     * @var float the market transactions for this type, averaged over the last 7 days
     */
    protected $avgTx;
    
    /**
     * @var float market "low", as returned by EVEs history
     */
    protected $low;
    
    /**
     * @var float market "high", as returned by EVEs history
     */
    protected $high;
    
    /**
     * @var float market "avg", as returned by EVEs history
     */
    protected $avg;

    /**
     * Gets all necessary data from SQL.
     * @return array
     * @throws TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes() {
        $sde = SDE::instance();
        $row = $sde->query(
            "SELECT 
            it.groupID, 
            ig.categoryID,
            it.typeName, 
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID, 
            histDate, 
            priceDate, 
            vol, 
            sell, 
            buy,
            tx,
            low,
            high,
            avg,
            supplyIn5,
            demandIn5,
            avgSell5OrderAge,
            avgBuy5OrderAge
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            LEFT JOIN (
                SELECT 
                iveeTrackedPrices.typeID, 
                UNIX_TIMESTAMP(lastHistUpdate) AS histDate, 
                UNIX_TIMESTAMP(lastPriceUpdate) AS priceDate, 
                ah.vol, 
                ah.tx,
                ah.low,
                ah.high,
                ah.avg,
                ap.sell, 
                ap.buy,
                ap.supplyIn5,
                ap.demandIn5,
                ap.avgSell5OrderAge,
                ap.avgBuy5OrderAge
                FROM iveeTrackedPrices
                LEFT JOIN iveePrices AS ah ON iveeTrackedPrices.newestHistData = ah.id
                LEFT JOIN iveePrices AS ap ON iveeTrackedPrices.newestPriceData = ap.id
                WHERE iveeTrackedPrices.typeID = " . (int) $this->typeID . "
                AND iveeTrackedPrices.regionID = " . (int) $sde->defaults->getDefaultRegionID() . "
            ) AS atp ON atp.typeID = it.typeID
            WHERE it.published = 1 
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();
        
        if (empty($row))
            throw new TypeIdNotFoundException("typeID ". (int) $this->typeID . " not found");
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object. Overwrites inherited method.
     * @param array $row data from DB
     */
    protected function setAttributes($row) {
        //call parent method
        parent::setAttributes($row);
        if (isset($row['marketGroupID']))
            $this->marketGroupID = (int)$row['marketGroupID'];
        if (isset($row['histDate']))
            $this->histDate = (int)$row['histDate'];
        if (isset($row['priceDate']))
            $this->priceDate = (int)$row['priceDate'];
        if (isset($row['vol']))
            $this->avgVol = (float) $row['vol'];
        if (isset($row['sell']))
            $this->sellPrice = (float) $row['sell'];
        if (isset($row['buy']))
            $this->buyPrice = (float) $row['buy'];
        if (isset($row['tx']))
            $this->avgTx = (float) $row['tx'];
        if (isset($row['low']))
            $this->low = (float) $row['low'];
        if (isset($row['high']))
            $this->high = (float) $row['high'];
        if (isset($row['avg']))
            $this->avg = (float) $row['avg'];
        if (isset($row['supplyIn5']))
            $this->supplyIn5 = (int) $row['supplyIn5'];
        if (isset($row['demandIn5']))
            $this->demandIn5 = (int) $row['demandIn5'];
        if (isset($row['avgSell5OrderAge']))
            $this->avgSell5OrderAge = (int) $row['avgSell5OrderAge'];
        if (isset($row['avgBuy5OrderAge']))
            $this->avgBuy5OrderAge = (int) $row['avgBuy5OrderAge'];
    }

    /**
     * @return int marketGroupID
     */
    public function getMarketGroupID() {
        return $this->marketGroupID;
    }
    
    /**
     * Returns boolean on whether item can be sold or not
     * @return bool
     */
    public function onMarket(){
        return isset($this->marketGroupID);
    }
    
    /**
     * Returns the realistic buy price as estimated in emdr.php
     * @param int $maxPriceDataAge optional parameter, specifies the maximum price data age in seconds.
     * @return float 
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     * @throws NoPriceDataAvailableException if no buy price available
     * @throws PriceDataTooOldException if a maxPriceDataAge has been specified and the data is too old
     */
    public function getBuyPrice($maxPriceDataAge = null) {
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        if(is_null($this->buyPrice))
            throw new NoPriceDataAvailableException("No buy price available for " . $this->typeName);
        elseif($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < mktime())
            throw new PriceDataTooOldException('Price data for ' . $this->typeName . ' is too old');

        return $this->buyPrice;
    }
    
    /**
     * Returns the realistic sell price as estimated in emdr.php
     * @param int $maxPriceDataAge optional parameter, specifies the maximum price data age in seconds.
     * @return float
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     * @throws NoPriceDataAvailableException if no buy price available
     * @throws PriceDataTooOldException if a maxPriceDataAge has been specified and the data is too old
     */
    public function getSellPrice($maxPriceDataAge = null) {
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        if(is_null($this->sellPrice))
            throw new NoPriceDataAvailableException("No sell price available for " . $this->typeName);
        elseif($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < mktime())
            throw new PriceDataTooOldException('Price data for ' . $this->typeName . ' is too old');

        return $this->sellPrice;
    }

    /**
     * Returns the complete history for given region and time range.
     * @param int $regionID optional parameter, specifies the regionID for which data should be returned. 
     * If left null, the default regionID is used. 
     * @param string $fromDate in format YYYY-mm-dd, optional parameter. 
     * If left null, a date 90 days ago will be used.
     * @param string $toDate in format YYYY-mm-dd, optional parameter. If left null, the current date will be used.
     * @return array
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     * @throws InvalidParameterValueException if invalid dates are given
     */
    public function getHistory($regionID = null, $fromDate = null, $toDate = null){
        $sde = SDE::instance();
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');        
        //set default region if null
        if(is_null($regionID))
            $regionID = $sde->defaults->getDefaultRegionID();
        
        //set 90 day default if fromDate is null
        if(is_null($fromDate))
            $fromDate = date('Y-m-d', mktime() - 90 * 24 * 3600);
        else {
            //input validation
            if(!preg_match(iveeCoreConfig::DATE_PATTERN, $fromDate)){
                throw new InvalidParameterValueException('Invalid fromDate given.');
            }
        }
        
        //set 'now' default if toDate is null
        if(is_null($toDate))
            $toDate = date('Y-m-d', mktime());
        else {
            //input validation
            if(!preg_match(iveeCoreConfig::DATE_PATTERN, $toDate)){
                throw new InvalidParameterValueException('Invalid toDate given.');
            }
        }
        
        $res = $sde->query("
            SELECT
            date,
            low,
            high,
            avg,
            vol,
            tx,
            sell,
            buy,
            supplyIn5,
            demandIn5,
            avgSell5OrderAge,
            avgBuy5OrderAge
            FROM iveePrices
            WHERE typeID = " . (int) $this->typeID."
            AND regionID = " . (int) $regionID."
            AND date > '".$fromDate."'
            AND date <= '".$toDate."';"
        );
        $ret = array();
        while ($row = $res->fetch_assoc()){
            $ret[$row['date']] = $row;
        }
        return $ret;
    }
    
    /**
     * @return int the price data date as unix timestamp
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getPriceDate() {
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->priceDate;
    }

    /**
     * @return float the market volume, averaged over the last 7 days
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgVol(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->avgVol;
    }
    
    /**
     * @return float the market transactions, averaged over the last 7 days
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgTx(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->avgTx;
    }
    
    /**
     * @return int the volume available in sell orders withing 5% of sellPrice
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getSupplyIn5(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->supplyIn5;
    }

    /**
     * @return int the volume demanded by buy orders withing 5% of buyPrice
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getDemandIn5(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->demandIn5;
    }
    
    /**
     * @return int average time passed since update for buy orders within 5% of the buyPrice. 
     * A measure of market competition.
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgBuy5OrderAge(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->avgBuy5OrderAge;
    }

    /**
     * @return int average time passed since update for sell orders within 5% of the sellPrice. 
     * A measure of market competition.
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgSell5OrderAge(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->avgSell5OrderAge;
    }

    /**
     * @return int the history data date as unix timestamp
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getHistDate(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->histDate;
    }

    /**
     * @return float market "low"
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getLow(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->low;
    }
    
    /**
     * @return float market "high"
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getHigh(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->high;
    }
    
    /**
     * @return float market "avg"
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvg(){
        if(is_null($this->marketGroupID))
            throw new NotOnMarketException($this->typeName . ' is not sellable on the market');
        return $this->avg;
    }
}

?>