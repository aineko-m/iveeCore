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
 * Inheritance: CopyProcessData -> ProcessData -> ProcessDataCommon.
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
     * @var int $activityId of this process.
     */
    protected $activityId = self::ACTIVITY_COPYING;

    /**
     * @var int $outputRuns per copy.
     */
    protected $outputRuns;

    /**
     * Constructor.
     *
     * @param int $bpCopyId ID of the blueprint being copied
     * @param int $copyQuantity the number of copies being made
     * @param int $outputRuns the number of runs per copy
     * @param int $copyTime the time the copy process takes
     * @param float $processCost the cost of performing this reseach process
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int $assemblyLineId ID of the AssemblyLine where the research is being performed
     */
    public function __construct(
        $bpCopyId,
        $copyQuantity,
        $outputRuns,
        $copyTime,
        $processCost,
        $solarSystemId,
        $assemblyLineId
    ) {
        parent::__construct($bpCopyId, $copyQuantity, $copyTime, $processCost);
        $this->outputRuns     = (int) $outputRuns;
        $this->solarSystemId  = (int) $solarSystemId;
        $this->assemblyLineId = (int) $assemblyLineId;
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
