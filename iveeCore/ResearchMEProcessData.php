<?php
/**
 * ResearchMEProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ResearchMEProcessData.php
 */

namespace iveeCore;

/**
 * ResearchMEProcessData holds data about ME research processes.
 * Inheritance ResearchMEProcessData -> ProcessData
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ResearchMEProcessData.php
 */
class ResearchMEProcessData extends ProcessData
{
    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_RESEARCH_ME;

    /**
     * @var int $startMELevel the initial ME level of the Blueprint
     */
    protected $startMELevel;

    /**
     * @var int $endMELevel the ME level of the Blueprint after the research
     */
    protected $endMELevel;

    /**
     * Constructor.
     *
     * @param int $researchedBpID of the Blueprint being researched
     * @param int $researchTime the time the process takes
     * @param float $processCost the cost of performing this reseach process
     * @param int $startMELevel the initial ME level of the Blueprint
     * @param int $endMELevel the ME level of the Blueprint after the research
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     */
    public function __construct($researchedBpID, $researchTime, $processCost, $startMELevel, $endMELevel,
        $solarSystemID, $assemblyLineID
    ) {
        parent::__construct($researchedBpID, 1, $researchTime, $processCost);
        $this->startMELevel   = (int) $startMELevel;
        $this->endMELevel     = (int) $endMELevel;
        $this->solarSystemID  = (int) $solarSystemID;
        $this->assemblyLineID = (int) $assemblyLineID;
    }

    /**
     * Returns the initial ME level of the Blueprint.
     *
     * @return int
     */
    public function getStartMELevel()
    {
        return $this->startMELevel;
    }

    /**
     * Returns the ME level of the Blueprint after the research.
     *
     * @return int
     */
    public function getEndMELevel()
    {
        return $this->endMELevel;
    }
}
