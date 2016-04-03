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
 * Inheritance: InventionProcessData -> ProcessData -> ProcessDataCommon.
 *
 * Note the design decision of making the "invention success" cases override the inherited methods while the "invention
 * attemp" methods are re-implemented explicitly with "Attemp" in the method name. This simplifies ProcessData trees,
 * moving all special casing from other ProcessData classes to this one.
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
     * @var int $resultRuns the number of runs on the resulting T2/T3 BPC if invention is successful
     */
    protected $resultRuns;

    /**
     * @var int $resultME the ME level on the resulting T2/T3 BPC if invention is successful
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
     * @param int $inventTime the invention attempt takes in seconds
     * @param float $processCost the cost of performing this reseach process
     * @param float $probability  chance of success for invention
     * @param int $resultRuns the number of runs on the resulting T2/T3 BPC if invention is successful
     * @param int $resultME the ME level on the resulting T2/T3 BPC if invention is successful
     * @param int $resultTE the TE level on the resulting T2/T3 BPC if invention is successful
     * @param int $solarSystemId ID of the SolarSystem the research is performed
     * @param int $assemblyLineId ID of the AssemblyLine where the research is being performed
     */
    public function __construct(
        $inventedBpId,
        $inventTime,
        $processCost,
        $probability,
        $resultRuns,
        $resultME,
        $resultTE,
        $solarSystemId,
        $assemblyLineId
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
     * Returns the number of runs on the resulting T2/T3 BPC if invention is successful.
     *
     * @return int
     */
    public function getResultRuns()
    {
        return $this->resultRuns;
    }

    /**
     * Returns the ME level on the resulting T2/T3 BPC if invention is successful.
     *
     * @return int
     */
    public function getResultME()
    {
        return $this->resultME;
    }

    /**
     * Returns the TE level on the resulting T2/T3 BPC if invention is successful.
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
     * Returns the time per invention attempt, without sub-processes.
     *
     * @return float
     */
    public function getAttemptTime()
    {
        return $this->processTime;
    }

    /**
     * Returns the average time until invention success, without sub-processes.
     *
     * @return float
     */
    public function getTime()
    {
        return $this->getAttemptTime() / $this->probability;
    }

    /**
     * Returns sum of all times per invention attempt, in seconds, including sub-processes.
     *
     * @return int|float
     */
    public function getTotalAttemptTime()
    {
        $sum = $this->getAttemptTime();
        foreach ($this->getSubProcesses() as $subProcessData) {
            $sum += $subProcessData->getTotalTime();
        }

        return $sum;
    }

    /**
     * Returns the average time until invention success, including sub-processes.
     *
     * @return float
     */
    public function getTotalTime()
    {
        return $this->getTotalAttemptTime() / $this->probability;
    }

    /**
     * Returns array with process times per invention attempt, summed by activity, in seconds, including sub-processes.
     *
     * @return float[] in the form activityId => float
     */
    public function getTotalAttemptTimes()
    {
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0.0,
            static::ACTIVITY_RESEARCH_TE   => 0.0,
            static::ACTIVITY_RESEARCH_ME   => 0.0,
            static::ACTIVITY_COPYING       => 0.0,
            static::ACTIVITY_INVENTING     => 0.0,
            static::ACTIVITY_REACTING      => 0.0
        );

        $sum[$this->getActivityId()] = $this->getAttemptTime();

        foreach ($this->getSubProcesses() as $subProcessData) {
            foreach ($subProcessData->getTotalTimes() as $activityId => $time) {
                $sum[$activityId] += $time;
            }
        }

        return $sum;
    }

    /**
     * Returns array with sum of average time until invention success, grouped by activity, including sub-processes.
     *
     * @return float[]
     */
    public function getTotalTimes()
    {
        $sum = $this->getTotalAttemptTimes();
        foreach ($sum as $activityId => $time) {
            $sum[$activityId] = $time / $this->probability;
        }

        return $sum;
    }

    /**
     * Returns a clone of the MaterialMap for this process, representing an invention attemp, WITHOUT sub-processes.
     * Will return an empty new MaterialMap object if this process has no material requirements.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getAttemptMaterialMap()
    {
        if (isset($this->materials)) {
            return clone $this->materials; //we return a clone so the original doesn't get altered
        } else {
            $materialClass = Config::getIveeClassName('MaterialMap');
            return new $materialClass;
        }
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, without sub-processes.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getMaterialMap()
    {
        return $this->getAttemptMaterialMap()->multiply(1 / $this->probability);
    }

    /**
     * Returns MaterialMap object with required materials per invention attempt, including sub-processes.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getTotalAttemptMaterialMap()
    {
        $smat = $this->getAttemptMaterialMap();
        foreach ($this->getSubProcesses() as $subProcessData) {
            $smat->addMaterialMap($subProcessData->getTotalMaterialMap());
        }

        return $smat;
    }

    /**
     * Returns MaterialMap object with average required materials until invention success, including sub-processes.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getTotalMaterialMap()
    {
        return $this->getTotalAttemptMaterialMap()->multiply(1 / $this->probability);
    }

    /**
     * Returns process cost per invention attempt, without subprocesses.
     *
     * @return float
     */
    public function getAttemptProcessCost()
    {
        return $this->processCost;
    }

    /**
     * Returns average invention slot cost until success, without subprocesses.
     *
     * @return float
     */
    public function getProcessCost()
    {
        return $this->getAttemptProcessCost() / $this->probability;
    }

    /**
     * Returns average total slot cost until success, including subprocesses.
     *
     * @return float
     */
    public function getTotalProcessCost()
    {
        return $this->getTotalAttemptProcessCost() / $this->probability;
    }

    /**
     * Returns process cost (no materials) per invention attempt, including subprocesses.
     *
     * @return float
     */
    public function getTotalAttemptProcessCost()
    {
        $sum = $this->getAttemptProcessCost();
        foreach ($this->getSubProcesses() as $subProcessData) {
            $sum += $subProcessData->getTotalProcessCost();
        }

        return $sum;
    }

    /**
     * Returns total cost per invention attempt, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $buyContext for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalAttemptCost(IndustryModifier $buyContext)
    {
        return $this->getTotalAttemptProcessCost()
            + $this->getTotalAttemptMaterialMap()->getMaterialBuyCost($buyContext);
    }

    /**
     * Returns total average cost until success, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for market context
     *
     * @return float
     */
    public function getTotalCost(IndustryModifier $iMod)
    {
        return $this->getTotalAttemptCost($iMod) / $this->probability;
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
        print_r($this->getTotalTimes());

        echo "Average total success materials:" . PHP_EOL;
        foreach ($this->getTotalMaterialMap()->getMaterials() as $typeId => $amount) {
            echo $amount . 'x ' . Type::getById($typeId)->getName() . PHP_EOL;
        }

        echo "Total average success material cost: "
        . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost($buyContext)) . "ISK" . PHP_EOL;
        echo "Total average success slot cost: "
        . $utilClass::quantitiesToReadable($this->getTotalProcessCost()) . "ISK" . PHP_EOL;
        echo "Total average success cost: "
        . $utilClass::quantitiesToReadable($this->getTotalCost($buyContext)) . "ISK" . PHP_EOL;
        echo "Total profit: "
        . $utilClass::quantitiesToReadable($this->getTotalProfit($buyContext, $sellContext)) . "ISK" . PHP_EOL;
    }
}
