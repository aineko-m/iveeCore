<?php
/**
 * ProcessData class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessData.php
 *
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
 *
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
     * @var int $activityID of this process.
     */
    protected $activityID = 0;

    /**
     * @var int $producesTypeID the resulting item of this process.
     */
    protected $producesTypeID;

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
     * @var int $assemblyLineID the type of AssemblyLine being used in this process
     */
    protected $assemblyLineID;

    /**
     * @var int $solarSystemID the ID of the SolarSystem the process is being performed
     */
    protected $solarSystemID;

    /**
     * @var int $teamID the ID of the Team this process is using, if at all
     */
    protected $teamID;

    /**
     * @var SkillMap $skills an object defining the minimum required skills to perform this activity.
     */
    protected $skills;

    /**
     * @var MaterialMap $materials object holding required materials and amounts
     */
    protected $materials;

    /**
     * @var array $subProcessData holds (recursive|sub) ProcessData objects.
     */
    protected $subProcessData;

    /**
     * Constructor.
     *
     * @param int $producesTypeID typeID of the item resulting from this process
     * @param int $producesQuantity the number of produces items
     * @param int $processTime the time this process takes in seconds
     * @param float $processCost the cost of performing this activity (without material cost or subprocesses)
     *
     * @return \iveeCore\ProcessData
     */
    public function __construct($producesTypeID = -1, $producesQuantity = 0, $processTime = 0, $processCost = 0)
    {
        $this->producesTypeID   = (int) $producesTypeID;
        $this->producesQuantity = (int) $producesQuantity;
        $this->processTime      = (int) $processTime;
        $this->processCost      = (float) $processCost;
    }

    /**
     * Add required material and amount to total material array.
     *
     * @param int $typeID of the material
     * @param int $amount of the material
     *
     * @return void
     */
    public function addMaterial($typeID, $amount)
    {
        if (!isset($this->materials)) {
            $materialClass = Config::getIveeClassName('MaterialMap');
            $this->materials = new $materialClass;
        }
        $this->getMaterialMap()->addMaterial($typeID, $amount);
    }

    /**
     * Add required skill to the total skill map
     *
     * @param int $skillID of the skill
     * @param int $level of the skill
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if the skill level is not a valid integer between
     * 0 and 5
     */
    public function addSkill($skillID, $level)
    {
        if (!isset($this->skills)) {
            $skillClass = Config::getIveeClassName('SkillMap');
            $this->skills = new $skillClass;
        }
        $this->getSkillMap()->addSkill($skillID, $level);
    }

    /**
     * Add a skillMap to the required skills
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
     * Add sub-ProcessData object. This can be used to make entire build-trees or build batches
     *
     * @param \iveeCore\ProcessData $subProcessData ProcessData object to add as a sub-process
     *
     * @return void
     */
    public function addSubProcessData(ProcessData $subProcessData)
    {
        if (!isset($this->subProcessData))
            $this->subProcessData = array();
        $this->subProcessData[] = $subProcessData;
    }

    /**
     * Returns the activityID of the process
     *
     * @return int
     */
    public function getActivityID()
    {
        return $this->activityID;
    }

    /**
     * Returns Type resulting from this process
     *
     * @return \iveeCore\Type
     * @throws \iveeCore\Exceptions\NoOutputItemException if process results in no new item
     */
    public function getProducedType()
    {
        if ($this->producesTypeID < 0) {
            $exceptionClass = Config::getIveeClassName('NoOutputItemException');
            throw new $exceptionClass("This process results in no new item");
        } else {
            return Type::getById($this->producesTypeID);
        }
    }

    /**
     * Returns number of items resulting from this process
     *
     * @return int
     */
    public function getNumProducedUnits()
    {
        return $this->producesQuantity;
    }

    /**
     * Returns all sub process data objects, if any
     *
     * @return array with ProcessData objects
     */
    public function getSubProcesses()
    {
        if (!isset($this->subProcessData))
            return array();
        return $this->subProcessData;
    }

    /**
     * Returns process cost, without subprocesses
     *
     * @return float
     */
    public function getProcessCost()
    {
        return $this->processCost;
    }

    /**
     * Returns ID of the SolarSystem this process is performed in
     *
     * @return int
     */
    public function getSolarSystemID()
    {
        return $this->solarSystemID;
    }

    /**
     * Returns ID of the AssemblyLine this process is performed in
     *
     * @return int
     */
    public function getAssemblyLineTypeID()
    {
        return $this->assemblyLineID;
    }

    /**
     * Returns ID of the Team this process is using, if at all
     *
     * @return int|null
     */
    public function getTeamID()
    {
        return $this->teamID;
    }

    /**
     * Returns process cost (no materials), including subprocesses
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
     * Returns material buy cost, without subprocesses
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @param int $regionId of the market region to be used for price lookup. If none passed, default is are used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getMaterialBuyCost($maxPriceDataAge = null, $regionId = null)
    {
        if (!isset($this->materials))
            return 0;
        return $this->getMaterialMap()->getMaterialBuyCost($maxPriceDataAge, $regionId);
    }

    /**
     * Returns material buy cost, including subprocesses
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @param int $regionId of the market region to be used for price lookup. If none passed, default is are used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalMaterialBuyCost($maxPriceDataAge = null, $regionId = null)
    {
        $sum = $this->getMaterialBuyCost($maxPriceDataAge);
        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                $sum += $subProcessData->getTotalSuccessMaterialBuyCost($maxPriceDataAge, $regionId);
            else
                $sum += $subProcessData->getTotalMaterialBuyCost($maxPriceDataAge, $regionId);
        }
        return $sum;
    }

    /**
     * Returns total cost, including subprocesses
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     * @param int $regionId of the market region to be used for price lookup. If none passed, default is are used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost($maxPriceDataAge = null, $regionId = null)
    {
        return $this->getTotalProcessCost() + $this->getTotalMaterialBuyCost($maxPriceDataAge, $regionId);
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
     * in numbers that are not multiple of portionSize
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
     * Returns object defining the minimum skills required for this process, without sub-processes
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
     * Returns a new object with all skills required, including sub-processes
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
     * Returns the time for this process, in seconds, without sub-processes
     *
     * @return int
     */
    public function getTime()
    {
        return $this->processTime;
    }

    /**
     * Returns sum of all times, in seconds, including sub-processes
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
     * Returns array with process times summed by activity, in seconds, including sub-processes
     *
     * @return array in the form activityID => int
     */
    public function getTotalTimes()
    {
        $sum = array(
            static::ACTIVITY_MANUFACTURING => 0,
            static::ACTIVITY_RESEARCH_TE => 0,
            static::ACTIVITY_RESEARCH_ME => 0,
            static::ACTIVITY_COPYING => 0,
            static::ACTIVITY_INVENTING => 0
        );

        if ($this->processTime > 0)
            $sum[$this->activityID] = $this->processTime;

        foreach ($this->getSubProcesses() as $subProcessData) {
            if ($subProcessData instanceof InventionProcessData)
                foreach ($subProcessData->getTotalSuccessTimes() as $activityID => $time)
                    $sum[$activityID] += $time;
            else
                foreach ($subProcessData->getTotalTimes() as $activityID => $time)
                    $sum[$activityID] += $time;
        }
        return $sum;
    }

    /**
     * Returns total profit for this batch (direct child ManufactureProcessData sub-processes)
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds.
     * @param int $regionId of the market region to be used for price lookup. If none passed, default is are used.
     *
     * @return array
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getTotalProfit($maxPriceDataAge = null, $regionId = null)
    {
        $sum = 0;
        foreach ($this->getSubProcesses() as $spd)
            if ($spd instanceof ManufactureProcessData)
                $sum += $spd->getTotalProfit($maxPriceDataAge, $regionId);

        return $sum;
    }

    /**
     * Prints data about this process
     *
     * @return void
     */
    public function printData()
    {
        $utilClass = Config::getIveeClassName('Util');
        echo "Total slot time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterialMap()->getMaterials() as $typeID => $amount)
            echo $amount . 'x ' . Type::getById($typeID)->getName() . PHP_EOL;

        echo "Material cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost()) . "ISK" . PHP_EOL;
        echo "Slot cost: "     . $utilClass::quantitiesToReadable($this->getTotalProcessCost()) . "ISK" . PHP_EOL;
        echo "Total cost: "    . $utilClass::quantitiesToReadable($this->getTotalCost()) . "ISK" . PHP_EOL;
        echo "Total profit: "  . $utilClass::quantitiesToReadable($this->getTotalProfit()) . "ISK" . PHP_EOL;
    }
}
