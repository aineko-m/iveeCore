<?php

/**
 * Holds data about invention processes
 * 
 * Note the design decision of making "invention attempt" cases override the normal inherited methods while the 
 * "invention success" cases are defined explicitly in new methods. This is less error error prone than the other way
 * round.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/InventionData.php
 * @package iveeCore
 */
class InventionData extends ProcessData {
    
    /**
     * @var int $activityID of this process.
     */
    protected $activity = self::ACTIVITY_INVENTING;
    
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
            self::ACTIVITY_MANUFACTURING => 0, 
            self::ACTIVITY_COPYING => 0, 
            self::ACTIVITY_INVENTING => 0
        );
        
        $sum[$this->activity] = $this->processTime / $this->inventionChance;
        
        foreach ($this->subProcessData as $subProcessData){
            foreach ($subProcessData->getTotalTimes() as $activity => $time){
                $sum[$activity] += $time / $this->inventionChance;
            }
        }
        return $sum;
    }
    
    /**
     * Returns array with sum of average required materials until invention success, grouped by activity, WITHOUT 
     * sub-processes
     * @return array
     */
    public function getSuccessMaterials(){
        $sum = array();
        foreach ($this->materials as $typeID => $quantity){
            if(isset($ret[$typeID])){
                $sum[$typeID] += $quantity / $this->inventionChance;
            } else {
                $sum[$typeID] = $quantity / $this->inventionChance;
            }
        }
        return $sum;
    }
    
    /**
     * Returns array with sum of average required materials until invention success, grouped by activity, including 
     * sub-processes
     * @return array
     */
    public function getTotalSuccessMaterials(){
        $sum = $this->getSuccessMaterials();
        foreach ($this->subProcessData as $subProcessData){
            foreach ($subProcessData->getTotalMaterials() as $typeID => $quantity){
                if(isset($sum[$typeID])){
                    $sum[$typeID] += $quantity / $this->inventionChance;
                } else {
                    $sum[$typeID] = $quantity / $this->inventionChance;
                }
            }
        }
        return $sum;
    }
    
    /**
     * Returns single invention attempt slot cost, WITHOUT subprocesses
     * @return float
     */
    public function getSlotCost(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        return $this->processTime * (iveeCoreConfig::getUsePosInvention() ? 
            $utilClass::getPosSlotCostPerSecond() : iveeCoreConfig::getStationInventionCostPerSecond());
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
    public function getSuccessMaterialCost($maxPriceDataAge = null){
        return $this->getMaterialCost($maxPriceDataAge) / $this->inventionChance;
    }
    
    /**
     * Returns average material cost until success, including subprocesses
     * @return float
     */
    public function getTotalSuccessMaterialCost($maxPriceDataAge = null){
        return $this->getTotalMaterialCost($maxPriceDataAge) / $this->inventionChance;
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
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        echo "Total average Success Times:" . PHP_EOL;
        print_r($this->getTotalSuccessTimes());
        
        echo "Total average Success Materials:" . PHP_EOL;
        print_r($this->getTotalSuccessMaterials());
        
        echo "Total average success Material Cost: " . $utilClass::quantitiesToReadable($this->getTotalSuccessMaterialCost()) . "ISK" . PHP_EOL;
        echo "Total average success Slot Cost: "     . $utilClass::quantitiesToReadable($this->getTotalSuccessSlotCost()) . "ISK" . PHP_EOL;
        echo "Total average success Cost: "          . $utilClass::quantitiesToReadable($this->getTotalSuccessCost()) . "ISK" . PHP_EOL;
        echo "Total Profit: "                        . $utilClass::quantitiesToReadable($this->getTotalProfit()) . "ISK" . PHP_EOL;
    }
}

?>