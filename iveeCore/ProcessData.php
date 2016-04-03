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
 * ProcessData represents a generic industrial process. This class has not been made abstract so it can be used to
 * aggregate multiple IProcessData objects ("shopping cart" functionality).
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ProcessData.php
 */
class ProcessData extends ProcessDataCommon
{
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
     * @var \iveeCore\SkillMap $skills an object defining the minimum required skills to perform this activity.
     */
    protected $skills;

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
        $this->materials->addMaterial($typeId, $amount);
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
        $this->skills->addSkill($skillId, $level);
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
        if (isset($this->skills)) {
            $this->skills->addSkillMap($sm);
        } else {
            $this->skills = $sm;
        }
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
     * Returns ID of the AssemblyLine this process is performed in.
     *
     * @return int
     */
    public function getAssemblyLineTypeId()
    {
        return $this->assemblyLineId;
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
     * Returns process cost, without subprocesses.
     *
     * @return float
     */
    public function getProcessCost()
    {
        return $this->processCost;
    }
    
    /**
     * Returns a clone of the SkillMap defining the minimum skills required for this process, without sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getSkillMap()
    {
        return clone $this->skills; //we return a clone so the original doesn't get altered
    }

    /**
     * Returns total profit of all its the sub-processes.
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext will be
     * used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getTotalProfit(IndustryModifier $buyContext, IndustryModifier $sellContext = null)
    {
        $sum = -$this->getProcessCost();
        foreach ($this->getSubProcesses() as $spd) {
            if ($spd instanceof ManufactureProcessData or $spd instanceof ReactionProcessData) {
                $sum += $spd->getTotalProfit($buyContext, $sellContext);
            } else {
                $sum -= $spd->getTotalCost($buyContext);
            }
        }

        return $sum;
    }

    /**
     * Prints data about this process.
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext will be
     * used.
     *
     * @return void
     */
    public function printData(IndustryModifier $buyContext, IndustryModifier $sellContext = null)
    {
        $utilClass = Config::getIveeClassName('Util');
        echo "Total slot time: " .  $utilClass::secondsToReadable($this->getTotalTime()) . PHP_EOL;

        //iterate over materials
        foreach ($this->getTotalMaterialMap()->getMaterials() as $typeId => $amount) {
            echo $amount . 'x ' . Type::getById($typeId)->getName() . PHP_EOL;
        }

        echo "Material cost: " . $utilClass::quantitiesToReadable($this->getTotalMaterialBuyCost($buyContext)) . "ISK"
            . PHP_EOL;
        echo "Slot cost: "     . $utilClass::quantitiesToReadable($this->getTotalProcessCost()) . "ISK" . PHP_EOL;
        echo "Total cost: "    . $utilClass::quantitiesToReadable($this->getTotalCost($buyContext)) . "ISK" . PHP_EOL;
        echo "Total profit: "  . $utilClass::quantitiesToReadable($this->getTotalProfit($buyContext, $sellContext))
            . "ISK" . PHP_EOL;
    }
}
