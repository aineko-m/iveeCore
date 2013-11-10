<?php

/**
 * SkillSet is used for holding data about skills, typically in a role of activity requirement
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/SkillSet.php
 * @package iveeCore
 */
class SkillSet {
    
    /**
     * @var array $skills holds the data in the form $skillID => $level
     */
    protected $skills = array();
    
    /**
     * Add required skill to the total skill array
     * @param int $skillID of the skill
     * @param int $level of the skill
     * @throws Exception if the skill level is not a valid integer between 0 and 5
     */
    public function addSkill($skillID, $level) {
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        $utilClass::sanityCheckSkillLevel($level);
        if (isset($this->skills[(int)$skillID])) {
            //overwrite existing skill if $level is higher
            if ($this->skills[(int)$skillID] < $level)
                $this->skills[(int)$skillID] = (int)$level;
        } else
            $this->skills[(int)$skillID] = (int)$level;
    }
    
    /**
     * Sums the skills of another SkillSet object to this
     * @param SkillSet $skills
     */
    public function addSkillSet(SkillSet $skills){
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
}
?>
