<?php
/**
 * ICharacterModifier interface file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICharacterModifier.php
 */

namespace iveeCore;

/**
 * ICharacterModifier is the interface to be implemented by classes that will be used as CharacterModifier, providing
 * methods for getting or calculating industry relevant data like skill levels, time bonuses, market taxes and
 * standings.
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICharacterModifier.php
 */
interface ICharacterModifier
{
    /**
     * Gets the level of a skill.
     *
     * @param int $skillId of the skill
     *
     * @return int the level
     */
    public function getSkillLevel($skillId);

    /**
     * Returns the implant dependent time factor for a specific industry activity.
     *
     * @param int $activityId of the activity
     *
     * @return float in the form 0.98 for 2% bonus
     */
    public function getIndustryImplantTimeFactor($activityId);

    /**
     * Gets the standings and skills dependant total market buy order tax.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float in the form 1.012 for 1.2% total tax
     */
    public function getBuyTaxFactor($factionId = 0, $corpId = 0);

    /**
     * Gets the standings and skills dependant total market sell order tax.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float in the form 0.988 for 1.2% total tax
     */
    public function getSellTaxFactor($factionId = 0, $corpId = 0);

    /**
     * Gets the skill-dependent time factor for a specific industry activity.
     *
     * @param int $activityId of the activity
     *
     * @return float in the form 0.75 meaning 25% bonus
     */
    public function getIndustrySkillTimeFactor($activityId);

    /**
     * Gets the standings and skills dependant broker tax as percent.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float as percent, 1.2 for 1.2% tax
     */
    public function getBrokerTax($factionId, $corpId);

    /**
     * Gets the skills dependant transaction tax as percent.
     *
     * @return float as percent, 1.35 for 1.35% tax
     */
    public function getTransactionTax();

    /**
     * Gets the faction standing towards the character.
     *
     * @param int $factionId of the faction
     * @param bool $considerConnections whether the Connections skill shall be applied to the standings
     *
     * @return float
     */
    public function getFactionStanding($factionId, $considerConnections = false);

    /**
     * Gets the corporation standing towards the character.
     *
     * @param int $corpId of the faction
     * @param bool $considerConnections whether the Connections skill shall be applied to the standings
     *
     * @return float
     */
    public function getCorporationStanding($corpId, $considerConnections = false);

    /**
     * Calculates the tax factor for reprocessing in stations (5% tax = factor of 0.95).
     *
     * @param int $corpId of the corporation of the station you are reprocessing at
     *
     * @return float reprocessing tax factor
     */
    public function getReprocessingTaxFactor($corpId);

    /**
     * Gets the yield factor for reprocessing implants.
     *
     * @return float reprocessing factor. 1.04 for 4% bonus, 1.0 for no implant.
     */
    public function getReprocessingImplantYieldFactor();
}
