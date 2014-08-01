<?php
/**
 * ReactionProduct class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProduct.php
 *
 */

namespace iveeCore;

/**
 * Class for items that can result from reactions.
 * Inheritance: ReactionProduct -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProduct.php
 *
 */
class ReactionProduct extends Sellable
{
    /**
     * @var array $productOfReactionIDs the typeID(s) of the reactions this product can be produced from. Includes
     * alchemy reactions.
     */
    protected $productOfReactionIDs = array();

    /**
     * Constructor. Use \iveeCore\Type::getType() to instantiate ReactionProduct objects instead.
     * 
     * @param int $typeID of the ReactionProduct object
     * 
     * @return ReactionProduct
     * @throws Exception if typeID is not found
     */
    protected function __construct($typeID)
    {
        //call parent constructor
        parent::__construct($typeID);

        $sdeClass = Config::getIveeClassName('SDE');

        //fetch reactions this type can result from
        $res = $sdeClass::instance()->query(
            "(SELECT reactionTypeID
            FROM invTypeReactions as itr
            JOIN invTypes as it ON it.typeID = itr.reactionTypeID
            WHERE itr.typeID = " . (int) $this->typeID . "
            AND itr.input = 0
            AND it.published = 1)
            UNION
            (SELECT itr.reactionTypeID
            FROM invTypes as it
            JOIN invTypeMaterials as itm ON itm.typeID = it.typeID
            JOIN invTypeReactions as itr ON itr.typeID = it.typeID
            WHERE it.groupID = 428
            AND it.published = 1
            AND materialTypeID = " . (int) $this->typeID . "
            AND itr.input = 0);"
        );

        while ($row = $res->fetch_assoc())
            $this->productOfReactionIDs[] = (int) $row['reactionTypeID'];
    }

    /**
     * Gets the Reaction object(s) this product can be produced from
     * 
     * @return array with Reaction objects(s)
     */
    public function getReactions()
    {
        $typeClass = Config::getIveeClassName('Type');
        $ret = array();
        foreach ($this->productOfReactionIDs as $reactionID)
            $ret[$reactionID] = $typeClass::getType($reactionID);
        
        return $ret;
    }

    /**
     * Gets the Reaction ID(s) this product can be produced from
     * 
     * @return array with Reaction ID(s)
     */
    public function getReactionIDs()
    {
        return $this->productOfReactionIDs;
    }
}
