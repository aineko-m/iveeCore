<?php

/**
 * Class for blueprints that can be used for inventing. 
 * Inheritance: InventorBlueprint -> Blueprint -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/InventorBlueprint.php
 * @package iveeCore
 */
class InventorBlueprint extends Blueprint {

    /**
     * @var array $inventsBlueprintID holds the inventable blueprint ID(s)
     */
    protected $inventsBlueprintID = array();
    
    /**
     * @var float $baseChance the base invention success chance
     */
    protected $baseChance;
    
    /**
     * @var int $decryptorGroupID groupID of compatible decryptors
     */
    protected $decryptorGroupID;

    /**
     * Constructor.
     * Use SDE->getType() to instantiate InventorBlueprint objects.
     * @param int $typeID of the Type object
     * @return InventorBlueprint
     * @throws TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($typeID) {
        parent::__construct($typeID);
        $sde = SDE::instance();

        //query for inventable blueprints 
        $res = $sde->query(
            "SELECT t2bp.blueprintTypeId as t2bpID
            FROM `invMetaTypes` as iMT
            JOIN invTypes as t1 ON t1.typeID = iMT.parentTypeID
            JOIN invTypes as t2 ON t2.typeID = iMT.typeID
            JOIN invBlueprintTypes as t1bp ON t1bp.productTypeID = t1.typeID
            JOIN invBlueprintTypes as t2bp ON t2bp.productTypeID = t2.typeID
            WHERE iMT.metaGroupID = 2
            AND t1bp.blueprintTypeId = " . (int) $this->typeID . ';'
        );
        
        while ($row = $res->fetch_assoc()) {
            $this->inventsBlueprintID[] = (int) $row['t2bpID'];
        }

        //query for base chance of invention + decryptorGroupID
        $res = $sde->query(
            "SELECT CASE
            WHEN t.groupID IN (419,27) OR t.typeID = 17476
            THEN 0.20
            WHEN t.groupID IN (26,28) OR t.typeID = 17478
            THEN 0.25
            WHEN t.groupID IN (25,420,513) OR t.typeID = 17480
            THEN 0.30
            WHEN EXISTS (SELECT typeID FROM invMetaTypes WHERE parentTypeID = t.typeID AND metaGroupID = 2)
            THEN 0.40
            END as chance,
            dg.decryptorGroupID
            FROM
            invBlueprintTypes AS bt
            JOIN invTypes as t ON bt.productTypeID = t.typeID
            LEFT JOIN (
                SELECT rtr.typeID as blueprintTypeID, dta.valueINT as decryptorGroupID
                FROM ramTypeRequirements as rtr
                LEFT JOIN dgmTypeAttributes as dta ON dta.typeID = rtr.requiredTypeID
                WHERE 
                rtr.typeID = " . (int) $this->typeID . "
                AND rtr.activityID = 8
                AND dta.attributeID = 1115
            ) as dg ON dg.blueprintTypeID = bt.blueprintTypeID
            WHERE bt.blueprintTypeID = " . (int) $this->typeID . ';'
        );
        while ($row = $res->fetch_assoc()) {
            $this->baseChance = (float) $row['chance'];
            $this->decryptorGroupID = (int) $row['decryptorGroupID'];
        }
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     * @param int $inventedBpTypeID the ID if the blueprint to be invented. If left null, it is set to the first 
     * inventable blueprint ID
     * @param int $decryptorID the decryptor the be used, if any
     * @param int|string $inputBPCRuns the number of input runs on the BPC, 'max' for maximum runs.
     * @param boolean $recursive defines if manufacturables should be build recursively
     * @return InventionProcessData 
     * @throws NotInventableException if the specified blueprint can't be invented from this
     * @throws InvalidParameterValueException if inputBPCRuns exceeds limit
     * @throws WrongTypeException if decryptorID isn't a decryptor
     * @throws InvalidDecryptorGroupException if a non-matching decryptor is specified
     */
    public function invent($inventedBpTypeID = null, $decryptorId = null, $inputBPCRuns = 1, $recursive = true) {
        //if no result BP ID is given, set to first available
        if (is_null($inventedBpTypeID))
            $inventedBpTypeID = $this->inventsBlueprintID[0];
        
        //check if the given BP can be invented from this
        elseif(!in_array ($inventedBpTypeID, $this->inventsBlueprintID))
            throw new NotInventableException("Specified blueprint can't be invented from this blueprint.");
        
        //convert 'max' into maximum number of runs
        if ($inputBPCRuns == 'max')
            $inputBPCRuns = $this->maxProductionLimit;
        
        //check the number of runs on BPC
        elseif ($inputBPCRuns > $this->maxProductionLimit OR $inputBPCRuns < 1)
            throw new InvalidParameterValueException('Input BPC runs exceeds limit');

        $sde = SDE::instance();
        
        //get Invention Data class
        $inventionDataClass = iveeCoreConfig::getIveeClassName('InventionProcessData');
        
        //branch with decryptor
        if ($decryptorId > 0) {
            $decryptor = $sde->getType($decryptorId);
            
            //check if decryptorID is actually a decryptor
            if (!($decryptor instanceof Decryptor))
                throw new WrongTypeException('typeID ' . $decryptorId . ' is not a Decryptor');
            
            //check if decryptor group matches blueprint
            if ($decryptor->getGroupID() != $this->decryptorGroupID)
                throw new InvalidDecryptorGroupException('Given decryptor does not match blueprint race');
            
            //instantiate invention data class with all required parameters
            $id = new $inventionDataClass(
                $inventedBpTypeID, 
                $this->calcInventionTime(), 
                $this->calcInventionChance($decryptor->getProbabilityModifier()), 
                $this->calcOutputRuns($inputBPCRuns, $decryptor->getRunModifier()),
                -4 + $decryptor->getMEModifier(),
                -4 + $decryptor->getPEModifier()
            );
            //add decryptor to the list of required materials
            $id->addMaterial($decryptorId, 1);
        } 
        //branch without decryptor
        else {
            //instantiate invention data class with all required parameters
            $id = new $inventionDataClass(
                $inventedBpTypeID, 
                $this->calcInventionTime(), 
                $this->calcInventionChance(), 
                $this->calcOutputRuns($inputBPCRuns)
            );
        }

        //iterate over invention requirements
        foreach ($this->typeRequirements[ProcessData::ACTIVITY_INVENTING] as $typeID => $mat) {
            $type = $sde->getType($typeID);
            
            //add needed skills to skill array
            if ($type->getCategoryID() == 16){
                $id->addSkill($typeID, $mat['q']);
            } 

            //handle recursive component building; don't build interfaces
            elseif ($recursive AND $type instanceof Manufacturable AND $type->getGroupID() != 716) {
                //build components
                $subPd = $type->getBlueprint()->manufacture($mat['q']);

                //add it to main inventionProcessData object
                $id->addSubProcessData($subPd);
            } 
            //add non-manufacturable materials
            else 
                $id->addMaterial($typeID, $mat['q'] * ($type->getGroupID() == 716 ? 0 : $mat['d']));
        }

        return $id;
    }
    
    /**
     * Copy, invent T2 blueprint and manufacture from it in one go
     * @param int $inventedBpTypeID the ID of the blueprint to be invented. If left null it will default to the first 
     * blueprint defined in inventsBlueprintID
     * @param int $decryptorID the decryptor the be used, if any
     * @param int|string $inputBPCRuns the number of input runs on the BPC, 'max' for maximum runs.
     * @param boolean $recursive defines if manufacturables should be build recursively
     * @return ManufactureProcessData with cascaded InventionProcessData and CopyProcessData objects 
     */
    public function copyInventManufacture($inventedBpTypeID = null, $decryptorID = null, $BPCRuns = 1, 
        $recursive = true) {
        //make one BP copy
        $copyData = $this->copy(1, $BPCRuns, $recursive);

        //run the invention
        $inventionData = $this->invent(
            $inventedBpTypeID, 
            $decryptorID, 
            $copyData->getOutputRuns(), 
            $recursive
        );

        //add copyData to invention data
        $inventionData->addSubProcessData($copyData);

        //manufacture from invented BP
        $manufactureData = $inventionData->getProducedType()->manufacture(
            $inventionData->getResultRuns(), 
            $inventionData->getResultME(), 
            $inventionData->getResultPE(), 
            $recursive
        );

        //add invention data to the manufactureProcessData object
        $manufactureData->addSubProcessData($inventionData);
        
        return $manufactureData;
    }
    
    /**
     * Returns an array with the IDs of inventable blueprints
     * @return array
     */
    public function getInventableBlueprintIDs(){
        return $this->inventsBlueprintID;
    }
    
    /**
     * Returns an array with the IDs of compatible decryptors
     * @return array
     */
    public function getDecryptorIDs() {
        return Decryptor::getIDsFromGroup($this->decryptorGroupID);
    }

    /**
     * Calculates the invention chance
     * @return float
     */
    public function calcInventionChance($decryptorMod = 1, $encryptionSkill = 4, $datacoreSkill1 = 5, 
        $datacoreSkill2 = 5, $metaLevel = 0) {
        return $this->baseChance * (1 + 0.01 * $encryptionSkill) 
            * (1 + ($datacoreSkill1 + $datacoreSkill2) * (0.1 / (5 - $metaLevel))) * $decryptorMod;
    }

    /**
     * Calculates the number of runs on the invented T2 BPC
     * @return float
     */
    public function calcOutputRuns($inputBPCRuns = 1, $runModifier = 0) {
        //get stats from invented BP. We can pick the 0th because even if multiple BP types can be invented, 
        //the stats are the same
        $T2BpcMaxProductionLimit = SDE::instance()->getType($this->inventsBlueprintID[0])->getMaxProductionLimit();
        $truns = max(
            array(
                floor(($inputBPCRuns / $this->maxProductionLimit) * ($T2BpcMaxProductionLimit / 10)),
                1
            )
        ) + $runModifier;
        return min(
            array(
                $truns,
                $T2BpcMaxProductionLimit
            )
        );
    }
    
    /**
     * Calculates the invention time
     * @return int
     */
    public function calcInventionTime($slotMod = null, $implantMod = 1){
        if(is_null($slotMod))
            $slotMod = iveeCoreConfig::getUsePosInvention() ? iveeCoreConfig::getPosInventionSlotTimeFactor() : 1;
        
        return (int)round($this->researchTechTime * $slotMod * $implantMod);
    }
}

?>