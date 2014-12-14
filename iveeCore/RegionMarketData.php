<?php

namespace iveeCore;

/**
 * Description of MarketData
 *
 * @author sknop
 */
class RegionMarketData extends CacheableCommon
{
    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) objects
     */
    protected static $instancePool;

    /**
     * @var string $classNick holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing) and is used as part of the cache key prefix for objects of this and child classes
     */
    protected static $classNick = 'RegionMarketData';

    /**
     * @var int $regionID of the region this object refers to
     */
    protected $regionID;

    /**
     * @var int $priceDate unix timestamp of the date of the price data in default region. Day granularity.
     */
    protected $priceDate;

    /**
     * @var float $sellPrice the realistic sell price as estimated in EMDR\PriceUpdate.php for the default region
     */
    protected $sellPrice;

    /**
     * @var float $buyPrice the realistic buy price as estimated in EMDR\PriceUpdate.php for the default region
     */
    protected $buyPrice;

    /**
     * @var int $supplyIn5 the volume available in sell orders within 5% of the sellPrice for default region
     */
    protected $supplyIn5;

    /**
     * @var int $demandIn5 the volume demanded by buy orders within 5% of the buyPrice for default region
     */
    protected $demandIn5;

    /**
     * @var int $avgSell5OrderAge average time passed since updates for sell orders within 5% of the sellPrice in
     * default region. A measure of competition.
     */
    protected $avgSell5OrderAge;

    /**
     * @var int $avgBuy5OrderAge average time passed since update for buy orders within 5% of the buyPrice in default
     * region. A measure of competition.
     */
    protected $avgBuy5OrderAge;

    /**
     * @var int $histDate unix timestamp of the date of the history data for default region. Day granularity.
     */
    protected $histDate;

    /**
     * @var float $avgVol the market volume of this type in default region, averaged over the last 7 days
     */
    protected $avgVol;

    /**
     * @var float $avgTx the market transactions for this type in default region, averaged over the last 7 days
     */
    protected $avgTx;

    /**
     * @var float $low market "low", as returned by EVEs history for default region
     */
    protected $low;

    /**
     * @var float $high market "high", as returned by EVEs history for default region
     */
    protected $high;

    /**
     * @var float $avg market "avg", as returned by EVEs history for default region
     */
    protected $avg;

    /**
     * Main function for getting RegionMarketData objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $typeID of type
     * @param int $regionID of the region. If none passed, default is looked up.
     *
     * @return \iveeCore\RegionMarketData
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    public static function getByIdAndRegion($typeID, $regionID = null)
    {
        if (!isset(static::$instancePool))
            static::init();

        if(is_null($regionID)){
            $defaultsClass = Config::getIveeClassName('Defaults');
            $regionID = $defaultsClass::instance()->getDefaultRegionID();
        }

        try {
            return static::$instancePool->getObjByKey($regionID . '_' . $typeID);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $typeClass = Config::getIveeClassName(static::$classNick);
            $type = new $typeClass($typeID, $regionID);
            //store object in instance pool (and cache if configured)
            static::$instancePool->setObj($type);

            return $type;
        }
    }

    /**
     * Constructor. Use \iveeCore\RegionMarketData::getByIdAndRegion() to instantiate RegionMarketData objects instead.
     *
     * @param int $typeID of type
     * @param int $regionID of the region
     *
     * @return \iveeCore\RegionMarketData
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    protected function __construct($typeID, $regionID)
    {
        $this->id = (int) $typeID;
        $this->regionID = (int) $regionID;

        $type = Type::getById($this->id);
        if(!$type->onMarket())
            $this->throwNotOnMarketException($type);

        $sdeClass = Config::getIveeClassName('SDE');

        //get market data
        $row = $sdeClass::instance()->query(
            "SELECT
            iveeTrackedPrices.typeID,
            UNIX_TIMESTAMP(lastHistUpdate) AS histDate,
            UNIX_TIMESTAMP(lastPriceUpdate) AS priceDate,
            iveeTrackedPrices.avgVol AS vol,
            iveeTrackedPrices.avgTx AS tx,
            ah.low,
            ah.high,
            ah.avg,
            ap.sell,
            ap.buy,
            ap.supplyIn5,
            ap.demandIn5,
            ap.avgSell5OrderAge,
            ap.avgBuy5OrderAge
            FROM " . \iveeCore\Config::getIveeDbName() . ".iveeTrackedPrices
            LEFT JOIN " . \iveeCore\Config::getIveeDbName() . ".iveePrices AS ah ON iveeTrackedPrices.newestHistData = ah.id
            LEFT JOIN " . \iveeCore\Config::getIveeDbName() . ".iveePrices AS ap ON iveeTrackedPrices.newestPriceData = ap.id
            WHERE iveeTrackedPrices.typeID = " . $this->id . "
            AND iveeTrackedPrices.regionID = " . $this->regionID . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException('NoPriceDataAvailableException', "No region market data for typeID=" . $this->id
                . " and regionID=" . $this->regionID . " found");

        //set data to attributes
        if (isset($row['histDate']))
            $this->histDate  = (int) $row['histDate'];
        if (isset($row['priceDate']))
            $this->priceDate = (int) $row['priceDate'];
        if (isset($row['vol']))
            $this->avgVol    = (float) $row['vol'];
        if (isset($row['sell']))
            $this->sellPrice = (float) $row['sell'];
        if (isset($row['buy']))
            $this->buyPrice  = (float) $row['buy'];
        if (isset($row['tx']))
            $this->avgTx     = (float) $row['tx'];
        if (isset($row['low']))
            $this->low       = (float) $row['low'];
        if (isset($row['high']))
            $this->high      = (float) $row['high'];
        if (isset($row['avg']))
            $this->avg       = (float) $row['avg'];
        if (isset($row['supplyIn5']))
            $this->supplyIn5 = (int) $row['supplyIn5'];
        if (isset($row['demandIn5']))
            $this->demandIn5 = (int) $row['demandIn5'];
        if (isset($row['avgSell5OrderAge']))
            $this->avgSell5OrderAge   = (int) $row['avgSell5OrderAge'];
        if (isset($row['avgBuy5OrderAge']))
            $this->avgBuy5OrderAge    = (int) $row['avgBuy5OrderAge'];
    }

    /**
     * Returns the id of the the region this objects refers to
     *
     * @return int
     */
    public function getRegionID()
    {
        return $this->regionID;
    }

    /**
     * Returns the key used to store and find the object in the cache
     *
     * @return string
     */
    public function getKey()
    {
        return $this->getRegionID() . '_' . $this->getId();
    }

    /**
     * Returns the type object this market data refers to
     *
     * @return \iv eCore\Type
     */
    public function getType()
    {
        return Type::getById($this->getId());
    }

    /**
     * Gets the objects cache time to live
     *
     * @return int
     */
    public function getCacheTTL()
    {
        return 6 * 3600;
    }

    /**
     * Returns the realistic buy price as estimated in \iveeCore\EMDR\PriceUpdate
     *
     * @param int $maxPriceDataAge optional parameter, specifies the maximum price data age in seconds.
     *
     * @return float
     * @throws NotOnMarketException if the item is not actually sellable (child classes)
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no buy price available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getBuyPrice($maxPriceDataAge = null)
    {
        if (is_null($this->buyPrice))
            self::throwException('NoPriceDataAvailableException', "No buy price available for "
                . $this->getType()->getName());
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException('PriceDataTooOldException', 'Price data for ' . $this->getType()->getName()
                . ' is too old');

        return $this->buyPrice;
    }

    /**
     * Returns the realistic sell price as estimated in EmdrPriceUpdate
     *
     * @param int $maxPriceDataAge optional parameter, specifies the maximum price data age in seconds.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no buy price available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getSellPrice($maxPriceDataAge = null)
    {
        if (is_null($this->sellPrice))
            self::throwException('NoPriceDataAvailableException', "No sell price available for "
                . $this->getType()->getName());
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException('PriceDataTooOldException', 'Price data for ' . $this->getType()->getName()
                . ' is too old');

        return $this->sellPrice;
    }

    /**
     * Returns the complete history for given region and time range.
     *
     * @param int $fromDateTS as unix timestamp. If left null, a date 90 days ago will be used.
     * @param int $toDateTS as unix timestamp. If left null, the current date will be used.
     *
     * @return array
     * @throws \iveeCore\Exception\NotOnMarketException if the item is not actually sellable (child classes)
     * @throws \iveeCore\Exception\InvalidParameterValueException if invalid date timestamps are given
     */
    public function getHistory($fromDateTS = null, $toDateTS = null)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //set 90 day default if fromDate is null
        if (is_null($fromDateTS))
            $fromDateTS = time() - 90 * 24 * 3600;
        else
            $fromDateTS = (int) $fromDateTS;

        //set 'now' default if toDate is null
        if (is_null($toDateTS))
            $toDateTS = time();
        else
            $toDateTS = (int) $toDateTS;

        if($fromDateTS > $toDateTS)
            static::throwException ('InvalidParameterValueException', "From-date is more recent than to-date");

        $res = $sde->query(
            "SELECT
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
            FROM " . \iveeCore\Config::getIveeDbName() . ".iveePrices
            WHERE typeID = " . $this->id . "
            AND regionID = " . $this->regionID . "
            AND date > '" . date('Y-m-d', $fromDateTS) . "'
            AND date <= '" . date('Y-m-d', $toDateTS) . "';"
        );
        $ret = array();
        while ($row = $res->fetch_assoc()) {
            $ret[$row['date']] = $row;
        }
        return $ret;
    }

    /**
     * Gets unix timestamp of the date of the last price data update for default region (day granularity)
     *
     * @return int
     */
    public function getPriceDate()
    {
        return $this->priceDate;
    }

    /**
     * Gets the average volume in default region, computed over the last 7 days
     *
     * @return float
     */
    public function getAvgVol()
    {
        return $this->avgVol;
    }

    /**
     * Gets the average number of transactions in default region, computed over the last 7 days
     *
     * @return float
     */
    public function getAvgTx()
    {
        return $this->avgTx;
    }

    /**
     * Gets the volume available in sell orders within 5% of sellPrice
     *
     * @return int
     */
    public function getSupplyIn5()
    {
        return $this->supplyIn5;
    }

    /**
     * Gets the volume demanded by buy orders withing 5% of buyPrice
     *
     * @return int
     */
    public function getDemandIn5()
    {
        return $this->demandIn5;
    }

    /**
     * Gets the average time passed since update for buy orders within 5% of the buyPrice. A measure of market
     * competition.
     *
     * @return int
     */
    public function getAvgBuy5OrderAge()
    {
        return $this->avgBuy5OrderAge;
    }

    /**
     * Gets the average time passed since update for sell orders within 5% of the sellPrice. A measure of market
     * competition.
     *
     * @return int
     */
    public function getAvgSell5OrderAge()
    {
        return $this->avgSell5OrderAge;
    }

    /**
     * Gets the unix timestamp of the date of the last history data update for default region (day granularity)
     *
     * @return int
     */
    public function getHistDate()
    {
        return $this->histDate;
    }

    /**
     * Gets market "low", as returned by EVEs history for default region
     *
     * @return float
     */
    public function getLow()
    {
        return $this->low;
    }

    /**
     * Gets market "high", as returned by EVEs history for default region
     *
     * @return float
     */
    public function getHigh()
    {
        return $this->high;
    }

    /**
     * Gets market "avg", as returned by EVEs history for default region
     *
     * @return float
     */
    public function getAvg()
    {
        return $this->avg;
    }

    /**
     * Throws NotOnMarketException
     *
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException
     */
    protected function throwNotOnMarketException(SdeType $type)
    {
        $exceptionClass = Config::getIveeClassName('NotOnMarketException');
        throw new $exceptionClass($type->getName() . ' cannot be bought or sold on the market');
    }
}
