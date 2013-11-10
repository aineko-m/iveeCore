<?php

/**
 * MaterialSet is used for holding data about materials and quantities, typically in a bill-of-materials role
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/MaterialSet.php
 * @package iveeCore
 */
class MaterialSet {
    
    /**
     * @var array $materials holds the data in the form $typeID => $quantity
     * Note that quantities of 0 may indicate that an item is required, but not consumed
     */
    protected $materials = array();
    
    /**
     * Add required material and amount to total material array.
     * @param int $typeID of the material
     * @param int $quantity of the material
     */
    public function addMaterial($typeID, $quantity){
        if (isset($this->materials[(int)$typeID]))
            $this->materials[(int)$typeID] += $quantity;
        else
            $this->materials[(int)$typeID] = $quantity;
    }
    
    /**
     * Sums the materials of another Materials object to this
     * @param MaterialSet $materials
     */
    public function addMaterialSet(MaterialSet $materials){
        foreach ($materials->getMaterials() as $typeID => $quantity){
            $this->addMaterial($typeID, $quantity);
        }
    }
    
    /**
     * Returns the materials as array $typeID => $quantity
     * @return array
     */
    public function getMaterials(){
        return $this->materials;
    }
    
    /**
     * Returns the volume of the materials
     * @return float the volume
     */
    public function getMaterialVolume(){
        $sum = 0;
        $sde = SDE::instance();
        foreach ($this->getMaterials() as $typeID => $quantity){
            $sum += $sde->getType($typeID)->getVolume() * $quantity;
        }
        return $sum;
    }
    
    /**
     * Returns material buy cost, considering taxes
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost($maxPriceDataAge = null){
        $sde = SDE::instance();
        $sum = 0;
        foreach ($this->getMaterials() as $typeID => $amount) {
            $type = $sde->getType($typeID);
            if (!($type instanceof Sellable) OR !$type->onMarket()) 
                continue;
            if($amount > 0)
                $sum += $type->getBuyPrice($maxPriceDataAge) * $amount * iveeCoreConfig::getDefaultBuyTaxFactor();
        }
        return $sum;
    }
    
        /**
     * Returns material sell value, cosnidering taxes
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialSellValue($maxPriceDataAge = null){
        $sde = SDE::instance();
        $sum = 0;
        foreach ($this->getMaterials() as $typeID => $amount) {
            $type = $sde->getType($typeID);
            if (!($type instanceof Sellable) OR !$type->onMarket()) 
                continue;
            if($amount > 0)
                $sum += $type->getSellPrice($maxPriceDataAge) * $amount * iveeCoreConfig::getDefaultSellTaxFactor();
        }
        return $sum;
    }
}
?>