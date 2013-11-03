<?php

/**
 * Holds data about manufacturing processes.
 * Inheritance: ManufactureData -> ProcessData
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/ManufactureData.php
 * @package iveeCore
 */
class ManufactureData extends ProcessData {

    /**
     * @var int $bpMeLevel ME level of the blueprint used in the manufacturing.
     */
    protected $bpMeLevel;
    
    /**
     * @var int $bpPeLevel PE level of the blueprint used in the manufacturing.
     */
    protected $bpPeLevel;
    
    /**
     * @var int $activityID of this process.
     */
    protected $activity = self::ACTIVITY_MANUFACTURING;
    
    /**
     * Constructor.
     * @param int $producesTypeID typeID of the item manufactured in this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @param int $bpMeLevel the ME level of the blueprint used in this process
     * @param int $bpPeLevel the PE level of the blueprint used in this process
     * @return ProcessData
     */
    public function __construct($producesTypeID, $producesQuantity, $processTime, $bpMeLevel, $bpPeLevel){
        parent::__construct($producesTypeID, $producesQuantity, $processTime);
        $this->bpMeLevel = $bpMeLevel;
        $this->bpPeLevel = $bpPeLevel;
    }
    
    /**
     * Returns the ME level of the blueprint used in this process
     * @return int
     */
    public function getMeLevel(){
        return $this->bpMeLevel;
    }

    /**
     * Returns the PE level of the blueprint used in this process
     * @return int
     */
    public function getPeLevel(){
        return $this->bpPeLevel;
    }

    /**
     * Returns the the slot cost of this process, WITHOUT subprocesses
     * @return float
     */
    public function getSlotCost(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        return $this->processTime * (iveeCoreConfig::getUsePosManufacturing() ? 
            $utilClass::getPosSlotCostPerSecond() : iveeCoreConfig::getStationManufacturingCostPerSecond());
    }
    
    /**
     * Returns the the total cost per single produced unit
     * @return float
     */
    public function getTotalCostPerUnit($maxPriceDataAge = null){
        return $this->getTotalCost($maxPriceDataAge) / $this->producesQuantity;
    }
    
    /**
     * Returns the the total profit for batch. Considers sell tax.
     * @return float
     */
    public function getTotalProfit($maxPriceDataAge = null) {
        return (SDE::instance()->getType($this->producesTypeID)->getSellPrice($maxPriceDataAge) 
            * $this->producesQuantity * iveeCoreConfig::getDefaultSellTaxFactor()) - ($this->getTotalCost($maxPriceDataAge));
    }
    
    /**
     * Prints data about this process
     */
    public function printData(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        echo "Total Slot Time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;
        echo "Total Materials for " . $this->producesQuantity . "x " . SDE::instance()->getType($this->producesTypeID)->getName() . ":" . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterials() as $typeID => $amount){
            echo $amount .'x '.SDE::instance()->getType($typeID)->getName().PHP_EOL;
        }
        echo "Total Material Cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialCost()) . "ISK" . PHP_EOL;
        echo "Total Slot Cost: "     . $utilClass::quantitiesToReadable($this->getTotalSlotCost()) . "ISK" . PHP_EOL;
        echo "Total Cost: "          . $utilClass::quantitiesToReadable($this->getTotalCost()) . "ISK" . PHP_EOL;
        echo "Total Profit: ";
        try{
            echo $utilClass::quantitiesToReadable($this->getTotalProfit()) . "ISK" . PHP_EOL;
        } catch (Exception $e){
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

?>