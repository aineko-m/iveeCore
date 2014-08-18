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
 * Inheritance: Relic -> Sellable -> Type.
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
     * Constructor. Use \iveeCore\Type::getType() to instantiate Relic objects instead.
     *
     * @param int $typeID of the Relic object
     *
     * @return \iveeCore\Relic
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function __construct($typeID)
    {
        //call parent constructor
        parent::__construct($typeID);

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
            AND iam.typeID = " . (int) $this->typeID . "
            AND relicProd.typeID = " . (int) $this->typeID . ';'
        );

        if ($res->num_rows < 1) {
            $exceptionClass = Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass("ReverseEngineering data for Relic ID=" . (int) $this->typeID ." not found");
        }

        while ($row = $res->fetch_assoc()) {
            $this->reverseEngineersBlueprintIDs[(int) $row['resultBpID']] = 1;
            $this->reverseEngineersBlueprintIDsByRaceID[(int) $row['raceID']][] = $row['resultBpID'];
            $this->reverseEngineerProbability   = (float) $row['probability'];
            $this->decryptorGroupID             = (int) $row['decryptorGroupID'];
            $this->reverseEngineerOutputRuns    = (int) $row['quantity'];
        }

        //get the mapping for skills to datacore or interface
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
     * Returns an ReverseEngineerProcessData object describing the reverse engineering process.
     *
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $reverseEngineeredBpID the ID if the blueprint to be reverse engineered
     * @param boolean $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\ReverseEngineerProcessData
     * @throws \iveeCore\Exceptions\NotReverseEngineerableException if the specified blueprint can't be reverse
     * engineered from this Relic
     */
    public function reverseEngineer(IndustryModifier $iMod, $reverseEngineeredBpID, $recursive = true)
    {
        $reverseEngineeringDataClass = Config::getIveeClassName('ReverseEngineerProcessData');
        $typeClass = Config::getIveeClassName('Type');

        if (!isset($this->reverseEngineersBlueprintIDs[$reverseEngineeredBpID])) {
            $exceptionClass = Config::getIveeClassName('NotReverseEngineerableException');
            throw new $exceptionClass("Specified type can't be reverse engineered from this Relic");
        }

        //get reverse engineered BP
        $reBP = $typeClass::getType($reverseEngineeredBpID);
        $decryptor = $reBP->getReverseEngineeringDecryptor();

        //get modifiers and test if reverse engineering is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_REVERSE_ENGINEERING, $this);

        //calculate base cost, its the average of all possible reverse engineered BP's product base cost, which is the
        //same for all possible result REBlueprints, so we can just get it from the one we are RE'ing
        $baseCost = $reBP->getProductBaseCost();

        $red = new $reverseEngineeringDataClass(
            $reBP->getTypeID(),
            $this->getBaseTimeForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING) * $modifier['t'],
            $baseCost * 0.02 * $modifier['c'],
            $this->calcReverseEngineeringProbability() * $decryptor->getProbabilityModifier(),
            $this->getReverseEngineeringOutputRuns() + $decryptor->getRunModifier(),
            $modifier['solarSystemID'],
            $modifier['assemblyLineTypeID'],
            isset($modifier['teamID']) ? $modifier['teamID'] : null
        );

        $red->addMaterial($decryptor->getTypeID(), 1);
        $red->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_REVERSE_ENGINEERING) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m']);

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $red->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable) {
                var_dump($mat->getTypeID());
                $red->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            } else {
                $red->addMaterial($matID, $totalNeeded);
            }
        }

        return $red;
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
        else {
            $exceptionClass = Config::getIveeClassName('ActivityIdNotFoundException');
            throw new $exceptionClass("ActivityID " . (int) $activityID . " not found.");
        }
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
