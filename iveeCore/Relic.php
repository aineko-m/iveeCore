<?php
/**
 * Relic class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 */

namespace iveeCore;

/**
 * Relic class represents items that can be used to invent T3Blueprints.
 *
 * Since Phoebe 1.0, Relic is a subclass of InventorBlueprint, as the distinction between reverse engineering and
 * invention was mostly eliminated. However, since Relic is not a Blueprint, some of the inherited methods are blocked
 * from use.
 *
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: Relic -> InventorBlueprint -> Blueprint -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Relic.php
 */
class Relic extends InventorBlueprint
{
    /**
     * Gets all necessary data from SQL.
     *
     * @return array
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeId is not found
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
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpId the ID if the T3Blueprint to be invented. If left null, it is set to the first
     * inventable blueprint ID
     * @param int $decryptorId the decryptor the be used, if any
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     *
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if the specified blueprint can't be invented from this
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorId isn't a decryptor
     */
    public function invent(IndustryModifier $iMod, $inventedBpId = null, $decryptorId = null, $manuRecursionDepth = 1)
    {
        $id = parent::invent($iMod, $inventedBpId, $decryptorId, $manuRecursionDepth);
        $id->addMaterial($this->getId(), 1);
        return $id;
    }

    /**
     * Invent T3Blueprint and manufacture from it in one go.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpId the ID of the T3Blueprint to be invented. If left null it will default to the first
     * blueprint defined in inventsBlueprintId
     * @param int $decryptorId the decryptor the be used, if any
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     * @param int $reactionRecursionDepth defines if and how deep used materials should be gained through reaction
     * recursively
     *
     * @return \iveeCore\ManufactureProcessData with cascaded InventionProcessData object
     * @throws \iveeCore\Exceptions\WrongTypeException if product is no an InventableBlueprint
     */
    public function inventManufacture(IndustryModifier $iMod, $inventedBpId = null, $decryptorId = null,
        $manuRecursionDepth = 1, $reactionRecursionDepth = 0
    ) {
        //run the invention
        $inventionData = $this->invent(
            $iMod,
            $inventedBpId,
            $decryptorId,
            $manuRecursionDepth
        );

        $producedType = $inventionData->getProducedType();
        if(!$producedType instanceof T3Blueprint)
            self::throwException('WrongTypeException', 'Given object is not instance of T3Blueprint');

        //manufacture from invented BP
        $manufactureData = $producedType->manufacture(
            $iMod,
            $inventionData->getResultRuns(),
            $inventionData->getResultME(),
            $inventionData->getResultTE(),
            $manuRecursionDepth,
            $reactionRecursionDepth
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

    public function copyInventManufacture(IndustryModifier $iMod, $inventedBpId = null, $decryptorId = null,
        $manuRecursionDepth = 1, $reactionRecursionDepth = 0
    ) {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function getProductBaseCost($maxPriceDataAge)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function manufacture(IndustryModifier $iMod, $units = 1, $bpME = null, $bpTE = null, $manuRecursionDepth = 1,
        $reactionRecursionDepth = 0
    ) {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $manuRecursionDepth = 1)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function researchME(IndustryModifier $iMod, $startME, $endME, $manuRecursionDepth = 1)
    {
        self::throwException('IveeCoreException', "Relics do not support this method");
    }

    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $manuRecursionDepth = 1)
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
