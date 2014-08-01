<?php
/**
 * InventionProcessData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventionProcessData.php
 *
 */

namespace iveeCore;

/**
 * Holds data about invention processes.
 * Inheritance: InventionProcessData -> ProcessData.
 *
 * Note the design decision of making "invention attempt" cases override the normal inherited methods while the
 * "invention success" cases are defined explicitly in new methods. This is less error error prone than the other way
 * round.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventionProcessData.php
 *
 */
class InventionProcessData extends ProcessData
{
    /**
     * @var int $activityID of this process.
     */
    protected $activityID = self::ACTIVITY_INVENTING;

    /**
     * @var float $inventionChance chance of success for invention.
     */
    protected $inventionChance;

    /**
     * @var int $resultRuns the number of runs on the resulting T2 BPC if invention is successful
     */
    protected $resultRuns;

    /**
     * @var int $resultME the ME level on the resulting T2 BPC if invention is successful
     */
    protected $resultME;

    /**
     * @var int $resultPE PE level on the resulting T2 BPC if invention is successful
     */
    protected $resultTE;

    /**
     * Constructor.
     * 
     * @param int $inventedBpID typeID of the inveted blueprint
     * @param int $inventTime the invention takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param float $inventionChance  chance of success for invention
     * @param int $resultRuns the number of runs on the resulting T2 BPC if invention is successful
     * @param int $resultME the ME level on the resulting T2 BPC if invention is successful
     * @param int $resultTE the TE level on the resulting T2 BPC if invention is successful
     * @param int $solarSystemID ID of the SolarSystem the research is performed
     * @param int $assemblyLineID ID of the AssemblyLine where the research is being performed
     * @param int $teamID the ID of the Team being used, if at all
     * 
     * @return ProcessData
     */
    public function __construct($inventedBpID, $inventTime, $processCost, $inventionChance, $resultRuns,
        $resultME, $resultTE, $solarSystemID, $assemblyLineID, $teamID = null
    ) {
        parent::__construct($inventedBpID, 1, $inventTime, $processCost);
        $this->inventionChance = (float) $inventionChance;
        $this->resultRuns      = (int) $resultRuns;
        $this->resultME        = (int) $resultME;
        $this->resultTE        = (int) $resultTE;
        $this->solarSystemID   = (int) $solarSystemID;
        $this->assemblyLineID  = (int) $assemblyLineID;
        if (isset($teamID))
            $this->teamID      = (int) $teamID;
    }

    /**
     * Returns the number of runs on the resulting T2 BPC if invention is successful
     * 
     * @return int
     */
    public function getResultRuns()
    {
        return $this->resultRuns;
    }

    /**
     * Returns the ME level on the resulting T2 BPC if invention is successful
     * 
     * @return int
     */
    public function getResultME()
    {
        return $this->resultME;
    }

    /**
     * Returns the TE level on the resulting T2 BPC if invention is successful
     * 
     * @return int
     */
    public function getResultTE()
    {
        return $this->resultTE;
    }

    /**
     * Returns the chance of success for the invention
     * 
     * @return float
     */
    public function getInventionChance()
    {
        return $this->inventionChance;
    }

    /**
     * Returns the average time until invention success, without sub-processes
     * 
     * @return float
     */
    public function getSuccesTime()
    {
        return $this->getTime() / $this->inventionChance;
    }

    /**
     * Returns the average time until invention success, including sub-processes
     * 
     * @return float
     */
    public function getTotalSuccessTime()
    {
        return $this->getTotalTime() / $this->inventionChance;
    }

    /**
     * Returns array with sum of average time until invention success, grouped by activity, including sub-processes
     * 
     * @return array
     */
    public function getTotalSuccessTimes()
    {
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0,
            static::ACTIVITY_COPYING => 0,
            static::ACTIVITY_INVENTING => 0
        );

        $sum[$this->activityID] = $this->processTime / $this->inventionChance;

        foreach ($this->getSubProcesses() as $subProcessData)
            foreach ($subProcessData->getTotalTimes() as $activityID => $time)
                $sum[$activityID] += $time / $this->inventionChance;

        return $sum;
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, without sub-processes
     * 
     * @return MaterialMap
     */
    public function getSuccessMaterialMap()
    {
        $materialsClass = Config::getIveeClassName('MaterialMap');
        $smat = new $materialsClass;
        if (isset($this->materials))
            foreach ($this->getMaterialMap()->getMaterials() as $typeID => $quantity)
                $smat->addMaterial($typeID, $quantity / $this->inventionChance);

        return $smat;
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, including sub-processes
     * 
     * @return MaterialMap
     */
    public function getTotalSuccessMaterialMap()
    {
        $smat = $this->getSuccessMaterialMap();
        foreach ($this->getSubProcesses() as $subProcessData)
            foreach ($subProcessData->getTotalMaterialMap()->getMaterials() as $typeID => $quantity)
                $smat->addMaterial($typeID, $quantity / $this->inventionChance);

        return $smat;
    }

    /**
     * Returns volume of average required materials until invention success, without sub-processes
     * 
     * @return float volume
     */
    public function getSuccessMaterialVolume()
    {
        return $this->getMaterialVolume() / $this->inventionChance;
    }

    /**
     * Returns volume of average required materials until invention success, including sub-processes
     * 
     * @return float volume
     */
    public function getTotalSuccessMaterialVolume()
    {
        return $this->getTotalMaterialVolume() / $this->inventionChance;
    }

    /**
     * Returns average invention slot cost until success, without subprocesses
     * 
     * @return float
     */
    public function getSuccessProcessCost()
    {
        return $this->getProcessCost() / $this->inventionChance;
    }

    /**
     * Returns average total slot cost until success, including subprocesses
     * 
     * @return float
     */
    public function getTotalSuccessProcessCost()
    {
        return $this->getTotalProcessCost() / $this->inventionChance;
    }

    /**
     * Returns average material cost until success, without subprocesses
     * 
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * 
     * @return float
     */
    public function getSuccessMaterialBuyCost($maxPriceDataAge = null)
    {
        return $this->getMaterialBuyCost($maxPriceDataAge) / $this->inventionChance;
    }

    /**
     * Returns average material cost until success, including subprocesses
     * 
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * 
     * @return float
     */
    public function getTotalSuccessMaterialBuyCost($maxPriceDataAge = null)
    {
        return $this->getTotalMaterialBuyCost($maxPriceDataAge) / $this->inventionChance;
    }

    /**
     * Returns total average cost until success, including subprocesses
     * 
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * 
     * @return float
     */
    public function getTotalSuccessCost($maxPriceDataAge = null)
    {
        return $this->getTotalCost($maxPriceDataAge) / $this->inventionChance;
    }

    /**
     * Prints data about this process
     * 
     * @return void
     */
    public function printData()
    {
        $typeClass = Config::getIveeClassName('Type');
        $utilClass = Config::getIveeClassName('Util');

        echo "Average total success times:" . PHP_EOL;
        print_r($this->getTotalSuccessTimes());

        echo "Average total success materials:" . PHP_EOL;
        foreach ($this->getTotalSuccessMaterialMap()->getMaterials() as $typeID => $amount) {
            echo $amount . 'x ' . $typeClass::getType($typeID)->getName() . PHP_EOL;
        }

        echo "Total average success material cost: " 
        . $utilClass::quantitiesToReadable($this->getTotalSuccessMaterialBuyCost()) . "ISK" . PHP_EOL;
        echo "Total average success slot cost: " 
        . $utilClass::quantitiesToReadable($this->getTotalSuccessProcessCost()) . "ISK" . PHP_EOL;
        echo "Total average success cost: " 
        . $utilClass::quantitiesToReadable($this->getTotalSuccessCost()) . "ISK" . PHP_EOL;
        echo "Total profit: " 
        . $utilClass::quantitiesToReadable($this->getTotalProfit()) . "ISK" . PHP_EOL;
    }
}
