<?php
/**
 * ReactionProduct class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProduct.php
 */

namespace iveeCore;

/**
 * Class for items that can result from reactions.
 * Inheritance: ReactionProduct -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ReactionProduct.php
 */
class ReactionProduct extends Type
{
    /**
     * @var int[] $productOfReactionIds the typeId(s) of the reactions this product can be produced from. Includes
     * alchemy reactions.
     */
    protected $productOfReactionIds = [];

    /**
     * Constructor. Use iveeCore\Type::getById() to instantiate ReactionProduct objects instead.
     *
     * @param int $id of the ReactionProduct object
     *
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeId is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        $sdeClass = Config::getIveeClassName('SDE');

        //fetch reactions this type can result from
        $res = $sdeClass::instance()->query(
            "(SELECT reactionTypeID
            FROM invTypeReactions as itr
            JOIN invTypes as it ON it.typeID = itr.reactionTypeID
            WHERE itr.typeID = " . $this->id . "
            AND itr.input = 0
            AND it.published = 1)
            UNION
            (SELECT itr.reactionTypeID
            FROM invTypes as it
            JOIN invTypeMaterials as itm ON itm.typeID = it.typeID
            JOIN invTypeReactions as itr ON itr.typeID = it.typeID
            WHERE it.groupID = 428
            AND it.published = 1
            AND materialTypeID = " . $this->id . "
            AND itr.input = 0);"
        );

        if (empty($res))
            static::throwException('TypeIdNotFoundException', "ReactionProduct ID=". $this->id . " not found");

        while ($row = $res->fetch_assoc())
            $this->productOfReactionIds[] = (int) $row['reactionTypeID'];
    }

    /**
     * Gets the Reaction object(s) this product can be produced from.
     *
     * @return \iveeCore\Reaction[] with Reaction objects(s)
     */
    public function getReactions()
    {
        $ret = [];
        foreach ($this->productOfReactionIds as $reactionId)
            $ret[$reactionId] = Type::getById($reactionId);

        return $ret;
    }

    /**
     * Gets the Reaction ID(s) this product can be produced from.
     *
     * @return int[] with Reaction ID(s)
     */
    public function getReactionIds()
    {
        return $this->productOfReactionIds;
    }

    /**
     * Runs the reaction for producing a given number of units of this type. If multiple reactions are possible (like
     * alchemy), the most cost effective option is used.
     *
     * @param int|float $units defines the number of desired output material units
     * @param \iveeCore\IndustryModifier $iMod as industry context
     * @param \iveeCore\IndustryModifier $buyContext for pricing context for chosing the cheapest reaction. If not
     * given, it defaults to default Jita 4-4 CNAP.
     *
     * @return \iveeCore\ReactionProcessData
     */
    public function doBestReaction($units, IndustryModifier $iMod, IndustryModifier $buyContext = null)
    {
        $reactions = $this->getReactions();

        //if there's only one reaction option, do straight forward reaction
        if (count($reactions) == 1)
            return array_pop($reactions)->reactExact($units, $iMod);

        //if no pricing context waas given, get the default one
        if (is_null($buyContext)) {
            $iModClass = Config::getIveeClassName('IndustryModifier');
            $buyContext = $iModClass::getByStationId(60003760); //hardcoded Jita 4-4 CNAP
        }
        $bestRpd = null;
        $bestCost = null;

        //run all reaction options and pick the cheapest one
        foreach ($reactions as $reaction) {
            $rpd = $reaction->reactExact($units, $iMod);
            $cost = $rpd->getInputBuyCost($buyContext);
            if (is_null($bestCost) OR $cost < $bestCost) {
                $bestCost = $cost;
                $bestRpd = $rpd;
            }
        }
        return $bestRpd;
    }
}
