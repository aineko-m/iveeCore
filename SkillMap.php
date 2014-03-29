<?php

/**
 * SkillMap is used for holding data about skills, typically in a role of activity requirement
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/SkillMap.php
 * @package iveeCore
 */
class SkillMap {
    
    /**
     * @var array $skills holds the data in the form $skillID => $level
     */
    protected $skills = array();
    
    /**
     * Add required skill to the total skill array
     * @param int $skillID of the skill
     * @param int $level of the skill
     * @throws InvalidParameterValueException if the skill level is not a valid integer between 0 and 5
     */
    public function addSkill($skillID, $level) {
        static::sanityCheckSkillLevel($level);
        if (isset($this->skills[(int)$skillID])) {
            //overwrite existing skill if $level is higher
            if ($this->skills[(int)$skillID] < $level)
                $this->skills[(int)$skillID] = (int)$level;
        } else
            $this->skills[(int)$skillID] = (int)$level;
    }
    
    /**
     * Sums the skills of another SkillMap object to this
     * @param SkillMap $skills
     */
    public function addSkillMap(SkillMap $skills){
        foreach ($skills->getSkills() as $skillID => $level){
            $this->addSkill($skillID, $level);
        }
    }
    
    /**
     * Returns the skills as array $skillID => $level
     * @return array
     */
    public function getSkills(){
        return $this->skills;
    }
    
    /**
     * Sanity checks a skill level (verify it's an integer between 0 and 5)
     * @param int $skillLevel the value to be checked
     * @return bool true on success
     * @throws InvalidParameterValueException if $skillLevel is not a valid skill level
     */
    public static function sanityCheckSkillLevel($skillLevel){
        if($skillLevel < 0 OR $skillLevel > 5 OR $skillLevel%1 > 0)
            throw new InvalidParameterValueException("Skill level needs to be an integer between 0 and 5");
        return true;
    }
}
?>