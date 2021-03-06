<?php
/**
 * Blueprint class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Blueprint.php
 */

namespace iveeCore;

use iveeCore\Exceptions\NoPriceDataAvailableException;

/**
 * Blueprint base class.
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Blueprint -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Blueprint.php
 */
class Blueprint extends Type
{
    /**
     * @var int $productId ID of produced item.
     */
    protected $productId;

    /**
     * @var int $maxProductionLimit defines the maximum production batch size.
     */
    protected $maxProductionLimit;

    /**
     * @var array $activityMaterials holds material requirements by activity.
     * $activityMaterials[$activityId][$typeId] => quantity
     */
    protected $activityMaterials = [];

    /**
     * @var \iveeCore\SkillMap[] $activitySkills holds activity skill requirements.
     * $activitySkills[$activityId] => SkillMap
     */
    protected $activitySkills = [];

    /**
     * @var int[] $activityTimes holds activity time requirements.
     * $activityTimes[$activityId] => int seconds
     */
    protected $activityTimes = [];

    /**
     * @var float $productBaseCost, lazy loaded
     */
    protected $productBaseCost;

    /**
     * @var int[] $baseResearchModifier holds the base research modifier for time and cost scaling.
     */
    protected static $baseResearchModifier = array(
        0 => 0,
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
     * Constructor. Use iveeCore\Type::getById() to instantiate Blueprint objects instead.
     *
     * @param int $id of the Blueprint object
     *
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeId is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $this->loadActivityMaterials($sde);
        $this->loadActivitySkills($sde);
        $this->loadActivityTimes($sde);
    }

    /**
     * Loads activity material requirements, if any.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadActivityMaterials(SDE $sde)
    {
        $res = $sde->query(
            'SELECT activityID, materialTypeID, quantity
            FROM industryActivityMaterials
            WHERE typeID = ' . $this->id .';'
        );
        //add materials to the array
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $this->activityMaterials[(int) $row['activityID']][(int) $row['materialTypeID']]
                    = (int) $row['quantity'];
            }
        }
    }

    /**
     * Loads activity skill requirements, if any.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadActivitySkills(SDE $sde)
    {
        $res = $sde->query(
            'SELECT activityID, skillID, level
            FROM industryActivitySkills
            WHERE typeID = ' . $this->id .';'
        );
        //set skill data to array
        while ($row = $res->fetch_assoc()) {
            if (!isset($this->activitySkills[(int) $row['activityID']])) {
                $skillMapClass = Config::getIveeClassName('SkillMap');
                $this->activitySkills[(int) $row['activityID']] = new $skillMapClass;
            }
            $this->activitySkills[(int) $row['activityID']]->addSkill((int) $row['skillID'], (int) $row['level']);
        }
    }

    /**
     * Loads activity times.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadActivityTimes(SDE $sde)
    {
        $res = $sde->query(
            'SELECT activityID, time
            FROM industryActivity
            WHERE typeID = ' . $this->id .';'
        );
        //set time data to array
        while ($row = $res->fetch_assoc()) {
            $this->activityTimes[(int) $row['activityID']] = (int) $row['time'];
        }
    }

    /**
     * Gets all necessary data from SQL.
     *
     * @return array
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeId is not found
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
            maxprod.maxProductionLimit
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            JOIN industryActivityProducts as prod ON prod.typeID = it.typeID
            JOIN industryBlueprints as maxprod ON maxprod.typeID = it.typeID
            WHERE it.published = 1
            AND prod.activityID = 1
            AND it.typeID = " . $this->id . ";"
        )->fetch_assoc();

        if (empty($row)) {
            self::throwException('TypeIdNotFoundException', "Blueprint ID=" . $this->id . " not found");
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
        parent::setAttributes($row);
        $this->productId      = (int) $row['productTypeID'];
        $this->maxProductionLimit = (int) $row['maxProductionLimit'];
    }

    /**
     * Gets products base cost based on the adjustedPrice from CREST for each of the input materials.
     *
     * @param int $maxPriceDataAge the maximum price data age in seconds. null for unlimited (no updates or exceptions).
     *
     * @return float
     */
    public function getProductBaseCost($maxPriceDataAge)
    {
        if (isset($this->productBaseCost)) {
            return $this->productBaseCost;
        }

        $this->productBaseCost = 0.0;
        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_MANUFACTURING) as $matId => $rawAmount) {
            $mat = Type::getById($matId);
            try {
                $adjustedPrice = $mat->getGlobalPriceData()->getAdjustedPrice($maxPriceDataAge);
            } catch (NoPriceDataAvailableException $ex) {
                //If no global price data is available (new items, for instance) use jita buy, for lack of better value
                $adjustedPrice = $mat->getMarketPrices(10000002)->getBuyPrice();
            }
            $this->productBaseCost += $adjustedPrice * $rawAmount;
        }

        return $this->productBaseCost;
    }

    /**
     * Manufacture using this BP.
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, tax and assemblyLines.
     * @param int $units the number of items to produce; defaults to 1.
     * @param int $bpME level of the BP; if left null, get from IBlueprintModifier contained in IndustryModifier
     * @param int $bpTE level of the BP; if left null, get from IBlueprintModifier contained in IndustryModifier
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     * @param int $reactionRecursionDepth defines if and how deep used materials should be gained through reaction
     * recursively
     *
     * @return \iveeCore\ManufactureProcessData describing the manufacturing process
     * @throws \iveeCore\Exceptions\TypeNotCompatibleException if the product cannot be manufactured in any of the
     * assemblyLines given in the IndustryModifier object
     */
    public function manufacture(
        IndustryModifier $iMod,
        $units = 1,
        $bpME = null,
        $bpTE = null,
        $manuRecursionDepth = 1,
        $reactionRecursionDepth = 0
    ) {
        //get product
        $product = $this->getProduct();
        //get modifiers and test if manufacturing the product is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_MANUFACTURING, $product);

        //get required class FQDN
        $manufactureDataClass = Config::getIveeClassName('ManufactureProcessData');

        //Some items are manufactured in atomic batches (charges, for instance).
        //We need to normalize quantities and times to the equivalent needed for 1 unit * requested units.
        $numPortions = $units / $product->getPortionSize();

        //lookup ME & TE levels if not set
        if (is_null($bpME)) {
            $bpME = $iMod->getBlueprintModifier()->getBpMeLevel($this->id);
        }
        if (is_null($bpTE)) {
            $bpTE = $iMod->getBlueprintModifier()->getBpTeLevel($this->id);
        }

        //instantiate manu data object
        $mdata = new $manufactureDataClass(
            $this->getProductId(),
            $units,
            ceil(
                $numPortions
                * $this->getBaseTimeForActivity(ProcessData::ACTIVITY_MANUFACTURING)
                * static::convertBpLevelToFactor($bpTE)
                * $modifier['t']
            ),
            $this->getProductBaseCost($iMod->getMaxPriceDataAge()) * $numPortions * $modifier['c'],
            $bpME,
            $bpTE,
            $modifier['solarSystemId'],
            $modifier['assemblyLineTypeId']
        );

        //add skills
        $mdata->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_MANUFACTURING));

        //add required materials
        $this->addActivityMaterials(
            $iMod,
            $mdata,
            ProcessData::ACTIVITY_MANUFACTURING,
            static::convertBpLevelToFactor($bpME) * $modifier['m'],
            $numPortions,
            $manuRecursionDepth,
            $reactionRecursionDepth
        );
        return $mdata;
    }

    /**
     * Make copy using this BP.
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, tax and assemblyLines
     * @param int $copies the number of copies to produce; defaults to 1.
     * @param int|string $runs the number of runs on each copy. Use 'max' for the maximum possible number of runs.
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     *
     * @return \iveeCore\CopyProcessData describing the copy process
     */
    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $manuRecursionDepth = 1)
    {
        //get modifiers and test if copying is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_COPYING, $this);

        //convert 'max' into max number of runs
        if ($runs == 'max') {
            $runs = $this->maxProductionLimit;
        }
        $totalRuns = $copies * $runs;

        $copyDataClass = Config::getIveeClassName('CopyProcessData');

        //instantiate copy data class with required parameters
        $cdata = new $copyDataClass(
            $this->id,
            $copies,
            $runs,
            ceil($this->getBaseTimeForActivity(ProcessData::ACTIVITY_COPYING) * $totalRuns * $modifier['t']),
            $this->getProductBaseCost($iMod->getMaxPriceDataAge()) * 0.02 * $totalRuns * $modifier['c'],
            $modifier['solarSystemId'],
            $modifier['assemblyLineTypeId']
        );

        $cdata->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_COPYING));

        $this->addActivityMaterials(
            $iMod,
            $cdata,
            ProcessData::ACTIVITY_COPYING,
            $modifier['m'] * $totalRuns,
            $copies,
            $manuRecursionDepth,
            0
        );
        return $cdata;
    }

    /**
     * Research ME on this BLueprint.
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, tax and assemblyLines
     * @param int $startME the initial ME level
     * @param int $endME the ME level after the research
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     *
     * @return \iveeCore\ResearchMEProcessData describing the research process
     */
    public function researchME(IndustryModifier $iMod, $startME, $endME, $manuRecursionDepth = 1)
    {
        $startME = abs((int) $startME);
        $endME   = abs((int) $endME);
        if ($startME < 0 or $startME >= $endME or $endME > 10) {
            self::throwException('InvalidParameterValueException', "Invalid start or end research levels given");
        }

        //get modifiers and test if ME research is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_RESEARCH_ME, $this);

        $researchMEDataClass = Config::getIveeClassName('ResearchMEProcessData');

        $researchMultiplier = static::calcResearchMultiplier($startME, $endME);

        $rmdata = new $researchMEDataClass(
            $this->id,
            ceil(
                $researchMultiplier
                * $modifier['t']
                * $this->getBaseTimeForActivity(ProcessData::ACTIVITY_RESEARCH_ME)
            ),
            $researchMultiplier * $modifier['c'] * $this->getProductBaseCost($iMod->getMaxPriceDataAge()) * 0.02,
            - $startME,
            - $endME,
            $modifier['solarSystemId'],
            $modifier['assemblyLineTypeId']
        );

        $rmdata->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_RESEARCH_ME));

        $this->addActivityMaterials(
            $iMod,
            $rmdata,
            ProcessData::ACTIVITY_RESEARCH_ME,
            $modifier['m'],
            ($endME - $startME),
            $manuRecursionDepth,
            0
        );
        return $rmdata;
    }

    /**
     * Research TE on this BLueprint.
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, tax and assemblyLines
     * @param int $startTE the initial TE level
     * @param int $endTE the TE level after the research
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     *
     * @return \iveeCore\ResearchTEProcessData describing the research process
     */
    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $manuRecursionDepth = 1)
    {
        $startTE = abs((int) $startTE);
        $endTE   = abs((int) $endTE);
        if ($startTE < 0 or $startTE >= $endTE or $endTE > 20 or $startTE % 2 != 0 or $endTE % 2 != 0) {
            self::throwException('InvalidParameterValueException', "Invalid start or end research levels given");
        }

        //get modifiers and test if TE research is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_RESEARCH_TE, $this);

        $scaleModifier = static::calcResearchMultiplier($startTE / 2, $endTE / 2);

        $researchTEDataClass = Config::getIveeClassName('ResearchTEProcessData');
        $rtdata = new $researchTEDataClass(
            $this->id,
            ceil($scaleModifier * $modifier['t'] * $this->getBaseTimeForActivity(ProcessData::ACTIVITY_RESEARCH_TE)),
            $scaleModifier * $modifier['c'] * $this->getProductBaseCost($iMod->getMaxPriceDataAge()) * 0.02,
            - $startTE,
            - $endTE,
            $modifier['solarSystemId'],
            $modifier['assemblyLineTypeId']
        );

        $rtdata->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_RESEARCH_TE));

        $this->addActivityMaterials(
            $iMod,
            $rtdata,
            ProcessData::ACTIVITY_RESEARCH_TE,
            $modifier['m'],
            ($endTE - $startTE) / 2,
            $manuRecursionDepth,
            0
        );
        return $rtdata;
    }

    /**
     * Computes and adds the material requirements for a process to a ProcessData object.
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, tax and assemblyLines
     * @param ProcessData $pdata to which materials shall be added
     * @param int $activityId of the activity
     * @param float $materialFactor the IndustryModifier and Blueprint ME level dependant ME bonus factor
     * @param float $numPortions the number of portions being built or researched. Note that passing a fraction will
     * make the method not use the rounding for the resulting required amounts.
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     * @param int $reactionRecursionDepth defines if and how deep used materials should be gained through reaction
     * recursively
     *
     * @return void
     */
    protected function addActivityMaterials(
        IndustryModifier $iMod,
        ProcessData $pdata,
        $activityId,
        $materialFactor,
        $numPortions,
        $manuRecursionDepth,
        $reactionRecursionDepth
    ) {
        foreach ($this->getMaterialsForActivity($activityId) as $matId => $rawAmount) {
            $mat = Type::getById($matId);
            $amount = $rawAmount * $materialFactor * $numPortions;

            //calculate total quantity needed, applying all modifiers
            //if number of portions is a fraction, don't ceil() amounts
            if (fmod($numPortions, 1.0) > 0.000000001) {
                $totalNeeded = $amount;
            } else {
                //fix float precision problems
                if (fmod($amount, 1.0) < 0.000000001) {
                    $totalNeeded = round($amount);
                } else {
                    $totalNeeded = ceil($amount);
                }
            }

            //at least one unit of material is required per portion
            if ($totalNeeded < $numPortions) {
                $totalNeeded = $numPortions;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($manuRecursionDepth > 0 and $mat instanceof Manufacturable) {
                $pdata->addSubProcessData(
                    $mat->getBlueprint()->manufacture(
                        $iMod,
                        $totalNeeded,
                        null,
                        null,
                        $manuRecursionDepth - 1,
                        $reactionRecursionDepth
                    )
                );
            } //is using recursive reaction and material is a ReactionProduct, recurse!
            elseif ($reactionRecursionDepth > 0 and $mat instanceof ReactionProduct) {
                $pdata->addSubProcessData($mat->doBestReaction($iMod, $totalNeeded, $reactionRecursionDepth - 1));
            } else {
                $pdata->addMaterial($matId, $totalNeeded);
            }
        }
    }

    /**
     * Returns raw activity material requirements.
     *
     * @return array with the requirements in the form activityId => materialId => quantity
     */
    protected function getActivityMaterials()
    {
        return $this->activityMaterials;
    }

    /**
     * Returns raw activity material requirements for activity.
     *
     * @param int $activityId specifies for which activity the requirements should be returned.
     *
     * @return array in the form materialId => quantity
     */
    protected function getMaterialsForActivity($activityId)
    {
        if (isset($this->activityMaterials[(int) $activityId])) {
            return $this->activityMaterials[(int) $activityId];
        } else {
            return [];
        }
    }

    /**
     * Returns manufacturing product ID.
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Returns an Manufacturable object representing the item produced by this Blueprint.
     *
     * @return \iveeCore\Manufacturable
     */
    public function getProduct()
    {
        return Type::getById($this->getProductId());
    }

    /**
     * Returns SkillMap of minimum skill requirements for activity.
     *
     * @param int $activityId of the desired activity. See ProcessData constants.
     *
     * @return \iveeCore\SkillMap
     */
    protected function getSkillMapForActivity($activityId)
    {
        if (isset($this->activitySkills[(int) $activityId])) {
            return clone $this->activitySkills[(int) $activityId];
        } else {
            $skillMapClass = Config::getIveeClassName('SkillMap');
            return new $skillMapClass;
        }
    }

    /**
     * Returns the base activity time.
     *
     * @param int $activityId of the desired activity. See ProcessData constants.
     *
     * @return int base activity time in seconds
     * @throws \iveeCore\Exceptions\ActivityIdNotFoundException if activity is not possible with Blueprint
     */
    protected function getBaseTimeForActivity($activityId)
    {
        if (isset($this->activityTimes[(int) $activityId])) {
            return $this->activityTimes[(int) $activityId];
        } else {
            self::throwException('ActivityIdNotFoundException', "ActivityId " . (int) $activityId . " not found.");
        }
    }

    /**
     * Returns Blueprint rank.
     *
     * @return int
     */
    public function getRank()
    {
        return (int) $this->getBaseTimeForActivity(ProcessData::ACTIVITY_RESEARCH_TE) / 105;
    }

    /**
     * Returns the maximum batch size.
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
     * Calculates the research multiplier for time and cost scaling depending on start and end ME/TE levels.
     * Note: TE levels have to be divided by 2, as they go from 0 to -20 in 10 steps.
     *
     * @param int $startLevel the initial ME or TE level
     * @param int $endLevel the end ME or TE level
     *
     * @return float
     */
    public static function calcResearchMultiplier($startLevel, $endLevel)
    {
        if ($startLevel < 0 or $startLevel >= $endLevel or $endLevel > 10) {
            self::throwException('InvalidParameterValueException', "Invalid start or end research levels given");
        }

        return (static::$baseResearchModifier[$endLevel] - static::$baseResearchModifier[$startLevel]) / 105;
    }
}
