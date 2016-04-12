<?php
/**
 * GlobalPriceData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/GlobalPriceData.php
 */

namespace iveeCore;

use iveeCore\Exceptions\KeyNotFoundInCacheException;

/**
 * GlobalPriceData represents the global price data returned by public CREST.
 * Inheritance: GlobalPriceData -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/GlobalPriceData.php
 */
class GlobalPriceData extends CoreDataCommon
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'GlobalPriceData';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) objects
     */
    protected static $instancePool;

    /**
     * @var int $updateTs unix timstamp for the last update to global prices from CREST
     */
    protected $updateTs;

    /**
     * @var float $averagePrice eve-wide average, as returned by CREST
     */
    protected $averagePrice;

    /**
     * @var float $adjustedPrice eve-wide adjusted price, as returned by CREST, relevant for industry activity
     * cost calculations. CREST returns price data even for some items that aren't on the market.
     */
    protected $adjustedPrice;

    /**
     * @var int $priceDate unix timstamp for the last update to market prices from CREST (day granularity). CREST
     * returns price data even for some items that aren't on the market.
     */
    protected $priceDate;

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
     * Retuns a GlobalPriceData object. Tries caches and instantiates new objects if necessary.
     *
     * @param int $typeId of requested market data typeId
     * @param int $maxDataAge the maximum acceptable CREST global price data age. This is in addition to a 24h minimum
     * validity.
     *
     * @return \iveeCore\GlobalPriceData
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no price data available for the typeId
     */
    public static function getById($typeId, $maxDataAge = 3600)
    {
        //setup instance pool if needed
        if (!isset(static::$instancePool)) {
            static::init();
        }

        //try instance pool and cache
        try {
            $gpd = static::$instancePool->getItem(
                static::getClassHierarchyKeyPrefix() . (int) $typeId
            );
            if (!$gpd->isTooOld($maxDataAge)) {
                return $gpd;
            }
        } catch (KeyNotFoundInCacheException $e) { //empty as we are using Exceptions for flow control here
        }

        $lastUpdateTs = static::getLastUpdateTs();

        //if the last update is too long ago, do another
        if ($lastUpdateTs + $maxDataAge < time()) {
            //fetch data from CREST and update DB for all systems
            $crestGlobalPricesUpdaterClass = Config::getIveeClassName('CrestGlobalPricesUpdater');
            $crestGlobalPricesUpdaterClass::doUpdate();

            //since the CREST update affects all system indices, clear the instance pool
            static::$instancePool->clearPool();
        }

        //instantiate new SystemIndustryIndices
        $gpdClass = Config::getIveeClassName(static::getClassNick());
        $gpd = new $gpdClass($typeId, $lastUpdateTs, $maxDataAge);

        //store object in instance pool and cache
        static::$instancePool->setItem($gpd);
        return $gpd;
    }

    /**
     * Gets the timestamp for the last performed update of the global prices via CREST.
     *
     * @return int
     */
    public static function getLastUpdateTs()
    {
        $sdeClass = Config::getIveeClassName('SDE');

        //get most recent update date
        $res = $sdeClass::instance()->query(
            "SELECT UNIX_TIMESTAMP(lastUpdate) as lastUpdateTs
            FROM " . Config::getIveeDbName() . ".trackedCrestUpdates
            WHERE name = 'globalPrices';"
        );

        if ($res->num_rows > 0) {
            return (int) $res->fetch_assoc()['lastUpdateTs'];
        } else {
            return 0;
        }
    }

    /**
     * Constructor. Use iveeCore\GlobalPriceData::getById() to instantiate GlobalPriceData objects instead.
     *
     * @param int $typeId of the market data type
     * @param int $lastUpdateTs the timestamp of the last performed update from CREST
     * @param int $maxDataAge maximum global price data age. This is in addition to a 24h minimum validity.
     *
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no price data available for the typeId
     */
    protected function __construct($typeId, $lastUpdateTs, $maxDataAge)
    {
        $this->id = (int) $typeId;
        //get data from SQL
        $row = $this->queryAttributes();
        //set data to object attributes
        $this->setAttributes($row);

        $this->updateTs = $lastUpdateTs;
        //calc expiry as the next day + max price data age
        $this->expiry = $this->priceDate + 24 * 3600 + $maxDataAge;
    }

    /**
     * Gets all necessary data from SQL.
     *
     * @return array with attributes queried from DB
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException when a typeId is not found
     */
    protected function queryAttributes()
    {
        //lookup IveeCore class
        $sdeClass = Config::getIveeClassName('SDE');

        $row = $sdeClass::instance()->query(
            "SELECT UNIX_TIMESTAMP(date) as priceDate,
            averagePrice,
            adjustedPrice
            FROM " . Config::getIveeDbName() . ".globalPrices
            WHERE typeID = " . $this->id . "
            ORDER BY date DESC LIMIT 1;"
        )->fetch_assoc();

        if (empty($row)) {
            self::throwException(
                'NoPriceDataAvailableException',
                "No global price data for " . $this->getType()->getName()
                    . " (typeId=" . $this->id . ") found"
            );
        }

        return $row;
    }

    /**
     * Sets attributes from SQL result row to object.
     *
     * @param array $row data from DB
     *
     * @return void
     */
    protected function setAttributes(array $row)
    {
        $this->priceDate     = (int) $row['priceDate'];
        $this->averagePrice  = (float) $row['averagePrice'];
        $this->adjustedPrice = (float) $row['adjustedPrice'];
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
     * Gets the unix timestamp of the date of the last CREST price data update (day granularity).
     *
     * @return int
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no CREST price data available
     */
    public function getPriceDate()
    {
        if ($this->priceDate > 0) {
            return $this->priceDate;
        } else {
            self::throwException(
                'NoPriceDataAvailableException',
                "No CREST price available for " . $this->getType()->getName()
            );
        }
    }

    /**
     * Gets eve-wide average, as returned by CREST.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds. null for unlimited.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no CREST price data available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the CREST
     * price data is older
     */
    public function getAveragePrice($maxPriceDataAge)
    {
        if (is_null($this->averagePrice)) {
            self::throwException(
                'NoPriceDataAvailableException',
                "No averagePrice available for " . $this->getType()->getName()
            );
        } elseif ($this->isTooOld($maxPriceDataAge)) {
            self::throwException(
                'PriceDataTooOldException',
                'averagePrice data for ' . $this->getType()->getName() . ' is too old'
            );
        }

        return $this->averagePrice;
    }

    /**
     * Gets eve-wide adjusted price, as returned by CREST; relevant for industry activity cost calculations.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds. null for unlimited.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no CREST price data available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the CREST
     * price data is older
     */
    public function getAdjustedPrice($maxPriceDataAge)
    {
        if (is_null($this->adjustedPrice)) {
            self::throwException(
                'NoPriceDataAvailableException',
                "No adjustedPrice available for " . $this->getType()->getName()
            );
        } elseif ($this->isTooOld($maxPriceDataAge)) {
            self::throwException(
                'PriceDataTooOldException',
                'adjustedPrice data for ' . $this->getType()->getName() . ' is too old'
            );
        }
        return $this->adjustedPrice;
    }

    /**
     * Gets whether the current data is too old.
     * Note that a date converted to timestamp is treated as midnight (start of day), therefore the date timestamp will
     * lag up to a whole day + how long it takes to get the new data from CREST behind the current timestamp. An
     * appropriate offset is automatically applied when performing the check.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds. null for unlimited.
     *
     * @return bool
     */
    public function isTooOld($maxPriceDataAge)
    {
        //take the data timestamp, add a whole day as it is valid until the end of the current day.
        //Then add whatever wiggle room we get from maxPriceDataAge.
        return !is_null($maxPriceDataAge) and $this->priceDate + 86400 + $maxPriceDataAge < time();
    }
}
