<?php

/**
 * ReactionProcessData is the class used for detailing reaction processes
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/ReactionProcessData.php
 * @package iveeCore
 */
class ReactionProcessData {
    
    /**
     * @var MaterialMap $inputMaterialMap holding the input materials for the reaction
     */
    protected $inputMaterialMap;

    /**
     * @var MaterialMap $outputMaterialMap holding the output materials of the reaction
     */
    protected $outputMaterialMap;
    
    /**
     * @var int|float $cycles the number of reaction cycles
     */
    protected $cycles;
    
    /**
     * @var bool $withRefining defines if the reaction process has a refining step, which can happen for alchemy
     */
    protected $withRefining;
    
    /**
     * @var bool $withFeedback defines if the reaction process has a feedback loop, which can happen for alchemy
     */
    protected $withFeedback;
    
    /**
     * Constructor.
     * @param MaterialMap $inputMaterialMap for the reaction input materials
     * @param MaterialMap $outputMaterialMap for the reaction output materials
     * @param int $cycles defines the number of cycles the object covers
     * @param bool $withRefining defines if the process includes a regining step, which can happen for alchemy
     * @param bool $withFeedback defines if the process includes a material feedback loop, which can happen for alchemy
     * @return ReactionProcessData
     */
    public function __construct(
            MaterialMap $inputMaterialMap, 
            MaterialMap $outputMaterialMap, 
            $cycles = 1,
            $withRefining = false,
            $withFeedback = false) {
        $this->inputMaterialMap = $inputMaterialMap;
        $this->outputMaterialMap = $outputMaterialMap;
        $this->cycles = $cycles;
        $this->withRefining = $withRefining;
        $this->withFeedback = $withFeedback;
    }
    
    /**
     * Returns the MaterialMap representing the consumed materials of the reaciton
     * @return MaterialMap
     */
    public function getInputMaterialMap(){
        return $this->inputMaterialMap;
    }
    
    /**
     * Returns the MaterialMap representing the output materials of the reaciton
     * @return MaterialMap
     */
    public function getOutputMaterialMap(){
        return $this->outputMaterialMap;
    }
    
    /**
     * Returns the number of cycles of reactions
     * @return int|float
     */
    public function getCycles(){
        return $this->cycles;
    }
    
    /**
     * Returns the seconds of reaction
     * @return int|float
     */
    public function getTime(){
        return $this->getCycles() * 3600;
    }
    
    /**
     * Returns a boolean defining if this reaction process includes a reaction step (alchemy).
     * @return bool
     */
    public function withRefining(){
        return $this->withRefining;
    }
    
    /**
     * Returns a boolean defining if this reaction process includes a feedback step (alchemy).
     * @return bool
     */
    public function withFeedback(){
        return $this->withFeedback;
    }
    
    /**
     * Convenience function for getting the buy cost of the input materials
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getInputBuyCost($maxPriceDataAge = null){
        return $this->getInputMaterialMap()->getMaterialBuyCost($maxPriceDataAge);
    }
    
    /**
     * Convenience function for getting the sell value of the input materials
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getOutputSellValue($maxPriceDataAge = null){
        return $this->getOutputMaterialMap()->getMaterialSellValue($maxPriceDataAge);
    }
    
    /**
     * Convenience function for getting the profit from this reaction process
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getProfit($maxPriceDataAge = null){
        return $this->getOutputSellValue($maxPriceDataAge) - $this->getInputBuyCost($maxPriceDataAge);
    }
}

?>