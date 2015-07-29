<?php
/**
 * MarketHistory class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MarketHistory.php
 */

namespace iveeCore;
use iveeCore\Exceptions\KeyNotFoundInCacheException, iveeCore\Exceptions\NoPriceDataAvailableException;

/**
 * MarketHistory represents the market history as time series of all the data that was collected over time.
 * Note that these objects can become large, so carefully chose whether you want to cache them or not, as it can easily
 * lead to cache thrashing.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MarketHistory.php
 */
class MarketHistory extends CoreDataCommon
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'MarketHistory';

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
     * @var int $lastHistUpdate unix timestamp of the latest update from CREST.
     */
    protected $lastHistUpdate = 0;

    /**
     * @var int $oldestDate unix timestamp of the oldest date with any history data.
     */
    protected $oldestDate = PHP_INT_MAX;

    /**
     * @var int $newestDate unix timestamp of the newest date with any history data.
     */
    protected $newestDate = 0;

    /**
     * @var int[] $vol the market volume over time, keyed by date as unix timestamp
     */
    protected $vol = [];

    /**
     * @var int[] $tx the market transactions over time, keyed by date as unix timestamp
     */
    protected $tx = [];

    /**
     * @var float[] $low market "low", as returned by EVEs history over time, keyed by date as unix timestamp
     */
    protected $low = [];

    /**
     * @var float[] $high market "high", as returned by EVEs history over time, keyed by date as unix timestamp
     */
    protected $high = [];

    /**
     * @var float[] $avg market "avg", as returned by EVEs history over time, keyed by date as unix timestamp
     */
    protected $avg = [];

    /**
     * @var float[] $sell realistic sell price as calculated by the PriceEstimator over time, keyed by date as unix
     * timestamp
     */
    protected $sell = [];

    /**
     * @var float[] $buy realistic buy price as calculated by the PriceEstimator over time, keyed by date as unix
     * timestamp
     */
    protected $buy = [];

    /**
     * @var int[] $supplyIn5 the volume available in sell orders within 5% of the sell price over time, keyed by date as
     * unix timestamp
     */
    protected $supplyIn5 = [];

    /**
     * @var int[] $supplyIn5 the volume demanded by buy orders within 5% of the sell price over time, keyed by date as
     * unix timestamp
     */
    protected $demandIn5 = [];

    /**
     * @var int[] $avgSell5OrderAge average time passed since update to sell orders within 5% of the sell price over
     * time, keyed by date as unix timestamp. A measure of competition and activity.
     */
    protected $avgSell5OrderAge = [];

    /**
     * @var int[] $avgBuy5OrderAge average time passed since update to buy orders within 5% of the buy price over time,
     * keyed by date as unix timestamp. A measure of competition and activity.
     */
    protected $avgBuy5OrderAge = [];

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
     * Main function for getting MarketHistory objects. Tries caches, DB and goes to CREST if necessary.
     *
     * @param int $typeId of type
     * @param int $regionId of the region. If none passed, default is looked up.
     * @param int $maxPriceDataAge for the maximum acceptable market data age. null for unlimited.
     * @param bool $cache whether this object should be cached. Note that it can be quite large, so chose carefully.
     *
     * @return \iveeCore\MarketHistory
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    public static function getByIdAndRegion($typeId, $regionId = null, $maxPriceDataAge = null, $cache = true)
    {
        //setup instance pool if needed
        if (!isset(static::$instancePool))
            static::init();

        //get default market regionId if none passed
        if (is_null($regionId))
            $regionId = Config::getDefaultMarketRegionId();

        //try instance pool and cache
        try {
            $mh = static::$instancePool->getItem(
                static::getClassHierarchyKeyPrefix() . (int) $regionId . '_' . (int) $typeId
            );
            if (!$mh->isTooOld($maxPriceDataAge))
                return $mh;
        } catch (KeyNotFoundInCacheException $e) { //empty as we are using Exceptions for flow control here
        }

        $mhClass = Config::getIveeClassName(static::getClassNick());

        //try DB
        try {
            $mh = new $mhClass($typeId, $regionId);

            //use it only if its not too old or unlimited
            if (!$mh->isTooOld($maxPriceDataAge)) {
                static::$instancePool->setItem($mh);
                return $mh;
            }
        } catch (NoPriceDataAvailableException $e) { //empty as we are using Exceptions for flow control here
        }

        if (is_null(static::$crestMarketProcessor)) {
            $crestMarketProcessorClass = Config::getIveeClassName('CrestMarketProcessor');
            static::$crestMarketProcessor = new $crestMarketProcessorClass;
        }

        //fetch data from CREST and update DB
        //we don't cache the CREST call because we already cache this object
        static::$crestMarketProcessor->getNewestHistoryData($typeId, $regionId, false);

        //instantiate new MarketHistory fetching data from DB
        $mh = new $mhClass($typeId, $regionId);

        //store object in instance pool and cache
        if ($cache)
            static::$instancePool->setItem($mh);
        return $mh;
    }

    /**
     * Constructor. Use getByIdAndRegion() instead.
     *
     * @param int $typeId of type
     * @param int $regionId of the region
     *
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    protected function __construct($typeId, $regionId)
    {
        $this->id = (int) $typeId;
        $this->regionId = (int) $regionId;

        $type = Type::getById($this->id);
        if(!$type->onMarket())
            $this->throwNotOnMarketException($type);

        //get timestamp for today, 0h 05m
        $ts = mktime(0, 5, 0);
        //calc expiry as the next occurance of 0h 05m
        $this->expiry = time() < $ts ? $ts : $ts + 24 * 3600;

        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();
        $iveeDbName = Config::getIveeDbName();

        $row = $sde->query(
            'SELECT UNIX_TIMESTAMP(lastHistUpdate) as lastHistUpdate '
            . 'FROM ' . $iveeDbName . '.trackedMarketData '
            . 'WHERE typeID = ' . $this->id . ' AND regionID = ' . $this->regionId . ';'
        )->fetch_assoc();

        $this->lastHistUpdate = (int) $row['lastHistUpdate'];

        //fetch the complete history
        $res = $sde->query(
            'SELECT 
            UNIX_TIMESTAMP(date) as date,
            low,
            high,
            avg,
            vol,
            tx
            FROM ' . $iveeDbName . '.marketHistory
            WHERE typeID = ' . $this->id . '
            AND regionID = ' . $this->regionId . ';'
        );

        while ($row = $res->fetch_assoc()) {
            $date = (int) $row['date'];

            //save the min and max dates
            if ($date < $this->oldestDate OR !isset($this->oldestDate))
                $this->oldestDate = $date;
            if ($date > $this->newestDate)
                $this->newestDate = $date;

            //we store the values by column instead of row to conserve memory
            if (isset($row['low']))
                $this->low[$date] = (float) $row['low'];
            if (isset($row['high']))
                $this->high[$date] = (float) $row['high'];
            if (isset($row['avg']))
                $this->avg[$date] = (float) $row['avg'];
            if (isset($row['vol']))
                $this->vol[$date] = (int) $row['vol'];
            if (isset($row['tx']))
                $this->tx[$date] = (int) $row['tx'];
        }

        //fetch the complete history
        $res = $sde->query(
            'SELECT 
            UNIX_TIMESTAMP(date) as date,
            sell,
            buy,
            supplyIn5,
            demandIn5,
            avgSell5OrderAge,
            avgBuy5OrderAge
            FROM ' . $iveeDbName . '.marketPrices
            WHERE typeID = ' . $this->id . '
            AND regionID = ' . $this->regionId . ';'
        );

        while ($row = $res->fetch_assoc()) {
            $date = (int) $row['date'];

            //save the min and max dates
            if ($date < $this->oldestDate OR !isset($this->oldestDate))
                $this->oldestDate = $date;
            if ($date > $this->newestDate)
                $this->newestDate = $date;

            //we store the values by column instead of row to conserve memory
            if (isset($row['sell']))
                $this->sell[$date] = (float) $row['sell'];
            if (isset($row['buy']))
                $this->buy[$date] = (float) $row['buy'];
            if (isset($row['supplyIn5']))
                $this->supplyIn5[$date] = (int) $row['supplyIn5'];
            if (isset($row['demandIn5']))
                $this->demandIn5[$date] = (int) $row['demandIn5'];
            if (isset($row['avgSell5OrderAge']))
                $this->avgSell5OrderAge[$date] = (int) $row['avgSell5OrderAge'];
            if (isset($row['avgBuy5OrderAge']))
                $this->avgBuy5OrderAge[$date] = (int) $row['avgBuy5OrderAge'];
        }
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
     * Returns the type object this market data refers to.
     *
     * @return \iveeCore\Type
     */
    public function getType()
    {
        return Type::getById($this->getId());
    }

    /**
     * Gets the unix timestamp of the last history update.
     *
     * @return int
     */
    public function getLastHistUpdateTs()
    {
        return $this->lastHistUpdate;
    }

    /**
     * Gets the date unix timestamp of the oldest data available
     *
     * @return int
     */
    public function getOldestDate()
    {
        return $this->oldestDate;
    }

    /**
     * Gets the date unix timestamp of the newest data available
     *
     * @return int
     */
    public function getNewestDate()
    {
        return $this->newestDate;
    }

    /**
     * Gets whether the current data is too old.
     * Note that a date converted to timestamp is treated as midnight (start of day) and CREST only returns data for the
     * past day, therefore the date timestamp will lag up to two whole days + how long it takes to get the new data from
     * CREST behind the current timestamp. An appropriate offset is automatically applied when performing the check.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds. null for unlimited.
     *
     * @return bool
     */
    public function isTooOld($maxPriceDataAge)
    {
        return !is_null($maxPriceDataAge) AND $this->getLastHistUpdateTs() + 2 * 86400 + $maxPriceDataAge < time();
    }

    /**
     * Gets all the available history values for a specific date
     *
     * @param int $dateTs the date as unix timestamp
     *
     * @return array
     */
    public function getValuesForDate($dateTs)
    {
        $ret = [];
        if (isset($this->vol[$dateTs]))
            $ret['vol'] = $this->vol[$dateTs];
        if (isset($this->tx[$dateTs]))
            $ret['tx'] = $this->tx[$dateTs];
        if (isset($this->low[$dateTs]))
            $ret['low'] = $this->low[$dateTs];
        if (isset($this->high[$dateTs]))
            $ret['high'] = $this->high[$dateTs];
        if (isset($this->avg[$dateTs]))
            $ret['avg'] = $this->avg[$dateTs];
        if (isset($this->sell[$dateTs]))
            $ret['sell'] = $this->sell[$dateTs];
        if (isset($this->buy[$dateTs]))
            $ret['buy'] = $this->buy[$dateTs];
        if (isset($this->supplyIn5[$dateTs]))
            $ret['supplyIn5'] = $this->supplyIn5[$dateTs];
        if (isset($this->demandIn5[$dateTs]))
            $ret['demandIn5'] = $this->demandIn5[$dateTs];
        if (isset($this->avgBuy5OrderAge[$dateTs]))
            $ret['avgBuy5OrderAge'] = $this->avgBuy5OrderAge[$dateTs];
        if (isset($this->avgSell5OrderAge[$dateTs]))
            $ret['avgSell5OrderAge'] = $this->avgSell5OrderAge[$dateTs];
        return $ret;
    }

    /**
     * Gets the volume over time.
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getVol()
    {
        return $this->vol;
    }

    /**
     * Gets the number of transactions over time.
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getTx()
    {
        return $this->tx;
    }

    /**
     * Gets market "low", as returned by EVEs history over time.
     *
     * @return float[] keyed by date as unix timestamp
     */
    public function getLow()
    {
        return $this->low;
    }

    /**
     * Gets market "high", as returned by EVEs history over time.
     *
     * @return float[] keyed by date as unix timestamp
     */
    public function getHigh()
    {
        return $this->high;
    }

    /**
     * Gets market "avg", as returned by EVEs history over time.
     *
     * @return float[] keyed by date as unix timestamp
     */
    public function getAvg()
    {
        return $this->avg;
    }

    /**
     * Gets the realistic sell price as calculated by the PriceEstimator over time.
     *
     * @return float[] keyed by date as unix timestamp
     */
    public function getSell()
    {
        return $this->sell;
    }

    /**
     * Gets the realistic buy price as calculated by the PriceEstimator over time.
     *
     * @return float[] keyed by date as unix timestamp
     */
    public function getBuy()
    {
        return $this->buy;
    }

    /**
     * Gets the volume available in sell orders within 5% of the sell price over time
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getSupplyIn5()
    {
        return $this->supplyIn5;
    }

    /**
     * Gets the volume demanded in buy orders within 5% of the buy price over time
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getDemandIn5()
    {
        return $this->demandIn5;
    }

    /**
     * Gets the average time passed since update to sell orders within 5% of the sell price over time, a measure of
     * competition and activity.
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getAvgSell5OrderAge()
    {
        return $this->avgSell5OrderAge;
    }

    /**
     * Gets the average time passed since update to buy orders within 5% of the buy price over time, a measure of
     * competition and activity.
     *
     * @return int[] keyed by date as unix timestamp
     */
    public function getAvgBuy5OrderAge()
    {
        return $this->avgBuy5OrderAge;
    }
    /**
     * Throws NotOnMarketException.
     *
     * @param \iveeCore\SdeType $type that isn't on the market
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
