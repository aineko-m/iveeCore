<?php
/**
 * CopyProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CopyProcessData.php
 */

namespace iveeCore;

/**
 * Class for holding copy process data.
 * Inheritance: CopyProcessData -> ProcessData.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CopyProcessData.php
 */
class CopyProcessData extends ProcessData
{
    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_COPYING;

    /**
     * @var int $outputRuns per copy.
     */
    protected $outputRuns;

    /**
     * Constructor.
     *
     * @param int $bpCopyID typeID of the blueprint being copied
     * @param int $copyQuantity the number of copies being made
     * @param int $outputRuns the number of runs per copy
     * @param int $copyTime the time the copy process takes
     * @param float $processCost the cost of performing this reseach process
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     */
    public function __construct($bpCopyID, $copyQuantity, $outputRuns, $copyTime, $processCost, $solarSystemID,
        $assemblyLineID
    ) {
        parent::__construct($bpCopyID, $copyQuantity, $copyTime, $processCost);
        $this->outputRuns     = (int) $outputRuns;
        $this->solarSystemID  = (int) $solarSystemID;
        $this->assemblyLineID = (int) $assemblyLineID;
    }

    /**
     * Returns the number of runs per copy.
     *
     * @return int
     */
    public function getOutputRuns()
    {
        return $this->outputRuns;
    }
}
