<?php
/**
 * Relic class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 *
 */

namespace iveeCore;

/**
 * Relic class represents items that can be reverse engineered into T3 blueprints (REBlueprints)
 * There is a lot in common with Blueprint and InventorBlueprint, but it was decided against making this a subclass of
 * either as there are a few key differences.
 *
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Relic -> Sellable -> Type -> SdeTypeCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 *
 */
class Relic extends Sellable
{
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
     * @var array $reverseEngineersBlueprintIDs holds the possible REBlueprint IDs
     */
    protected $reverseEngineersBlueprintIDs = array();

    /**
     * @var array $reverseEngineersBlueprintIDsByRaceID raceID => REBlueprint IDs
     */
    protected $reverseEngineersBlueprintIDsByRaceID = array();

    /**
     * @var int $decryptorGroupID the groupID of the compatible decryptors
     */
    protected $decryptorGroupID;

    /**
     * @var float $reverseEngineerProbability the base reverse engineering chance
     */
    protected $reverseEngineerProbability;

    /**
     * @var int $reverseEngineerOutputRuns the base number of runs on the reverse engineering output REBlueprint
     */
    protected $reverseEngineerOutputRuns;

    /**
     * @var int $encryptionSkillID the relevant decryptor skillID
     */
    protected $encryptionSkillID;

    /**
     * @var array $datacoreSkillIDs the relevant datacore skillIDs
     */
    protected $datacoreSkillIDs;

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate Relic objects instead.
     *
     * @param int $id of the Relic object
     *
     * @return \iveeCore\Relic
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //load all required data from the DB
        $this->loadActivityMaterials($sde);
        $this->loadActivitySkills($sde);
        $this->loadActivityTimes($sde);
        $this->loadReStats($sde);
        $this->loadSkillToDatacoreInterface($sde); 
    }

    /**
     * Loads activity material requirements, if any
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadActivityMaterials(SDE $sde)
    {
        $res = $sde->query(
            'SELECT activityID, materialTypeID, quantity, consume
            FROM industryActivityMaterials
            WHERE typeID = ' . $this->id .';'
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
    }

    /**
     * Loads activity skill requirements, if any
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
     * Loads activity times
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadActivityTimes(SDE $sde)
    {
        //get activity times
        $res = $sde->query(
            'SELECT activityID, time
            FROM industryActivity
            WHERE typeID = ' . $this->id .';'
        );
        //set time data to array
        while ($row = $res->fetch_assoc())
            $this->activityTimes[(int) $row['activityID']] = (int) $row['time'];
    }

    /**
     * Loads REBlueprint that can be reverse-engineered from this Relic, probabilities, result runs, decryptorGroupID
     * and t3 product raceID
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if expected data is not found for this typeID
     */
    protected function loadReStats(SDE $sde)
    {
        //get REBlueprint that can be reverse-engineered from this Relic, probabilities, result runs, decryptorGroupID
        //and t3 product raceID
        $res = $sde->query(
            "SELECT relicProd.productTypeID as resultBpID, proba.probability,
            COALESCE(dta.valueInt, dta.valueFloat) as decryptorGroupID, relicProd.quantity, t3.raceID
            FROM dgmTypeAttributes as dta
            JOIN industryActivityMaterials as iam ON iam.materialTypeID = dta.typeID
            JOIN industryActivityProbabilities as proba ON proba.typeID = iam.typeID
            JOIN industryActivityProducts as relicProd ON relicProd.productTypeID = proba.productTypeID
            JOIN industryActivityProducts as t3BPprod ON t3BPprod.typeID = relicProd.productTypeID
            JOIN invTypes as t3 ON t3.typeID = t3BPprod.productTypeID
            WHERE attributeID = 1115
            AND iam.activityID = 7
            AND proba.activityID = 7
            AND t3BPprod.activityID = 1
            AND iam.typeID = " . $this->id . "
            AND relicProd.typeID = " . $this->id . ';'
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException', 
                "ReverseEngineering data for Relic ID=" . $this->id ." not found"
            );

        while ($row = $res->fetch_assoc()) {
            $this->reverseEngineersBlueprintIDs[(int) $row['resultBpID']] = 1;
            $this->reverseEngineersBlueprintIDsByRaceID[(int) $row['raceID']][] = $row['resultBpID'];
            $this->reverseEngineerProbability   = (float) $row['probability'];
            $this->decryptorGroupID             = (int) $row['decryptorGroupID'];
            $this->reverseEngineerOutputRuns    = (int) $row['quantity'];
        }
    }

    /**
     * Loads the mapping for skills to datacore or interface
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadSkillToDatacoreInterface(SDE $sde)
    {
        $res = $sde->query(
            "SELECT COALESCE(valueInt, valueFloat) as skillID, it.groupID
            FROM dgmTypeAttributes as dta
            JOIN invTypes as it ON it.typeID = dta.typeID
            WHERE dta.attributeID = 182
            AND groupID IN (333, 716)
            AND COALESCE(valueInt, valueFloat) IN ("
            . implode(', ', array_keys($this->getSkillMapForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING)->getSkills()))
            . ");"
        );
        $this->datacoreSkillIDs = array();
        while ($row = $res->fetch_assoc()) {
            if ($row['groupID'] == 333)
                $this->datacoreSkillIDs[] = $row['skillID'];
            elseif ($row['groupID'] == 716)
                $this->encryptionSkillID = $row['skillID'];
        }
    }

    /**
     * Returns an ReverseEngineerProcessData object describing the reverse engineering process. It can give both
     * requirements per attempt or average per success, by using the appropriate methods.
     * 
     * Note that, unlike with invention, it is not possible to select which item exactly is gonna be produced by the 
     * reverse engineering process apart from the race. Therefore using this method for determining the profits for
     * a certain reverse engineered item is not realistic, as it does not take into account the other items that might 
     * also be produced instead. The method reverseEngineerByRaceID() tries to better model this by calculating the 
     * process for each of the possible results. 
     *
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $reverseEngineeredBpID the ID of the blueprint to be reverse engineered
     * @param boolean $recursive defines if manufacturables should be built recursively
     *
     * @return \iveeCore\ReverseEngineerProcessData
     * @throws \iveeCore\Exceptions\NotReverseEngineerableException if the specified blueprint can't be reverse
     * engineered from this Relic
     */
    public function reverseEngineer(IndustryModifier $iMod, $reverseEngineeredBpID, $recursive = true)
    {
        $reverseEngineeringDataClass = Config::getIveeClassName('ReverseEngineerProcessData');

        if (!isset($this->reverseEngineersBlueprintIDs[$reverseEngineeredBpID]))
            self::throwException(
                'NotReverseEngineerableException', 
                "Specified type can't be reverse engineered from this Relic"
            );

        //get reverse engineered BP
        $reBP = Type::getById($reverseEngineeredBpID);
        $decryptor = $reBP->getReverseEngineeringDecryptor();

        //get modifiers and test if reverse engineering is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_REVERSE_ENGINEERING, $this);

        //calculate base cost, its the average of all possible reverse engineered BP's product base cost, which is the
        //same for all possible result REBlueprints, so we can just get it from the one we are RE'ing
        $baseCost = $reBP->getProductBaseCost();

        $red = new $reverseEngineeringDataClass(
            $reBP->getId(),
            $this->getBaseTimeForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING) * $modifier['t'],
            $baseCost * 0.02 * $modifier['c'],
            $this->calcReverseEngineeringProbability() * $decryptor->getProbabilityModifier(),
            $this->getReverseEngineeringOutputRuns() + $decryptor->getRunModifier(),
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $red->addMaterial($decryptor->getId(), 1);
        $red->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING));
        $this->addActivityMaterials(
            $iMod,
            $red, 
            ProcessData::ACTIVITY_REVERSE_ENGINEERING,
            $modifier['m'],
            $recursive
        );
 
        return $red;
    }

    /**
     * Computes and adds the material requirements for a process to a ProcessData object.
     * 
     * 
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param ProcessData $pdata to which materials shall be added
     * @param int $activityId of the activity
     * @param float $materialFactor the IndustryModifier and Blueprint ME level dependant ME bonus factor
     * @param float $numPortions the number of portions being built or researched. Note that passing a fraction will
     * make the method not use the rounding for the resulting required amounts.
     * @param bool $recursive defines if used materials should be manufactured recursively
     * 
     * @return void
     */
    protected function addActivityMaterials(IndustryModifier $iMod, ProcessData $pdata, $activityId, $materialFactor, 
        $recursive
    ) {
        foreach ($this->getMaterialsForActivity($activityId) as $matID => $matData) {
            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $pdata->addMaterial($matID, 0);
                continue;
            }

            $mat = Type::getById($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $materialFactor);

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable)
                $pdata->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            else
                $pdata->addMaterial($matID, $totalNeeded);
        }
    }
    /**
     * This method exists to better model the fact that in reverse engineering, it is only possible to chose the race of
     * the output item by definig the decryptor to be used. This method thus returns a ProcessData object with sub
     * processes for each of the possible outputs. This allows for more realistic profit estimation. This is only 
     * relevant when multiple possible outputs exist per race, i.e. subsystems.
     *
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $raceID for the race of the items you are trying to reverse engineer (implies a decryptor)
     * @param boolean $recursive defines if manufacturables should be built recursively
     *
     * @return \iveeCore\ProcessData
     * @throws \iveeCore\Exceptions\NotReverseEngineerableException if no REBlueprints are found for the specified 
     * raceID
     */
    public function reverseEngineerByRaceID(IndustryModifier $iMod, $raceID, $recursive = true)
    {
        $raceBpIDs = $this->getReverseEngineeringBlueprintIDsByRaceID($raceID);
        if (count($raceBpIDs) < 1)
            self::throwException('NotReverseEngineerableException', "No REBlueprints were found for the given raceID");
        elseif (count($raceBpIDs) == 1)
            return $this->reverseEngineer($iMod, $raceBpIDs[0], $recursive);
        
        $processDataClass = Config::getIveeClassName('ProcessData');
        $pd = new $processDataClass;
        
        foreach ($raceBpIDs as $bpID)
            $pd->addSubProcessData($this->reverseEngineer($iMod, $bpID, $recursive));

        return $pd;
    }

    /**
     * Returns all reverse engineerable BPC IDs
     *
     * @return array
     */
    public function getReverseEngineeringBlueprintIDs()
    {
        return array_keys($this->reverseEngineersBlueprintIDs);
    }

    /**
     * Returns the reverse engineerable BPC IDs of a given race
     *
     * @param int $raceID the race for which the blueprints should be looked up. See table chrRaces for IDs.
     *
     * @return array
     */
    public function getReverseEngineeringBlueprintIDsByRaceID($raceID)
    {
        if (!isset($this->reverseEngineersBlueprintIDsByRaceID[$raceID]))
            return array();
        return $this->reverseEngineersBlueprintIDsByRaceID[$raceID];
    }

    /**
     * Returns the number of runs on the output BPC
     *
     * @return int
     */
    public function getReverseEngineeringOutputRuns()
    {
        return $this->reverseEngineerOutputRuns;
    }

    /**
     * Returns the base reverse engineering chance
     *
     * @return float
     */
    public function getReverseEngineeringProbability()
    {
        return $this->reverseEngineerProbability;
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
        else 
            self::throwException('ActivityIdNotFoundException', "ActivityID " . (int) $activityID . " not found.");
    }

    /**
     * Calculates the reverse engineering chance considering skills
     *
     * @return float
     */
    public function calcReverseEngineeringProbability()
    {
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        return $this->getReverseEngineeringProbability()
            * (1 + 0.01 * $defaults->getSkillLevel($this->encryptionSkillID)
                + 0.1 * ($defaults->getSkillLevel($this->datacoreSkillIDs[0])
                + $defaults->getSkillLevel($this->datacoreSkillIDs[1])
            )
        );
    }
}
