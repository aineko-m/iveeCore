<?php
/**
 * Blueprint class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Blueprint.php
 *
 */

namespace iveeCore;

/**
 * Blueprint base class.
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Blueprint -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Blueprint.php
 *
 */
class Blueprint extends Sellable
{
    /**
     * @var int $productTypeID typeID of produced item.
     */
    protected $productTypeID;

    /**
     * @var int $maxProductionLimit defines the maximum production batch size.
     */
    protected $maxProductionLimit;

    /**
     * @var int $rank defines the Blueprints rank
     */
    protected $rank;

    /**
     * @var array $activityMaterials holds activity material requirements.
     * $activityMaterials[$activityID][$typeID]['q'|'c'] for quantity and consume flag, respectively.
     * Array entries for consume flag are omitted if the value is 1.
     */
    protected $activityMaterials = array();

    /**
     * @var array $activitySkills holds activity skill requirements.
     * $activitySkills[$activityID] => SkillMap
     */
    protected $activitySkills = array();

    /**
     * @var array $activityTimes holds activity time requirements.
     * $activityTimes[$activityID] => int seconds
     */
    protected $activityTimes = array();

    /**
     * @var array $baseResearchTimes holds the base research times
     */
    protected static $baseResearchTimes = array(
        1 => 105,
        2 => 250,
        3 => 595,
        4 => 1414,
        5 => 3360,
        6 => 8000,
        7 => 19000,
        8 => 45255,
        9 => 107700,
        10 => 256000
    );

    /**
     * @var array $baseResearchCostMultiplier holds the base research cost multipliers
     */
    protected static $baseResearchCostMultiplier = array(
        1 => 1.0,
        2 => 1.380952381,
        3 => 3.285714286,
        4 => 7.8,
        5 => 18.533333333,
        6 => 44.19047619,
        7 => 104.761904762,
        8 => 250.047619048,
        9 => 594.714285714,
        10 => 1412.380952381
    );

    /**
     * Constructor. Use \iveeCore\Type::getType() to instantiate Blueprint objects instead.
     * 
     * @param int $typeID of the Blueprint object
     * 
     * @return \iveeCore\Blueprint
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function __construct($typeID)
    {
        //call parent constructor
        parent::__construct($typeID);

        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //get activity material requirements, if any
        $res = $sde->query(
            'SELECT activityID, materialTypeID, quantity, consume
            FROM industryActivityMaterials
            WHERE typeID = ' . (int) $this->typeID .';'
        );
        //add materials to the array
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $this->activityMaterials[(int) $row['activityID']][(int) $row['materialTypeID']]['q']
                    = (int) $row['quantity'];
                //to reduce memory usage the consume flag is only explicitly stored if != 1
                if ($row['consume'] != 1)
                    $this->activityMaterials[(int) $row['activityID']][(int) $row['materialTypeID']]['c']
                        = (int) $row['consume'];
            }
        }

        //get activity skills
        $res = $sde->query(
            'SELECT activityID, skillID, level
            FROM industryActivitySkills
            WHERE typeID = ' . (int) $this->typeID .';'
        );
        //set skill data to array
        while ($row = $res->fetch_assoc()) {
            if (!isset($this->activitySkills[(int) $row['activityID']])) {
                $skillMapClass = Config::getIveeClassName('SkillMap');
                $this->activitySkills[(int) $row['activityID']] = new $skillMapClass;
            }
            $this->activitySkills[(int) $row['activityID']]->addSkill((int) $row['skillID'], (int) $row['level']);
        }

        //get activity times
        $res = $sde->query(
            'SELECT activityID, time
            FROM industryActivity
            WHERE typeID = ' . (int) $this->typeID .';'
        );
        //set time data to array
        while ($row = $res->fetch_assoc())
            $this->activityTimes[(int) $row['activityID']] = (int) $row['time'];
    }

    /**
     * Gets all necessary data from SQL
     * 
     * @return array
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function queryAttributes()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();
        $row = $sde->query(
            "SELECT
            it.groupID,
            ig.categoryID,
            it.typeName,
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID,
            prod.productTypeID,
            maxprod.maxProductionLimit,
            COALESCE(r.valueInt, r.valueFloat) as rank,
            cp.crestPriceDate,
            cp.crestAveragePrice,
            cp.crestAdjustedPrice
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            JOIN industryActivityProducts as prod ON prod.typeID = it.typeID
            JOIN industryBlueprints as maxprod ON maxprod.typeID = it.typeID
            JOIN dgmTypeAttributes as r ON r.typeID = it.typeID
            LEFT JOIN (
                SELECT typeID, UNIX_TIMESTAMP(date) as crestPriceDate,
                averagePrice as crestAveragePrice, adjustedPrice as crestAdjustedPrice
                FROM iveeCrestPrices
                WHERE typeID = " . (int) $this->typeID . "
                ORDER BY date DESC LIMIT 1
            ) AS cp ON cp.typeID = it.typeID
            WHERE it.published = 1
            AND prod.activityID = 1
            AND r.attributeID = 1955
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();

        if (empty($row)) {
            $exceptionClass = Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass("typeID " . (int) $this->typeID ." not found");
        }
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * 
     * @param array $row data from DB
     * 
     * @return void
     */
    protected function setAttributes(array $row)
    {
        parent::setAttributes($row);
        $this->productTypeID      = (int) $row['productTypeID'];
        $this->maxProductionLimit = (int) $row['maxProductionLimit'];
        $this->rank               = (float) $row['rank'];
    }

    /**
     * Gets the buy price for this BP
     * 
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * 
     * @return float the buy price for default region as calculated in EmdrPriceUpdate, or basePrice if the BP cannot
     * be sold on the market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no buy price available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is 
     * too old
     */
    public function getBuyPrice($maxPriceDataAge = null)
    {
        //some BPs cannot be sold on the market
        if (empty($this->marketGroupID))
            return $this->basePrice;
        else
            return parent::getBuyPrice($maxPriceDataAge);
    }

    /**
     * Gets the sell price for this BP
     * 
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * 
     * @return float the sell price for default region as calculated in EmdrPriceUpdate, or basePrice if the BP cannot
     * be sold on the market
     * @throws \iveeCore\Exceptions\NoPriceDataAvailableException if no buy price available
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is 
     * too old
     */
    public function getSellPrice($maxPriceDataAge = null)
    {
        //some BPs cannot be sold on the market
        if (empty($this->marketGroupID))
            return $this->basePrice;
        else
            return parent::getSellPrice($maxPriceDataAge);
    }

    /**
     * Gets products base cost based on the adjustedPrice from CREST for each of the input materials
     * 
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * 
     * @return float
     */
    public function getProductBaseCost($maxPriceDataAge = null)
    {
        //lookup Type class
        $typeClass = Config::getIveeClassName('Type');

        if (is_null($maxPriceDataAge)) {
            $defaultsClass = Config::getIveeClassName('Defaults');
            $maxPriceDataAge = $defaultsClass::instance()->getMaxPriceDataAge();
        }

        $baseCost = 0;
        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_MANUFACTURING) as $matID => $matData) {
            if (isset($matData['c'])) 
                continue;
            $baseCost += $typeClass::getType($matID)->getCrestAdjustedPrice($maxPriceDataAge) * $matData['q'];
        }
        return $baseCost;
    }

    /**
     * Manufacture using this BP
     * 
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * 
     * @param int $units the number of items to produce; defaults to 1.
     * @param int $bpME level of the BP; if left null, it is looked up in defaults class
     * @param int $bpTE level of the BP; if left null, it is looked up in defaults class
     * @param bool $recursive defines if components should be manufactured recursively
     * 
     * @return \iveeCore\ManufactureProcessData describing the manufacturing process
     * @throws \iveeCore\Exceptions\TypeNotCompatibleException if the product cannot be manufactured in any of the 
     * assemblyLines given in the IndustryModifier object
     */
    public function manufacture(IndustryModifier $iMod, $units = 1, $bpME = null, $bpTE = null, $recursive = true)
    {
        //get product
        $product = $this->getProduct();
        //get modifiers and test if manufacturing the product is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_MANUFACTURING, $product);

        //lookup Manufacture Data class name
        $manufactureDataClass = Config::getIveeClassName('ManufactureProcessData');
        //lookup Type class
        $typeClass = Config::getIveeClassName('Type');
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        //Some items are manufactured in atomic batches (charges, for instance).
        //We need to normalize quantities and times to the equivalent needed for 1 unit * requested units.
        $runFactor = $units / $product->getPortionSize();

        //get baseCost
        $baseCost = $this->getProductBaseCost();

        if (is_null($bpME))
            $bpME = $defaults->getBpMeLevel($this->typeID);
        if (is_null($bpTE))
            $bpTE = $defaults->getBpTeLevel($this->typeID);
        $meFactor = static::convertBpLevelToFactor($bpME);
        $teFactor = static::convertBpLevelToFactor($bpTE);

        //instantiate manu data object
        $md = new $manufactureDataClass(
            $this->getProductTypeID(),
            $units,
            ceil($runFactor * $this->getBaseTimeForActivity(ProcessData::ACTIVITY_MANUFACTURING) * $teFactor * $modifier['t']),
            $baseCost * $runFactor * $modifier['c'],
            $bpME,
            $bpTE,
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $md->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_MANUFACTURING));

        //iterate over all materials
        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_MANUFACTURING) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $meFactor * $modifier['m'] * $runFactor);

            //at least one unit of material is required per output unit
            if ($totalNeeded < $units) $totalNeeded = $units;

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable)
                $md->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            else
                $md->addMaterial($matID, $totalNeeded);
        }
        return $md;
    }

    /**
     * Make copy using this BP
     * 
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $copies the number of copies to produce; defaults to 1.
     * @param int|string $runs the number of runs on each copy. Use 'max' for the maximum possible number of runs.
     * @param bool $recursive defines if used materials should be manufactured recursively
     * 
     * @return \iveeCore\CopyProcessData describing the copy process
     */
    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $recursive = true)
    {
        //get modifiers and test if copying is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_COPYING, $this);

        //convert 'max' into max number of runs
        if ($runs == 'max')
            $runs = $this->maxProductionLimit;
        $totalRuns = $copies * $runs;

        $copyDataClass = Config::getIveeClassName('CopyProcessData');
        $typeClass = Config::getIveeClassName('Type');

        //instantiate copy data class with required parameters
        $cd = new $copyDataClass(
            $this->typeID,
            $copies,
            $runs,
            ceil($this->getBaseTimeForActivity(ProcessData::ACTIVITY_COPYING) * $totalRuns * $modifier['t']),
            $this->getProductBaseCost() * 0.02 * $totalRuns * $modifier['c'],
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $cd->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_COPYING));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_COPYING) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m'] * $totalRuns);

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $cd->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable)
                $cd->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            else
                $cd->addMaterial($matID, $totalNeeded);
        }
        return $cd;
    }

    /**
     * Research ME on this BLueprint
     * 
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $startME the initial ME level
     * @param int $endME the ME level after the research
     * @param bool $recursive defines if used materials should be manufactured recursively
     * 
     * @return \iveeCore\ResearchMEProcessData describing the research process
     */
    public function researchME(IndustryModifier $iMod, $startME, $endME, $recursive = true)
    {
        $startME = abs((int) $startME);
        $endME   = abs((int) $endME);
        if ($startME < 0 OR $startME >= $endME OR $endME > 10) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Invalid start or end research levels given");
        }

        //get modifiers and test if ME research is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_RESEARCH_ME, $this);

        $researchMEDataClass = Config::getIveeClassName('ResearchMEProcessData');
        $typeClass = Config::getIveeClassName('Type');

        $rmd = new $researchMEDataClass(
            $this->typeID,
            static::calcBaseResearchTime($startME, $endME) * $this->getRank() * $modifier['t'],
            static::calcResearchCostMultiplier($startME, $endME)
                * $this->getProductBaseCost() * 0.02 * $modifier['c'],
            - $startME,
            - $endME,
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $rmd->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_RESEARCH_ME));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_RESEARCH_ME) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m'] * ($endME - $startME));

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $rmd->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable)
                $rmd->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            else
                $rmd->addMaterial($matID, $totalNeeded);
        }
        return $rmd;
    }

    /**
     * Research TE on this BLueprint
     * 
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $startTE the initial TE level
     * @param int $endTE the TE level after the research
     * @param bool $recursive defines if used materials should be manufactured recursively
     * 
     * @return \iveeCore\ResearchTEProcessData describing the research process
     */
    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $recursive = true)
    {
        $startTE = abs((int) $startTE);
        $endTE   = abs((int) $endTE);
        if ($startTE < 0 OR $startTE >= $endTE OR $endTE > 20 OR $startTE % 2 != 0 OR $endTE % 2 != 0) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Invalid start or end research levels given");
        }

        //get modifiers and test if TE research is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_RESEARCH_TE, $this);

        $researchTEDataClass = Config::getIveeClassName('ResearchTEProcessData');
        $typeClass = Config::getIveeClassName('Type');

        $rtd = new $researchTEDataClass(
            $this->typeID,
            static::calcBaseResearchTime($startTE / 2, $endTE / 2) * $this->getRank() * $modifier['t'],
            static::calcResearchCostMultiplier($startTE / 2, $endTE / 2)
                * $this->getProductBaseCost() * 0.02 * $modifier['c'],
            - $startTE,
            - $endTE,
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $rtd->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_RESEARCH_TE));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_RESEARCH_TE) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m'] * ($endTE - $startTE) / 2);

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $rtd->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable)
                $rtd->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            else
                $rtd->addMaterial($matID, $totalNeeded);
        }
        return $rtd;
    }

    /**
     * Returns raw activity material requirements
     * 
     * @return array with the requirements in the form activityID => materialID => array ('q' => ... , 'c' => ...)
     */
    protected function getMaterials()
    {
        return $this->activityMaterials;
    }
    
    /**
     * Returns raw activity material requirements for activity
     * 
     * @param int $activityID specifies for which activity the requirements should be returned.
     * 
     * @return array in the form materialID => array ('q' => ... , 'c' => ...)
     */
    protected function getMaterialsForActivity($activityID)
    {
        if (isset($this->activityMaterials[(int) $activityID]))
            return $this->activityMaterials[(int) $activityID];
        else
            return array();
    }

    /**
     * Returns manufacturing product ID
     * 
     * @return int
     */
    public function getProductTypeID()
    {
        return $this->productTypeID;
    }

    /**
     * Returns an Manufacturable object representing the item produced by this Blueprint
     * 
     * @return \iveeCore\Manufacturable
     */
    public function getProduct()
    {
        //lookup Type class
        $typeClass = Config::getIveeClassName('Type');
        return $typeClass::getType($this->getProductTypeID());
    }

    /**
     * Returns SkillMap of minimum skill requirements for activity
     * 
     * @param int $activityID of the desired activity. See ProcessData constants.
     * 
     * @return \iveeCore\SkillMap
     */
    protected function getSkillMapForActivity($activityID)
    {
        if (isset($this->activitySkills[(int) $activityID]))
            return clone $this->activitySkills[(int) $activityID];
        else {
            $skillMapClass = Config::getIveeClassName('SkillMap');
            return new $skillMapClass;
        }
    }

    /**
     * Returns the base activity time
     * 
     * @param int $activityID of the desired activity. See ProcessData constants.
     * 
     * @return int base activity time in seconds
     * @throws \iveeCore\Exceptions\ActivityIdNotFoundException if activity is not possible with Blueprint
     */
    protected function getBaseTimeForActivity($activityID)
    {
        if (isset($this->activityTimes[(int) $activityID]))
            return $this->activityTimes[(int) $activityID];
        else {
            $exceptionClass = Config::getIveeClassName('ActivityIdNotFoundException');
            throw new $exceptionClass("ActivityID " . (int) $activityID . " not found.");
        }
    }

    /**
     * Returns Blueprint rank
     * 
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Returns the maximum batch size
     * 
     * @return int
     */
    public function getMaxProductionLimit()
    {
        return $this->maxProductionLimit;
    }

    /**
     * Checks if this item is reprocesseable. Blueprints never are.
     * 
     * @return bool
     */
    public function isReprocessable()
    {
        return false;
    }

    /**
     * Converts a Blueprint level to a factor <= 1.0.
     * 
     * @param int $bpLevel the ME or TE level to convert. Positive or negative integers are allowed (there are no more positive
     * Blueprint research levels, so they are just adapted)
     * 
     * @return float
     */
    public static function convertBpLevelToFactor($bpLevel)
    {
        return 1.0 - abs($bpLevel) / 100;
    }

    /**
     * Calculates the base research time depending on start and end ME/TE levels
     * Note: TE levels have to be divided by 2, as they go from 0 to -20 in 10 steps
     * 
     * @param int $startLevel the initial ME or TE level
     * @param int $endLevel the end ME or TE level
     * 
     * @return int
     */
    public static function calcBaseResearchTime($startLevel, $endLevel)
    {
        if ($startLevel < 0 OR $startLevel >= $endLevel OR $endLevel > 10) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Invalid start or end research levels given");
        }

        $timeSum = 0;
        for($i = $startLevel + 1; $i <= $endLevel; $i++)
            $timeSum += static::$baseResearchTimes[$i];

        return $timeSum;
    }

    /**
     * Calculates the base research cost depending on start and end ME/TE levels
     * Note: TE levels have to be divided by 2, as they go from 0 to -20 in 10 steps
     * 
     * @param int $startLevel the initial ME or TE level
     * @param int $endLevel the end ME or TE level
     * 
     * @return float
     */
    public static function calcResearchCostMultiplier($startLevel, $endLevel)
    {
        if ($startLevel < 0 OR $startLevel >= $endLevel OR $endLevel > 10) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Invalid start or end research levels given");
        }

        $multiplierSum = 0;
        for($i = $startLevel + 1; $i <= $endLevel; $i++)
            $multiplierSum += static::$baseResearchCostMultiplier[$i];

        return $multiplierSum;
    }
}
