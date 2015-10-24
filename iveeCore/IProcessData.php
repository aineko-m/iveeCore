<?php
/**
 * IProcessData interface file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IProcessData.php
 */
namespace iveeCore;

/**
 * IProcessData defines the interface to be implemented by objects that describe industry activity processes that can be
 * chained to form process trees, like manufacturing with recursive component building.
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IProcessData.php
 */
interface IProcessData
{
    //activity ID constants
    const ACTIVITY_MANUFACTURING = 1;
    const ACTIVITY_RESEARCH_TE   = 3;
    const ACTIVITY_RESEARCH_ME   = 4;
    const ACTIVITY_COPYING       = 5;
    const ACTIVITY_INVENTING     = 8;
    const ACTIVITY_REACTING      = 109; //reactions don't have an activityID, this is an unofficial extension of the Ids

    /**
     * Returns the activityId of the process.
     *
     * @return int
     */
    public function getActivityId();

    /**
     * Returns ID of the SolarSystem this process is performed in.
     *
     * @return int
     */
    public function getSolarSystemId();

    /**
     * Add sub-ProcessData object. This can be used to make entire build-trees or build batches.
     *
     * @param \iveeCore\IProcessData $subProcessData IProcessData object to add as a sub-process
     *
     * @return void
     */
    public function addSubProcessData(IProcessData $subProcessData);

    /**
     * Returns all sub process data objects, if any.
     *
     * @return \iveeCore\IProcessData[]
     */
    public function getSubProcesses();

    /**
     * Returns process cost, without subprocesses.
     *
     * @return float
     */
    public function getProcessCost();

    /**
     * Returns process cost (no materials), including subprocesses.
     *
     * @return float
     */
    public function getTotalProcessCost();

    /**
     * Returns total cost, including subprocesses.
     *
     * @param \iveeCore\IndustryModifier $buyContext for market context
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if $maxPriceDataAge is exceeded by any of the materials
     */
    public function getTotalCost(IndustryModifier $buyContext);

    /**
     * Returns required materials object for this process, WITHOUT sub-processes. Will return an empty new MaterialMap
     * object if this has none.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getMaterialMap();

    /**
     * Returns a new MaterialMap object containing all required materials, including sub-processes.
     * Note that material quantities might be fractionary, due to invention chance effects or requesting builds of items
     * in numbers that are not multiple of portionSize.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getTotalMaterialMap();

    /**
     * Returns object defining the minimum skills required for this process, without sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getSkillMap();

    /**
     * Returns a new object with all skills required, including sub-processes.
     *
     * @return \iveeCore\SkillMap
     */
    public function getTotalSkillMap();

    /**
     * Returns the time for this process, in seconds, without sub-processes.
     *
     * @return int
     */
    public function getTime();

    /**
     * Returns sum of all times, in seconds, including sub-processes.
     *
     * @return int|float
     */
    public function getTotalTime();

    /**
     * Returns array with process times summed by activity, in seconds, including sub-processes.
     *
     * @return float[] in the form activityId => float
     */
    public function getTotalTimes();

    /**
     * Returns total profit for direct child ManufactureProcessData or ReactionProcessData sub-processes (activities
     * with a product that can be sold on the market).
     *
     * @param \iveeCore\IndustryModifier $buyContext for buying context
     * @param \iveeCore\IndustryModifier $sellContext for selling context, optional. If not given, $buyContext will be
     * used.
     *
     * @return float
     * @throws \iveeCore\Exceptions\PriceDataTooOldException if a maxPriceDataAge has been specified and the data is
     * too old
     */
    public function getTotalProfit(IndustryModifier $buyContext, IndustryModifier $sellContext = null);
}
