<?php

/**
 * Class for all Types that can be manufactured
  * Inheritance: Manufacturable -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Manufacturable.php
 * @package iveeCore
 */
class Manufacturable extends Sellable {

    /**
     * @var int $producedFromBlueprintID the of the blueprint this item can be manufactured from
     */
    protected $producedFromBlueprintID;

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
            ibt.blueprintTypeID, 
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
            LEFT JOIN invBlueprintTypes as ibt ON ibt.productTypeID = it.typeID
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
                WHERE iveeTrackedPrices.typeID = " . (int) $this->typeID . "
                AND iveeTrackedPrices.regionID = " . (int) $sde->defaults->getDefaultRegionID() . "
            ) AS atp ON atp.typeID = it.typeID
            WHERE it.published = 1 
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();
        
        if (empty($row))
            throw new TypeIdNotFoundException("typeID ". (int) $this->typeID . " not found");
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * @param array $row data from DB
     */
    protected function setAttributes($row) {
        parent::setAttributes($row);
        if (isset($row['blueprintTypeID']))
            $this->producedFromBlueprintID = (int) $row['blueprintTypeID'];
    }

    /**
     * Returns blueprint object that can manufacture this item.
     * @return Blueprint
     */
    public function getBlueprint() {
        return SDE::instance()->getType($this->producedFromBlueprintID);
    }
    
    /**
     * Returns a MaterialMap object representing the reprocessing materials of the manufacturable.
     * Do note that due to EvE's "weird" rounding, values might be off-by-1.
     * @param int $batchSize number of items being reprocessed, needs to be multiple of portionSize
     * @param float $reprocessingYield the skill and station dependant reprocessing yield
     * @param float $reprocessingTaxFactor the standing dependant reprocessing tax factor
     * @return MaterialMap
     * @throws InvalidParameterValueException if batchSize is not multiple of portionSize or if effectiveYield is not 
     * sane
     */
    public function getReprocessingMaterialMap($batchSize, $reprocessingYield, $reprocessingTaxFactor){
        if($reprocessingYield > 1)
            throw new InvalidParameterValueException('Reprocessing yield can never be > 1.0');
        if($reprocessingTaxFactor > 1)
            throw new InvalidParameterValueException('Reprocessing tax factor can never be > 1.0');
        
        $materialsClass = iveeCoreConfig::getIveeClassName('MaterialMap');
        $rmat = new $materialsClass;
        
        //get the number of portions being reprocessed
        $numPortions = $batchSize / $this->portionSize;
        
        $sde = SDE::instance();
        
        //iterate over requirements
       foreach ($this->typeMaterials as $typeID => $quantity) {
            $type = $sde->getType($typeID);
            
            //skip skill requirements
            if($type->getCategoryID() == 16) 
                continue;

            //add base material to reprocessing materials, account for portionSize
            else 
                $rmat->addMaterial($typeID, 
                    round(round($quantity * $reprocessingYield) * $reprocessingTaxFactor) * $numPortions);
        }
        return $rmat;
    }
}

?>