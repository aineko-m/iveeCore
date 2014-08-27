<?php
/**
 * Sellable class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Sellable.php
 *
 */

namespace iveeCore;

/**
 * Class for all items that can be sold on the market.
 * Inheritance: Sellable -> Type.
 *
 * Note that item objects from child classes are not necessarily sellable on the market.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Sellable.php
 *
 */
class Sellable extends Type
{
    /**
     * @var int $marketGroupID the marketGroupID of this Type
     */
    protected $marketGroupID;

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
     * Use \iveeCore\Type::getType() instead
     *
     * @param int $typeID of requested Type
     *
     * @return void
     * @throws \iveeCore\Exceptions\IveeCoreException always
     */
    public static final function getType($typeID)
    {
        self::throwException(
            'IveeCoreException', 
            "Use \iveeCore\Type::getType() to instantiate objects from Type and it's children"
        );
    }

    /**
     * Use \iveeCore\Type::getTypeIdByName() instead
     *
     * @param string $typeName of requested Type
     *
     * @return void
     * @throws \iveeCore\Exceptions\IveeCoreException always
     */
    public static final function getTypeIdByName($typeName)
    {
        self::throwException('IveeCoreException', "Use \iveeCore\Type::getTypeIdByName() instead");
    }

    /**
     * Use \iveeCore\Type::getTypeByName() instead
     *
     * @param string $typeName of requested Type
     *
     * @return void
     * @throws \iveeCore\Exceptions\IveeCoreException always
     */
    public static final function getTypeByName($typeName)
    {
        self::throwException('IveeCoreException', "Use \iveeCore\Type::getTypeByName() instead");
    }

    /**
     * Constructor. Use \iveeCore\Type::getType() to instantiate Sellable objects instead.
     * 
     * @param int $typeID of the Sellable object
     * 
     * @return \iveeCore\Sellable
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($typeID)
    {
        //call parent constructor
        parent::__construct($typeID);

        $sdeClass      = Config::getIveeClassName('SDE');
        $sde           = $sdeClass::instance();
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults      = $defaultsClass::instance();

        //get market data
        $row = $sde->query(
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
            FROM iveeTrackedPrices
            LEFT JOIN iveePrices AS ah ON iveeTrackedPrices.newestHistData = ah.id
            LEFT JOIN iveePrices AS ap ON iveeTrackedPrices.newestPriceData = ap.id
            WHERE iveeTrackedPrices.typeID = " . (int) $this->typeID . "
            AND iveeTrackedPrices.regionID = " . (int) $defaults->getDefaultRegionID() . ";"
        )->fetch_assoc();

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
     * Gets all necessary data from SQL.
     * 
     * @return array
     * 
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes()
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $row = $sdeClass::instance()->query(
            "SELECT
            it.groupID,
            ig.categoryID,
            it.typeName,
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID,
            valueInt as reprocessingSkillID,
            cp.crestPriceDate,
            cp.crestAveragePrice,
            cp.crestAdjustedPrice
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            LEFT JOIN (
                SELECT typeID, valueInt
                FROM dgmTypeAttributes
                WHERE attributeID = 790
                AND typeID = " . (int) $this->typeID . "
            ) as reproc ON reproc.typeID = it.typeID
            LEFT JOIN (
                SELECT typeID, UNIX_TIMESTAMP(date) as crestPriceDate,
                averagePrice as crestAveragePrice, adjustedPrice as crestAdjustedPrice
                FROM iveeCrestPrices
                WHERE typeID = " . (int) $this->typeID . "
                ORDER BY date DESC LIMIT 1
            ) AS cp ON cp.typeID = it.typeID
            WHERE it.published = 1
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException('TypeIdNotFoundException', "typeID ". (int) $this->typeID . " not found");

        return $row;
    }

    /**
     * Sets attributes from SQL result row to object. Overwrites inherited method.
     * 
     * @param array $row data from DB
     * 
     * @return void
     */
    protected function setAttributes(array $row)
    {
        //call parent method
        parent::setAttributes($row);
        if (isset($row['marketGroupID']))
            $this->marketGroupID = (int) $row['marketGroupID'];
    }

    /**
     * Gets marketGroupID
     * 
     * @return int marketGroupID
     */
    public function getMarketGroupID()
    {
        return $this->marketGroupID;
    }

    /**
     * Returns boolean on whether item can be sold/bought or not
     * 
     * @return bool
     */
    public function onMarket()
    {
        return isset($this->marketGroupID);
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
        if (is_null($this->marketGroupID)) 
            $this->throwNotOnMarketException();
        elseif (is_null($this->buyPrice)) 
            self::throwException('NoPriceDataAvailableException', "No buy price available for " . $this->typeName);
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException('PriceDataTooOldException', 'Price data for ' . $this->typeName . ' is too old');

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
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        elseif (is_null($this->sellPrice))
            self::throwException('NoPriceDataAvailableException', "No sell price available for " . $this->typeName);
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException('PriceDataTooOldException', 'Price data for ' . $this->typeName . ' is too old');

        return $this->sellPrice;
    }

    /**
     * Returns the complete history for given region and time range.
     * 
     * @param int $regionID optional parameter, specifies the regionID for which data should be returned. If left null, 
     * the default regionID is used.
     * @param int $fromDateTS as unix timestamp. If left null, a date 90 days ago will be used.
     * @param int $toDateTS as unix timestamp. If left null, the current date will be used.
     * 
     * @return array
     * @throws \iveeCore\Exception\NotOnMarketException if the item is not actually sellable (child classes)
     * @throws \iveeCore\Exception\InvalidParameterValueException if invalid date timestamps are given
     */
    public function getHistory($regionID = null, $fromDateTS = null, $toDateTS = null)
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        //set default region if null
        if (is_null($regionID))
            $regionID = $defaults->getDefaultRegionID();

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
            $this->throwException ('InvalidParameterValueException', "From-date is more recent than to-date");

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
            FROM iveePrices
            WHERE typeID = " . (int) $this->typeID . "
            AND regionID = " . (int) $regionID . "
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
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getPriceDate()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->priceDate;
    }

    /**
     * Gets the average volume in default region, computed over the last 7 days
     * 
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgVol()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->avgVol;
    }

    /**
     * Gets the average number of transactions in default region, computed over the last 7 days
     * 
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgTx()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->avgTx;
    }

    /**
     * Gets the volume available in sell orders within 5% of sellPrice
     * 
     * @return int
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getSupplyIn5()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->supplyIn5;
    }

    /**
     * Gets the volume demanded by buy orders withing 5% of buyPrice
     * 
     * @return int
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getDemandIn5()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->demandIn5;
    }

    /**
     * Gets the average time passed since update for buy orders within 5% of the buyPrice. A measure of market 
     * competition.
     * 
     * @return int 
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgBuy5OrderAge()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->avgBuy5OrderAge;
    }

    /**
     * Gets the average time passed since update for sell orders within 5% of the sellPrice. A measure of market 
     * competition.
     * 
     * @return int                  
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvgSell5OrderAge()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->avgSell5OrderAge;
    }

    /**
     * Gets the unix timestamp of the date of the last history data update for default region (day granularity)
     * 
     * @return int
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getHistDate()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->histDate;
    }

    /**
     * Gets market "low", as returned by EVEs history for default region
     * 
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getLow()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->low;
    }

    /**
     * Gets market "high", as returned by EVEs history for default region
     * 
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getHigh()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->high;
    }

    /**
     * Gets market "avg", as returned by EVEs history for default region
     * 
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     */
    public function getAvg()
    {
        if (is_null($this->marketGroupID))
            $this->throwNotOnMarketException();
        return $this->avg;
    }

    /**
     * Throws NotOnMarketException
     * 
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException
     */
    protected function throwNotOnMarketException()
    {
        $exceptionClass = Config::getIveeClassName('NotOnMarketException');
        throw new $exceptionClass($this->typeName . ' cannot be bought or sold on the market');
    }
}
