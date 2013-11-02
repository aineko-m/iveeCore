<?php

/**
 * Class for holding copy process data. 
 * Inheritance: CopyData -> ProcessData.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/CopyData.php
 * @package iveeCore
 */
class CopyData extends ProcessData {

    /**
     * @var int $activityID of this process.
     */
    protected $activity = self::ACTIVITY_COPYING;
    
    /**
     * @var int $outputRuns per copy.
     */
    protected $outputRuns;

    /**
     * Constructor.
     * @param int $BpCopyTypeID typeID of the blueprint being copied
     * @param int $copyQuantity the number of copies being made
     * @param int $copyTime the time the copy process takes
     * @param int $outputRuns the number of runs per copy
     * @return ProcessData
     */
    public function __construct($BpCopyTypeID, $copyQuantity, $copyTime, $outputRuns) {
        parent::__construct($BpCopyTypeID, $copyQuantity, $copyTime);
        $this->outputRuns = (int)$outputRuns;
    }
    
    /**
     * Returns slot cost, WITHOUT subprocesses
     * @return float
     */
    public function getSlotCost(){
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        return $this->processTime * (iveeCoreConfig::getUsePosCopying() ? 
            $utilClass::getPosSlotCostPerSecond() : iveeCoreConfig::getStationCopyingCostPerSecond());
    }
    
    /**
     * Returns the number of runs per copy
     * @return int
     */
    public function getOutputRuns(){
        return $this->outputRuns;
    }
}

?>