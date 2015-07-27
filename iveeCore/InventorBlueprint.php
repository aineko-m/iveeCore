<?php
/**
 * InventorBlueprint class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 */

namespace iveeCore;

/**
 * Class for blueprints that can be used for inventing.
 * Inheritance: InventorBlueprint -> Blueprint -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 */
class InventorBlueprint extends Blueprint
{
    /**
     * @var int[] $inventsBlueprintId holds the inventable blueprint ID(s)
     */
    protected $inventsBlueprintIds = array();

    /**
     * @var array $inventsBlueprintIdsByRaceId raceId => Blueprint IDs
     */
    protected $inventsBlueprintIdsByRaceId = array();

    /**
     * @var float $inventionProbability the base invention chance
     */
    protected $inventionProbability;

    /**
     * @var int $inventionOutputRuns the base number of runs on the invented blueprint
     */
    protected $inventionOutputRuns;

    /**
     * @var int $decryptorGroupId groupId of compatible decryptors
     */
    protected $decryptorGroupId = 1304;

    /**
     * @var int $encryptionSkillId the relevant decryptor skillId
     */
    protected $encryptionSkillId;

    /**
     * @var int[] $datacoreSkillIds the relevant datacore skillIds
     */
    protected $datacoreSkillIds;

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate InventorBlueprint objects instead.
     *
     * @param int $id of the InventorBlueprint object
     *
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeId is not found
     */
    protected function __construct($id)
    {
        parent::__construct($id);
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $this->loadInventionStats($sde);
        $this->loadSkillToDatacoreInterface($sde);
    }

    /**
     * Load inventable blueprints, blueprints by race, probability, and invention output runs.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if expected data is not found for this typeId
     */
    protected function loadInventionStats(SDE $sde)
    {
        $res = $sde->query(
            "SELECT inventorProd.productTypeID as resultBpID, proba.probability, inventorProd.quantity, product.raceID
            FROM industryActivityProbabilities as proba
            JOIN industryActivityProducts as inventorProd ON inventorProd.productTypeID = proba.productTypeID
            JOIN industryActivityProducts as inventableProd ON inventableProd.typeID = inventorProd.productTypeID
            JOIN invTypes as product ON product.typeID = inventableProd.productTypeID
            WHERE proba.activityID = 8
            AND inventableProd.activityID = 1
            AND proba.typeID = " . $this->id . "
            AND inventorProd.typeID = " . $this->id . ";"
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException',
                "Inventor data for Type ID=" . $this->id ." not found"
            );

        while ($row = $res->fetch_assoc()) {
            $this->inventsBlueprintIds[(int) $row['resultBpID']] = 1;
            $this->inventsBlueprintIdsByRaceId[(int) $row['raceID']][] = $row['resultBpID'];
            $this->inventionProbability = (float) $row['probability'];
            $this->inventionOutputRuns  = (int) $row['quantity'];
        }
    }

    /**
     * Loads the mapping for skills to datacore or interface.
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
            . implode(', ', array_keys($this->getSkillMapForActivity(ProcessData::ACTIVITY_INVENTING)->getSkills()))
            . ");"
        );
        $this->datacoreSkillIds = array();
        while ($row = $res->fetch_assoc()) {
            if ($row['groupID'] == 333)
                $this->datacoreSkillIds[] = $row['skillID'];
            elseif ($row['groupID'] == 716)
                $this->encryptionSkillId = $row['skillID'];
        }
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpId the ID if the blueprint to be invented. If left null, it is set to the first
     * inventable blueprint ID
     * @param int $decryptorId the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if the specified blueprint can't be invented from this
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorId isn't a decryptor
     */
    public function invent(IndustryModifier $iMod, $inventedBpId = null, $decryptorId = null, $recursive = true)
    {
        $inventionDataClass = Config::getIveeClassName('InventionProcessData');
        $inventableBpIds = $this->getInventableBlueprintIds();

        //if no inventedBpId given, set to first inventable BP ID
         if (is_null($inventedBpId))
             $inventedBpId = $inventableBpIds[0];

        //check if the given BP can be invented from this
        elseif (!isset($this->inventsBlueprintIds[$inventedBpId]))
            self::throwException(
                'NotInventableException',
                "Specified blueprint can't be invented from this inventor blueprint."
            );

        //get invented BP
        $inventedBp = Type::getById($inventedBpId);

        //get modifiers and test if inventing is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_INVENTING, $inventedBp->getProduct());

        //calculate base cost, its the average of all possible invented BP's product base cost
        $baseCost = 0;
        $numInventableBps = 0;
        foreach ($inventableBpIds as $inventableBpId) {
            $inventableBp = Type::getById($inventableBpId);
            if ($inventableBp instanceof InventableBlueprint) {
                $baseCost += $inventableBp->getProductBaseCost($iMod->getMaxPriceDataAge());
                $numInventableBps++;
            }
        }
            
        $baseCost = $baseCost / $numInventableBps;

        //with decryptor
        if ($decryptorId > 0) {
            $decryptor = $this->getAndCheckDecryptor($decryptorId);
            $idata = new $inventionDataClass(
                $inventedBpId,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionProbability($iMod->getCharacterModifier()) * $decryptor->getProbabilityModifier(),
                $this->inventionOutputRuns + $decryptor->getRunModifier(),
                -2 - $decryptor->getMEModifier(),
                -4 - $decryptor->getTEModifier(),
                $modifier['solarSystemId'],
                $modifier['assemblyLineTypeId']
            );
            $idata->addMaterial($decryptorId, 1);
        } else { //without decryptor
            $idata = new $inventionDataClass(
                $inventedBpId,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionProbability($iMod->getCharacterModifier()),
                $this->inventionOutputRuns,
                -2,
                -4,
                $modifier['solarSystemId'],
                $modifier['assemblyLineTypeId']
            );
        }
        $idata->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_INVENTING));

        $this->addActivityMaterials(
            $iMod,
            $idata,
            ProcessData::ACTIVITY_INVENTING,
            $modifier['m'],
            1,
            $recursive
        );
        return $idata;
    }

    /**
     * For a given typeId, checks if its a compatible decryptor and returns a Decryptor object.
     *
     * @param int $decryptorId the decryptorId to be checked
     *
     * @return \iveeCore\Decryptor
     * @throws \iveeCore\Exceptions\WrongTypeException if $decryptorId does not reference a Decryptor
     */
    protected function getAndCheckDecryptor($decryptorId)
    {
        $decryptor = Type::getById($decryptorId);

        //check if decryptorId is actually a decryptor
        if (!($decryptor instanceof Decryptor))
            self::throwException('WrongTypeException', 'typeId ' . $decryptorId . ' is not a Decryptor');

        return $decryptor;
    }

    /**
     * Copy, invent T2 blueprint and manufacture from it in one go.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpId the ID of the blueprint to be invented. If left null it will default to the first
     * blueprint defined in inventsBlueprintId
     * @param int $decryptorId the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\ManufactureProcessData with cascaded InventionProcessData and CopyProcessData objects
     * @throws \iveeCore\Exceptions\WrongTypeException if product is no an InventableBlueprint
     */
    public function copyInventManufacture(IndustryModifier $iMod, $inventedBpId = null, $decryptorId = null,
        $recursive = true
    ) {
        //make one BP copy
        $copyData = $this->copy($iMod, 1, 1, $recursive);

        //run the invention
        $inventionData = $this->invent(
            $iMod,
            $inventedBpId,
            $decryptorId,
            $recursive
        );

        //add copyData to invention data
        $inventionData->addSubProcessData($copyData);
        
        $producedType = $inventionData->getProducedType();
        if(!$producedType instanceof InventableBlueprint)
            self::throwException('WrongTypeException', 'Given object is not instance of InventableBlueprint');

        //manufacture from invented BP
        $manufactureData = $producedType->manufacture(
            $iMod,
            $inventionData->getResultRuns(),
            $inventionData->getResultME(),
            $inventionData->getResultTE(),
            $recursive
        );

        //add invention data to the manufactureProcessData object
        $manufactureData->addSubProcessData($inventionData);

        return $manufactureData;
    }

    /**
     * Returns an array with the IDs of inventable blueprints.
     *
     * @return int[]
     */
    public function getInventableBlueprintIds()
    {
        return array_keys($this->inventsBlueprintIds);
    }

    /**
     * Returns an array with the inventable blueprint instances.
     *
     * @return \iveeCore\InventableBlueprint[]
     */
    public function getInventableBlueprints()
    {
        $ret = array();
        foreach($this->getInventableBlueprintIds() as $bpId)
            $ret[$bpId] = Type::getById($bpId);
        return $ret;
    }

    /**
     * Returns the base invention chance.
     *
     * @return float
     */
    public function getInventionProbability()
    {
        return $this->inventionProbability;
    }

    /**
     * Returns the inventable BPC IDs of a given race.
     *
     * @param int $raceId the race for which the blueprints should be looked up. See table chrRaces for IDs.
     *
     * @return int[]
     */
    public function getInventableBlueprintIdsByRaceId($raceId)
    {
        if (!isset($this->inventsBlueprintIdsByRaceId[$raceId]))
            return array();
        return $this->inventsBlueprintIdsByRaceId[$raceId];
    }

    /**
     * Returns the base number of runs on the output BPC.
     *
     * @return int
     */
    public function getInventionOutputRuns()
    {
        return $this->inventionOutputRuns;
    }

    /**
     * Returns the groupId of compatible Decryptors.
     *
     * @return int
     */
    public function getDecryptorGroupId()
    {
        return $this->decryptorGroupId;
    }

    /**
     * Returns an array with the IDs of compatible decryptors.
     *
     * @return int[]
     */
    public function getDecryptorIds()
    {
        return Decryptor::getIdsFromGroup($this->getDecryptorGroupId());
    }

    /**
     * Calculates the invention chance considering skills.
     *
     * @param \iveeCore\ICharacterModifier $charMod specific to a character
     *
     * @return float
     */
    public function calcInventionProbability(ICharacterModifier $charMod)
    {
        return $this->getInventionProbability() 
            * (1 +
                ($charMod->getSkillLevel($this->datacoreSkillIds[0])
                    + $charMod->getSkillLevel($this->datacoreSkillIds[1])
                ) / 30
                + $charMod->getSkillLevel($this->encryptionSkillId) / 40
        );
    }
}
