<?php
/**
 * SkillMap class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SkillMap.php
 *
 */

namespace iveeCore;

/**
 * SkillMap is used for holding data about skills, typically in a role of activity requirement
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SkillMap.php
 *
 */
class SkillMap
{
    /**
     * @var array $skills holds the data in the form $skillID => $level
     */
    protected $skills = array();

    /**
     * Sanity checks a skill level (verify it's an integer between 0 and 5)
     * 
     * @param int $skillLevel the value to be checked
     * 
     * @return bool true on success
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if $skillLevel is not a valid skill level
     */
    public static function sanityCheckSkillLevel($skillLevel)
    {
        if ($skillLevel < 0 OR $skillLevel > 5 OR $skillLevel%1 > 0) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Skill level needs to be an integer between 0 and 5");
        }
        return true;
    }

    /**
     * Add required skill to the total skill array
     * 
     * @param int $skillID of the skill
     * @param int $level of the skill
     * 
     * @return void
     * @throws \iveeCore\Exception\InvalidParameterValueException if the skill level is not a valid integer between 0 
     * and 5
     */
    public function addSkill($skillID, $level)
    {
        static::sanityCheckSkillLevel($level);
        if (isset($this->skills[(int) $skillID])) {
            //overwrite existing skill if $level is higher
            if ($this->skills[(int) $skillID] < $level)
                $this->skills[(int) $skillID] = (int) $level;
        } else
            $this->skills[(int) $skillID] = (int) $level;
    }

    /**
     * Sums the skills of another SkillMap object to this
     * 
     * @param SkillMap $skillMap of skills to be added
     * 
     * @return void
     */
    public function addSkillMap(SkillMap $skillMap)
    {
        foreach ($skillMap->getSkills() as $skillID => $level)
            $this->addSkill($skillID, $level);
    }

    /**
     * Returns the skills as array $skillID => $level
     * 
     * @return array
     */
    public function getSkills()
    {
        return $this->skills;
    }
}
