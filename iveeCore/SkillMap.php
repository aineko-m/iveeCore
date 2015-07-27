<?php
/**
 * SkillMap class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SkillMap.php
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
 */
class SkillMap
{
    /**
     * @var array $skills holds the data in the form $skillId => $level
     */
    protected $skills = array();

    /**
     * Sanity checks a skill level (verify it's an integer between 0 and 5).
     *
     * @param int $skillLevel the value to be checked
     *
     * @return bool true on success
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if $skillLevel is not a valid skill level
     */
    public static function sanityCheckSkillLevel($skillLevel)
    {
        if ($skillLevel < 0 OR $skillLevel > 5 OR !is_int($skillLevel)) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Skill level needs to be an integer between 0 and 5");
        }
        return true;
    }

    /**
     * Add required skill to the total skill array.
     *
     * @param int $skillId of the skill
     * @param int $level of the skill
     *
     * @return void
     * @throws \iveeCore\Exception\InvalidParameterValueException if the skill level is not a valid integer between 0
     * and 5
     */
    public function addSkill($skillId, $level)
    {
        static::sanityCheckSkillLevel($level);
        if (isset($this->skills[(int) $skillId])) {
            //overwrite existing skill if $level is higher
            if ($this->skills[(int) $skillId] < $level)
                $this->skills[(int) $skillId] = (int) $level;
        } else
            $this->skills[(int) $skillId] = (int) $level;
    }

    /**
     * Sums the skills of another SkillMap object to this.
     *
     * @param \iveeCore\SkillMap $skillMap of skills to be added
     *
     * @return void
     */
    public function addSkillMap(SkillMap $skillMap)
    {
        foreach ($skillMap->getSkills() as $skillId => $level)
            $this->addSkill($skillId, $level);
    }

    /**
     * Returns the skills as array $skillId => $level.
     *
     * @return int[]
     */
    public function getSkills()
    {
        return $this->skills;
    }
}
