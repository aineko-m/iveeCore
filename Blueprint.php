<?php

/**
 * Class for all blueprints. 
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
     * @var int $researchCopyTime base copy time = the time in seconds to make maxProductionLimit/2 single run copies.
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
     * @var array $requirements holds activity materials and requirements
     */
    protected $requirements = array();
    
    /**
     * Constructor.
     * Use SDE->getType() to instantiate Blueprint objects.
     * @param int $typeID of the Type object
     * @return Blueprint
     * @throws Exception if typeID is not found
     */
    protected function __construct($typeID) {
        //call parent constructor
        parent::__construct($typeID);
        
        $sde = SDE::instance();
        
        //query requirements for all BP activities by calling stored procedure.
        $res = $sde->query("CALL iveeGetRequirements(" . (int)$this->typeID . ");");

        while ($row = $res->fetch_assoc()) {
            $this->requirements[(int) $row['activityID']][] = array(
                'cat' => (int) $row['categoryID'],
                'typ' => (int) $row['requiredTypeID'],
                'qua' => (int) $row['quantity'],
                'dam' => (float) $row['damagePerJob'],
                'bas' => (int) $row['baseMaterial']
            );
        }
        $res->free();
        
        //get rid of other result sets
        $sde->flushDbResults();
    }

    /**
     * Gets all necessary data from SQL
     * @return array
     * @throws Exception when a typeID is not found
     */
    protected function queryAttributes() {
        $row = SDE::instance()->query(
            "SELECT 
            it.groupID,
            ig.categoryID, 
            it.typeName, 
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
                ah.vol, 
                ah.tx,
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
                WHERE iveeTrackedPrices.typeID = " . (int)$this->typeID . "
                AND iveeTrackedPrices.regionID = " . (int)iveeCoreConfig::getDefaultRegionID() . "
            ) AS atp ON atp.typeID = it.typeID
            WHERE it.published = 1 
            AND it.typeID = " . (int)$this->typeID . ";"
        )->fetch_assoc();
        
        if (empty($row))
            throw new Exception("typeID not found");
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
     * @return float the buy price for default region as calculated in emdr.php, or basePrice if the BP cannot be sold 
     * on the market
     * @throws Exception if maxPriceDataAge is set and the price data is too old
     */
    public function getBuyPrice($maxPriceDataAge = null) {
        //some BPs cannot be sold on the market
        if(empty($this->marketGroupID)){
            return $this->basePrice;
        } else {
            return parent::getBuyPrice($maxPriceDataAge);
        }
    }

    /**
     * Gets the sell price for this BP
     * @param int $maxPriceDataAge the maximum price data age in seconds
     * @return float the sell price for default region as calculated in emdr.php, or basePrice if the BP cannot be sold 
     * on the market
     * @throws Exception if maxPriceDataAge is set and the price data is too old
     */
    public function getSellPrice($maxPriceDataAge = null) {
        //some BPs cannot be sold on the market
        if(empty($this->marketGroupID)){
            return $this->basePrice;
        } else {
            return parent::getSellPrice($maxPriceDataAge);
        }
    }

    /**
     * Manufacture using this BP
     * @param int $units the number of items to produce; defaults to 1.
     * @param int $ME level of the BP; if left null, it is looked up in SDEUtil::getBpMeLevel()
     * @param int $PE level of the BP; if left null, it is looked up in SDEUtil::getBpPeLevel()
     * @param boolean $recursive defines if components should be manufactured recursively
     * @return ManufactureData describing the manufacturing process
     * @throws Exception if there is no manufacturing requirements data for this BP
     */
    public function manufacture($units = 1, $ME = null, $PE = null, $recursive = true) {
        if (!isset($this->requirements[ProcessData::ACTIVITY_MANUFACTURING]))
            throw new Exception('No manufacturing requirements data for this blueprint available');
        
        //Lookup default ME and PE levels for this BP if not set
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        if(is_null($ME)) $ME = $utilClass::getBpMeLevel($this->typeID);
        if(is_null($PE)) $PE = $utilClass::getBpPeLevel($this->typeID);

        //get waste factor
        $materialFactor = $this->calcMaterialFactor($ME);

        //Some items are manufactured in atomic batches (charges, for instance). 
        //We need to normalize quantities and times to the equivalent needed for 1 unit * requested units.
        $runFactor = $units / $this->getProduct()->getPortionSize();

        //get Manufacture Data class name
        $manufactureDataClass = iveeCoreConfig::getIveeClassName('manufacturedata');

        //instantiate manu data object
        $md = new $manufactureDataClass(
            $this->productTypeID, 
            $units, 
            $runFactor * $this->calcProductionTime($PE),
            $ME,
            $PE
        );

        $sde = SDE::instance();

        //iterate over manufacturing requirements
        foreach ($this->requirements[ProcessData::ACTIVITY_MANUFACTURING] as $mat) {
            //add needed skills to skill array
            if ($mat['cat'] == 16) {
                $md->addSkill($mat['typ'], $mat['qua']);
                continue;
            }

            //only apply extra material waste factor if that material is also a base material
            if($mat['qua'] > $mat['bas'] AND $mat['bas'] > 0){
                $extraMaterialFactor = 1 + (0.25 - 0.05 * $utilClass::getSkillLevel(3388));
            } else {
                $extraMaterialFactor = 1;
            }
            
            //Quantity needed for 1 batch (= portionSize). Apply regular waste factor to base materials. 
            //Apple extra material waste to extra materials. Also apply damage factor to all.
            $matQuantity = (round($mat['bas'] * $materialFactor) 
                + round(($mat['qua'] - $mat['bas']) * $extraMaterialFactor)) * $mat['dam'];

            //handle recursive component building
            if ($recursive) {
                $type = $sde->getType($mat['typ']);
                if ($type instanceof Manufacturable) {
                    //build components
                    $subMd = $type->getBlueprint()->manufacture($matQuantity * $runFactor);
                    //add it to main processData object
                    $md->addSubProcessData($subMd);
                    continue;
                }
            }
            //non-recursive building or not manufacturable
            //add materials applying run factor
            $md->addMaterial($mat['typ'], $runFactor * $matQuantity);
        }
        return $md;
    }

    /**
     * Make copy using this BP
     * @param int $copies the number of copies to produce; defaults to 1.
     * @param int|string $runs the number of runs on each copy. Use 'max' for the maximum possible number of runs.
     * @param boolean $recursive
     * @param bool $recursive defines if used materials should be manufactured recursively
     * @return CopyData describing the copy process
     */
    public function copy($copies = 1, $runs = 'max', $recursive = true) {
        //convert 'max' into max number of runs
        if ($runs == 'max') 
            $runs = $this->maxProductionLimit;
        $totalRuns = $copies * $runs;
        
        //get copy data class
        $copyDataClass = iveeCoreConfig::getIveeClassName('copydata');

        //instantiate copy data class with required parameters
        $cd = new $copyDataClass(
            $this->typeID, 
            $copies, 
            $this->calcCopyTime() * $totalRuns, 
            $runs
        );

        $sde = SDE::instance();

        //iterate over copying requirements, if any
        if (isset($this->requirements[ProcessData::ACTIVITY_COPYING])) {
            foreach ($this->requirements[ProcessData::ACTIVITY_COPYING] as $mat) {
                //add needed skills to skill array
                if ($mat['cat'] == 16) {
                    $cd->addSkill($mat['typ'], $mat['qua']);
                    continue;
                }

                //quantity needed for one single run copy
                $matQuantity = $mat['qua'] * $mat['dam'];

                //handle recursive component building
                if ($recursive) {
                    $type = $sde->getType($mat['typ']);
                    if ($type instanceof Manufacturable) {
                        //build required manufacturables
                        $subMd = $type->getBlueprint()->manufacture($totalRuns * $matQuantity);
                        //add it to main processData object
                        $cd->addSubProcessData($subMd);
                        continue;
                    }
                }
                //non-recursive building or not manufacturable
                //add materials applying run factor
                $cd->addMaterial($mat['typ'], $totalRuns * $matQuantity);
            }
        }
        return $cd;
    }
    
    /**
     * Returns raw requirements
     * @param int $activityID optional parameter that specifies for which activity the requirements should be returned.
     * @return array with the requirements
     * @throws Exception if the given activityID is not found in the requirements array
     */
    public function getRequirements($activityID = null){
        if(is_null($activityID)){
            return $this->requirements;
        } else {
            if(isset($this->requirements[$activityID])){
                return $this->requirements[$activityID];
            } else {
                throw new Exception("ActivityID not found.");
            }
        }
    }

    /**
     * Returns an object representing the item produced by this Blueprint.
     * @return Manufacturable 
     * @throws Exception if the productTypeID can't be found. This should never happen.
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
        return $this->researchProductivityModifier;
    }
    
    /**
     * Returns the material modifier
     * @return int
     */
    public function getMaterialModifier(){
        return $this->researchMaterialModifier;
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
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        if(is_null($ME)) $ME = $utilClass::getBpMeLevel($this->typeID);
        if(is_null($productionEfficiencySkill)) $productionEfficiencySkill = $utilClass::getSkillLevel(3388);
        
        if ($ME < 0) {
            $meMod = 1 - $ME;
        } else {
            $meMod = 1 / (1 + $ME);
        }
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
     */
    public function calcProductionTime($PE = null, $industrySkill = null, $slotMod = null, $implantMod = 1) {
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        if(is_null($PE)) $PE = $utilClass::getBpPeLevel($this->typeID);
        if(is_null($industrySkill)) $industrySkill = $utilClass::getSkillLevel(3380);        
        if(is_null($slotMod))
            $slotMod = iveeCoreConfig::getUsePosManufacturing() ? iveeCoreConfig::getPosManufactureSlotTimeFactor() : 1;
        
        if ($PE < 0) {
            $peMod = $PE - 1;
        } else {
            $peMod = $PE / (1 + $PE);
        }
        return (int) round((1 - (0.04) * $industrySkill) * $implantMod * $slotMod * $this->productionTime 
                * (1 - ($this->productivityModifier / $this->productionTime) * $peMod));
    }

    /**
     * Calculates the copying time for a single run copy
     * @param int $scienceSkill the level of the influencing skill. If left null, a default level is looked up in 
     * SDEUtil
     * @param float $slotMod defines the slot dependant time modifier. If left null, a default value is looked up in 
     * SDEUtil 
     * @param float $implantMod defines implant dependant time modifiers. Optional.
     * @return int the time in seconds
     */
    public function calcCopyTime($scienceSkill = null, $slotMod = null, $implantMod = 1) {
        if(is_null($scienceSkill)){
            $utilClass = iveeCoreConfig::getIveeClassName('util');
            $scienceSkill = $utilClass::getSkillLevel(3402);
        }
        if(is_null($slotMod)){
            $slotMod = iveeCoreConfig::getUsePosCopying() ? iveeCoreConfig::getPosCopySlotTimeFactor() : 1;
        }
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
     */
    public function calcPEResearchTime($researchSkill = null, $slotMod = null, $implantMod = 1){
        if(is_null($researchSkill)){
            $utilClass = iveeCoreConfig::getIveeClassName('util');
            $researchSkill = $utilClass::getSkillLevel(3403);
        }
        if(is_null($slotMod)){
            $slotMod = iveeCoreConfig::getUsePosPeResearch() ? iveeCoreConfig::getPosPeResearchSlotTimeFactor() : 1;
        }
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
     */
    public function calcMEResearchTime($metallurgySkill = null, $slotMod = null, $implantMod = 1){
        if(is_null($metallurgySkill)){
            $utilClass = iveeCoreConfig::getIveeClassName('util');
            $metallurgySkill = $utilClass::getSkillLevel(3409);
        }
        if(is_null($slotMod)){
            $slotMod = iveeCoreConfig::getUsePosMeResearch() ? iveeCoreConfig::getPosMeResearchSlotTimeFactor() : 1;
        }
        return (int)round($this->researchMaterialTime * (1 - 0.05 * $metallurgySkill) * $slotMod * $implantMod);
    }
}

?>