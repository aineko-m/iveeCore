<?php

/**
 * Class for reaction products
 * Inheritance: ReactionProduct -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/ReactionProduct.php
 * @package iveeCore
 */
class ReactionProduct extends Sellable {
    
    /**
     * @var int|aray $productOfReactionIDs the typeID(s) of the reactions this product can be produced from. Includes
     * alchemy reactions.
     */
    protected $productOfReactionIDs;
    
    /**
     * Constructor.
     * Use SDE->getType() to instantiate Type objects.
     * @param int $typeID of the Type object
     * @return Type
     * @throws Exception if typeID is not found
     */
    protected function __construct($typeID) {
        //call parent constructor
        parent::__construct($typeID);
        
        //fetch reactions this type can result from
        $res = SDE::instance()->query(
            "(SELECT reactionTypeID
            FROM invTypeReactions as itr
            JOIN invTypes as it ON it.typeID = itr.reactionTypeID
            WHERE itr.typeID = " . (int)$this->typeID . "
            AND itr.input = 0
            AND it.published = 1)
            UNION
            (SELECT itr.reactionTypeID 
            FROM invTypes as it
            JOIN invTypeMaterials as itm ON itm.typeID = it.typeID
            JOIN invTypeReactions as itr ON itr.typeID = it.typeID
            WHERE it.groupID = 428 
            AND it.published = 1
            AND materialTypeID = " . (int)$this->typeID . "
            AND itr.input = 0);"
        );
        
        //a few items are result of multiple potential reactions
        if($res->num_rows > 1){
            $this->productOfReactionIDs = array();
            while ($row = $res->fetch_assoc()) {
                $this->productOfReactionIDs[] = $row['reactionTypeID'];
            }
        } else {
            $row = $res->fetch_assoc();
            $this->productOfReactionIDs = $row['reactionTypeID'];
        }
    }
    
    /**
     * Gets the Reaction object(s) this product can be produced from
     * @return array with Reaction objects(s)
     */
    public function getReactions(){
        if(is_array($this->productOfReactionIDs)){
            $ret = array();
            foreach ($this->productOfReactionIDs as $reactionID){
                $ret[$reactionID] = SDE::instance()->getType($reactionID);
            }
            return $ret;
        }
        else 
            return SDE::instance()->getType($this->productOfReactionIDs);
    }
    
    /**
     * Gets the Reaction ID(s) this product can be produced from
     * @return array with Reaction ID(s)
     */
    public function getReactionIDs(){
        if(is_array($this->productOfReactionIDs))
            return $this->productOfReactionIDs;
        else
            return array($this->productOfReactionIDs);
    }
}

?>