<?php

/**
 * MaterialMap is used for holding data about materials and quantities, typically in a bill-of-materials role
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/MaterialMap.php
 * @package iveeCore
 */
class MaterialMap {
    
    /**
     * @var array $materials holds the data in the form $typeID => $quantity
     * Note that quantities of 0 may indicate that an item is required, but not consumed
     */
    protected $materials = array();
    
    /**
     * Add required material and amount to total material array.
     * @param int $typeID of the material
     * @param int $quantity of the material
     * @throws InvalidParameterValueException
     */
    public function addMaterial($typeID, $quantity){
        if($quantity < 0) 
            throw new InvalidParameterValueException("Can't add negative material amounts to MaterialMap");
        if (isset($this->materials[(int)$typeID]))
            $this->materials[(int)$typeID] += $quantity;
        else
            $this->materials[(int)$typeID] = $quantity;
    }
    
    /**
     * Add required materials and amounts to total material array.
     * @param array $materials in the form $typeID => $quantity
     * @throws InvalidParameterValueException
     */
    public function addMaterials($materials){
        foreach ($materials as $typeID => $quantity){
            $this->addMaterial($typeID, $quantity);
        }
    }
    
    /**
     * Subtracts materials from the total material array.
     * @param int $typeID of the material
     * @param int $quantity of the material
     * @throws InvalidParameterValueException
     */
    public function subtractMaterial($typeID, $quantity){
        if(!isset($this->materials[$typeID])) throw 
            new InvalidParameterValueException('Trying to subtract materials of typeID ' . (int) $typeID 
            . " from MaterialMap, which doesn't occur");
        
        if($quantity > $this->materials[$typeID]) throw 
            new InvalidParameterValueException('Trying to subtract more materials of typeID ' . (int) $typeID 
            . " from MaterialMap than is available");
        
        $this->materials[$typeID] -= $quantity;
        if($this->materials[$typeID] == 0) unset($this->materials[$typeID]);
    }
    
    /**
     * Given two MaterialMap objects, creates the symmetric difference between the two. That is, whichever quantities 
     * of the same type appear in both are subtracted from both
     * @param MaterialMap $m1
     * @param MaterialMap $m2
     */
    public static function symmetricDifference(MaterialMap $m1, MaterialMap $m2){
        $mats1 = $m1->getMaterials();
        foreach ($m2->getMaterials() as $typeID => $quantity){
            //check if type occurs in both
            if(isset($mats1[$typeID])){
                //get the smaller quantity value
                $subVal = ($quantity > $mats1[$typeID] ? $mats1[$typeID] : $quantity);
                //subtract from both maps
                $m1->subtractMaterial($typeID, $subVal);
                $m2->subtractMaterial($typeID, $subVal);
            }
        }
    }
    
    /**
     * Sums the materials of another Materials object to this
     * @param MaterialMap $materials
     */
    public function addMaterialMap(MaterialMap $materials){
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
     * Returns a new materialMap object with quantities multiplied by given factor
     * @param float|int $factor
     * @return MaterialMap
     */
    public function getMultipliedMaterialMap($factor){
        $materialMapClass = iveeCoreConfig::getIveeClassName('MaterialMap');
        $multipleMaterialMap = new $materialMapClass;
        foreach ($this->getMaterials() as $typeID => $quantity){
            $multipleMaterialMap->addMaterial($typeID, $quantity * $factor);
        }
        return $multipleMaterialMap;
    }
    
    /**
     * Replaces every reprocessable in the map with its reprocessed materials
     * @param float $reprocessingYield the skill and station dependant reprocessing yield
     * @param float $reprocessingTaxFactor the standing dependant reprocessing tax factor
     */
    public function reprocessMaterials($reprocessingYield = 1, $reprocessingTaxFactor = 1){
        foreach ($this->materials as $typeID => $quantity){
            $type = SDE::instance()->getType($typeID);
            if($type->isReprocessable()){
                unset($this->materials[$typeID]);
                $this->addMaterialMap(
                    $type->getReprocessingMaterialMap($quantity, $reprocessingYield, $reprocessingTaxFactor));
            }
        }
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