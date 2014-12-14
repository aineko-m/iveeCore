<?php
/**
 * ReactionProcessData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProcessData.php
 *
 */

namespace iveeCore;

/**
 * ReactionProcessData is used for describing reaction processes
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProcessData.php
 *
 */
class ReactionProcessData
{
    /**
     * @var MaterialMap $inputMaterialMap holding the input materials for the reaction
     */
    protected $inputMaterialMap;

    /**
     * @var MaterialMap $outputMaterialMap holding the output materials of the reaction
     */
    protected $outputMaterialMap;

    /**
     * @var int|float $cycles the number of reaction cycles
     */
    protected $cycles;

    /**
     * @var bool $withReprocessing if the reaction process has a reprocessing step, which can happen for alchemy
     */
    protected $withReprocessing;

    /**
     * @var bool $withFeedback defines if the reaction process has a feedback loop, which can happen for alchemy
     */
    protected $withFeedback;

    /**
     * Constructor
     *
     * @param MaterialMap $inputMaterialMap for the reaction input materials
     * @param MaterialMap $outputMaterialMap for the reaction output materials
     * @param int $cycles defines the number of cycles the object covers
     * @param bool $withReprocessing defines if the process includes a reprocessing step, which can happen for alchemy
     * @param bool $withFeedback defines if the process includes a material feedback loop, which can happen for alchemy
     *
     * @return ReactionProcessData
     */
    public function __construct(MaterialMap $inputMaterialMap, MaterialMap $outputMaterialMap, $cycles = 1,
        $withReprocessing = false, $withFeedback = false
    ) {
        $this->inputMaterialMap  = $inputMaterialMap;
        $this->outputMaterialMap = $outputMaterialMap;
        $this->cycles            = $cycles;
        $this->withReprocessing  = $withReprocessing;
        $this->withFeedback      = $withFeedback;
    }

    /**
     * Returns the MaterialMap representing the consumed materials of the reaction
     *
     * @return MaterialMap
     */
    public function getInputMaterialMap()
    {
        return $this->inputMaterialMap;
    }

    /**
     * Returns the MaterialMap representing the output materials of the reaction
     *
     * @return MaterialMap
     */
    public function getOutputMaterialMap()
    {
        return $this->outputMaterialMap;
    }

    /**
     * Returns the number of cycles of reactions
     *
     * @return int|float
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    /**
     * Returns the seconds of reaction
     *
     * @return int|float
     */
    public function getTime()
    {
        return $this->getCycles() * 3600;
    }

    /**
     * Returns a boolean defining if this reaction process includes a reprocessing step (alchemy).
     *
     * @return bool
     */
    public function withReprocessing()
    {
        return $this->withReprocessing;
    }

    /**
     * Returns a boolean defining if this reaction process includes a feedback step (alchemy).
     *
     * @return bool
     */
    public function withFeedback()
    {
        return $this->withFeedback;
    }

    /**
     * Convenience function for getting the buy cost of the input materials
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     *
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getInputBuyCost($maxPriceDataAge = null)
    {
        return $this->getInputMaterialMap()->getMaterialBuyCost($maxPriceDataAge);
    }

    /**
     * Convenience function for getting the sell value of the input materials
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     *
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getOutputSellValue($maxPriceDataAge = null)
    {
        return $this->getOutputMaterialMap()->getMaterialSellValue($maxPriceDataAge);
    }

    /**
     * Convenience function for getting the profit from this reaction process
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     *
     * @return float
     * @throws PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getProfit($maxPriceDataAge = null)
    {
        return $this->getOutputSellValue($maxPriceDataAge) - $this->getInputBuyCost($maxPriceDataAge);
    }
}
