<?php
/**
 * ProcessDataCommon class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessDataCommon.php
 */

namespace iveeCore;

/**
 * ProcessDataCommon provides some basic functionality shared by classes implementing the IProcessData interface.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessDataCommon.php
 */
abstract class ProcessDataCommon implements IProcessData
{
    /**
     * @var int $activityId of this process.
     */
    protected $activityId = 0;

    /**
     * @var int $solarSystemId the ID of the SolarSystem the process is being performed
     */
    protected $solarSystemId;

    /**
     * @var \iveeCore\IProcessData[] $subProcessData holds (recursive|sub) process objects.
     */
    protected $subProcessData;

    /**
     * @var \iveeCore\MaterialMap $materials object holding required materials and amounts
     */
    protected $materials;

    /**
     * Add sub-ProcessData object. This can be used to make entire build-trees or build batches.
     *
     * @param \iveeCore\IProcessData $subProcessData IProcessData object to add as a sub-process
     *
     * @return void
     */
    public function addSubProcessData(IProcessData $subProcessData)
    {
        if (!isset($this->subProcessData))
            $this->subProcessData = [];
        $this->subProcessData[] = $subProcessData;
    }

    /**
     * Returns all sub process data objects, if any.
     *
     * @return \iveeCore\IProcessData[]
     */
    public function getSubProcesses()
    {
        if (!isset($this->subProcessData))
            return [];
        return $this->subProcessData;
    }

    /**
     * Returns the activityId of the process.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * Returns ID of the SolarSystem this process is performed in.
     *
     * @return int
     */
    public function getSolarSystemId()
    {
        return $this->solarSystemId;
    }

    /**
     * Returns a clone of the MaterialMap for this process, WITHOUT sub-processes. Will return an empty new MaterialMap
     * object if this process has no material requirements.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getMaterialMap()
    {
        if (isset($this->materials)) {
            return clone $this->materials; //we return a clone so the original doesn't get altered
        } else {
            $materialClass = Config::getIveeClassName('MaterialMap');
            return new $materialClass;
        }
    }

    /**
     * Returns a new MaterialMap object containing all required materials, including sub-processes.
     * Note that material quantities might be fractionary, due to invention chance effects or requesting builds of items
     * in numbers that are not multiple of portionSize.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getTotalMaterialMap()
    {
        $tmat = $this->getMaterialMap();
        foreach ($this->getSubProcesses() as $subProcessData)
            $tmat->addMaterialMap($subProcessData->getTotalMaterialMap());

        return $tmat;
    }

    /**
     * Returns a new object with all skills required, including sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getTotalSkillMap()
    {
        $tskills = $this->getSkillMap();
        foreach ($this->getSubProcesses() as $subProcessData)
            $tskills->addSkillMap($subProcessData->getTotalSkillMap());

        return $tskills;
    }

    /**
     * Returns process cost (no materials), including subprocesses.
     *
     * @return float
     */
    public function getTotalProcessCost()
    {
        $sum = $this->getProcessCost();
        foreach ($this->getSubProcesses() as $subProcessData)
            $sum += $subProcessData->getTotalProcessCost();

        return $sum;
    }

    /**
     * Returns material buy cost, without subprocesses.
     *
     * @param \iveeCore\IndustryModifier $buyContext for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost(IndustryModifier $buyContext)
    {
        if (!isset($this->materials))
            return 0;
        return $this->materials->getMaterialBuyCost($buyContext);
    }

    /**
     * Returns material buy cost, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $buyContext for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalMaterialBuyCost(IndustryModifier $buyContext)
    {
        $sum = $this->getMaterialBuyCost($buyContext);
        foreach ($this->getSubProcesses() as $subProcessData)
            $sum += $subProcessData->getTotalMaterialBuyCost($buyContext);

        return $sum;
    }

    /**
     * Returns total cost, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $buyContext for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost(IndustryModifier $buyContext)
    {
        return $this->getTotalProcessCost() + $this->getTotalMaterialBuyCost($buyContext);
    }

    /**
     * Returns sum of all times, in seconds, including sub-processes.
     *
     * @return int|float
     */
    public function getTotalTime()
    {
        $sum = $this->getTime();
        foreach ($this->getSubProcesses() as $subProcessData)
            $sum += $subProcessData->getTotalTime();

        return $sum;
    }

    /**
     * Returns array with process times summed by activity, in seconds, including sub-processes.
     *
     * @return float[] in the form activityId => float
     */
    public function getTotalTimes()
    {
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0.0,
            static::ACTIVITY_RESEARCH_TE   => 0.0,
            static::ACTIVITY_RESEARCH_ME   => 0.0,
            static::ACTIVITY_COPYING       => 0.0,
            static::ACTIVITY_INVENTING     => 0.0,
            static::ACTIVITY_REACTING      => 0.0
        );

        $sum[$this->getActivityId()] = $this->getTime();

        foreach ($this->getSubProcesses() as $subProcessData)
            foreach ($subProcessData->getTotalTimes() as $activityId => $time)
                $sum[$activityId] += $time;

        return $sum;
    }
}
