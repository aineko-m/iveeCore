<?php
/**
 * REBlueprint class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/REBlueprint.php
 *
 */

namespace iveeCore;

/**
 * REBlueprint represents blueprints that can be reverse engineered from Relics.
 * Where applicable, attribute names are the same as SDE database column names.
 * Inheritance: REBlueprint -> Blueprint -> Sellable -> Type -> SdeTypeCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/REBlueprint.php
 *
 */
class REBlueprint extends Blueprint
{
    /**
     * @var array $raceDecryptor lookup table from raceID to the required decryptorID
     */
    protected static $raceDecryptor = array(
        1 => 30383, //Caldari
        2 => 30384, //Minmatar
        4 => 30382, //Amarr
        8 => 30385  //Gallente
    );

    /**
     * @var array $reverseEngineeredFromRelicIDs the IDs of Relics this REBlueprint can be reverse engineered from
     */
    protected $reverseEngineeredFromRelicIDs;

    /**
     * @var int ID of the decryptor required in the reverse engineering process to make this REBlueprint. It behaves
     * like an invention decryptor although it has "Interface" in the name
     */
    protected $reverseEngineeringDecryptorID;

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate REBlueprint objects instead.
     *
     * @param int $id of the REBlueprint object
     *
     * @return \iveeCore\REBlueprint
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if the typeID is not found
     */
    protected function __construct($id)
    {
        //call parent constructor
        parent::__construct($id);

        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //get Relics this REBlueprint can be reverse-engineered from
        $res = $sde->query(
            "SELECT typeID
            FROM industryActivityProducts
            WHERE activityID = 7
            AND productTypeID = " . $this->id . ';'
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException', 
                "Reverse Engineering data for REBlueprint ID=" . $this->id ." not found"
            );

        while ($row = $res->fetch_assoc())
            $this->reverseEngineeredFromRelicIDs[] = (int) $row['typeID'];

        //get the raceID of the product
        $res = $sde->query(
            "SELECT raceID
            FROM invTypes
            WHERE typeID = " . $this->productId . ';'
        );

        if ($res->num_rows < 1)
            self::throwException(
                'TypeIdNotFoundException', 
                "Reverse Engineering data for REBlueprint ID=" . $this->id ." not found"
            );

        //lookup decryptor based on race
        while ($row = $res->fetch_assoc())
            $this->reverseEngineeringDecryptorID = static::$raceDecryptor[(int) $row['raceID']];
    }

    /**
     * Returns the IDs of the Relics this REBlueprint can be reverse engineered from
     *
     * @return array
     */
    public function getReverseEngineeringRelicIDs()
    {
        return $this->reverseEngineeredFromRelicIDs;
    }

    /**
     * Returns the ID the decryptor required to reverse engineer this REBlueprint
     *
     * @return int
     */
    public function getReverseEngineeringDecryptorID()
    {
        return $this->reverseEngineeringDecryptorID;
    }

    /**
     * Returns the decryptor required to reverse engineer this REBlueprint
     *
     * @return \iveeCore\Decryptor
     */
    public function getReverseEngineeringDecryptor()
    {
        return Type::getById($this->getReverseEngineeringDecryptorID());
    }

    /**
     * RElueprints can't be bought on the market
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     *
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException always
     */
    public function getBuyPrice($maxPriceDataAge = null)
    {
        $this->throwNotOnMarketException();
    }

    /**
     * REblueprints can't be sold on the market
     *
     * @param int $maxPriceDataAge maximum acceptable price data age in seconds. Optional.
     *
     * @return void
     * @throws \iveeCore\Exceptions\NotOnMarketException always
     */
    public function getSellPrice($maxPriceDataAge = null)
    {
        $this->throwNotOnMarketException();
    }

    /**
     * Can't copy REBlueprints
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $copies the number of copies to produce; defaults to 1.
     * @param int|string $runs the number of runs on each copy. Use 'max' for the maximum possible number of runs.
     * @param bool $recursive defines if used materials should be manufactured recursively
     *
     * @return \iveeCore\CopyProcessData describing the copy process
     * @throws \iveeCore\Exceptions\NotResearchableException always
     */
    public function copy(IndustryModifier $iMod, $copies = 1, $runs = 'max', $recursive = true)
    {
        self::throwException('NotResearchableException', "Relics can't be copied");
    }

    /**
     * Can't research REBlueprints
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $startME the initial ME level
     * @param int $endME the ME level after the research
     * @param bool $recursive defines if used materials should be manufactured recursively
     *
     * @return \iveeCore\ResearchMEProcessData describing the research process
     * @throws \iveeCore\Exceptions\NotResearchableException always
     */
    public function researchME(IndustryModifier $iMod, $startME, $endME, $recursive = true)
    {
        self::throwException('NotResearchableException', "Relics can't be copied");
    }

    /**
     * Can't research REBlueprints
     *
     * @param IndustryModifier $iMod the object that holds all the information about skills, implants, system industry
     * indices, teams, tax and assemblyLines
     * @param int $startTE the initial TE level
     * @param int $endTE the TE level after the research
     * @param bool $recursive defines if used materials should be manufactured recursively
     *
     * @return \iveeCore\ResearchTEProcessData describing the research process
     * @throws \iveeCore\Exceptions\NotResearchableException always
     */
    public function researchTE(IndustryModifier $iMod, $startTE, $endTE, $recursive = true)
    {
        self::throwException('NotResearchableException', "Relics can't be copied");
    }
}
