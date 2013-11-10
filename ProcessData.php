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
    const ACTIVITY_MANUFACTURING = 1; //also used for reprocessing
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
     * @var SkillSet $skills an object defining the minimum required skills to perform this activity.
     */
    protected $skills;
    
    /**
     * @var MaterialSet $materials object
     */
    protected $materials;
    
    /**
     * @var array $subProcessData holds (recursive|sub) ProcessData objects.
     */
    protected $subProcessData;
    
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
        if(!isset($this->materials)){
            $materialClass = iveeCoreConfig::getIveeClassName('materials');
            $this->materials = new $materialClass;
        }
        $this->getMaterialSet()->addMaterial($typeID, $amount);
    }
    
    /**
     * Add required skill to the total skill array
     * @param int $skillID of the skill
     * @param int $level of the skill
     * @throws Exception if the skill level is not a valid integer between 0 and 5
     */
    public function addSkill($skillID, $level) {
        if(!isset($this->skills)){
            $skillClass = iveeCoreConfig::getIveeClassName('skills');
            $this->skills = new $skillClass;
        }
        $this->getSkillSet()->addSkill($skillID, $level);
    }
    
    /**
     * Add sub-ProcessData object.
     * This can be use to make entire build-trees or build batches
     * @param ProcessData $subProcessData of the skill
     * @param int $level of the skill
     */
    public function addSubProcessData(ProcessData $subProcessData){
        if(!isset($this->subProcessData)) $this->subProcessData = array();
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
     * @throws NoOutputItemException if process results in no new item
     */
    public function getProducedType(){
        if($this->producesTypeID < 0)
            throw new NoOutputItemException("This process results in no new item");
        else
            return SDE::instance()->getType($this->producesTypeID);
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
        if(!isset($this->subProcessData)) return array();
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
        foreach ($this->getSubProcesses() as $subProcessData){
            if($subProcessData instanceof InventionData)
                $sum += $subProcessData->getTotalSuccessSlotCost();
            else
                $sum += $subProcessData->getTotalSlotCost();
        }
        return $sum;
    }
    
    /**
     * Returns material buy cost, WITHOUT subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost($maxPriceDataAge = null){
        if(!isset($this->materials)) return 0;
        return $this->getMaterialSet()->getMaterialBuyCost($maxPriceDataAge);
    }
    
    /**
     * Returns material buy cost, including subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalMaterialBuyCost($maxPriceDataAge = null){
        $sum = $this->getMaterialBuyCost($maxPriceDataAge);
        foreach ($this->getSubProcesses() as $subProcessData){
            if($subProcessData instanceof InventionData)
                $sum += $subProcessData->getTotalSuccessMaterialBuyCost($maxPriceDataAge);
            else
                $sum += $subProcessData->getTotalMaterialBuyCost($maxPriceDataAge);
        }
        return $sum;
    }
    
    /**
     * Returns total cost, including subprocesses
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost($maxPriceDataAge = null){
        return $this->getTotalSlotCost() + $this->getTotalMaterialBuyCost($maxPriceDataAge);
    }
    
    /**
     * Returns required materials object for this process, WITHOUT sub-processes
     * @return MaterialSet
     */
    public function getMaterialSet(){
        return $this->materials;
    }
    
    /**
     * Returns a new MaterialSet object containing all required materials, including sub-processes.
     * Note that material quantities might be fractionary, due to invention chance effects, requesting builds of items
     * in numbers that are not multiple of portionSize or due to materials that take damage instead of being consumed.
     * @return MaterialSet
     */
    public function getTotalMaterialSet(){
        $materialsClass = iveeCoreConfig::getIveeClassName('materials');
        $tmat = new $materialsClass;
        if(isset($this->materials)) $tmat->addMaterialSet($this->getMaterialSet());
        foreach ($this->getSubProcesses() as $subProcessData){
            if($subProcessData instanceof InventionData)
                $tmat->addMaterialSet($subProcessData->getTotalSuccessMaterialSet());
            else
                $tmat->addMaterialSet($subProcessData->getTotalMaterialSet());
        }
        return $tmat;
    }
    
    /**
     * Returns the volume of the process materials, WITHOUT sub-processes.
     * @return float the volume
     */
    public function getMaterialVolume(){
        if(!isset($this->materials)) return 0;
        return $this->getMaterialSet()->getMaterialVolume();
    }
    
    /**
     * Returns the volume of the process materials, including sub-processes.
     * @return float the volume
     */
    public function getTotalMaterialVolume(){
        $sum = $this->getMaterialVolume();
        foreach ($this->getSubProcesses() as $subProcessData){
            if($subProcessData instanceof InventionData)
                $sum += $subProcessData->getTotalSuccessMaterialVolume();
            else
                $sum += $subProcessData->getTotalMaterialVolume();
        }
        return $sum;
    }
    
    /**
     * Returns object defining the skills required for this process, WITHOUT sub-processes
     * @return SkillSet
     */
    public function getSkillSet(){
        return $this->skills;
    }
    
    /**
     * Returns a new object with all skills required, including sub-processes
     * @return SkillSet
     */
    public function getTotalSkillSet(){
        $skillClass = iveeCoreConfig::getIveeClassName('skills');
        $tskills =  new $skillClass;
        if(isset($this->skills)) $tskills->addSkillSet($this->getSkillSet());
        foreach ($this->subProcessData as $subProcessData){
            $tskills->addSkillSet($subProcessData->getTotalSkillSet());
        }
        return $tskills;
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
        foreach ($this->getSubProcesses() as $subProcessData){
            if($subProcessData instanceof InventionData)
                $sum += $subProcessData->getTotalSuccessTime();
            else
                $sum += $subProcessData->getTotalTime();
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
        
        foreach ($this->getSubProcesses() as $subProcessData){
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
     * Returns total profit for this batch (direkt child ManufactureData sub-processes)
     * @param int $maxPriceDataAge maximum acceptable price data age
     * @return array
     * @throws PriceDataTooOldException if a maxPriceDataAge has been specified and the data is too old
     */
    public function getTotalProfit($maxPriceDataAge = null) {
        $sum = 0;
        foreach ($this->getSubProcesses() as $spd){
            if($spd instanceof ManufactureData)
                $sum += $spd->getTotalProfit($maxPriceDataAge);
        }
        return $sum;
    }
    
    /**
     * Prints data about this process
     */
    public function printData(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        echo "Total slot time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterialSet()->getMaterials() as $typeID => $amount){
            echo $amount . 'x ' . SDE::instance()->getType($typeID)->getName() . PHP_EOL;
        }
        echo "Material cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost()) . "ISK" . PHP_EOL;
        echo "Slot cost: "     . $utilClass::quantitiesToReadable($this->getTotalSlotCost()) . "ISK" . PHP_EOL;
        echo "Total cost: "    . $utilClass::quantitiesToReadable($this->getTotalCost()) . "ISK" . PHP_EOL;
        echo "Total profit: "  . $utilClass::quantitiesToReadable($this->getTotalProfit()) . "ISK" . PHP_EOL;
    }
}

?>