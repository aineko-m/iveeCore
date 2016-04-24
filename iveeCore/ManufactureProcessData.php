<?php
/**
 * ManufactureProcessData class file.
 *
 * PHP version 5.4
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
 * Inheritance: ManufactureProcessData -> ProcessData -> ProcessDataCommon.
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
     * @var int $activityId of this process.
     */
    protected $activityId = self::ACTIVITY_MANUFACTURING;

    /**
     * Constructor.
     *
     * @param int $producesTypeId typeId of the item manufactured in this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param int $bpMeLevel the ME level of the blueprint used in this process
     * @param int $bpTeLevel the TE level of the blueprint used in this process
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int $assemblyLineId ID of the AssemblyLine where the research is being performed
     */
    public function __construct(
        $producesTypeId,
        $producesQuantity,
        $processTime,
        $processCost,
        $bpMeLevel,
        $bpTeLevel,
        $solarSystemId,
        $assemblyLineId
    ) {
        parent::__construct($producesTypeId, $producesQuantity, $processTime, $processCost);
        $this->bpMeLevel      = (int) $bpMeLevel;
        $this->bpTeLevel      = (int) $bpTeLevel;
        $this->solarSystemId  = (int) $solarSystemId;
        $this->assemblyLineId = (int) $assemblyLineId;
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
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCostPerUnit(IndustryModifier $iMod)
    {
        return $this->getTotalCost($iMod) / $this->producesQuantity;
    }

    /**
     * Returns the the total profit for batch. Considers sell tax and cost of sub-processes.
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext will be
     * used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalProfit(IndustryModifier $buyContext, IndustryModifier $sellContext = null)
    {
        if (is_null($sellContext)) {
            $sellContext = $buyContext;
        }

        $marketPrices = $this->getProducedType()->getMarketPrices(
            $sellContext->getSolarSystem()->getRegionId(),
            $sellContext->getMaxPriceDataAge()
        );

        return $marketPrices->getSellPrice($sellContext->getMaxPriceDataAge()) * $this->producesQuantity
            * $sellContext->getSellTaxFactor() - $this->getTotalCost($buyContext);
    }
}
