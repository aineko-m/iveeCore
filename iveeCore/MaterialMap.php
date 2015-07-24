<?php
/**
 * MaterialMap class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MaterialMap.php
 */

namespace iveeCore;

/**
 * MaterialMap is used for holding data about materials and quantities, typically in a bill-of-materials role
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MaterialMap.php
 */
class MaterialMap
{
    /**
     * @var int[]|float[] $materials holds the data in the form $typeID => $quantity
     * Note that quantities of 0 may indicate that an item is required, but not consumed
     */
    protected $materials = array();

    /**
     * Add required material and amount to total material array.
     *
     * @param int $typeID of the material
     * @param int $quantity of the material
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException
     */
    public function addMaterial($typeID, $quantity)
    {
        if ($quantity < 0) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Can't add negative material amounts to MaterialMap");
        }

        if (isset($this->materials[(int) $typeID]))
            $this->materials[(int) $typeID] += $quantity;
        else
            $this->materials[(int) $typeID] = $quantity;
    }

    /**
     * Add required materials and amounts to total material array.
     *
     * @param int[]|float[] $materials in the form $typeID => $quantity
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException
     */
    public function addMaterials(array $materials)
    {
        foreach ($materials as $typeID => $quantity)
            $this->addMaterial($typeID, $quantity);
    }

    /**
     * Subtracts materials from the total material array.
     *
     * @param int $typeID of the material
     * @param int $quantity of the material
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException
     */
    public function subtractMaterial($typeID, $quantity)
    {
        $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
        if (!isset($this->materials[$typeID]))
            throw new $exceptionClass('Trying to subtract materials of typeID ' . (int) $typeID
                . " from MaterialMap, which doesn't occur");
        elseif ($quantity > $this->materials[$typeID])
            throw new $exceptionClass('Trying to subtract more materials of typeID ' . (int) $typeID
            . " from MaterialMap than is available");

        $this->materials[$typeID] -= $quantity;
        if ($this->materials[$typeID] == 0)
            unset($this->materials[$typeID]);
    }

    /**
     * Given two MaterialMap objects, creates the symmetric difference between the two. That is, whichever quantities
     * of the same type appear in both are subtracted from both.
     *
     * @param \iveeCore\MaterialMap $m1 first MaterialMap
     * @param \iveeCore\MaterialMap $m2 second MaterialMap
     *
     * @return void
     */
    public static function symmetricDifference(MaterialMap $m1, MaterialMap $m2)
    {
        $mats1 = $m1->getMaterials();
        foreach ($m2->getMaterials() as $typeID => $quantity) {
            //check if type occurs in both
            if (isset($mats1[$typeID])) {
                //get the smaller quantity value
                $subVal = ($quantity > $mats1[$typeID] ? $mats1[$typeID] : $quantity);
                //subtract from both maps
                $m1->subtractMaterial($typeID, $subVal);
                $m2->subtractMaterial($typeID, $subVal);
            }
        }
    }

    /**
     * Sums the materials of another Materials object to this.
     *
     * @param \iveeCore\MaterialMap $materials to add materials from
     *
     * @return void
     */
    public function addMaterialMap(MaterialMap $materials)
    {
        foreach ($materials->getMaterials() as $typeID => $quantity)
            $this->addMaterial($typeID, $quantity);
    }

    /**
     * Returns the materials as array $typeID => $quantity.
     *
     * @return int[]|float[]
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * Returns a new materialMap object with quantities multiplied by given factor.
     *
     * @param float|int $factor for the multiplication
     *
     * @return \iveeCore\MaterialMap
     */
    public function getMultipliedMaterialMap($factor)
    {
        $materialMapClass = Config::getIveeClassName('MaterialMap');
        $multipleMaterialMap = new $materialMapClass;
        foreach ($this->getMaterials() as $typeID => $quantity)
            $multipleMaterialMap->addMaterial($typeID, $quantity * $factor);

        return $multipleMaterialMap;
    }

    /**
     * Replaces every reprocessable in the map with its reprocessed materials.
     *
     * @param \iveeCore\IndustryModifier $iMod as industry context
     *
     * @return \iveeCore\MaterialMap
     */
    public function reprocessMaterials(IndustryModifier $iMod)
    {
        foreach ($this->materials as $typeID => $quantity) {
            $type = Type::getById($typeID);
            if ($type->isReprocessable()) {
                unset($this->materials[$typeID]);
                $this->addMaterialMap($type->getReprocessingMaterialMap($iMod, $quantity));
            }
        }
        return $this;
    }

    /**
     * Returns the volume of the materials.
     *
     * @return float
     */
    public function getMaterialVolume()
    {
        $sum = 0;
        foreach ($this->getMaterials() as $typeID => $quantity)
            $sum += Type::getById($typeID)->getVolume() * $quantity;

        return $sum;
    }

    /**
     * Returns material buy cost, considering station specific taxes. The best station (lowest tax) in system will be
     * chosen for that.
     * Items that are not on the market will be ignored.
     *
     * @param IndustryModifier $iMod used for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost(IndustryModifier $iMod)
    {
        $sum = 0;
        foreach ($this->getMaterials() as $typeID => $amount) {
            $type = Type::getById($typeID);
            if (!$type->onMarket())
                continue;
            if ($amount > 0)
                $sum += $type->getMarketPrices($iMod->getSolarSystem()->getRegionID())
                    ->getBuyPrice($iMod->getMaxPriceDataAge()) * $amount * $iMod->getBuyTaxFactor();
        }
        return $sum;
    }

    /**
     * Returns material sell value, considering taxes.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialSellValue(IndustryModifier $iMod)
    {
        $sum = 0;
        foreach ($this->getMaterials() as $typeID => $amount) {
            $type = Type::getById($typeID);
            if (!$type->onMarket())
                continue;
            if ($amount > 0)
                $sum += $type->getMarketPrices($iMod->getSolarSystem()->getRegionID())
                    ->getSellPrice($iMod->getMaxPriceDataAge()) * $amount * $iMod->getSellTaxFactor();
        }
        return $sum;
    }
}
