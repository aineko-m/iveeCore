<?php

/**
 * Class for blueprints that can be invented. 
 * Inheritance: InventableBlueprint -> Blueprint -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/InventableBlueprint.php
 * @package iveeCore
 */
class InventableBlueprint extends Blueprint {
    
    /**
     * @var int $inventedFromBlueprintID ID for the Blueprint which this can be invented from
     */
    protected $inventedFromBlueprintID;

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
            ibt.*, 
            inventor.t1bpID, 
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
            LEFT JOIN invBlueprintTypes as ibt ON ibt.blueprintTypeID = it.typeID
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
            LEFT JOIN (
                SELECT 
                t1bp.blueprintTypeId as t1bpID, 
                t2bp.blueprintTypeID as t2bpID
                FROM `invMetaTypes` as iMT
                JOIN invTypes as t1 ON t1.typeID = iMT.parentTypeID
                JOIN invTypes as t2 ON t2.typeID = iMT.typeID
                JOIN invBlueprintTypes as t1bp ON t1bp.productTypeID = t1.typeID
                JOIN invBlueprintTypes as t2bp ON t2bp.productTypeID = t2.typeID
                WHERE iMT.metaGroupID = 2
                AND t2bp.blueprintTypeId = " . (int) $this->typeID . "
            ) AS inventor ON inventor.t2bpID = it.typeID
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
        if (isset($row['t1bpID']))
            $this->inventedFromBlueprintID = (int) $row['t1bpID'];
    }

    /**
     * Returns the inventor blueprint
     * @return InventorBlueprint 
     */
    public function getInventorBlueprint() {
        return SDE::instance()->getType($this->inventedFromBlueprintID);
    }
    
    /**
     * Invetanble blueprints can't be sold on the market
     * @throws NotOnMarketException 
     */
    public function getBuyPrice() {
        throw new NotOnMarketException($this->typeName." can't be bought on the market.");
    }
    
    /**
     * Invetanble blueprints can't be sold on the market
     * @throws NotOnMarketException 
     */
    public function getSellPrice() {
        throw new NotOnMarketException($this->typeName." can't be sold on the market.");
    }

    /**
     * Convenience function for inventing starting from the inveted blueprint instead of inventor
     * @param int $decryptorID the decryptor the be used, if any
     * @param int|string $inputBPCRuns the number of input runs on the BPC, 'max' for maximum runs.
     * @param boolean $recursive defines if manufacturables should be build recursively
     * @return InventionProcessData 
     */
    public function invent($decryptorID = null, $inputBPCRuns = 1, $recursive = true) {
        return $this->getInventorBlueprint()->invent($this->typeID, $decryptorID, $inputBPCRuns, $recursive);
    }

    /**
     * Convenience function to copy, invent T2 blueprint and manufacture from blueprint in one go
     * @param int $decryptorID the decryptor the be used, if any
     * @param int|string $inputBPCRuns the number of input runs on the BPC, 'max' for maximum runs.
     * @param boolean $recursive defines if manufacturables should be build recursively
     * @return ManufactureProcessData with cascaded InventionProcessData and CopyProcessData objects 
     */
    public function copyInventManufacture($decryptorID = null, $BPCRuns = 1, $recursive = true) {
        return $this->getInventorBlueprint()->copyInventManufacture($this->typeID, $decryptorID, $BPCRuns, $recursive);
    }
}

?>