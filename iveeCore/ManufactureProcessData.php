<?php
/**
 * ManufactureProcessData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ManufactureProcessData.php
 */

namespace iveeCore;

/**
 * Holds data about manufacturing processes.
 * Inheritance: ManufactureProcessData -> ProcessData
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ManufactureProcessData.php
 */
class ManufactureProcessData extends ProcessData
{
    /**
     * @var int $bpMeLevel ME level of the blueprint used in the manufacturing.
     */
    protected $bpMeLevel;

    /**
     * @var int $bpPeLevel PE level of the blueprint used in the manufacturing.
     */
    protected $bpTeLevel;

    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_MANUFACTURING;

    /**
     * Constructor.
     *
     * @param int $producesTypeID typeID of the item manufactured in this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param int $bpMeLevel the ME level of the blueprint used in this process
     * @param int $bpTeLevel the TE level of the blueprint used in this process
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     */
    public function __construct($producesTypeID, $producesQuantity, $processTime, $processCost, $bpMeLevel,
        $bpTeLevel, $solarSystemID, $assemblyLineID
    ) {
        parent::__construct($producesTypeID, $producesQuantity, $processTime, $processCost);
        $this->bpMeLevel      = (int) $bpMeLevel;
        $this->bpTeLevel      = (int) $bpTeLevel;
        $this->solarSystemID  = (int) $solarSystemID;
        $this->assemblyLineID = (int) $assemblyLineID;
    }

    /**
     * Returns the ME level of the blueprint used in this process.
     *
     * @return int
     */
    public function getMeLevel()
    {
        return $this->bpMeLevel;
    }

    /**
     * Returns the PE level of the blueprint used in this process.
     *
     * @return int
     */
    public function getTeLevel()
    {
        return $this->bpTeLevel;
    }

    /**
     * Returns the the total cost per single produced unit.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCostPerUnit(IndustryModifier $iMod)
    {
        return $this->getTotalCost($iMod) / $this->producesQuantity;
    }

    /**
     * Returns the the total profit for batch. Considers sell tax.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalProfit(IndustryModifier $iMod)
    {
        return (Type::getById($this->producesTypeID)->getRegionMarketData($iMod->getSolarSystem()->getRegionID())
                ->getSellPrice($iMod->getMaxPriceDataAge()) * $this->producesQuantity * $iMod->getSellTaxFactor()
            ) - ($this->getTotalCost($iMod)
        );
    }

    /**
     * Prints data about this process.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return void
     */
    public function printData(IndustryModifier $iMod)
    {
        $utilClass = Config::getIveeClassName('Util');

        echo "Total Slot Time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;
        echo "Total Materials for " . $this->producesQuantity . "x "
            . Type::getById($this->producesTypeID)->getName() . ":" . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterialMap()->getMaterials() as $typeID => $amount)
            echo $amount . 'x ' . Type::getById($typeID)->getName() . PHP_EOL;

        echo "Total Material Cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost($iMod))
            . "ISK" . PHP_EOL;
        echo "Total Slot Cost: " . $utilClass::quantitiesToReadable($this->getTotalProcessCost($iMod))
            . "ISK" . PHP_EOL;
        echo "Total Cost: " . $utilClass::quantitiesToReadable($this->getTotalCost($iMod))
            . "ISK" . PHP_EOL;
        try {
            echo "Total Profit: "        . $utilClass::quantitiesToReadable($this->getTotalProfit($iMod))
                . "ISK" . PHP_EOL;
        } catch (Exceptions\NoPriceDataAvailableException $e) {
            echo "No profit calculation possible due to missing price data for product" . PHP_EOL;
        }
    }
}
