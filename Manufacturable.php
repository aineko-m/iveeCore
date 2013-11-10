<?php

/**
 * Class for all Types that can be manufactured
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
        $row = SDE::instance()->query(
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
                AND iveeTrackedPrices.regionID = " . (int) iveeCoreConfig::getDefaultRegionID() . "
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
     * Returns a MaterialSet object representing the reprocessing materials of this item
     * @param int $batchSize number of items being reprocessed, needs to be multiple of portionSize
     * @param float $effectiveYield the skill, standing and station dependant reprocessing yield
     * @return MaterialSet
     * @throws InvalidParameterValueException if batchSize is not multiple of portionSize or if effectiveYield is not sane
     */
    public function getReprocessingMaterialSet($batchSize, $effectiveYield){
        return $this->getBlueprint()->getProductReprocessingMaterialSet($batchSize, $effectiveYield);
    }
}

?>