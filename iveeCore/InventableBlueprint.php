<?php
/**
 * InventableBlueprint class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventableBlueprint.php
 */

namespace iveeCore;

/**
 * Class for blueprints that can be invented.
 * Inheritance: InventableBlueprint -> Blueprint -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventableBlueprint.php
 */
class InventableBlueprint extends Blueprint
{
    /**
     * @var int $inventedFrom ID of the InventorBlueprint from which this InventableBlueprint can be invented from
     */
    protected $inventedFrom;

    /**
     * Constructor. Use iveeCore\Type::getById() to instantiate InventableBlueprint objects instead.
     *
     * @param int $id of the InventableBlueprint object
     *
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeId is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        $sdeClass = Config::getIveeClassName('SDE');
        $this->loadOriginatingBlueprints($sdeClass::instance());
    }

    /**
     * Load Blueprint this InventableBlueprint can be invented from.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if expected data is not found for this typeId
     */
    protected function loadOriginatingBlueprints(SDE $sde)
    {
        $res = $sde->query(
            "SELECT typeID
            FROM industryActivityProducts
            WHERE activityID = 8
            AND productTypeID = " . $this->id . ';'
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException',
                "Originating Blueprint data for InventableBlueprint ID=" . $this->id ." not found"
            );

        while ($row = $res->fetch_assoc())
            $this->inventedFrom = (int) $row['typeID'];
    }

    /**
     * Returns the inventor blueprint ID.
     *
     * @return int
     */
    public function getInventorBlueprintId()
    {
        return $this->inventedFrom;
    }

    /**
     * Returns the inventor blueprint.
     *
     * @return \iveeCore\InventorBlueprint
     */
    public function getInventorBlueprint()
    {
        return Type::getById($this->getInventorBlueprintId());
    }

    /**
     * Convenience function for inventing starting from the inveted blueprint instead of inventor.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $decryptorId the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\InventionProcessData
     */
    public function invent(IndustryModifier $iMod, $decryptorId = null, $recursive = true)
    {
        return $this->getInventorBlueprint()->invent($iMod, $this->id, $decryptorId, $recursive);
    }

    /**
     * Convenience function to copy, invent T2 blueprint and manufacture from blueprint in one go.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $decryptorId the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     *
     * @return \iveeCore\ManufactureProcessData with cascaded iveeCore\InventionProcessData and
     * iveeCore\CopyProcessData objects
     */
    public function copyInventManufacture(IndustryModifier $iMod, $decryptorId = null, $recursive = true)
    {
        return $this->getInventorBlueprint()->copyInventManufacture($iMod, $this->id, $decryptorId, $recursive);
    }
}
