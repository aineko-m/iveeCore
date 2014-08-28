<?php
/**
 * InventableBlueprint class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventableBlueprint.php
 *
 */

namespace iveeCore;

/**
 * Class for blueprints that can be invented.
 * Inheritance: InventableBlueprint -> Blueprint -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventableBlueprint.php
 *
 */
class InventableBlueprint extends Blueprint
{
    /**
     * @var int $inventedFromBlueprintID ID for the Blueprint which this can be invented from
     */
    protected $inventedFromBlueprintID;

    /**
     * Gets all necessary data from SQL
     * 
     * @return array
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes()
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $row = $sdeClass::instance()->query(
            "SELECT
            it.groupID,
            ig.categoryID,
            it.typeName,
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID,
            prod.productTypeID,
            maxprod.maxProductionLimit,
            inv.typeID as t1BpID
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            JOIN industryActivityProducts as prod ON prod.typeID = it.typeID
            JOIN industryBlueprints as maxprod ON maxprod.typeID = it.typeID
            JOIN industryActivityProducts as inv ON inv.productTypeID = it.typeID
            WHERE it.published = 1
            AND prod.activityID = 1
            AND inv.activityID = 8
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException('TypeIdNotFoundException', "typeID ". (int) $this->typeID . " not found");
        
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * 
     * @param array $row data from DB
     * 
     * @return void
     */
    protected function setAttributes(array $row)
    {
        parent::setAttributes($row);
        if (isset($row['t1BpID']))
            $this->inventedFromBlueprintID = (int) $row['t1BpID'];
    }

    /**
     * Returns the inventor blueprint ID
     *
     * @return int
     */
    public function getInvetorBlueprintId()
    {
        return $this->inventedFromBlueprintID;
    }

    /**
     * Returns the inventor blueprint
     * 
     * @return InventorBlueprint
     */
    public function getInventorBlueprint()
    {
        $typeClass = Config::getIveeClassName('Type');
        return $typeClass::getType($this->getInvetorBlueprintId());
    }

    /**
     * Invetanble blueprints can't be sold on the market
     * 
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * 
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException always
     */
    public function getBuyPrice($maxPriceDataAge = null)
    {
        $this->throwNotOnMarketException();
    }

    /**
     * Invetanble blueprints can't be sold on the market
     * 
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * 
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException always
     */
    public function getSellPrice($maxPriceDataAge = null)
    {
        $this->throwNotOnMarketException();
    }

    /**
     * Convenience function for inventing starting from the inveted blueprint instead of inventor
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $decryptorID the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     * 
     * @return \iveeCore\InventionProcessData
     */
    public function invent(IndustryModifier $iMod, $decryptorID = null, $recursive = true)
    {
        return $this->getInventorBlueprint()->invent($iMod, $this->typeID, $decryptorID, $recursive);
    }

    /**
     * Convenience function to copy, invent T2 blueprint and manufacture from blueprint in one go
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $decryptorID the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     * 
     * @return \iveeCore\ManufactureProcessData with cascaded \iveeCore\InventionProcessData and 
     * \iveeCore\CopyProcessData objects
     */
    public function copyInventManufacture(IndustryModifier $iMod, $decryptorID = null, $recursive = true)
    {
        return $this->getInventorBlueprint()->copyInventManufacture($iMod, $this->typeID, $decryptorID, $recursive);
    }
}
