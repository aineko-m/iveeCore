<?php

/**
 * Holds data about invention processes.
 * Inheritance: InventionProcessData -> ProcessData.
 * 
 * Note the design decision of making "invention attempt" cases override the normal inherited methods while the 
 * "invention success" cases are defined explicitly in new methods. This is less error error prone than the other way
 * round.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/InventionProcessData.php
 * @package iveeCore
 */
class InventionProcessData extends ProcessData {
    
    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_INVENTING;
    
    /**
     * @var float $inventionChance chance of success for invention.
     */
    protected $inventionChance;
    
    /**
     * @var int $resultRuns the number of runs on the resulting T2 BPC if invention is successful
     */
    protected $resultRuns;
    
    /**
     * @var int $resultME the ME level on the resulting T2 BPC if invention is successful
     */
    protected $resultME;
    
    /**
     * @var int $resultPE PE level on the resulting T2 BPC if invention is successful
     */
    protected $resultPE;
    
    /**
     * Constructor.
     * @param int $inventedBpTypeID typeID of the inveted blueprint
     * @param int $inventTime the invention takes in seconds
     * @param float $inventionChance chance of success for invention
     * @param int $resultRuns the number of runs on the resulting T2 BPC if invention is successful
     * @param int $resultME the ME level on the resulting T2 BPC if invention is successful
     * @param int $resultPE the PE level on the resulting T2 BPC if invention is successful
     * @return ProcessData
     */
    public function __construct($inventedBpTypeID, $inventTime, $inventionChance, $resultRuns, $resultME = -4, 
            $resultPE = -4) {
        parent::__construct($inventedBpTypeID, 1, $inventTime);
        $this->inventionChance = $inventionChance;
        $this->resultRuns = $resultRuns;
        $this->resultME = $resultME;
        $this->resultPE = $resultPE;
    }
    
    /**
     * Returns the number of runs on the resulting T2 BPC if invention is successful
     * @return int
     */
    public function getResultRuns(){
        return $this->resultRuns;
    }
    
    /**
     * Returns the ME level on the resulting T2 BPC if invention is successful
     * @return int
     */
    public function getResultME(){
        return $this->resultME;
    }
    
    /**
     * Returns the PE level on the resulting T2 BPC if invention is successful
     * @return int
     */
    public function getResultPE(){
        return $this->resultPE;
    }
    
    /**
     * Returns the chance of success for the invention
     * @return float
     */
    public function getInventionChance(){
        return $this->inventionChance;
    }

    /**
     * Returns the average time until invention success, WITHOUT sub-processes
     * @return float
     */
    public function getSuccesTime(){
        return $this->getTime() / $this->inventionChance;
    }
    
    /**
     * Returns the average time until invention success, including sub-processes
     * @return float
     */
    public function getTotalSuccessTime(){
        return $this->getTotalTime() / $this->inventionChance;
    }
    
    /**
     * Returns array with sum of average time until invention success, grouped by activity, including sub-processes
     * @return array
     */
    public function getTotalSuccessTimes(){
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0, 
            static::ACTIVITY_COPYING => 0, 
            static::ACTIVITY_INVENTING => 0
        );
        
        $sum[$this->activityID] = $this->processTime / $this->inventionChance;
        
        foreach ($this->getSubProcesses() as $subProcessData){
            foreach ($subProcessData->getTotalTimes() as $activityID => $time){
                $sum[$activityID] += $time / $this->inventionChance;
            }
        }
        return $sum;
    }
    
    /**
     * Returns MaterialMap object with average required materials until invention success, WITHOUT sub-processes
     * @return MaterialMap
     */
    public function getSuccessMaterialMap(){
        $materialsClass = iveeCoreConfig::getIveeClassName('MaterialMap');
        $smat = new $materialsClass;
        if(isset($this->materials)){
            foreach ($this->getMaterialMap()->getMaterials() as $typeID => $quantity){
                $smat->addMaterial($typeID, $quantity / $this->inventionChance);
            }
        }
        return $smat;
    }
    
    /**
     * Returns MaterialMap object with average required materials until invention success, including sub-processes
     * @return MaterialMap
     */
    public function getTotalSuccessMaterialMap(){
        $smat = $this->getSuccessMaterialMap();
        foreach ($this->getSubProcesses() as $subProcessData){
            foreach ($subProcessData->getTotalMaterialMap()->getMaterials() as $typeID => $quantity){
                $smat->addMaterial($typeID, $quantity / $this->inventionChance);
            }
        }
        return $smat;
    }
    
    /**
     * Returns volume of average required materials until invention success, WITHOUT sub-processes
     * @return float volume
     */
    public function getSuccessMaterialVolume(){
        return $this->getMaterialVolume() / $this->inventionChance;
    }
    
    /**
     * Returns volume of average required materials until invention success, including sub-processes
     * @return float volume
     */
    public function getTotalSuccessMaterialVolume(){
        return $this->getTotalMaterialVolume() / $this->inventionChance;
    }
    
    /**
     * Returns single invention attempt slot cost, WITHOUT subprocesses
     * @return float
     */
    public function getSlotCost(){
        $defaults = SDE::instance()->defaults;
        return $this->processTime * ($defaults->getUsePosInvention() ? 
            $defaults->getPosSlotCostPerSecond() : $defaults->getStationInventionCostPerSecond());
    }

    /**
     * Returns average invention slot cost until success, WITHOUT subprocesses
     * @return float
     */
    public function getSuccessSlotCost(){
        return $this->getSlotCost() / $this->inventionChance;
    }
    
    /**
     * Returns average total slot cost until success, including subprocesses
     * @return float
     */
    public function getTotalSuccessSlotCost(){
        return $this->getTotalSlotCost() / $this->inventionChance;
    }
    
    /**
     * Returns average material cost until success, WITHOUT subprocesses
     * @return float
     */
    public function getSuccessMaterialBuyCost($maxPriceDataAge = null){
        return $this->getMaterialBuyCost($maxPriceDataAge) / $this->inventionChance;
    }
    
    /**
     * Returns average material cost until success, including subprocesses
     * @return float
     */
    public function getTotalSuccessMaterialBuyCost($maxPriceDataAge = null){
        return $this->getTotalMaterialBuyCost($maxPriceDataAge) / $this->inventionChance;
    }
    
    /**
     * Returns total average cost until success, including subprocesses
     * @return float
     */
    public function getTotalSuccessCost($maxPriceDataAge = null) {
        return $this->getTotalCost($maxPriceDataAge) / $this->inventionChance;
    }
    
    /**
     * Prints data about this process
     */
    public function printData(){
        $utilClass = iveeCoreConfig::getIveeClassName('SDEUtil');
        echo "Average total success times:" . PHP_EOL;
        print_r($this->getTotalSuccessTimes());
        
        echo "Average total success materials:" . PHP_EOL;
        foreach ($this->getTotalSuccessMaterialMap()->getMaterials() as $typeID => $amount){
            echo $amount . 'x ' . SDE::instance()->getType($typeID)->getName() . PHP_EOL;
        }
        
        echo "Total average success material cost: " . $utilClass::quantitiesToReadable(
            $this->getTotalSuccessMaterialBuyCost()) . "ISK" . PHP_EOL;
        echo "Total average success slot cost: " . $utilClass::quantitiesToReadable(
            $this->getTotalSuccessSlotCost()) . "ISK" . PHP_EOL;
        echo "Total average success cost: " . $utilClass::quantitiesToReadable(
            $this->getTotalSuccessCost()) . "ISK" . PHP_EOL;
        echo "Total profit: " . $utilClass::quantitiesToReadable(
            $this->getTotalProfit()) . "ISK" . PHP_EOL;
    }
}

?>