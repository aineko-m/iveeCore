<?php
/**
 * Relic class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 *
 */

namespace iveeCore;

/**
 * Relic class represents items that can be used to invent T3Blueprints
 *
 * Since Phoebe 1.0, Relic is a subclass of InventorBlueprint, as the distinction between reverse engineering and
 * invention was mostly eliminated. However, since Relic is not a Blueprint, some of the inherited methods are blocked
 * from use.
 *
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Relic -> InventorBlueprint -> Blueprint -> Type -> SdeType -> CacheableCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 *
 */
class Relic extends InventorBlueprint
{
    /**
     * Gets all necessary data from SQL.
     *
     * @return array
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function queryAttributes()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();
        $row = $sde->query(
            "SELECT
            it.groupID,
            ig.categoryID,
            it.typeName,
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID,
            0 as productTypeID,
            0 as maxProductionLimit
            FROM invTypes AS it
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            WHERE it.published = 1
            AND it.typeID = " . $this->id . ";"
        )->fetch_assoc();

        if (empty($row))
            self::throwException('TypeIdNotFoundException', "Relic ID=" . $this->id . " not found");

        return $row;
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     *
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID if the T3Blueprint to be invented. If left null, it is set to the first
     * inventable blueprint ID
     * @param int $decryptorID the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if the specified blueprint can't be invented from this
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorID isn't a decryptor
     * @throws \iveeCore\Exceptions\InvalidDecryptorGroupException if a non-matching decryptor is specified
     */
    public function invent(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null, $recursive = true)
    {
        $id = parent::invent($iMod, $inventedBpID, $decryptorID, $recursive);
        $id->addMaterial($this->getId(), 1);
        return $id;
    }

    /**
     * Invent T3Blueprint and manufacture from it in one go
     *
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID of the T3Blueprint to be invented. If left null it will default to the first
     * blueprint defined in inventsBlueprintID
     * @param int $decryptorID the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     *
     * @return ManufactureProcessData with cascaded InventionProcessData object
     */
    public function inventManufacture(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null,
        $recursive = true
    ) {
        //run the invention
        $inventionData = $this->invent(
            $iMod,
            $inventedBpID,
            $decryptorID,
            $recursive
        );

        //manufacture from invented BP
        $manufactureData = $inventionData->getProducedType()->manufacture(
            $iMod,
            $inventionData->getResultRuns(),
            $inventionData->getResultME(),
            $inventionData->getResultTE(),
            $recursive
        );

        //add invention data to the manufactureProcessData object
        $manufactureData->addSubProcessData($inventionData);

        return $manufactureData;
    }

    /**
     * The following methods had to be blocked from use as a Relic is not a Blueprint. While not a clean design, it is
     * preferable to making Relic not inherit from Blueprint / InventorBlueprint and duplicating the shared code. If
     * iveeCore is ever moved to PHP 5.4, this could be solved via Traits.
     */

    public function copyInventManufacture(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null,
        $recursive = true
    ) {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function getProductBaseCost($maxPriceDataAge = null)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function manufacture(IndustryModifier $iMod, $units = 1, $bpME = null, $bpTE = null, $recursive = true)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $recursive = true)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function researchME(IndustryModifier $iMod, $startME, $endME, $recursive = true)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $recursive = true)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function getProductId()
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function getRank()
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function getMaxProductionLimit()
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }
}
