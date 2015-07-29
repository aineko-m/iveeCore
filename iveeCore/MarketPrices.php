<?php
/**
 * MarketPrices class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MarketPrices.php
 */

namespace iveeCore;
use iveeCore\Exceptions\KeyNotFoundInCacheException, iveeCore\Exceptions\NoPriceDataAvailableException;

/**
 * MarketPrices holds the current market prices for a market item in a specific region. The "realistic" sell and buy
 * prices are estimated in PriceEstimator during fetching from CREST.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MarketPrices.php
 */
class MarketPrices extends CoreDataCommon
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'MarketPrices';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) objects
     */
    protected static $instancePool;

    /**
     * @var \iveeCore\CrestMarketProcessor $crestMarketProcessor used for processing market data from CREST
     */
    protected static $crestMarketProcessor;
   
    /**
     * @var int $regionId of the region this object refers to
     */
    protected $regionId;

    /**
     * @var int $lastPriceUpdate unix timestamp of the latest market data update from CREST.
     */
    protected $lastPriceUpdate = 0;

    /**
     * @var int $lastHistUpdate unix timestamp of the latest history data update from CREST.
     */
    protected $lastHistUpdate = 0;

    /**
     * @var float $sellPrice the realistic sell price
     */
    protected $sellPrice;

    /**
     * @var float $buyPrice the realistic buy price
     */
    protected $buyPrice;

    /**
     * @var int $supplyIn5 the volume available in sell orders within 5% of the sellPrice
     */
    protected $supplyIn5;

    /**
     * @var int $demandIn5 the volume demanded by buy orders within 5% of the buyPrice
     */
    protected $demandIn5;

    /**
     * @var int $avgSell5OrderAge average time passed since updates for sell orders within 5% of the sellPrice. A
     * measure of competition.
     */
    protected $avgSell5OrderAge;

    /**
     * @var int $avgBuy5OrderAge average time passed since update for buy orders within 5% of the buyPrice. A measure of
     * competition.
     */
    protected $avgBuy5OrderAge;

    /**
     * @var float $avgVol the weekly average traded volume per day.
     */
    protected $avgVol;

    /**
     * @var float $avgTx the weekly average number of transactions per day.
     */
    protected $avgTx;

    /**
     * Returns a string that is used as cache key prefix specific to a hierarchy of SdeType classes. Example:
     * Type and Blueprint are in the same hierarchy, Type and SolarSystem are not.
     *
     * @return string
     */
    public static function getClassHierarchyKeyPrefix()
    {
        return __CLASS__ . '_';
    }

    /**
     * Main function for getting MarketPrices objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $typeId of type
     * @param int $regionId of the region. If none passed, default is looked up.
     * @param int $maxPriceDataAge for the maximum acceptable market data age. Use null for unlimited.
     *
     * @return \iveeCore\MarketPrices
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    public static function getByIdAndRegion($typeId, $regionId = null, $maxPriceDataAge = null)
    {
        //setup instance pool if needed
        if (!isset(static::$instancePool))
            static::init();

        //get default market regionId if none passed
        if (is_null($regionId))
            $regionId = Config::getDefaultMarketRegionId();

        //try instance pool and cache
        try {
            $mp = static::$instancePool->getItem(
                static::getClassHierarchyKeyPrefix() . (int) $regionId . '_' . (int) $typeId
            );
            if (!$mp->isTooOld($maxPriceDataAge))
                return $mp;
        } catch (KeyNotFoundInCacheException $e) { //empty as we are using Exceptions for flow control here
        }

        $mpClass = Config::getIveeClassName(static::getClassNick());

        //try DB
        try {
            $mp = new $mpClass($typeId, $regionId, $maxPriceDataAge);

            //use it only if its not too old or unlimited
            if (!$mp->isTooOld($maxPriceDataAge)) {
                static::$instancePool->setItem($mp);
                return $mp;
            }
        } catch (NoPriceDataAvailableException $e) { //empty as we are using Exceptions for flow control here
        }

        if (is_null(static::$crestMarketProcessor)) {
            $crestMarketProcessorClass = Config::getIveeClassName('CrestMarketProcessor');
            static::$crestMarketProcessor = new $crestMarketProcessorClass;
        }

        //fetch data from CREST and update DB
        //we don't cache the CREST call because we already cache this object
        $data = static::$crestMarketProcessor->getNewestPriceData($typeId, $regionId, false);

        //instantiate new MarketPrice
        $mp = new $mpClass($typeId, $regionId, $maxPriceDataAge, $data);

        //store object in instance pool and cache
        static::$instancePool->setItem($mp);
        return $mp;
    }

    /**
     * Constructor. Use getByIdAndRegion() instead.
     *
     * @param int $typeId of type
     * @param int $regionId of the region
     * @param int $maxPriceDataAge for the market prices, used for setting cache expiry
     * @param array $data to be used instead of DB lookup
     *
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    protected function __construct($typeId, $regionId, $maxPriceDataAge, array $data = null)
    {
        $this->id = (int) $typeId;
        $this->regionId = (int) $regionId;

        $type = Type::getById($this->id);
        if(!$type->onMarket())
            $this->throwNotOnMarketException($type);

        if(is_null($data))
            $data = $this->getDataFromDb();

        //set data to attributes
        $this->lastPriceUpdate = (int) $data['lastPriceUpdate'];
        if (isset($data['lastHistUpdate']))
            $this->lastHistUpdate = (int) $data['lastHistUpdate'];
        if (isset($data['avgVol']))
            $this->avgVol = (float) $data['avgVol'];
        if (isset($data['avgTx']))
            $this->avgTx = (float) $data['avgTx'];
        if (isset($data['sell']))
            $this->sellPrice = (float) $data['sell'];
        if (isset($data['buy']))
            $this->buyPrice  = (float) $data['buy'];
        if (isset($data['supplyIn5']))
            $this->supplyIn5 = (int) $data['supplyIn5'];
        if (isset($data['demandIn5']))
            $this->demandIn5 = (int) $data['demandIn5'];
        if (isset($data['avgSell5OrderAge']))
            $this->avgSell5OrderAge = (int) $data['avgSell5OrderAge'];
        if (isset($data['avgBuy5OrderAge']))
            $this->avgBuy5OrderAge = (int) $data['avgBuy5OrderAge'];

        if (is_null($maxPriceDataAge))
            $maxPriceDataAge = Config::getMaxPriceDataAge();

        if ($this->lastPriceUpdate + $maxPriceDataAge > time())
            $this->expiry = $this->lastPriceUpdate + $maxPriceDataAge;
        else
            $this->expiry = time() + 1800; //if the data somehow is too old, cache it for another half hour
    }

    /**
     * Fetches the data from DB.
     *
     * @return array
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    protected function getDataFromDb()
    {
        $sdeClass = Config::getIveeClassName('SDE');

        //get market data
        $row = $sdeClass::instance()->query(
            "SELECT
            atp.typeID,
            UNIX_TIMESTAMP(lastPriceUpdate) AS lastPriceUpdate,
            UNIX_TIMESTAMP(lastHistUpdate) AS lastHistUpdate,
            avgVol,
            avgTx,
            ap.sell,
            ap.buy,
            ap.supplyIn5,
            ap.demandIn5,
            ap.avgSell5OrderAge,
            ap.avgBuy5OrderAge
            FROM " . Config::getIveeDbName() . ".trackedMarketData as atp
            LEFT JOIN " . Config::getIveeDbName() . ".marketPrices AS ap ON atp.newestPriceData = ap.id
            WHERE atp.typeID = " . $this->id . "
            AND atp.regionID = " . $this->regionId . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException(
                'NoPriceDataAvailableException', "No region market data for typeId=" . $this->id . " and regionId="
                . $this->regionId . " found"
            );
        return $row;
    }

    /**
     * Returns the id of the the region this objects refers to.
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Returns the key used to store and find the object in the cache.
     *
     * @return string
     */
    public function getKey()
    {
        return static::getClassHierarchyKeyPrefix() . $this->getRegionId() . '_' . $this->getId();
    }

    /**
     * Gets unix timestamp of the last price data update.
     *
     * @return int
     */
    public function getLastPriceUpdateTs()
    {
        return $this->lastPriceUpdate;
    }

    /**
     * Gets whether the current data is too old.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds. null for unlimited.
     *
     * @return bool
     */
    public function isTooOld($maxPriceDataAge)
    {
        return !is_null($maxPriceDataAge) AND $this->getLastPriceUpdateTs() + $maxPriceDataAge < time();
    }

    /**
     * Gets unix timestamp of the last history data update (only relevant for avgVol and avgTx).
     *
     * @return int
     */
    public function getLastHistUpdateTs()
    {
        return $this->lastHistUpdate;
    }

    /**
     * Returns the type object this market data refers to.
     *
     * @return \iveeCore\Type
     */
    public function getType()
    {
        return Type::getById($this->getId());
    }

    /**
     * Returns the realistic buy price as estimated in PriceEstimator.
     *
     * @param int $maxPriceDataAge specifies the maximum price data age in seconds. null for unlimited.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NotOnMarketException if the item is not actually sellable (child classes)
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no buy price available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getBuyPrice($maxPriceDataAge = null)
    {
        if (is_null($this->buyPrice))
            self::throwException(
                'NoPriceDataAvailableException', "No buy price available for " . $this->getType()->getName()
            );
        elseif ($this->isTooOld($maxPriceDataAge))
            self::throwException(
                'PriceDataTooOldException', 'Price data for ' . $this->getType()->getName() . ' is too old'
            );

        return $this->buyPrice;
    }

    /**
     * Returns the realistic sell price as estimated in PriceEstimator.
     *
     * @param int $maxPriceDataAge specifies the maximum price data age in seconds. null for unlimited.
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
            self::throwException(
                'NoPriceDataAvailableException', "No sell price available for " . $this->getType()->getName()
            );
        elseif ($this->isTooOld($maxPriceDataAge))
            self::throwException(
                'PriceDataTooOldException', 'Price data for ' . $this->getType()->getName() . ' is too old'
            );

        return $this->sellPrice;
    }

    /**
     * Gets the volume available in sell orders within 5% of sellPrice.
     *
     * @return int
     */
    public function getSupplyIn5()
    {
        return $this->supplyIn5;
    }

    /**
     * Gets the volume demanded by buy orders withing 5% of buyPrice.
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
     * Gets the weekly average traded volume per day.
     *
     * @return float
     */
    public function getAvgVol()
    {
        return $this->avgVol;
    }

    /**
     * Gets the weekly average number of transactions per day.
     *
     * @return float
     */
    public function getAvgTx()
    {
        return $this->avgTx;
    }

    /**
     * Throws NotOnMarketException.
     *
     * @paran iveeCore\SdeType $type that isn't on the market
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
