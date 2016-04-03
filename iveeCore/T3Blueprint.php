<?php
/**
 * T3Blueprint class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/T3Blueprint.php
 */

namespace iveeCore;

/**
 * T3Blueprint represents blueprints that can be invented from Relics.
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: T3Blueprint -> InventableBlueprint -> Blueprint -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/T3Blueprint.php
 */
class T3Blueprint extends InventableBlueprint
{
    /**
     * @var int[] $inventedFrom IDs of the Relics this T3Blueprint can be invented from.
     */
    protected $inventedFrom;

    /**
     * Load Relics this T3Blueprint can be reverse-engineered from.
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
        if ($res->num_rows < 1) {
            self::throwException(
                'TypeIdNotFoundException',
                "Reverse Engineering data for T3Blueprint ID=" . $this->id ." not found"
            );
        }

        $this->inventedFrom = [];
        while ($row = $res->fetch_assoc()) {
            $this->inventedFrom[] = (int) $row['typeID'];
        }
    }

    /**
     * Returns the IDs of the Relics this T3Blueprint can be reverse engineered from.
     *
     * @return int[]
     */
    public function getInventionRelicIds()
    {
        return $this->inventedFrom;
    }

    /**
     * Returns the Relics this T3Blueprint can be reverse engineered from.
     *
     * @return \iveeCore\Relic[]
     */
    public function getInventionRelics()
    {
        $relics = [];
        foreach ($this->getInventionRelicIds() as $relicId) {
            $relics[$relicId] = Type::getById($relicId);
        }

        return $relics;
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $relicId the ID of the Relic to be used for invention
     * @param int $decryptorId the decryptor the be used, if any
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     *
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if a wrong relicId is given
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorId isn't a decryptor or if relicId isn't a Relic
     */
    public function inventFromRelic(IndustryModifier $iMod, $relicId, $decryptorId = null, $manuRecursionDepth = 1)
    {
        if (!in_array($relicId, $this->inventedFrom)) {
            self::throwException(
                'NotInventableException',
                "Can't use Relic ID=" . (int) $relicId . " to invent this T3Blueprint"
            );
        }

        $relic = Type::getById($relicId);
        if (!$relic instanceof Relic) {
            self::throwException('WrongTypeException', 'Given object is not instance of Relic');
        }

        return $relic->invent($iMod, $this->getId(), $decryptorId, $manuRecursionDepth);
    }

    /**
     * Returns a ManufactureProcessData object with cascaded InventionProcessData object.
     *
     * @param \iveeCore\IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $relicId the ID of the Relic to be used for invention
     * @param int $decryptorId the decryptor the be used, if any
     * @param int $manuRecursionDepth defines if and how deep used materials should be manufactured recursively
     * @param int $reactionRecursionDepth defines if and how deep used materials should be gained through reaction
     * recursively
     *
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if a wrong relicId is given
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorId isn't a decryptor or if relicId isn't a Relic
     */
    public function inventManufacture(
        IndustryModifier $iMod,
        $relicId,
        $decryptorId = null,
        $manuRecursionDepth = 1,
        $reactionRecursionDepth = 0
    ) {
        if (!in_array($relicId, $this->inventedFrom)) {
            self::throwException(
                'NotInventableException',
                "Can't use Relic ID=" . (int) $relicId . " to invent this T3Blueprint"
            );
        }
   
        $relic = Type::getById($relicId);
        if (!$relic instanceof Relic) {
            self::throwException('WrongTypeException', 'Given object is not instance of Relic');
        }

        return $relic->inventManufacture($iMod, $this->getId(), $decryptorId, $manuRecursionDepth, $reactionRecursionDepth);
    }

    /**
     * The following methods had to be blocked from use as a T3Blueprint is not a Blueprint. While not a clean design,
     * it is preferable to making T3Blueprint not inherit from Blueprint / InventableBlueprint and duplicating the
     * shared code.
     */

    public function copyInventManufacture(
        IndustryModifier $iMod,
        $decryptorId = null,
        $manuRecursionDepth = 1,
        $reactionRecursionDepth = 0
    ) {
        self::throwException('IveeCoreException', "Use inventManufacture()");
    }

    public function getInventorBlueprintId()
    {
        self::throwException('IveeCoreException', "T3Blueprints are invented from Relics, use getInventionRelicIds()");
    }

    public function getInventorBlueprint()
    {
        self::throwException('IveeCoreException', "T3Blueprints are invented from Relics");
    }

    public function invent(IndustryModifier $iMod, $decryptorId = null, $manuRecursionDepth = 1)
    {
        self::throwException('IveeCoreException', "Use inventFromRelic()");
    }

    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $manuRecursionDepth = 1)
    {
        self::throwException('NotResearchableException', "T3Blueprints can't be copied");
    }

    public function researchME(IndustryModifier $iMod, $startME, $endME, $manuRecursionDepth = 1)
    {
        self::throwException('NotResearchableException', "T3Blueprints can't be copied");
    }

    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $manuRecursionDepth = 1)
    {
        self::throwException('NotResearchableException', "T3Blueprints can't be copied");
    }
}
