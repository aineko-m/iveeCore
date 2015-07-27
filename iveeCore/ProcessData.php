<?php
/**
 * ProcessData class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessData.php
 */

namespace iveeCore;

/**
 * ProcessData is the base class for holding information about an industrial process. This class has not been made
 * abstract so it can be used to aggregate multiple ProcessData objects ("shopping cart" functionality).
 *
 * Note that some methods have special-casing for InventionProcessData objects. This is due to the design decision of
 * making "invention attempt" cases override the normal inherited methods while the "invention success" cases are
 * defined explicitly as new methods, which is less error prone.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessData.php
 */
class ProcessData
{
    //activity ID constants
    const ACTIVITY_MANUFACTURING = 1;
    const ACTIVITY_RESEARCH_TE   = 3;
    const ACTIVITY_RESEARCH_ME   = 4;
    const ACTIVITY_COPYING       = 5;
    const ACTIVITY_INVENTING     = 8;

    /**
     * @var int $activityId of this process.
     */
    protected $activityId = 0;

    /**
     * @var int $producesTypeId the resulting item of this process.
     */
    protected $producesTypeId;

    /**
     * @var int $producesQuantity the resulting quantity of this process.
     */
    protected $producesQuantity;

    /**
     * @var int $processTime the time this process takes in seconds.
     */
    protected $processTime = 0;

    /**
     * @var float $processCost the cost of performing this process (without subprocesses or material cost)
     */
    protected $processCost = 0;

    /**
     * @var int $assemblyLineId the type of AssemblyLine being used in this process
     */
    protected $assemblyLineId;

    /**
     * @var int $solarSystemId the ID of the SolarSystem the process is being performed
     */
    protected $solarSystemId;

    /**
     * @var \iveeCore\SkillMap $skills an object defining the minimum required skills to perform this activity.
     */
    protected $skills;

    /**
     * @var \iveeCore\MaterialMap $materials object holding required materials and amounts
     */
    protected $materials;

    /**
     * @var \iveeCore\ProcessData[] $subProcessData holds (recursive|sub) process objects.
     */
    protected $subProcessData;

    /**
     * Constructor.
     *
     * @param int $producesTypeId typeId of the item resulting from this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @param float $processCost the cost of performing this activity (without material cost or subprocesses)
     */
    public function __construct($producesTypeId = -1, $producesQuantity = 0, $processTime = 0, $processCost = 0.0)
    {
        $this->producesTypeId   = (int) $producesTypeId;
        $this->producesQuantity = (int) $producesQuantity;
        $this->processTime      = (int) $processTime;
        $this->processCost      = (float) $processCost;
    }

    /**
     * Add required material and amount to total material array.
     *
     * @param int $typeId of the material
     * @param int $amount of the material
     *
     * @return void
     */
    public function addMaterial($typeId, $amount)
    {
        if (!isset($this->materials)) {
            $materialClass = Config::getIveeClassName('MaterialMap');
            $this->materials = new $materialClass;
        }
        $this->getMaterialMap()->addMaterial($typeId, $amount);
    }

    /**
     * Add required skill to the total skill map.
     *
     * @param int $skillId of the skill
     * @param int $level of the skill
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if the skill level is not a valid integer between
     * 0 and 5
     */
    public function addSkill($skillId, $level)
    {
        if (!isset($this->skills)) {
            $skillClass = Config::getIveeClassName('SkillMap');
            $this->skills = new $skillClass;
        }
        $this->getSkillMap()->addSkill($skillId, $level);
    }

    /**
     * Add a skillMap to the required skills.
     *
     * @param \iveeCore\SkillMap $sm the SkillMap to add
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if a skill level is not a valid integer between
     * 0 and 5
     */
    public function addSkillMap(SkillMap $sm)
    {
        if (isset($this->skills))
            $this->skills->addSkillMap($sm);
        else {
            $this->skills = $sm;
        }
    }

    /**
     * Add sub-ProcessData object. This can be used to make entire build-trees or build batches.
     *
     * @param \iveeCore\ProcessData $subProcessData ProcessData object to add as a sub-process
     *
     * @return void
     */
    public function addSubProcessData(ProcessData $subProcessData)
    {
        if (!isset($this->subProcessData))
            $this->subProcessData = [];
        $this->subProcessData[] = $subProcessData;
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
     * Returns Type resulting from this process.
     *
     * @return \iveeCore\Type
     * @throws \iveeCore\Exceptions\NoOutputItemException if process results in no new item
     */
    public function getProducedType()
    {
        if ($this->producesTypeId < 0) {
            $exceptionClass = Config::getIveeClassName('NoOutputItemException');
            throw new $exceptionClass("This process results in no new item");
        } else {
            return Type::getById($this->producesTypeId);
        }
    }

    /**
     * Returns number of items resulting from this process.
     *
     * @return int
     */
    public function getNumProducedUnits()
    {
        return $this->producesQuantity;
    }

    /**
     * Returns all sub process data objects, if any.
     *
     * @return \iveeCore\ProcessData[]
     */
    public function getSubProcesses()
    {
        if (!isset($this->subProcessData))
            return [];
        return $this->subProcessData;
    }

    /**
     * Returns process cost, without subprocesses.
     *
     * @return float
     */
    public function getProcessCost()
    {
        return $this->processCost;
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
     * Returns ID of the AssemblyLine this process is performed in.
     *
     * @return int
     */
    public function getAssemblyLineTypeId()
    {
        return $this->assemblyLineId;
    }

    /**
     * Returns process cost (no materials), including subprocesses.
     *
     * @return float
     */
    public function getTotalProcessCost()
    {
        $sum = $this->getProcessCost();
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $sum += $subProcessData->getTotalSuccessProcessCost();
            else
                $sum += $subProcessData->getTotalProcessCost();
        }
        return $sum;
    }

    /**
     * Returns material buy cost, without subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost(IndustryModifier $iMod)
    {
        if (!isset($this->materials))
            return 0;
        return $this->getMaterialMap()->getMaterialBuyCost($iMod);
    }

    /**
     * Returns material buy cost, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalMaterialBuyCost(IndustryModifier $iMod)
    {
        $sum = $this->getMaterialBuyCost($iMod);
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $sum += $subProcessData->getTotalSuccessMaterialBuyCost($iMod);
            else
                $sum += $subProcessData->getTotalMaterialBuyCost($iMod);
        }
        return $sum;
    }

    /**
     * Returns total cost, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost(IndustryModifier $iMod)
    {
        return $this->getTotalProcessCost() + $this->getTotalMaterialBuyCost($iMod);
    }

    /**
     * Returns required materials object for this process, WITHOUT sub-processes. Will return an empty new MaterialMap
     * object if this has none.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getMaterialMap()
    {
        if (isset($this->materials)) {
            return $this->materials;
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
        $materialsClass = Config::getIveeClassName('MaterialMap');
        $tmat = new $materialsClass;
        if (isset($this->materials))
            $tmat->addMaterialMap($this->getMaterialMap());
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $tmat->addMaterialMap($subProcessData->getTotalSuccessMaterialMap());
            else
                $tmat->addMaterialMap($subProcessData->getTotalMaterialMap());
        }
        return $tmat;
    }

    /**
     * Returns the volume of the process materials, without sub-processes.
     *
     * @return float
     */
    public function getMaterialVolume()
    {
        if (!isset($this->materials))
            return 0;
        return $this->getMaterialMap()->getMaterialVolume();
    }

    /**
     * Returns the volume of the process materials, including sub-processes.
     *
     * @return float
     */
    public function getTotalMaterialVolume()
    {
        $sum = $this->getMaterialVolume();
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $sum += $subProcessData->getTotalSuccessMaterialVolume();
            else
                $sum += $subProcessData->getTotalMaterialVolume();
        }
        return $sum;
    }

    /**
     * Returns object defining the minimum skills required for this process, without sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getSkillMap()
    {
        if (isset($this->skills))
            return $this->skills;
        else {
            $skillClass = Config::getIveeClassName('SkillMap');
            return new $skillClass;
        }
    }

    /**
     * Returns a new object with all skills required, including sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getTotalSkillMap()
    {
        $skillClass = Config::getIveeClassName('SkillMap');
        $tskills =  new $skillClass;
        if (isset($this->skills))
            $tskills->addSkillMap($this->getSkillMap());
        foreach ($this->getSubProcesses() as $subProcessData)
            $tskills->addSkillMap($subProcessData->getTotalSkillMap());

        return $tskills;
    }

    /**
     * Returns the time for this process, in seconds, without sub-processes.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->processTime;
    }

    /**
     * Returns sum of all times, in seconds, including sub-processes.
     *
     * @return int|float
     */
    public function getTotalTime()
    {
        $sum = $this->getTime();
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $sum += $subProcessData->getTotalSuccessTime();
            else
                $sum += $subProcessData->getTotalTime();
        }
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
            static::ACTIVITY_RESEARCH_TE => 0.0,
            static::ACTIVITY_RESEARCH_ME => 0.0,
            static::ACTIVITY_COPYING => 0.0,
            static::ACTIVITY_INVENTING => 0.0
        );

        if ($this->processTime > 0)
            $sum[$this->activityId] = $this->processTime;

        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                foreach ($subProcessData->getTotalSuccessTimes() as $activityId => $time)
                    $sum[$activityId] += $time;
            else
                foreach ($subProcessData->getTotalTimes() as $activityId => $time)
                    $sum[$activityId] += $time;
        }
        return $sum;
    }

    /**
     * Returns total profit for this batch (direct child ManufactureProcessData sub-processes).
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getTotalProfit(IndustryModifier $iMod)
    {
        $sum = 0;
        foreach ($this->getSubProcesses() as $spd)
            if ($spd instanceof ManufactureProcessData)
                $sum += $spd->getTotalProfit($iMod);

        return $sum;
    }

    /**
     * Prints data about this process.
     *
     * @param \iveeCore\IndustryModifier $iMod for industry context
     *
     * @return void
     */
    public function printData(IndustryModifier $iMod)
    {
        $utilClass = Config::getIveeClassName('Util');
        echo "Total slot time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterialMap()->getMaterials() as $typeId => $amount)
            echo $amount . 'x ' . Type::getById($typeId)->getName() . PHP_EOL;

        echo "Material cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost($iMod)) . "ISK" . PHP_EOL;
        echo "Slot cost: "     . $utilClass::quantitiesToReadable($this->getTotalProcessCost()) . "ISK" . PHP_EOL;
        echo "Total cost: "    . $utilClass::quantitiesToReadable($this->getTotalCost($iMod)) . "ISK" . PHP_EOL;
        echo "Total profit: "  . $utilClass::quantitiesToReadable($this->getTotalProfit($iMod)) . "ISK" . PHP_EOL;
    }
}
