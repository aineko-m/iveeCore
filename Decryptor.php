<?php

/**
 * Class for invention decryptors.
 * Inheritance: Decryptor -> Sellable -> Type.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Decryptor.php
 * @package iveeCore
 */
class Decryptor extends Sellable {

    /**
     * @var int the material efficiency modifier
     */
    protected $MEModifier;
    
    /**
     * @var int the production efficiency modifier
     */
    protected $PEModifier;
    
    /**
     * @var int the production run modifier
     */
    protected $runModifier;
    
    /**
     * @var float the invention chance factor
     */
    protected $probabilityModifier;
    
    /**
     * @var array to hold the decryptor groups which in turn hold the decryptor IDs
     */
    protected static $decryptorGroups = array();

    /**
     * Constructor
     * Use SDE->getType() to instantiate Decryptor objects.
     * @param int typeID of the Decryptor object
     * @return Decryptor
     * @throws Exception when a typeID is not found or when loading Decryptor data fails
     */
    protected function __construct($typeID) {
        //call parent constructor
        parent::__construct($typeID);

        //fetch decryptor modifiers from DB
        $res = SDE::instance()->query(
            "SELECT 
            attributeID, 
            valueFloat
            FROM dgmTypeAttributes
            WHERE
            typeID = " . (int)$this->typeID . "
            AND attributeID IN (1112, 1113, 1114, 1124);");
        
        //set modifiers to object
        while ($row = $res->fetch_assoc()) {
            switch ($row['attributeID']) {
                case 1112:
                    $this->probabilityModifier = (float) $row['valueFloat'];
                    break;
                case 1113:
                    $this->MEModifier = (int) $row['valueFloat'];
                    break;
                case 1114:
                    $this->PEModifier = (int) $row['valueFloat'];
                    break;
                case 1124:
                    $this->runModifier = (int) $row['valueFloat'];
                    break;
                default:
                    throw new Exception("Error loading Decryptor data.");
            }
        }
    }
    
    /**
     * @return int the material efficiency modifier
     */
    public function getMEModifier() {
        return $this->MEModifier;
    }

    /**
     * @return int the production efficiency modifier
     */
    public function getPEModifier() {
        return $this->PEModifier;
    }

    /**
     * @return int the production run modifier
     */
    public function getRunModifier() {
        return $this->runModifier;
    }

    /**
     * @return float the invention chance factor
     */
    public function getProbabilityModifier() {
        return $this->probabilityModifier;
    }

    /**
     * @param int groupID specifies the decryptor group to return
     * @return array with the decryptor IDs
     * @throws Exception if decryptor group is not found
     */
    public static function getIDsFromGroup($groupID) {
        //lazy load data from DB
        if (empty(self::$decryptorGroups)) {
            $res = SDE::instance()->query(
                "SELECT it.groupID, it.typeID FROM invGroups as ig
                JOIN invTypes as it ON ig.groupID = it.groupID
                WHERE categoryID = 35
                AND it.published = 1"
            );
            while ($row = $res->fetch_assoc()){
                self::$decryptorGroups[(int) $row['groupID']][] = $row['typeID'];
            }
        }
        
        if (!isset(self::$decryptorGroups[$groupID]))
            throw new Exception("Decryptor group " . $groupID . " not found");
        return self::$decryptorGroups[$groupID];
    }
}

?>