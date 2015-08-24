<?php
/**
 * InventionProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventionProcessData.php
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
 */
class InventionProcessData extends ProcessData
{
    /**
     * @var int $activityId of this process.
     */
    protected $activityId = self::ACTIVITY_INVENTING;

    /**
     * @var float $probability chance of success for invention.
     */
    protected $probability;

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
     * @param int $inventedBpId typeId of the invented blueprint
     * @param int $inventTime the invention takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param float $probability  chance of success for invention
     * @param int $resultRuns the number of runs on the resulting T2 BPC if invention is successful
     * @param int $resultME the ME level on the resulting T2 BPC if invention is successful
     * @param int $resultTE the TE level on the resulting T2 BPC if invention is successful
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int $assemblyLineId ID of the AssemblyLine where the research is being performed
     */
    public function __construct($inventedBpId, $inventTime, $processCost, $probability, $resultRuns,
        $resultME, $resultTE, $solarSystemId, $assemblyLineId
    ) {
        parent::__construct($inventedBpId, 1, $inventTime, $processCost);
        $this->probability     = (float) $probability;
        $this->resultRuns      = (int) $resultRuns;
        $this->resultME        = (int) $resultME;
        $this->resultTE        = (int) $resultTE;
        $this->solarSystemId   = (int) $solarSystemId;
        $this->assemblyLineId  = (int) $assemblyLineId;
    }

    /**
     * Returns the number of runs on the resulting T2 BPC if invention is successful.
     *
     * @return int
     */
    public function getResultRuns()
    {
        return $this->resultRuns;
    }

    /**
     * Returns the ME level on the resulting T2 BPC if invention is successful.
     *
     * @return int
     */
    public function getResultME()
    {
        return $this->resultME;
    }

    /**
     * Returns the TE level on the resulting T2 BPC if invention is successful.
     *
     * @return int
     */
    public function getResultTE()
    {
        return $this->resultTE;
    }

    /**
     * Returns the chance of success for the invention.
     *
     * @return float
     */
    public function getProbability()
    {
        return $this->probability;
    }

    /**
     * Returns the average time until invention success, without sub-processes.
     *
     * @return float
     */
    public function getSuccesTime()
    {
        return $this->getTime() / $this->probability;
    }

    /**
     * Returns the average time until invention success, including sub-processes.
     *
     * @return float
     */
    public function getTotalSuccessTime()
    {
        return $this->getTotalTime() / $this->probability;
    }

    /**
     * Returns array with sum of average time until invention success, grouped by activity, including sub-processes.
     *
     * @return float[]
     */
    public function getTotalSuccessTimes()
    {
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0.0,
            static::ACTIVITY_RESEARCH_TE => 0.0,
            static::ACTIVITY_RESEARCH_ME => 0.0,
            static::ACTIVITY_COPYING => 0.0,
            static::ACTIVITY_INVENTING => 0.0
        );

        $sum[$this->activityId] = $this->processTime / $this->probability;

        foreach ($this->getSubProcesses() as $subProcessData)
            foreach ($subProcessData->getTotalTimes() as $activityId => $time)
                $sum[$activityId] += $time / $this->probability;

        return $sum;
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, without sub-processes.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getSuccessMaterialMap()
    {
        $materialsClass = Config::getIveeClassName('MaterialMap');
        $smat = new $materialsClass;
        if (isset($this->materials))
            foreach ($this->materials->getMaterials() as $typeId => $quantity)
                $smat->addMaterial($typeId, $quantity / $this->probability);

        return $smat;
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, including sub-processes.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getTotalSuccessMaterialMap()
    {
        $smat = $this->getSuccessMaterialMap();
        foreach ($this->getSubProcesses() as $subProcessData)
            foreach ($subProcessData->getTotalMaterialMap()->getMaterials() as $typeId => $quantity)
                $smat->addMaterial($typeId, $quantity / $this->probability);

        return $smat;
    }

    /**
     * Returns volume of average required materials until invention success, without sub-processes.
     *
     * @return float volume
     */
    public function getSuccessMaterialVolume()
    {
        return $this->getMaterialVolume() / $this->probability;
    }

    /**
     * Returns volume of average required materials until invention success, including sub-processes.
     *
     * @return float volume
     */
    public function getTotalSuccessMaterialVolume()
    {
        return $this->getTotalMaterialVolume() / $this->probability;
    }

    /**
     * Returns average invention slot cost until success, without subprocesses.
     *
     * @return float
     */
    public function getSuccessProcessCost()
    {
        return $this->getProcessCost() / $this->probability;
    }

    /**
     * Returns average total slot cost until success, including subprocesses.
     *
     * @return float
     */
    public function getTotalSuccessProcessCost()
    {
        return $this->getTotalProcessCost() / $this->probability;
    }

    /**
     * Returns average material cost until success, without subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     */
    public function getSuccessMaterialBuyCost(IndustryModifier $iMod)
    {
        return $this->getMaterialBuyCost($iMod) / $this->probability;
    }

    /**
     * Returns average material cost until success, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     */
    public function getTotalSuccessMaterialBuyCost(IndustryModifier $iMod)
    {
        return $this->getTotalMaterialBuyCost($iMod) / $this->probability;
    }

    /**
     * Returns total average cost until success, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     */
    public function getTotalSuccessCost(IndustryModifier $iMod)
    {
        return $this->getTotalCost($iMod) / $this->probability;
    }

    /**
     * Prints data about this process.
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext ist used.
     *
     * @return void
     */
    public function printData(IndustryModifier $buyContext, IndustryModifier $sellContext = null)
    {
        $utilClass = Config::getIveeClassName('Util');

        echo "Average total success times:" . PHP_EOL;
        print_r($this->getTotalSuccessTimes());

        echo "Average total success materials:" . PHP_EOL;
        foreach ($this->getTotalSuccessMaterialMap()->getMaterials() as $typeId => $amount) {
            echo $amount . 'x ' . Type::getById($typeId)->getName() . PHP_EOL;
        }

        echo "Total average success material cost: "
        . $utilClass::quantitiesToReadable($this->getTotalSuccessMaterialBuyCost($buyContext)) . "ISK" . PHP_EOL;
        echo "Total average success slot cost: "
        . $utilClass::quantitiesToReadable($this->getTotalSuccessProcessCost()) . "ISK" . PHP_EOL;
        echo "Total average success cost: "
        . $utilClass::quantitiesToReadable($this->getTotalSuccessCost($buyContext)) . "ISK" . PHP_EOL;
        echo "Total profit: "
        . $utilClass::quantitiesToReadable($this->getTotalProfit($buyContext, $sellContext)) . "ISK" . PHP_EOL;
    }
}
