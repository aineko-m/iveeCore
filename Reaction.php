<?php

/**
 * Class for all Reactions
 * Inheritance: Reaction -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Reaction.php
 * @package iveeCore
 */
class Reaction extends Sellable {
    
    /**
     * @var array $cycleInputMaterials contains the consumed materials for one reaction cycle
     */
    protected $cycleInputMaterials = array();
    
    /**
     * @var array $cycleOutputMaterials contains the output materials for one reaction cycle
     */
    protected $cycleOutputMaterials = array();
    
    /**
     * @var bool $isAlchemy defines if this reaction is an alchemy reaction
     */
    protected $isAlchemy = false;
    
    /**
     * Constructor.
     * Use SDE->getType() to instantiate Type objects.
     * @param int $typeID of the Type object
     * @return Type
     * @throws Exception if typeID is not found
     */
    protected function __construct($typeID) {
        $this->typeID = (int) $typeID;

        //get data from SQL
        $row = $this->queryAttributes();
        //set data to object attributes
        $this->setAttributes($row);
        
        //get reaction materials
        $res = SDE::instance()->query(
            'SELECT itr.input, 
            itr.typeID, 
            itr.quantity * IFNULL(COALESCE(dta.valueInt, dta.valueFloat), 1) as quantity
            FROM invTypeReactions as itr
            JOIN invTypes as it ON itr.typeID = it.typeID
            LEFT JOIN dgmTypeAttributes as dta ON itr.typeID = dta.typeID
            WHERE it.published = 1 
            AND (dta.attributeID = 726 OR dta.attributeID IS NULL)
            AND itr.reactionTypeID = ' . (int) $this->typeID . ';'
        );
        
        while ($row = $res->fetch_assoc()){
            if($row['input'] == 1)
                $this->cycleInputMaterials[$row['typeID']] = $row['quantity'];
            else{
                $this->cycleOutputMaterials[$row['typeID']] = $row['quantity'];
                if(SDE::instance()->getType($row['typeID'])->isReprocessable()) $this->isAlchemy = true;
            }
        }
    }
    
    /**
     * Gets the the array of input materials for one reaction cycle
     * @return array
     */
    public function getCycleInputMaterials(){
        return $this->cycleInputMaterials;
    }
    
    /**
     * Gets the the array of output materials for one reaction cycle
     * @return array
     */
    public function getCycleOutputMaterials(){
        return $this->cycleOutputMaterials;
    }
    
    /**
     * Returns whether this reaction is an alchemy reaction or not
     * @return bool
     */
    public function isAlchemy(){
        return $this->isAlchemy;
    }
    
    /**
     * Produces an ReactionProcessData object detailing a reaction process
     * @param int|float $cycles defines the number of reaction cycles to be calculated. 
     * One cycle takes 1h to complete.
     * @param bool $feedback defines if materials occuring in both input and output should be subtracted in the 
     * possible numbers, thus showing the effective input/output materials. Applies to alchemy reactions.
     * @param bool $refine defines if reprocessable reaction outputs should be refined as part of the process. Applies
     * to alchemy reaction.
     * @param float $reprocessingYield the skill and station dependant reprocessing yield
     * @param float $reprocessingTaxFactor the standing dependant reprocessing tax factor
     * @return ReactionProcessData
     */
    public function react($cycles = 1, $refine = true, $feedback = true, $reprocessingYield = 1.0, 
            $reprocessingTaxFactor = 1.0){
        $materialsClass = iveeCoreConfig::getIveeClassName('MaterialMap');
        $imm = new $materialsClass;
        $omm = new $materialsClass;
        $imm->addMaterials($this->getCycleInputMaterials());
        $omm->addMaterials($this->getCycleOutputMaterials());
        
        //if refine flag set, replace the refinable output materials by their refined materials
        if($refine) $omm->reprocessMaterials($reprocessingYield, $reprocessingTaxFactor);
        
        //if feedback flag set, subtract materials occurring in both input and output from each other, respecting 
        //quantities. This gives the effective required and resulting materials.
        if($feedback) $materialsClass::symmetricDifference($imm, $omm);
        
        $reactionProcessDataClass = iveeCoreConfig::getIveeClassName('ReactionProcessData');
        return new $reactionProcessDataClass(
            $imm->getMultipliedMaterialMap($cycles),
            $omm->getMultipliedMaterialMap($cycles),
            $cycles,
            ($this->isAlchemy AND $refine), //only pass on refine flag if this reaction actually produces a refinable
            $feedback
        );
    }
}

?>