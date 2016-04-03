<?php
/**
 * ResearchTEProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ResearchTEProcessData.php
 */

namespace iveeCore;

/**
 * ResearchTEProcessData holds data about TE research processes.
 * Inheritance ResearchTEProcessData -> ProcessData -> ProcessDataCommon.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ResearchTEProcessData.php
 */
class ResearchTEProcessData extends ProcessData
{
    /**
     * @var int $activityId of this process.
     */
    protected $activityId = self::ACTIVITY_RESEARCH_TE;

    /**
     * @var int $startTELevel the initial TE level of the Blueprint
     */
    protected $startTELevel;

    /**
     * @var int $endTELevel the TE level of the Blueprint after the research
     */
    protected $endTELevel;

    /**
     * Constructor.
     *
     * @param int $researchedBpId of the Blueprint being researched
     * @param int $researchTime the time the process takes
     * @param float $processCost the cost of performing this reseach process
     * @param int $startTELevel the initial TE level of the Blueprint
     * @param int $endTELevel the TE level of the Blueprint after the research
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int $assemblyLineId ID of the AssemblyLine where the research is being performed
     */
    public function __construct(
        $researchedBpId,
        $researchTime,
        $processCost,
        $startTELevel,
        $endTELevel,
        $solarSystemId,
        $assemblyLineId
    ) {
        parent::__construct($researchedBpId, 1, $researchTime, $processCost);
        $this->startTELevel   = (int) $startTELevel;
        $this->endTELevel     = (int) $endTELevel;
        $this->solarSystemId  = (int) $solarSystemId;
        $this->assemblyLineId = (int) $assemblyLineId;
    }

    /**
     * Returns the initial TE level of the Blueprint.
     *
     * @return int
     */
    public function getStartTELevel()
    {
        return $this->startTELevel;
    }

    /**
     * Returns the TE level of the Blueprint after the research.
     *
     * @return int
     */
    public function getEndTELevel()
    {
        return $this->endTELevel;
    }
}
