<?php
/**
 * InventorBlueprint class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 *
 */

namespace iveeCore;

/**
 * Class for blueprints that can be used for inventing.
 * Inheritance: InventorBlueprint -> Blueprint -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 *
 */
class InventorBlueprint extends Blueprint
{
    /**
     * @var array $inventsBlueprintID holds the inventable blueprint ID(s)
     */
    protected $inventsBlueprintIDs = array();

    /**
     * @var float $inventionProbability the base invention chance
     */
    protected $inventionProbability;

    /**
     * @var int $decryptorGroupID groupID of compatible decryptors
     */
    protected $decryptorGroupID;

    /**
     * @var int $encryptionSkillID the relevant decryptor skillID
     */
    protected $encryptionSkillID;

    /**
     * @var array $datacoreSkillIDs the relevant datacore skillIDs
     */
    protected $datacoreSkillIDs;

    /**
     * Constructor. Use \iveeCore\Type::getType() to instantiate InventorBlueprint objects instead.
     * 
     * @param int $typeID of the InventorBlueprint object
     * 
     * @return \iveeCore\InventorBlueprint
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($typeID)
    {
        parent::__construct($typeID);
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //query for inventable blueprints, probability and decryptorGroupID
        $res = $sde->query(
            "SELECT iap.productTypeID, iap.probability, COALESCE(valueInt, valueFloat) as decryptorGroupID
            FROM dgmTypeAttributes as dta
            JOIN industryActivityMaterials as iam ON iam.materialTypeID = dta.typeID
            JOIN industryActivityProbabilities as iap ON iap.typeID = iam.typeID
            WHERE attributeID = 1115
            AND iam.activityID = 8
            AND iap.activityID = 8
            AND iam.typeID = " . (int) $this->typeID . ';'
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException', 
                "Inventor data for blueprintID=" . (int) $this->typeID ." not found"
            );

        while ($row = $res->fetch_assoc()) {
            $this->inventsBlueprintIDs[(int) $row['productTypeID']] = 1;
            $this->inventionProbability = (float) $row['probability'];
            $this->decryptorGroupID = (int) $row['decryptorGroupID'];
        }

        //get the mapping for skills to datacore or interface
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
        $this->datacoreSkillIDs = array();
        while ($row = $res->fetch_assoc()) {
            if ($row['groupID'] == 333)
                $this->datacoreSkillIDs[] = $row['skillID'];
            elseif ($row['groupID'] == 716)
                $this->encryptionSkillID = $row['skillID'];
        }
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID if the blueprint to be invented. If left null, it is set to the first 
     * inventable blueprint ID
     * @param int $decryptorID the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     * 
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if the specified blueprint can't be invented from this
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorID isn't a decryptor
     * @throws \iveeCore\Exceptions\InvalidDecryptorGroupException if a non-matching decryptor is specified
     */
    public function invent(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null, $recursive = true)
    {
        $inventionDataClass = Config::getIveeClassName('InventionProcessData');
        $typeClass = Config::getIveeClassName('Type');
        $inventableBpIDs = $this->getInventableBlueprintIDs();

        //if no inventedBpID given, set to first inventable BP ID
         if (is_null($inventedBpID))
             $inventedBpID = $inventableBpIDs[0];

        //check if the given BP can be invented from this
        elseif (!isset($this->inventsBlueprintIDs[$inventedBpID]))
            self::throwException(
                'NotInventableException', 
                "Specified blueprint can't be invented from this inventor blueprint."
            );

        //get invented BP
        $inventedBp = $typeClass::getType($inventedBpID);

        //get modifiers and test if inventing is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_INVENTING, $inventedBp->getProduct());

        //calculate base cost, its the average of all possible invented BP's product base cost
        $baseCost = 0;
        foreach ($inventableBpIDs as $inventableBpID)
            $baseCost += $typeClass::getType($inventableBpID)->getProductBaseCost();
        $baseCost = $baseCost / count($inventableBpIDs);

        //with decryptor
        if ($decryptorID > 0) {
            $decryptor = $this->getAndCheckDecryptor($decryptorID);
            $id = new $inventionDataClass(
                $inventedBpID,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionProbability() * $decryptor->getProbabilityModifier(),
                $inventedBp->getMaxProductionLimit() + $decryptor->getRunModifier(),
                -2 - $decryptor->getMEModifier(),
                -4 - $decryptor->getTEModifier(),
                $modifier['solarSystemID'],
                $modifier['assemblyLineTypeID'],
                isset($modifier['teamID']) ? $modifier['teamID'] : null
            );
            $id->addMaterial($decryptorID, 1);
        } else { //without decryptor
            $id = new $inventionDataClass(
                $inventedBpID,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionProbability(),
                $inventedBp->getMaxProductionLimit(),
                -2,
                -4,
                $modifier['solarSystemID'],
                $modifier['assemblyLineTypeID'],
                isset($modifier['teamID']) ? $modifier['teamID'] : null
            );
        }
        $id->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_INVENTING));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_INVENTING) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m']);

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $id->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable) {
                $id->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            } else {
                $id->addMaterial($matID, $totalNeeded);
            }
        }
        return $id;
    }

    /**
     * For a given typeID, checks if its a compatible decryptor and returns a Decryptor object
     * 
     * @param int $decryptorID the decryptorID to be checked
     * 
     * @return \iveeCore\Decryptor
     * @throws \iveeCore\Exceptions\WrongTypeException if $decryptorID does not reference a Decryptor
     * @throws \iveeCore\Exceptions\InvalidDecryptorGroupException if the Decryptor is not compatible with Blueprint
     */
    protected function getAndCheckDecryptor($decryptorID)
    {
        $typeClass = Config::getIveeClassName('Type');
        $decryptor = $typeClass::getType($decryptorID);

        //check if decryptorID is actually a decryptor
        if (!($decryptor instanceof Decryptor))
            self::throwException('WrongTypeException', 'typeID ' . $decryptorID . ' is not a Decryptor');

        //check if decryptor group matches blueprint
        if ($decryptor->getGroupID() != $this->decryptorGroupID)
            self::throwException('InvalidDecryptorGroupException', 'Given decryptor does not match blueprint race');
 
        return $decryptor;
    }

    /**
     * Copy, invent T2 blueprint and manufacture from it in one go
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID of the blueprint to be invented. If left null it will default to the first 
     * blueprint defined in inventsBlueprintID
     * @param int $decryptorID the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     * 
     * @return ManufactureProcessData with cascaded InventionProcessData and CopyProcessData objects
     */
    public function copyInventManufacture(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null,
        $recursive = true
    ) {
        //make one BP copy
        $copyData = $this->copy($iMod, 1, 1, $recursive);

        //run the invention
        $inventionData = $this->invent(
            $iMod,
            $inventedBpID,
            $decryptorID,
            $recursive
        );

        //add copyData to invention data
        $inventionData->addSubProcessData($copyData);

        //manufacture from invented BP
        $manufactureData = $inventionData->getProducedType()->manufacture(
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
     * Returns an array with the IDs of inventable blueprints
     *
     * @return array
     */
    public function getInventableBlueprintIDs()
    {
        return array_keys($this->inventsBlueprintIDs);
    }

    /**
     * Returns the base invention chance
     *
     * @return float
     */
    public function getInventionProbability()
    {
        return $this->inventionProbability;
    }

    /**
     * Returns the groupID of compatible Decryptors
     *
     * @return int
     */
    public function getDecryptorGroupID()
    {
        return $this->decryptorGroupID;
    }

    /**
     * Returns an array with the IDs of compatible decryptors
     * 
     * @return array
     */
    public function getDecryptorIDs()
    {
        return Decryptor::getIDsFromGroup($this->getDecryptorGroupID());
    }

    /**
     * Calculates the invention chance considering skills and optional meta level item
     * 
     * @param int $metaLevel the metalevel of the optional input item
     * 
     * @return float
     */
    public function calcInventionProbability($metaLevel = null)
    {
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        return $this->getInventionProbability()
            * (1 + 0.01 * $defaults->getSkillLevel($this->encryptionSkillID)
                + 0.02 * ($defaults->getSkillLevel($this->datacoreSkillIDs[0])
                    + $defaults->getSkillLevel($this->datacoreSkillIDs[1]))
            )
            * (isset($metaLevel) ? (1 + 0.5 / (5 - $metaLevel)) : 1);
    }
}
