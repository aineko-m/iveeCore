<?php
/**
 * GlobalPriceData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/GlobalPriceData.php
 */

namespace iveeCore;

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
     * @param int $typeId of requested market data typeID
     *
     * @return \iveeCore\GlobalPriceData
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no price data available for the typeId
     */
    public static function getById($typeId)
    {
        if (!isset(static::$instancePool))
            static::init();

        try {
            return static::$instancePool->getItem(static::getClassHierarchyKeyPrefix() . (int) $typeId);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $typeClass = Config::getIveeClassName(static::getClassNick());
            $type = new $typeClass((int)$typeId);
            //store object in instance pool (and cache if configured)
            static::$instancePool->setItem($type);

            return $type;
        }
    }

    /**
     * Constructor. Use \iveeCore\GlobalPriceData::getById() to instantiate GlobalPriceData objects instead.
     *
     * @param int $typeId of the market data type
     *
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no price data available for the typeId
     */
    protected function __construct($typeId)
    {
        $this->id = (int) $typeId;
        //get data from SQL
        $row = $this->queryAttributes();
        //set data to object attributes
        $this->setAttributes($row);
    }

    /**
     * Gets all necessary data from SQL.
     *
     * @return array with attributes queried from DB
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException when a typeID is not found
     */
    protected function queryAttributes()
    {
        //lookup IveeCore class
        $sdeClass = Config::getIveeClassName('SDE');

        $row = $sdeClass::instance()->query(
            "SELECT UNIX_TIMESTAMP(date) as priceDate,
            averagePrice,
            adjustedPrice
            FROM " . Config::getIveeDbName() . ".iveeCrestPrices
            WHERE typeID = " . $this->id . "
            ORDER BY date DESC LIMIT 1;"
        )->fetch_assoc();

        if (empty($row))
            self::throwException(
                'NoPriceDataAvailableException', "No global price data for " . $this->getType()->getName()
                    . " (typeID=" . $this->id . ") found"
            );

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
        if ($this->priceDate > 0)
            return $this->priceDate;
        else
            self::throwException(
                'NoPriceDataAvailableException', "No CREST price available for " . $this->getType()->getName()
            );
    }

    /**
     * Gets eve-wide average, as returned by CREST.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds, 0 for unlimited.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no CREST price data available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the CREST
     * price data is older
     */
    public function getAveragePrice($maxPriceDataAge)
    {
        if (is_null($this->averagePrice))
            self::throwException(
                'NoPriceDataAvailableException',
                "No averagePrice available for " . $this->getType()->getName()
            );
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException(
                'PriceDataTooOldException',
                'averagePrice data for ' . $this->getType()->getName() . ' is too old'
            );

        return $this->averagePrice;
    }

    /**
     * Gets eve-wide adjusted price, as returned by CREST; relevant for industry activity cost calculations.
     *
     * @param int $maxPriceDataAge specifies the maximum CREST price data age in seconds, 0 for unlimited.
     *
     * @return float
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no CREST price data available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the CREST
     * price data is older
     */
    public function getAdjustedPrice($maxPriceDataAge)
    {
        if (is_null($this->adjustedPrice))
            self::throwException(
                'NoPriceDataAvailableException',
                "No adjustedPrice available for " . $this->getType()->getName()
            );
        elseif ($maxPriceDataAge > 0 AND ($this->priceDate + $maxPriceDataAge) < time())
            self::throwException(
                'PriceDataTooOldException',
                'adjustedPrice data for ' . $this->getType()->getName() . ' is too old'
            );
        return $this->adjustedPrice;
    }
}
