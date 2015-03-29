<?php
/**
 * Type class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Type.php
 */

namespace iveeCore;

/**
 * Base class for all inventory Type subclasses.
 * Inheritance: Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Type.php
 */
class Type extends SdeType
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'Type';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) Type and child objects
     */
    protected static $instancePool;

    /**
     * @var int $groupID the groupID of this Type.
     */
    protected $groupID;

    /**
     * @var int $categoryID the categoryID of this Type.
     */
    protected $categoryID;

    /**
     * @var float $volume the space the item occupies
     */
    protected $volume;

    /**
     * @var int $portionSize the portion size of this Type; defines the minimum size of production batches.
     */
    protected $portionSize;

    /**
     * @var float $basePrice the base price of this Type.
     */
    protected $basePrice;

    /**
     * @var int $marketGroupID the marketGroupID of this Type
     */
    protected $marketGroupID;

    /**
     * @var array $materials holds data from invTypeMaterials, which is used in reprocessing only
     */
    protected $materials;

    /**
     * @var int $reprocessingSkillID holds the ID of the specialized reprocessing skill if Type is an ore or ice
     */
    protected $reprocessingSkillID;

    /**
     * Main function for getting Type objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $id of requested Type
     *
     * @return \iveeCore\Type the requested Type or subclass object
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    public static function getById($id)
    {
        if (!isset(static::$instancePool))
            static::init();

        try {
            return static::$instancePool->getItem(static::getClassHierarchyKeyPrefix() . (int)$id);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $type = self::factory((int)$id);
            //store SdeType object in instance pool (and cache if configured)
            static::$instancePool->setItem($type);

            return $type;
        }
    }

    /**
     * Loads all type names from DB to PHP.
     *
     * @return void
     */
    protected static function loadNames()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        $res = $sdeClass::instance()->query(
            "SELECT typeID, typeName
            FROM invTypes
            WHERE published = 1;"
        );

        $namesToIds = array();
        while ($row = $res->fetch_assoc())
            $namesToIds[$row['typeName']] = (int) $row['typeID'];

        self::$instancePool->setNamesToKeys(static::getClassHierarchyKeyPrefix() . 'Names', $namesToIds);
    }

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
     * Instantiates type objects without caching logic.
     * This method shouldn't be called directly. Use Type::getById() instead.
     *
     * @param int   $typeID of the Type object
     * @param array $subtypeInfo optional parameter with the DB data used to decide Type subclass
     *
     * @return \iveeCore\Type the requested Type or subclass object
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    private static function factory($typeID, array $subtypeInfo = null)
    {
        //get type decision data if not given
        if (is_null($subtypeInfo))
            $subtypeInfo = self::getSubtypeInfo((int) $typeID);

        //decide type
        $subtype = self::decideType($subtypeInfo);

        //instantiate the appropriate Type or subclass object
        return new $subtype((int) $typeID);
    }

    /**
     * Helper method that returns data to be used to determine as which class to instantiate a certain type ID.
     *
     * @param int $typeID of the Type object
     *
     * @return array with the type decision data from the SDE DB
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    private static function getSubtypeInfo($typeID)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $row = $sdeClass::instance()->query(
            "SELECT
            it.typeID,
            it.groupID,
            ig.categoryID,
            bpProduct.productTypeID as manufacturable,
            bp.typeID as blueprint,
            inventor.activityID as inventor,
            inventable.activityID as inventable,
            rp.typeID as reactionProduct
            FROM invTypes as it
            JOIN invGroups as ig ON it.groupID = ig.groupID
            LEFT JOIN (
                SELECT productTypeID
                FROM industryActivityProducts as iap
                JOIN invTypes as it ON it.typeID = iap.typeID
                WHERE iap.productTypeID = " . (int) $typeID . "
                AND iap.activityID = 1
                AND it.published = 1
                LIMIT 1
            ) as bpProduct ON it.typeID = bpProduct.productTypeID
            LEFT JOIN (
                SELECT typeID
                FROM industryActivity
                WHERE typeID = " . (int) $typeID . "
                AND activityID != 7
                LIMIT 1
            ) as bp ON it.typeID = bp.typeID
            LEFT JOIN (
                SELECT activityID, typeID
                FROM industryActivityProbabilities
                WHERE typeID = " . (int) $typeID . "
                LIMIT 1
            ) as inventor ON it.typeID = inventor.typeID
            LEFT JOIN (
                SELECT productTypeID, activityID
                FROM industryActivityProbabilities as prob
                WHERE prob.productTypeID = " . (int) $typeID . "
                LIMIT 1
            ) as inventable ON it.typeID = inventable.productTypeID
            LEFT JOIN (
                SELECT ir.typeID
                FROM invTypeReactions as ir
                JOIN invTypes ON ir.reactionTypeID = invTypes.typeID
                WHERE ir.typeID = " . (int) $typeID . " AND input = 0 AND published = 1
                LIMIT 1
            ) as rp ON rp.typeID = it.typeID
            WHERE it.typeID = " . (int) $typeID . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException('TypeIdNotFoundException', "typeID " . (int) $typeID . " not found");

        return $row;
    }

    /**
     * Helper method to determine as which subclass to instantiate a certain Type.
     *
     * @param array $subtypeInfo as returned by method getSubtypeInfo()
     *
     * @return string name of the class to instantiate
     */
    private static function decideType(array $subtypeInfo)
    {
        $subtype = '';
        if (empty($subtypeInfo))
            self::throwException('TypeIdNotFoundException', "typeID not found");
        elseif ($subtypeInfo['categoryID'] == 24)
            $subtype = 'Reaction';
        elseif (!empty($subtypeInfo['reactionProduct']))
            $subtype = 'ReactionProduct';
        elseif (in_array($subtypeInfo['groupID'], array(973, 996, 1309)))
            $subtype = 'T3Blueprint';
        elseif (!empty($subtypeInfo['inventable']))
            $subtype = 'InventableBlueprint';
        elseif ($subtypeInfo['categoryID'] == 34)
            $subtype = 'Relic';
        elseif (!empty($subtypeInfo['inventor']))
            $subtype = 'InventorBlueprint';
        elseif (!empty($subtypeInfo['blueprint']))
            $subtype = 'Blueprint';
        elseif ($subtypeInfo['categoryID'] == 35)
            $subtype = 'Decryptor';
        elseif($subtypeInfo['groupID'] == 365)
            $subtype = 'Starbase';
        elseif (!empty($subtypeInfo['manufacturable']))
            $subtype = 'Manufacturable';
        else
            $subtype = 'Type';

        return Config::getIveeClassName($subtype);
    }

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate Type objects instead.
     *
     * @param int $id of the Type
     *
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($id)
    {
        $this->id = (int) $id;

        //get data from SQL
        $row = $this->queryAttributes();
        //set data to object attributes
        $this->setAttributes($row);

        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        //get typeMaterials, if any
        $res = $sdeClass::instance()->query(
            'SELECT
            materialTypeID,
            quantity
            FROM invTypeMaterials
            WHERE typeID = ' . (int) $this->id . ';'
        );
        if ($res->num_rows > 0) {
            $this->materials = array();
            //add materials to the array
            while ($row = $res->fetch_assoc())
                $this->materials[(int) $row['materialTypeID']] = (int) $row['quantity'];
        }
    }

    /**
     * Gets all necessary data from SQL.
     *
     * @return array with attributes queried from DB
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        $row = $sdeClass::instance()->query(
            "SELECT
            it.groupID,
            categoryID,
            typeName,
            volume,
            portionSize,
            basePrice,
            marketGroupID,
            valueInt as reprocessingSkillID
            FROM invTypes as it
            JOIN invGroups as ig ON it.groupID = ig.groupID
            LEFT JOIN (
                SELECT typeID, valueInt
                FROM dgmTypeAttributes
                WHERE attributeID = 790
                AND typeID = " . (int) $this->id . "
            ) as reproc ON reproc.typeID = it.typeID
            WHERE it.published = 1
            AND it.typeID = " . (int) $this->id . ';'
        )->fetch_assoc();

        if (empty($row))
            self::throwException('TypeIdNotFoundException', "typeID " . $this->id . " not found");

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
        $this->groupID     = (int) $row['groupID'];
        $this->categoryID  = (int) $row['categoryID'];
        $this->name        = $row['typeName'];
        $this->volume      = (float) $row['volume'];
        $this->portionSize = (int) $row['portionSize'];
        $this->basePrice   = (float) $row['basePrice'];
        if (isset($row['marketGroupID']))
            $this->marketGroupID = (int) $row['marketGroupID'];
        if (isset($row['reprocessingSkillID']))
            $this->reprocessingSkillID = (int) $row['reprocessingSkillID'];
    }

    /**
     * Gets the groupID of the Type.
     *
     * @return int
     */
    public function getGroupID()
    {
        return $this->groupID;
    }

    /**
     * Gets the categoryID of the Type.
     *
     * @return int
     */
    public function getCategoryID()
    {
        return $this->categoryID;
    }

    /**
     * Gets the volume of the Type.
     *
     * @return float
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Gets the portion size of the Type (relevant for manufacturing and reprocessing).
     *
     * @return int
     */
    public function getPortionSize()
    {
        return $this->portionSize;
    }

    /**
     * Gets the base price.
     * Not to be confused with the base price calculated in industry activities for cost calculation.
     *
     * @return float
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * Gets marketGroupID.
     *
     * @return int marketGroupID
     */
    public function getMarketGroupID()
    {
        return $this->marketGroupID;
    }

    /**
     * Returns boolean on whether item can be sold/bought or not.
     *
     * @return bool
     */
    public function onMarket()
    {
        return isset($this->marketGroupID);
    }

    /**
     * Returns the GlobalPriceData object for this Type.
     *
     * @return \iveeCore\GlobalPriceData
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if there is no price data available for this Type
     */
    public function getGlobalPriceData()
    {
        $globalPriceDataClass = Config::getIveeClassName('GlobalPriceData');
        return $globalPriceDataClass::getById($this->id);
    }

    /**
     * Returns the RegionMarketData object for this Type.
     *
     * @param int $regionID of the region to get market data for. If none passed, default is looked up.
     *
     * @return \iveeCore\RegionMarketData
     * @throws \iveeCore\Exceptions\NotOnMarketException if requested type is not on market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no region market data is found
     */
    public function getRegionMarketData($regionID = null)
    {
        $regionMarketDataClass = Config::getIveeClassName('RegionMarketData');
        return $regionMarketDataClass::getByIdAndRegion($this->id, $regionID);
    }

    /**
     * Returns whether this Type can be reprocessed or not.
     *
     * @return bool
     */
    public function isReprocessable()
    {
        return !empty($this->materials);
    }

    /**
     * Returns the materials for Type. Since Crius this is only relevant for reprocessing.
     *
     * @return int[] in the form typeID => quantity
     */
    public function getMaterials()
    {
        if (empty($this->materials))
            return array();
        else
            return $this->materials;
    }

    /**
     * Returns the specific reprocessing skill ID for this Type.
     *
     * @return int
     */
    public function getReprocessingSkillID()
    {
        return $this->reprocessingSkillID;
    }

    /**
     * Returns a MaterialMap object representing the reprocessing materials of the item.
     *
     * @param int $batchSize number of items being reprocessed, needs to be multiple of portionSize
     * @param float $equipmentYield the reprocessing yield of the station or array
     * @param float $taxFactor the tax imposed by station as factor (<1.0)
     * @param float $implantBonusFactor the reprocessing bonus given by an implant as factor (>1.0)
     *
     * @return \iveeCore\MaterialMap
     * @throws \iveeCore\Exceptions\NotReprocessableException if item is not reprocessable
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if batchSize is not multiple of portionSize or if
     * $equipmentYield or $implantBonusPercent is not sane
     */
    public function getReprocessingMaterialMap($batchSize, $equipmentYield = 0.5, $taxFactor = 0.95,
        $implantBonusFactor = 1.0
    ) {
        if (!$this->isReprocessable())
            self::throwException('NotReprocessableException', $this->name . ' is not reprocessable');

        $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        if ($batchSize % $this->portionSize != 0)
            throw new $exceptionClass('Reprocessing batch size needs to be multiple of ' . $this->portionSize);
        if ($equipmentYield > 1)
            throw new $exceptionClass('Equipment reprocessing yield can never be > 1.0');
        if ($implantBonusFactor > 1.04)
            throw new $exceptionClass('No implants has reprocessing bonus > 4%');
        if ($taxFactor > 1)
            throw new $exceptionClass('Reprocessing tax cannot be lower than 0%');

        //if (compressed) ore or ice
        if ($this->getCategoryID() == 25)
            //Reprocessing, Reprocessing Efficiency and specific Processing skills
            $yield = $equipmentYield
                * (1 + 0.03 * $defaults->getSkillLevel(3385)) //Reprocessing skill
                * (1 + 0.02 * $defaults->getSkillLevel(3389)) //Reprocessing Efficiency skill
                * (1 + 0.02 * $defaults->getSkillLevel($this->getReprocessingSkillID())) // specific skill
                * $implantBonusFactor;
        //everything else
        else
            $yield = $equipmentYield * (1 + 0.02 * $defaults->getSkillLevel(12196)); //Scrapmetal Processing skills

        $materialsClass = Config::getIveeClassName('MaterialMap');
        $rmat = new $materialsClass;

        $numPortions = $batchSize / $this->portionSize;
        foreach ($this->getMaterials() as $typeID => $quantity)
            $rmat->addMaterial($typeID, round($quantity * $yield * $numPortions * $taxFactor));
        return $rmat;
    }

    /**
     * Calculates the tax factor for reprocessing in stations (5% tax = factor of 0.95).
     *
     * @param float $standings with the corporation of the station you are reprocessing at
     *
     * @return float reprocessing tax factor
     * @throws \iveeCore\InvalidParameterValueException if invalid parameter values are given
     */
    public static function calcReprocessingTaxFactor($standings = 6.67)
    {
        //sanity checks
        if ($standings < 0 OR $standings > 10)
            self::throwException("InvalidParameterValueException", "Standing needs to be between 0.0 and 10.0");

        //calculate tax factor
        $tax = 0.05 - (0.0075 * $standings);
        if($tax < 0) $tax = 0;

        return 1 - $tax;
    }
}
