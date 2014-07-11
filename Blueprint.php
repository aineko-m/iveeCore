<?php

/**
 * Blueprint base class. 
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Blueprint -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Blueprint.php
 * @package iveeCore
 */
class Blueprint extends Sellable {

    /**
     * @var int $productTypeID typeID of produced item.
     */
    protected $productTypeID;
    
    /**
     * @var int $productionTime base production time in seconds.
     */
    protected $productionTime;
    
    /**
     * @var int $techLevel tech level of blueprint/produced item.
     */
    protected $techLevel;
    
    /**
     * @var int $researchProductivityTime base PE research time in seconds per level.
     */
    protected $researchProductivityTime;
    
    /**
     * @var int $researchMaterialTime base ME research time in seconds per level.
     */
    protected $researchMaterialTime;
    
    /**
     * @var int $researchCopyTime base copy time = the seconds to make maxProductionLimit/2 single run copies.
     */
    protected $researchCopyTime;
    
    /**
     * @var int $researchTechTime base invention time in seconds.
     */
    protected $researchTechTime;
    
    /**
     * @var int $productivityModifier. Affects how PE levels alter production time.
     */
    protected $productivityModifier;
    
    /**
     * @var int $materialModifier. Affects how ME levels alter production waste.
     */
    protected $materialModifier;
    
    /**
     * @var int $wasteFactor defines the base waste in %.
     */
    protected $wasteFactor;
    
    /**
     * @var int $maxProductionLimit defines the maximum production batch size.
     */
    protected $maxProductionLimit;
    
    /**
     * @var array $typeRequirements holds data from ramTypeRequirements, which covers activity materials and 
     * requirements.
     * $typeRequirements[$activityID][$typeID]['q'|'d'|'r'] for quantity, damage and recycle flag, respectively.
     * Array entries for d and r are omitted if the value is 0.
     */   
    protected $typeRequirements;
    
    /**
     * Constructor.
     * Use SDE->getType() to instantiate Blueprint objects.
     * @param int $typeID of the Type object
     * @return Blueprint
     * @throws Exception if typeID is not found
     */
    protected function __construct($typeID) {
        $this->typeID = (int) $typeID;

        //get data from SQL
        $row = $this->queryAttributes();
        
        //set data to object attributes
        $this->setAttributes($row);
        
        $sde = SDE::instance();
        
        //get typeRequirements, if any
        $res = $sde->query(
            '(SELECT activityID, requiredTypeID, quantity, damagePerJob, recycle # extra materials and skills
                FROM ramTypeRequirements
                WHERE typeID = ' . (int) $this->typeID . ')
            UNION
            (SELECT DISTINCT # invention skills
                8 activityID, 
                COALESCE(dta.valueInt, dta.valueFloat) requiredTypeID,
                sl.level quantity, 
                0 damagePerJob, 
                0 recycle
            FROM ramTypeRequirements r, invBlueprintTypes bt, dgmTypeAttributes dta
            LEFT JOIN (
                select r.requiredTypeID, COALESCE(dta.valueInt, dta.valueFloat) level, bt.blueprintTypeID
                FROM ramTypeRequirements r, invBlueprintTypes bt, dgmTypeAttributes dta
                WHERE r.typeID = bt.blueprintTypeID
                AND r.activityID = 8
                AND dta.typeID = r.requiredTypeID
                AND dta.attributeID = 277) as sl
            ON sl.requiredTypeID = dta.typeID
            WHERE r.typeID = bt.blueprintTypeID
            AND bt.blueprintTypeID = sl.blueprintTypeID
            AND bt.blueprintTypeID = ' . (int) $this->typeID . '
            AND r.activityID = 8
            AND dta.typeID = r.requiredTypeID
            AND dta.attributeID = 182);'
        );
        if($res->num_rows > 0){
            $this->typeRequirements = array();
            //add materials to the array
            //to reduce memory usage, damage and recycle flag are only stored if > 0
            while ($row = $res->fetch_assoc()){
                $this->typeRequirements[(int) $row['activityID']][(int) $row['requiredTypeID']]['q'] 
                    = (int) $row['quantity'];
                if($row['damagePerJob'] > 0)
                    $this->typeRequirements[(int) $row['activityID']][(int) $row['requiredTypeID']]['d'] 
                        = $row['damagePerJob'];
                if($row['recycle'] > 0){
                    $this->typeRequirements[(int) $row['activityID']][(int) $row['requiredTypeID']]['r'] 
                        = $row['recycle'];
                }
            }
        }
    }

    /**
     * Gets all necessary data from SQL
     * @return array
     * @throws TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes() {
        $sde = SDE::instance();
        $row = $sde->query(
            "SELECT 
            it.groupID,
            ig.categoryID, 
            it.typeName, 
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID, 
            ibt.*, 
            histDate, 
            priceDate, 
            vol, 
            sell, 
            buy,
            tx,
            low,
            high,
            avg,
            supplyIn5,
            demandIn5,
            avgSell5OrderAge,
            avgBuy5OrderAge
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            LEFT JOIN invBlueprintTypes as ibt ON ibt.blueprintTypeID = it.typeID
            LEFT JOIN (
                SELECT 
                iveeTrackedPrices.typeID, 
                UNIX_TIMESTAMP(lastHistUpdate) AS histDate, 
                UNIX_TIMESTAMP(lastPriceUpdate) AS priceDate,  
                iveeTrackedPrices.avgVol AS vol, 
                iveeTrackedPrices.avgTx AS tx,
                ah.low,
                ah.high,
                ah.avg,
                ap.sell, 
                ap.buy,
                ap.supplyIn5,
                ap.demandIn5,
                ap.avgSell5OrderAge,
                ap.avgBuy5OrderAge
                FROM iveeTrackedPrices 
                LEFT JOIN iveePrices AS ah ON iveeTrackedPrices.newestHistData = ah.id
                LEFT JOIN iveePrices AS ap ON iveeTrackedPrices.newestPriceData = ap.id
                WHERE iveeTrackedPrices.typeID = " . (int) $this->typeID . "
                AND iveeTrackedPrices.regionID = " . (int) $sde->defaults->getDefaultRegionID() . "
            ) AS atp ON atp.typeID = it.typeID
            WHERE it.published = 1 
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();
        
        if (empty($row))
            throw new TypeIdNotFoundException("typeID " . (int) $this->typeID ." not found");
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * @param array $row data from DB
     */
    protected function setAttributes($row) {
        parent::setAttributes($row);
        if (isset($row['productTypeID']))
            $this->productTypeID = (int) $row['productTypeID'];
        if (isset($row['productionTime']))
            $this->productionTime = (int) $row['productionTime'];
        if (isset($row['techLevel']))
            $this->techLevel = (int) $row['techLevel'];
        if (isset($row['researchProductivityTime']))
            $this->researchProductivityTime = (int) $row['researchProductivityTime'];
        if (isset($row['researchMaterialTime']))
            $this->researchMaterialTime = (int) $row['researchMaterialTime'];
        if (isset($row['researchCopyTime']))
            $this->researchCopyTime = (int) $row['researchCopyTime'];
        if (isset($row['researchTechTime']))
            $this->researchTechTime = (int) $row['researchTechTime'];
        if (isset($row['productivityModifier']))
            $this->productivityModifier = (int) $row['productivityModifier'];
        if (isset($row['materialModifier']))
            $this->materialModifier = (int) $row['materialModifier'];
        if (isset($row['wasteFactor']))
            $this->wasteFactor = (int) $row['wasteFactor'];
        if (isset($row['maxProductionLimit']))
            $this->maxProductionLimit = (int) $row['maxProductionLimit'];
    }

    /**
     * Gets the buy price for this BP
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * @return float the buy price for default region as calculated in emdr.php, or basePrice if the BP cannot be 
     * sold on the market
     * @throws NoPriceDataAvailableException if no buy price available
     * @throws PriceDataTooOldException if a maxPriceDataAge has been specified and the data is too old
     */
    public function getBuyPrice($maxPriceDataAge = null) {
        //some BPs cannot be sold on the market
        if(empty($this->marketGroupID))
            return $this->basePrice;
        else
            return parent::getBuyPrice($maxPriceDataAge);
    }

    /**
     * Gets the sell price for this BP
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * @return float the sell price for default region as calculated in emdr.php, or basePrice if the BP cannot be 
     * sold on the market
     * @throws NoPriceDataAvailableException if no buy price available
     * @throws PriceDataTooOldException if a maxPriceDataAge has been specified and the data is too old
     */
    public function getSellPrice($maxPriceDataAge = null) {
        //some BPs cannot be sold on the market
        if(empty($this->marketGroupID))
            return $this->basePrice;
        else
            return parent::getSellPrice($maxPriceDataAge);
    }

    /**
     * Manufacture using this BP
     * @param int $units the number of items to produce; defaults to 1.
     * @param int $ME level of the BP; if left null, it is looked up in defaults class
     * @param int $PE level of the BP; if left null, it is looked up in defaults class
     * @param boolean $recursive defines if components should be manufactured recursively
     * @param int $productionEfficiencySkill the level of the influencing skill. If left null, a default level is 
     * looked up in SDEUtil
     * @return ManufactureProcessData describing the manufacturing process
     * @throws NoManufacturingRequirementsException if there is no manufacturing requirements data for this BP
     */
    public function manufacture($units = 1, $ME = null, $PE = null, $recursive = true, 
        $productionEfficiencySkill = null) {
        
        //get product
        $product = $this->getProduct();
        //get raw materials from product
        $raw = $product->getTypeMaterials();
        
        if (!isset($this->typeRequirements[ProcessData::ACTIVITY_MANUFACTURING]) AND count($raw) < 1)
            throw new NoManufacturingRequirementsException(
                'No manufacturing requirements data for this blueprint available');
        
        //Lookup default ME and PE levels for this BP if not set
        $defaults = SDE::instance()->defaults;
        if(is_null($ME)) $ME = $defaults->getBpMeLevel($this->typeID);
        if(is_null($PE)) $PE = $defaults->getBpPeLevel($this->typeID);
        if(is_null($productionEfficiencySkill)) $productionEfficiencySkill = $defaults->getSkillLevel(3388);

        //get waste factor
        $materialFactor = $this->calcMaterialFactor($ME, $productionEfficiencySkill);

        //Some items are manufactured in atomic batches (charges, for instance). 
        //We need to normalize quantities and times to the equivalent needed for 1 unit * requested units.
        $runFactor = $units / $product->getPortionSize();

        //get Manufacture Data class name
        $manufactureDataClass = iveeCoreConfig::getIveeClassName('ManufactureProcessData');

        //instantiate manu data object
        $md = new $manufactureDataClass(
            $this->productTypeID, 
            $units, 
            $runFactor * $this->calcProductionTime($PE),
            $ME,
            $PE
        );

        $sde = SDE::instance();
        
        if(isset($this->typeRequirements[ProcessData::ACTIVITY_MANUFACTURING])){
            // iterate over extra materials to handle materials with recycle flag
            foreach ($this->typeRequirements[ProcessData::ACTIVITY_MANUFACTURING] as $typeID => $mat) {
                //skip if no recycle flag
                if(!isset($mat['r'])) continue;

                //get the materials reprocessing materials
                foreach ($sde->getType($typeID)->getTypeMaterials() as $reprocTypeID => $qua){
                    if(isset($raw[$reprocTypeID])){
                        //subtract from the raw quantities
                        $raw[$reprocTypeID] -= $qua;

                        //remove from raw requirements if eliminated
                        if($raw[$reprocTypeID] <= 0) unset($raw[$reprocTypeID]);
                    }
                }
            }
        }
        
        //add raw materials applying waste factor
        foreach ($raw as $typeID => $amount){
            $type = $sde->getType($typeID);
            $totalNeeded = round($amount * $materialFactor) * $runFactor;
            if($recursive AND $type instanceof Manufacturable){
                $md->addSubProcessData($type->getBlueprint()->manufacture($totalNeeded));
            } else {
                $md->addMaterial($typeID, $totalNeeded);
            }
        }
        
        if(isset($this->typeRequirements[ProcessData::ACTIVITY_MANUFACTURING])){
            //iterate over extra manufacturing requirements
            foreach ($this->typeRequirements[ProcessData::ACTIVITY_MANUFACTURING] as $typeID => $mat) {
                $type = $sde->getType($typeID);

                //add needed skills to skill array
                if ($type->getCategoryID() == 16) {
                    $md->addSkill($typeID, $mat['q']);
                    continue;
                }

                //apply skill dependant waste factor if the extra material also appears in the raw materials
                if(isset($raw[$typeID]) AND $raw[$typeID] > 0)
                    $extraMaterialFactor = 1 + (0.25 - 0.05 * $productionEfficiencySkill);
                else 
                    $extraMaterialFactor = 1;

                //extra material quantity needed for 1 batch (= portionSize). 
                //Apply extra material waste and damage factors.
                $totalExtra = $runFactor * round($mat['q'] * $extraMaterialFactor) * (isset($mat['d']) ? $mat['d'] : 0);

                //handle recursive component building
                if ($recursive AND $type instanceof Manufacturable) {
                    //build components
                    $subMd = $type->getBlueprint()->manufacture($totalExtra);
                    //add it to main processData object
                    $md->addSubProcessData($subMd);
                }
                //non-recursive building or not manufacturable
                //add extra materials applying run factor
                else 
                    $md->addMaterial($typeID, $totalExtra);
            }
        }
        
        return $md;
    }

    /**
     * Make copy using this BP
     * @param int $copies the number of copies to produce; defaults to 1.
     * @param int|string $runs the number of runs on each copy. Use 'max' for the maximum possible number of runs.
     * @param boolean $recursive
     * @param bool $recursive defines if used materials should be manufactured recursively
     * @return CopyProcessData describing the copy process
     */
    public function copy($copies = 1, $runs = 'max', $recursive = true) {
        //convert 'max' into max number of runs
        if ($runs == 'max') 
            $runs = $this->maxProductionLimit;
        $totalRuns = $copies * $runs;
        
        //get copy data class
        $copyDataClass = iveeCoreConfig::getIveeClassName('CopyProcessData');

        //instantiate copy data class with required parameters
        $cd = new $copyDataClass(
            $this->typeID, 
            $copies, 
            $this->calcCopyTime() * $totalRuns, 
            $runs
        );

        $sde = SDE::instance();

        //iterate over copying requirements, if any
        if (isset($this->typeRequirements[ProcessData::ACTIVITY_COPYING])) {
            foreach ($this->typeRequirements[ProcessData::ACTIVITY_COPYING] as $typeID => $mat) {
                $type = $sde->getType($typeID);

                //add needed skills to skill array
                if ($type->getCategoryID() == 16) {
                    $cd->addSkill($typeID, $mat['q']);
                    continue;
                }

                //quantity needed for one single run copy
                $matQuantity = $mat['q'] * (isset($mat['d']) ? $mat['d'] : 0);

                //handle recursive component building
                if ($recursive AND $type instanceof Manufacturable) {
                    //build required manufacturables
                    $subMd = $type->getBlueprint()->manufacture($totalRuns * $matQuantity);
                    //add it to main processData object
                    $cd->addSubProcessData($subMd);
                    continue;
                }
                //non-recursive building or not manufacturable
                //add materials applying run factor
                $cd->addMaterial($typeID, $totalRuns * $matQuantity);
            }
        }
        return $cd;
    }
    
    /**
     * Returns raw typeRequirements
     * @param int $activityID optional parameter that specifies for which activity the requirements should be
     * returned.
     * @return array with the requirements
     * @throws ActivityIdNotFoundException if the given activityID is not found in the requirements array
     */
    public function getTypeRequirements($activityID = null){
        if(is_null($activityID)){
            return $this->typeRequirements;
        } else {
            if(isset($this->typeRequirements[$activityID])){
                return $this->typeRequirements[$activityID];
            } else {
                throw new ActivityIdNotFoundException("ActivityID " . (int) $activityID . " not found.");
            }
        }
    }

    /**
     * Returns an object representing the item produced by this Blueprint.
     * @return Manufacturable 
     */
    public function getProduct() {
        return SDE::instance()->getType($this->productTypeID);
    }
    
    /**
     * Returns the base production time
     * @return int base production time in seconds 
     */
    public function getProductionTime(){
        return $this->productionTime;
    }
    
    /**
     * Returns the tech level of the Blueprint
     * @return int 
     */
    public function getTechLevel(){
        return $this->techLevel;
    }
    
    /**
     * Returns the base PE research time
     * @return int base PE research time in seconds per level
     */
    public function getResearchProductivityTime(){
        return $this->researchProductivityTime;
    }
    
    /**
     * Returns the base ME research time
     * @return int base ME research time in seconds per level
     */
    public function getResearchMaterialTime(){
        return $this->researchMaterialTime;
    }
    
    /**
     * Returns the base copy time
     * @return int base copy time = the time in seconds to make maxProductionLimit/2 single run copies.
     */
    public function getResearchCopyTime(){
        return $this->researchCopyTime;
    }
    
    /**
     * Returns the base invention time
     * @return int base invention time in seconds
     */
    public function getResearchTechTime(){
        return $this->researchTechTime;
    }
    
    /**
     * Returns the productivity modifier
     * @return int
     */
    public function getProductivityModifier(){
        return $this->productivityModifier;
    }
    
    /**
     * Returns the material modifier
     * @return int
     */
    public function getMaterialModifier(){
        return $this->materialModifier;
    }
    
    /**
     * Returns the maximum batch size
     * @return int
     */
    public function getMaxProductionLimit() {
        return $this->maxProductionLimit;
    }

    /**
     * Calculates the ME level and skill dependant waste factor
     * @param int $ME the ME level of the blueprint. If left null, a default level is looked up in SDEUtil
     * @param int $productionEfficiencySkill the level of the influencing skill. If left null, a default level is 
     * looked up in SDEUtil
     * @return float the waste factor
     */
    public function calcMaterialFactor($ME = null, $productionEfficiencySkill = null) {
        $defaults = SDE::instance()->defaults;
        if(is_null($ME)) $ME = $defaults->getBpMeLevel($this->typeID);
        if(is_null($productionEfficiencySkill))
            $productionEfficiencySkill = $defaults->getSkillLevel(3388);
        else{
            $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
            $skillMapClass::sanityCheckSkillLevel($productionEfficiencySkill);
        }

        if ($ME < 0)
            $meMod = 1 - $ME;
        else
            $meMod = 1 / (1 + $ME);

        return 1 + ($this->wasteFactor / 100) * $meMod + (0.25 - 0.05 * $productionEfficiencySkill);
    }

    /**
     * Calculates the production time for a single run (=portionSize)
     * @param int $PE the PE level of the blueprint. If left null, a default level is looked up in SDEUtil
     * @param int $industrySkill the level of the influencing skill. If left null, a default level is looked up in 
     * SDEUtil
     * @param float $slotMod defines the slot dependant time modifier. If left null, a default value is looked up in 
     * SDEUtil
     * @param float $implantMod defines implant dependant time modifiers. Optional.
     * @return int the time in seconds
     * @throws InvalidParameterValueException if invalid parameters given
     */
    public function calcProductionTime($PE = null, $industrySkill = null, $slotMod = null, $implantMod = 1) {
        $defaults = SDE::instance()->defaults;
        if(is_null($PE)) $PE = $defaults->getBpPeLevel($this->typeID);
        if(is_null($industrySkill))
            $industrySkill = $defaults->getSkillLevel(3380);        
        else{
            $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
            $skillMapClass::sanityCheckSkillLevel($industrySkill);
        }  

        if(is_null($slotMod))
            $slotMod = 
            $defaults->getUsePosManufacturing() ? $defaults->getPosManufactureSlotTimeFactor() : 1;
        if($implantMod > 1 OR $implantMod < 0.95) 
            throw new InvalidParameterValueException("Implant factor needs to be between 0.95 and 1.0");
        
        if ($PE < 0)
            $peMod = $PE - 1;
        else
            $peMod = $PE / (1 + $PE);

        return (int) round((1 - (0.04) * $industrySkill) * $implantMod * $slotMod * $this->productionTime 
                * (1 - ($this->productivityModifier / $this->productionTime) * $peMod));
    }

    /**
     * Calculates the copying time for a single run copy
     * @param int $scienceSkill the level of the influencing skill. If left null, a default level is looked up in 
     * MyIveeCoreDefaults
     * @param float $slotMod defines the slot dependant time modifier. If left null, a default value is looked up in 
     * SDEUtil 
     * @param float $implantMod defines implant dependant time modifiers. Optional.
     * @return int the time in seconds
     * @throws InvalidParameterValueException if invalid parameters given
     */
    public function calcCopyTime($scienceSkill = null, $slotMod = null, $implantMod = 1) {
        $defaults = SDE::instance()->defaults;
        if(is_null($scienceSkill))
            $scienceSkill = $defaults->getSkillLevel(3402);
        else{
            $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
            $skillMapClass::sanityCheckSkillLevel($scienceSkill);
        }

        if(is_null($slotMod))
            $slotMod = $defaults->getUsePosCopying() ? $defaults->getPosCopySlotTimeFactor() : 1;
        if($implantMod > 1 OR $implantMod < 0.95) 
            throw new InvalidParameterValueException("Implant factor needs to be between 0.95 and 1.0");
        
        return (int) round(2 * ($this->researchCopyTime / $this->maxProductionLimit) * (1 - (0.05 * $scienceSkill)) 
                * $slotMod * $implantMod);
    }
    
    /**
     * Calculates the PE research time for a single level
     * @param int $researchSkill the level of the influencing skill. If left null, a default level is looked up in 
     * SDEUtil
     * @param float $slotMod defines the slot dependant time modifier. If left null, a default value is looked up in 
     * SDEUtil 
     * @param float $implantMod defines implant dependant time modifiers. Optional.
     * @return int the time in seconds
     * @throws InvalidParameterValueException if invalid parameters given
     */
    public function calcPEResearchTime($researchSkill = null, $slotMod = null, $implantMod = 1){
        $defaults = SDE::instance()->defaults;
        if(is_null($researchSkill))  
            $researchSkill = $defaults->getSkillLevel(3403);
        else{
            $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
            $skillMapClass::sanityCheckSkillLevel($researchSkill);
        }

        if(is_null($slotMod))
            $slotMod = $defaults->getUsePosPeResearch() ? $defaults->getPosPeResearchSlotTimeFactor() : 1;
        if($implantMod > 1 OR $implantMod < 0.95) 
            throw new InvalidParameterValueException("Implant factor needs to be between 0.95 and 1.0");

        return (int)round($this->researchProductivityTime * (1 - 0.05 * $researchSkill) * $slotMod * $implantMod);
    }
    
    /**
     * Calculates the ME research time for a single level
     * @param int $metallurgySkill the level of the influencing skill. If left null, a default level is looked up in 
     * SDEUtil
     * @param float $slotMod defines the slot dependant time modifier. If left null, a default value is looked up in 
     * SDEUtil 
     * @param float $implantMod defines implant dependant time modifiers. Optional.
     * @return int the time in seconds
     * @throws InvalidParameterValueException if invalid parameters given
     */
    public function calcMEResearchTime($metallurgySkill = null, $slotMod = null, $implantMod = 1){
        $defaults = SDE::instance()->defaults;
        if(is_null($metallurgySkill))
            $metallurgySkill = $defaults->getSkillLevel(3409);
        else{
            $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
            $skillMapClass::sanityCheckSkillLevel($metallurgySkill);
        }

        if(is_null($slotMod))
            $slotMod = $defaults->getUsePosMeResearch() ? $defaults->getPosMeResearchSlotTimeFactor() : 1;
        if($implantMod > 1 OR $implantMod < 0.95) 
            throw new InvalidParameterValueException("Implant factor needs to be between 0.95 and 1.0");
        
        return (int)round($this->researchMaterialTime * (1 - 0.05 * $metallurgySkill) * $slotMod * $implantMod);
    }
    
    /**
     * @return bool if the item is reprocessable. Blueprints never are.
     */    
    public function isReprocessable(){
        return false;
    }
    
    /**
     * This method overwrites the inherited one from Type, as Blueprints are never reprocessable
     * @param int $batchSize number of items being reprocessed
     * @param float $reprocessingYield the skill and station dependant reprocessing yield
     * @param float $reprocessingTaxFactor the standing dependant reprocessing tax factor
     * @throws NotReprocessableException always
     */
    public function getReprocessingMaterialMap($batchSize, $reprocessingYield = 1, $reprocessingTaxFactor = 1){
        throw new NotReprocessableException($this->typeName . ' is not reprocessable');
    }
}

?>