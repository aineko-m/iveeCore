<?php
/**
 * ReverseEngineerProcessData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReverseEngineerProcessData.php
 *
 */

namespace iveeCore;

/**
 * Holds data about reverse engineering processes.
 * Inheritance: ReverseEngineerProcessData -> InventionProcessData -> ProcessData.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReverseEngineerProcessData.php
 *
 */
class ReverseEngineerProcessData extends InventionProcessData
{
    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_REVERSE_ENGINEERING;

    /**
     * Constructor.
     *
     * @param int $reverseEngineeredBpID typeID of the reverse engineered blueprint
     * @param int $reverseEngineeringTime the reverse engineering takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param float $reverseEngineeringProbability chance of success for reverse engineering
     * @param int $resultRuns the number of runs on the resulting T3 BPC if reverse engineering is successful
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     * @param int $teamID the ID of the Team being used, if at all
     *
     * @return ReverseEngineeringProcessData
     */
    public function __construct($reverseEngineeredBpID, $reverseEngineeringTime, $processCost,
        $reverseEngineeringProbability, $resultRuns, $solarSystemID, $assemblyLineID, $teamID = null
    ) {
        parent::__construct(
            $reverseEngineeredBpID,
            $reverseEngineeringTime,
            $processCost,
            $reverseEngineeringProbability,
            $resultRuns,
            0,
            0,
            $solarSystemID,
            $assemblyLineID,
            $teamID
        );
    }
}
