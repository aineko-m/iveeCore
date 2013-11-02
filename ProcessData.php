<?php

/**
 * ProcessData is the base class for holding information about an industrial process. This class has not been made 
 * abstract so it can be used to aggregate multiple ProcessData objects ("shopping cart" functionality).
 * 
 * Note that some methods have special-casing for InventionData objects. This is due to the design decision of making
 * "invention attempt" cases override the normal inherited methods while the "invention success" cases are defined
 * explicitly as new methods, which is less error prone.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/ProcessData.php
 * @package iveeCore
 */
class ProcessData {
    
    //activity ID constants 
    const ACTIVITY_MANUFACTURING = 1;
    const ACTIVITY_RESEARCH_PE   = 3;
    const ACTIVITY_RESEARCH_ME   = 4;
    const ACTIVITY_COPYING       = 5;
    const ACTIVITY_REVERSE_ENGINEERING = 7;
    const ACTIVITY_INVENTING     = 8;

    /**
     * @var int $activityID of this process.
     */
    protected $activity = 0;
    
    /**
     * @var int $producesTypeID the resulting item of this process.
     */
    protected $producesTypeID;
    
    /**
     * @var int $producesQuantity the resulting quantity of this process.
     */
    protected $producesQuantity;
    
    /**
     * @var int $processTime the time this process takes in seconds.
     */
    protected $processTime = 0;
    
    /**
     * @var array $skills the minimum required skill to perform this activity.
     */
    protected $skills = array();
    
    /**
     * @var array $materials required materials and quantities for this activity.
     */
    protected $materials = array();
    
    /**
     * @var array $subProcessData holds (recursive|sub) ProcessData objects.
     */
    protected $subProcessData = array();
    
    /**
     * Constructor.
     * @param int $producesTypeID typeID of the item resulting from this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @return ProcessData
     */
    public function __construct($producesTypeID = -1, $producesQuantity = 0, $processTime = 0){
        $this->producesTypeID   = (int) $producesTypeID;
        $this->producesQuantity = (int) $producesQuantity;
        $this->processTime      = (int) $processTime;
    }

    /**
     * Add required material and amount to total material array.
     * @param int $typeID of the material
     * @param int $amount of the material
     */
    public function addMaterial($typeID, $amount) {
        if (isset($this->materials[(int)$typeID])) {
            $this->materials[(int)$typeID] += $amount;
        } else {
            $this->materials[(int)$typeID] = $amount;
        }
    }
    
    /**
     * Add required skill to the total skill array
     * @param int $skillID of the skill
     * @param int $level of the skill
     */
    public function addSkill($skillID, $level) {
        if (isset($this->skills[(int)$skillID])) {
            //overwrite existing skill if $level is higher
            if ($this->skills[(int)$skillID] < $level) {
                $this->skills[(int)$skillID] = (int)$level;
            }
        } else {
            $this->skills[(int)$skillID] = (int)$level;
        }
    }
    
    /**
     * Add sub-ProcessData object.
     * This can be use to make entire build-trees or provide a "shopping-cart" for materials
     * @param ProcessData $subProcessData of the skill
     * @param int $level of the skill
     */
    public function addSubProcessData(ProcessData $subProcessData){
        $this->subProcessData[] = $subProcessData;
    }
    
    /**
     * Returns the activityID of the process
     * @return int
     */
    public function getActivity(){
        return $this->activity;
    }
    
    /**
     * Returns Type resulting from this process
     * @return Type
     * @throws Exception if process results in no new item
     */
    public function getProducedType(){
        if($this->producesTypeID < 0){
            throw new Exception("This process results in no new item");
        } else {
            return SDE::instance()->getType($this->producesTypeID);
        }
    }
    
    /**
     * Returns number of items resulting from this process
     * @return int
     */
    public function getNumProducedUnits(){
        return $this->producesQuantity;
    }

    /**
     * Returns all sub process data objects, if any
     * @return array with ProcessData objects
     */
    public function getSubProcesses(){
        return $this->subProcessData;
    }
    
    /**
     * Returns slot cost, WITHOUT subprocesses
     * @return float
     */
    public function getSlotCost(){
        return 0.0;
    }
    
    /**
     * Returns slot cost, inculding subprocesses
     * @return float
     */
    public function getTotalSlotCost(){
        $sum = $this->getSlotCost();
        foreach ($this->subProcessData as $subProcessData){
            if($subProcessData instanceof InventionData){
                $sum += $subProcessData->getTotalSuccessSlotCost();
            } else {
                $sum += $subProcessData->getTotalSlotCost();
            }
        }
        return $sum;
    }
    
    /**
     * Returns material cost, WITHOUT subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws Exception if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialCost($maxPriceDataAge = null){
        $sde = SDE::instance();
        $sum = 0;
        foreach ($this->materials as $typeID => $amount) {
            $type = $sde->getType($typeID);
            if (!($type instanceof Sellable)) continue;
            if($amount > 0){
                $sum += $type->getBuyPrice($maxPriceDataAge) * $amount * iveeCoreConfig::getDefaultBuyTaxFactor();
            }
        }
        return $sum;
    }
    
    /**
     * Returns material cost, including subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws Exception if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalMaterialCost($maxPriceDataAge = null){
        $sum = $this->getMaterialCost($maxPriceDataAge);
        foreach ($this->subProcessData as $subProcessData){
            if($subProcessData instanceof InventionData){
                $sum += $subProcessData->getTotalSuccessMaterialCost($maxPriceDataAge);
            } else {
                $sum += $subProcessData->getTotalMaterialCost($maxPriceDataAge);
            }
        }
        return $sum;
    }
    
    /**
     * Returns total cost, including subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws Exception if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost($maxPriceDataAge = null){
        return $this->getTotalSlotCost() + $this->getTotalMaterialCost($maxPriceDataAge);
    }
    
    /**
     * Returns required materials for this process, WITHOUT sub-processes
     * @return array
     */
    public function getMaterials(){
        return $this->materials;
    }
    
    /**
     * Returns all required materials, including sub-processes.
     * Note that material quantities might be fractionary, due to invention chance effects, requesting builds of items
     * in numbers that are not multiple of portionSize or due to materials that take damage instead of being consumed.
     * @return array
     */
    public function getTotalMaterials(){
        $sum = $this->getMaterials();
        foreach ($this->subProcessData as $subProcessData){
            if($subProcessData instanceof InventionData){
                foreach ($subProcessData->getTotalSuccessMaterials() as $typeID => $quantity){
                    if(isset($sum[$typeID])){
                        $sum[$typeID] += $quantity;
                    } else {
                        $sum[$typeID] = $quantity;
                    }
                }
            } else {
                foreach ($subProcessData->getTotalMaterials() as $typeID => $quantity){
                    if(isset($sum[$typeID])){
                        $sum[$typeID] += $quantity;
                    } else {
                        $sum[$typeID] = $quantity;
                    }
                }
            }
        }
        return $sum;
    }
    
    /**
     * Returns skills required for this process, WITHOUT sub-processes
     * @return array
     */
    public function getSkills(){
        return $this->skills;
    }
    
    /**
     * Returns all skills required, including sub-processes
     * @return array
     */
    public function getTotalSkills(){
        $sum = $this->getSkills();
        foreach ($this->subProcessData as $subProcessData){
            foreach ($subProcessData->getTotalSkills() as $skillID => $level){
                if (isset($sum[$skillID])) {
                    if ($sum[$skillID] < $level) {
                        $sum[$skillID] = $level;
                    }
                } else {
                    $sum[$skillID] = $level;
                }
            }
        }
        return $sum;
    }
    
    /**
     * Returns the time for this process, in seconds, WITHOUT sub-processes
     * @return int
     */
    public function getTime(){
        return $this->processTime;
    }
    
    /**
     * Returns sum of all times, in seconds, including sub-processes
     * @return int|float
     */
    public function getTotalTime(){
        $sum = $this->getTime();
        foreach ($this->subProcessData as $subProcessData){
            if($subProcessData instanceof InventionData){
                $sum += $subProcessData->getTotalSuccessTime();
            } else {
                $sum += $subProcessData->getTotalTime();
            }
        }
        return $sum;
    }
    
    /**
     * Returns array with process times summed by activity, in seconds, including sub-processes
     * @return array
     */
    public function getTotalTimes(){
        $sum = array(
            self::ACTIVITY_MANUFACTURING => 0, 
            self::ACTIVITY_COPYING => 0, 
            self::ACTIVITY_INVENTING => 0
        );
        
        if($this->processTime > 0) $sum[$this->activity] = $this->processTime;
        
        foreach ($this->subProcessData as $subProcessData){
            if($subProcessData instanceof InventionData){
                foreach ($subProcessData->getTotalSuccessTimes() as $activity => $time){
                    $sum[$activity] += $time;
                }
            } else {
                foreach ($subProcessData->getTotalTimes() as $activity => $time){
                    $sum[$activity] += $time;
                }
            }
        }
        return $sum;
    }
    
    /**
     * Returns total profit for this batch (this object and direkt ManufactureData sub-processes)
     * @param int $maxPriceDataAge maximum acceptable price data age
     * @return array
     */
    public function getTotalProfit($maxPriceDataAge = null) {
        $sum = 0;
        foreach ($this->subProcessData as $spd){
            if($spd instanceof ManufactureData){
                $sum += $spd->getTotalProfit($maxPriceDataAge);
            }
        }
        return $sum;
    }
    
    /**
     * Prints data about this process
     */
    public function printData(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        echo "Total Slot Time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterials() as $typeID => $amount){
            echo $amount .'x '.SDE::instance()->getType($typeID)->getName().PHP_EOL;
        }
        echo "Material Cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialCost(iveeCoreConfig::getMaxPriceDataAge())) . "ISK" . PHP_EOL;
        echo "Slot Cost: "     . $utilClass::quantitiesToReadable($this->getTotalSlotCost()) . "ISK" . PHP_EOL;
        echo "Total Cost: "    . $utilClass::quantitiesToReadable($this->getTotalCost(iveeCoreConfig::getMaxPriceDataAge())) . "ISK" . PHP_EOL;
        echo "Total Profit: ";
        try{
            echo $utilClass::quantitiesToReadable($this->getTotalProfit(iveeCoreConfig::getMaxPriceDataAge())) . "ISK" . PHP_EOL;
        } catch (Exception $e){
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

?>