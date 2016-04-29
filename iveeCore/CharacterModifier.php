<?php
/**
 * CharacterModifier class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CharacterModifier.php
 */

namespace iveeCore;

/**
 * CharacterModifier provides methods for getting or calculating industry relevant data like skill levels, time bonuses,
 * market taxes and standings. Note that it is a stub implementation, modelling a character with maximum skills, just a
 * reprocessing implant and base standings of 2.5 for all factions and 7.5 for all corps.
 * Developers should implement their own class extending from this one or implementing the ICharacterModifier interface.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CharacterModifier.php
 */
class CharacterModifier implements ICharacterModifier
{
    /**
     * @var array $pirateFactions IDs of the pirate factions for proper standings calculation. "Pirate faction" defined
     * as "CONCORD has negative standings towards them".
     */
    protected static $pirateFactions = array(
        500010, //Guristas Pirates
        500011, //Angel Cartel
        500012, //Blood Raider Covenant
        500019, //Sanha's Nation
        500020  //Serpentis
    );

    /**
     * @var array $pirateCorporations IDs of the pirate corporations for proper standings calculation. "Pirate corp"
     * defined as "CONCORD has negative standings towards their faction".
     */
    protected static $pirateCorporations = array(
        1000124,
        1000133,
        1000134,
        1000135,
        1000136,
        1000138,
        1000141,
        1000157,
        1000161,
        1000162
    );

    /**
     * Gets the level of a skill.
     *
     * @param int $skillId of the skill
     *
     * @return int the level
     */
    public function getSkillLevel($skillId)
    {
        return 5;
    }

    /**
     * Gets the skill-dependent time factor for a specific industry activity.
     *
     * @param int $activityId of the activity
     *
     * @return float in the form 0.75 meaning 25% bonus
     */
    public function getIndustrySkillTimeFactor($activityId)
    {
        switch ($activityId) {
            case 1:
                //Industry and Advanced Industry
                return (1.0 - 0.04 * $this->getSkillLevel(3380)) * (1.0 - 0.03 * $this->getSkillLevel(3388));
            case 3:
                //Research and Advanced Industry
                return (1.0 - 0.05 * $this->getSkillLevel(3403)) * (1.0 - 0.03 * $this->getSkillLevel(3388));
            case 4:
                //Metallurgy and Advanced Industry
                return (1.0 - 0.05 * $this->getSkillLevel(3409)) * (1.0 - 0.03 * $this->getSkillLevel(3388));
            case 5:
                //Science and Advanced Industry
                return (1.0 - 0.05 * $this->getSkillLevel(3402)) * (1.0 - 0.03 * $this->getSkillLevel(3388));
            case 8:
                //Advanced Industry
                return 1.0 - 0.03 * $this->getSkillLevel(3388);
            default:
                $exceptionClassName = Config::getIveeClassName('InvalidParameterValueException');
                throw new $exceptionClassName('Given activityId is not valid');
        }
    }

    /**
     * Returns the implant dependent time factor for a specific industry activity.
     *
     * @param int $activityId of the activity
     *
     * @return float in the form 0.98 for 2% bonus
     */
    public function getIndustryImplantTimeFactor($activityId)
    {
        switch ($activityId) {
            case 1:
            case 3:
            case 4:
            case 5:
            case 8:
                return 1.0;
            default:
                $exceptionClassName = Config::getIveeClassName('InvalidParameterValueException');
                throw new $exceptionClassName('Given activityId is not valid');
        }
    }

    /**
     * Gets the standings and skill dependant total market buy order tax.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float in the form 1.012 for 1.2% total tax
     */
    public function getBuyTaxFactor($factionId = 0, $corpId = 0)
    {
        return 1.0 + ($this->getBrokerTax($factionId, $corpId) + $this->getTransactionTax()) / 100;
    }

    /**
     * Gets the standings and skill dependant total market sell order tax.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float in the form 0.988 for 1.2% total tax
     */
    public function getSellTaxFactor($factionId = 0, $corpId = 0)
    {
        return 1.0 - ($this->getBrokerTax($factionId, $corpId) + $this->getTransactionTax()) / 100;
    }

    /**
     * Gets the standings and skills dependant broker tax as percent.
     *
     * @param int $factionId of the faction the station belongs to in which the order is being setup
     * @param int $corpId of the corp the station belongs to in which the order is being setup
     *
     * @return float as percent, 1.2 for 1.2% tax
     */
    public function getBrokerTax($factionId, $corpId)
    {
        return 3 - 0.1 * $this->getSkillLevel(3446) //Broker Relations skill
            - 0.03 * $this->getFactionStanding($factionId) - 0.02 * $this->getCorporationStanding($corpId);
    }

    /**
     * Gets the skills dependant transaction tax as percent.
     *
     * @return float as percent, 1.2 for 1.2% tax
     */
    public function getTransactionTax()
    {
        return 2 - 0.2 * $this->getSkillLevel(16622); //Accounting skill
    }

    /**
     * Gets the faction standing towards the character.
     *
     * @param int $factionId of the faction
     * @param bool $considerSkills whether social skills shall be applied to the standings
     *
     * @return float
     */
    public function getFactionStanding($factionId, $considerSkills = false)
    {
        $standings = 2.5;
        if ($considerSkills) {
            return $this->getStandingWithSkillBonus($standings, in_array($factionId, static::$pirateFactions));
        }
        return $standings;
    }

    /**
     * Gets the corporation standing towards the character.
     *
     * @param int $corpId of the faction
     * @param bool $considerSkills whether social skills shall be applied to the standings
     *
     * @return float
     */
    public function getCorporationStanding($corpId, $considerSkills = false)
    {
        $standings = 7.5;
        if ($considerSkills) {
            return $this->getStandingWithSkillBonus($standings, in_array($corpId, static::$pirateCorporations));
        }
        return $standings;
    }

    /**
     * Calculates effective standing based on skills.
     *
     * @param float $baseStandings without social skill effects
     * @param bool $isPirate whether the entitiy is "pirate", meaning having negative standings from CONCORD
     *
     * @return float
     */
    public function getStandingWithSkillBonus($baseStandings, $isPirate)
    {
        if ($baseStandings < 0) {
            $skillLevel = $this->getSkillLevel(3357); //Diplomacy skill
        } elseif ($isPirate) {
            $skillLevel = $this->getSkillLevel(3361); //Criminal Connections skill
        } else {
            $skillLevel = $this->getSkillLevel(3359); //Connections skill
        }
        return $baseStandings + (10 - $baseStandings) * 0.04 * $skillLevel;
    }

    /**
     * Calculates the tax factor for reprocessing in stations (5% tax = factor of 0.95).
     *
     * @param int $corpId of the corporation of the station you are reprocessing at
     *
     * @return float reprocessing tax factor
     */
    public function getReprocessingTaxFactor($corpId)
    {
        //calculate tax factor
        $tax = 0.05 - (0.0075 * $this->getCorporationStanding($corpId, true));
        if ($tax < 0) {
            $tax = 0;
        }

        return 1.0 - $tax;
    }

    /**
     * Gets the yield factor for reprocessing implants.
     *
     * @return float reprocessing factor. 1.04 for 4% bonus, 1.0 for no implant.
     */
    public function getReprocessingImplantYieldFactor()
    {
        return 1.04;
    }
}
