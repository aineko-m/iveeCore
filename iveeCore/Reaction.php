<?php
/**
 * Reaction class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Reaction.php
 */

namespace iveeCore;

/**
 * Class for all Reactions
 * Inheritance: Reaction -> Type -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Reaction.php
 */
class Reaction extends Type
{
    /**
     * @var int[] $cycleInputMaterials contains the consumed materials for one reaction cycle
     */
    protected $cycleInputMaterials = array();

    /**
     * @var int[] $cycleOutputMaterials contains the output materials for one reaction cycle
     */
    protected $cycleOutputMaterials = array();

    /**
     * @var bool $isAlchemy defines if this reaction is an alchemy reaction
     */
    protected $isAlchemy = false;

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate Reaction objects.
     *
     * @param int $id of the Reaction object
     *
     * @return Reaction
     * @throws Exception if typeID is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        //get data from SQL
        $row = $this->queryAttributes();
        //set data to object attributes
        $this->setAttributes($row);

        $sdeClass  = Config::getIveeClassName('SDE');

        //get reaction materials
        $res = $sdeClass::instance()->query(
            'SELECT itr.input,
            itr.typeID,
            itr.quantity * IFNULL(COALESCE(dta.valueInt, dta.valueFloat), 1) as quantity
            FROM invTypeReactions as itr
            JOIN invTypes as it ON itr.typeID = it.typeID
            LEFT JOIN dgmTypeAttributes as dta ON itr.typeID = dta.typeID
            WHERE it.published = 1
            AND (dta.attributeID = 726 OR dta.attributeID IS NULL)
            AND itr.reactionTypeID = ' . $this->id . ';'
        );

        while ($row = $res->fetch_assoc()) {
            if ($row['input'] == 1)
                $this->cycleInputMaterials[$row['typeID']] = $row['quantity'];
            else {
                $this->cycleOutputMaterials[$row['typeID']] = $row['quantity'];
                if (Type::getById($row['typeID'])->isReprocessable())
                    $this->isAlchemy = true;
            }
        }
    }

    /**
     * Gets the the array of input materials for one reaction cycle.
     *
     * @return int[]
     */
    public function getCycleInputMaterials()
    {
        return $this->cycleInputMaterials;
    }

    /**
     * Gets the the array of output materials for one reaction cycle.
     *
     * @return int[]
     */
    public function getCycleOutputMaterials()
    {
        return $this->cycleOutputMaterials;
    }

    /**
     * Returns whether this reaction is an alchemy reaction or not.
     *
     * @return bool
     */
    public function isAlchemy()
    {
        return $this->isAlchemy;
    }

    /**
     * Produces an ReactionProcessData object detailing a reaction process.
     *
     * @param int|float $cycles defines the number of reaction cycles to be calculated. One cycle takes 1h to complete.
     * @param bool $reprocess defines reprocessable reaction outputs should be reprocessed in the process. Applies to
     * alchemy reaction.
     * @param bool $feedback defines if materials occuring in both input and output should be subtracted in the
     * possible numbers, thus showing the effective input/output materials. Applies to alchemy reactions.
     * @param float $equipmentYield the station dependant reprocessing yield
     * @param float $reprocessingTaxFactor the standing dependant reprocessing tax factor
     *
     * @return \iveeCore\ReactionProcessData
     */
    public function react($cycles = 1, $reprocess = true, $feedback = true, $equipmentYield = 0.5,
        $reprocessingTaxFactor = 1.0
    ) {
        $materialsClass = Config::getIveeClassName('MaterialMap');
        $imm = new $materialsClass;
        $omm = new $materialsClass;
        $imm->addMaterials($this->getCycleInputMaterials());
        $omm->addMaterials($this->getCycleOutputMaterials());

        //if refine flag set, replace the refinable output materials by their refined materials
        if ($reprocess)
            $omm->reprocessMaterials($equipmentYield, $reprocessingTaxFactor, 1);

        //if feedback flag set, subtract materials occurring in both input and output from each other, respecting
        //quantities. This gives the effective required and resulting materials.
        if ($feedback)
            $materialsClass::symmetricDifference($imm, $omm);

        $reactionProcessDataClass = Config::getIveeClassName('ReactionProcessData');

        return new $reactionProcessDataClass(
            $imm->getMultipliedMaterialMap($cycles),
            $omm->getMultipliedMaterialMap($cycles),
            $cycles,
            ($this->isAlchemy AND $reprocess), //only pass on refine flag if this reaction actually produces a refinable
            $feedback
        );
    }
}
