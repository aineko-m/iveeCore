<?php
/**
 * ReactionProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProcessData.php
 */

namespace iveeCore;

/**
 * ReactionProcessData is used for describing reaction processes.
 * Inheritance: ReactionProcessData -> ProcessDataCommon.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProcessData.php
 */
class ReactionProcessData extends ProcessDataCommon
{
    /**
     * @var int $activityId of this process
     */
    protected $activityId = self::ACTIVITY_REACTING;

    /**
     * @var int $reactionId used in this process
     */
    protected $reactionId;

    /**
     * @var \iveeCore\MaterialMap $outputMaterials holding the output materials of the reaction
     */
    protected $outputMaterials;

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
     * Constructor.
     *
     * @param int $reactionId of the used reaction
     * @param MaterialMap $inputMaterialMap for the reaction input materials
     * @param MaterialMap $outputMaterialMap for the reaction output materials
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int|float $cycles defines the number of cycles the object covers
     * @param bool $withReprocessing defines if the process includes a reprocessing step, which can happen for alchemy
     * @param bool $withFeedback defines if the process includes a material feedback loop, which can happen for alchemy
     */
    public function __construct(
        $reactionId,
        MaterialMap $inputMaterialMap,
        MaterialMap $outputMaterialMap,
        $solarSystemId,
        $cycles = 1,
        $withReprocessing = false,
        $withFeedback = false
    ) {
        $this->reactionId       = (int) $reactionId;
        $this->materials        = $inputMaterialMap;
        $this->outputMaterials  = $outputMaterialMap;
        $this->solarSystemId    = (int) $solarSystemId;
        $this->cycles           = $cycles;
        $this->withReprocessing = $withReprocessing;
        $this->withFeedback     = $withFeedback;
    }

    /**
     * Returns the id of the reaction used
     *
     * @return int
     */
    public function getReactionId()
    {
        return $this->reactionId;
    }

    /**
     * Returns the MaterialMap representing the output materials of the reaction.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getOutputMaterialMap()
    {
        return clone $this->outputMaterials;
    }

    /**
     * Returns the number of cycles of reactions.
     *
     * @return int|float
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    /**
     * Returns the seconds of reaction.
     *
     * @return int|float
     */
    public function getTime()
    {
        return $this->getCycles() * 3600;
    }

    /**
     * Will return an empty new SkillMap as reactions don't have skill requirements.
     *
     * @return \iveeCore\SkillMap
     */
    public function getSkillMap()
    {
        $skillClass = Config::getIveeClassName('SkillMap');
        return new $skillClass;
    }

    /**
     * Returns process cost, without subprocesses.
     *
     * @return float
     */
    public function getProcessCost()
    {
        return 0.0;
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
     * Convenience function for getting the sell value of the input materials.
     *
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getOutputSellValue(IndustryModifier $iMod)
    {
        return $this->outputMaterials->getMaterialSellValue($iMod);
    }

    /**
     * Returns total profit for direct child ManufactureProcessData or ReactionProcessData sub-processes (activities
     * with a product that can be sold on the market).
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext will be
     * used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getTotalProfit(IndustryModifier $buyContext, IndustryModifier $sellContext = null)
    {
        return $this->getOutputSellValue(is_null($sellContext) ? $buyContext : $sellContext)
            - $this->getTotalCost($buyContext);
    }
}
