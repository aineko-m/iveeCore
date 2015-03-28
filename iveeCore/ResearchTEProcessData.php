<?php
/**
 * ResearchTEProcessData class file.
 *
 * PHP version 5.3
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
 * Inheritance ResearchTEProcessData -> ProcessData
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
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_RESEARCH_TE;

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
     * @param int $researchedBpID of the Blueprint being researched
     * @param int $researchTime the time the process takes
     * @param float $processCost the cost of performing this reseach process
     * @param int $startTELevel the initial TE level of the Blueprint
     * @param int $endTELevel the TE level of the Blueprint after the research
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     *
     * @return \iveeCore\ResearchTEProcessData
     */
    public function __construct($researchedBpID, $researchTime, $processCost, $startTELevel, $endTELevel,
        $solarSystemID, $assemblyLineID
    ) {
        parent::__construct($researchedBpID, 1, $researchTime, $processCost);
        $this->startTELevel   = (int) $startTELevel;
        $this->endTELevel     = (int) $endTELevel;
        $this->solarSystemID  = (int) $solarSystemID;
        $this->assemblyLineID = (int) $assemblyLineID;
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
