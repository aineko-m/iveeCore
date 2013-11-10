<?php

/**
 * Base class for all item subclasses.
 * Where applicable, attribute names are the same as SDE database column names.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/Type.php
 * @package iveeCore
 */
class Type {
    
    /**
     * @var int $typeID the typeID of this Type.
     */
    protected $typeID;
    
    /**
     * @var int $groupID the groupID of this Type.
     */
    protected $groupID;
    
    /**
     * @var int $categoryID the categoryID of this Type.
     */
    protected $categoryID;
    
    /**
     * @var string $typeName the name of this Type.
     */
    protected $typeName;
    
    /**
     * @var float $volume the space the item occupies
     */
    protected $volume;
    
    /**
     * @var int $portionSize the portion size of this Type; defines the minimum size of production batches.
     */
    protected $portionSize;
    
    /**
     * @var int $basePrice the base price of this Type.
     */
    protected $basePrice;
    
    /**
     * @var array $requirements holds reprocessing materials
     */
    protected $requirements;

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
        
        //get reprocessing materials, if any
        $res = SDE::instance()->query(
            'SELECT 
            materialTypeID, 
            quantity 
            FROM invTypeMaterials 
            WHERE typeID = ' . (int) $this->typeID . ';'
        );
        if($res->num_rows > 0){
            $this->requirements = array();
            //add materials to the requirements array, following the schema of Blueprint
            while ($row = $res->fetch_assoc()){
                $this->requirements[ProcessData::ACTIVITY_MANUFACTURING][] = array(
                    'typ' => (int) $row['materialTypeID'],
                    'qua' => (int) $row['quantity']
                );
            }
        }
    }

    /**
     * Gets all necessary data from SQL
     * @return array
     * @throws TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes() {
        $row = SDE::instance()->query(
            "SELECT 
            it.groupID, 
            categoryID, 
            typeName,
            volume,
            portionSize, 
            basePrice 
	    FROM invTypes as it
	    JOIN invGroups as ig ON it.groupID = ig.groupID
	    WHERE it.published = 1 
	    AND typeID = " . (int) $this->typeID . ';'
        )->fetch_assoc();

        if (empty($row))
            throw new TypeIdNotFoundException("typeID " . (int) $this->typeID ." not found");
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * @param array $row data from DB
     */
    protected function setAttributes($row) {
        $this->groupID     = (int) $row['groupID'];
        $this->categoryID  = (int) $row['categoryID'];
        $this->typeName    = $row['typeName'];
        $this->volume      = (float) $row['volume'];
        $this->portionSize = (int) $row['portionSize'];
        $this->basePrice   = (int) $row['basePrice'];
    }

    /**
     * Instantiates type objects.
     * This method shouldn't be called directly. Use SDE->getType() instead.
     * @param int $typeID of the Type object
     * @param array $subtypeInfo optional parameter with the DB data used to decide Type subclass
     * @return Type the requested Type or subclass object
     * @throws TypeIdNotFoundException when a typeID is not found
     */
    public static function factory($typeID, $subtypeInfo = NULL) {
        //get type decision data if not given
        if(is_null($subtypeInfo))
            $subtypeInfo = self::getSubtypeInfo((int)$typeID);

        //decide type
        $subtype = self::decideType($subtypeInfo);
        
        //instantiate the appropriate Type or subclass object
        return new $subtype((int)$typeID);
    }

    /**
     * Helper method that returns data to be used to determine as which class 
     * to instantiate a certain type ID.
     * @param int $typeID of the Type object
     * @return array with the type decision data from the SDE DB
     * @throws TypeIdNotFoundException when a typeID is not found
     */
    protected static function getSubtypeInfo($typeID) {
        $res = SDE::instance()->query(
            "SELECT 
            it.typeID, 
	    it.marketGroupID as sellable, 
	    bpProduct.productTypeID as manufacturable, 
	    bp.bluePrintTypeID as blueprint,
	    IF(it.groupID IN (728, 729, 730, 731), it.groupID, NULL) as decryptor,
	    inventor.parentTypeID as inventor, 
	    inventable.typeId as inventable
	    FROM invTypes as it
	    LEFT JOIN invBlueprintTypes as bpProduct ON it.typeID = bpProduct.productTypeID
	    LEFT JOIN invBlueprintTypes as bp ON it.typeID = bp.blueprintTypeID
	    LEFT JOIN (SELECT parentTypeID FROM invMetaTypes WHERE metaGroupID = 2 GROUP BY parentTypeID) as inventor ON inventor.parentTypeID = bp.productTypeID
	    LEFT JOIN (SELECT       typeID FROM invMetaTypes WHERE metaGroupID = 2) as inventable ON inventable.typeID = bp.productTypeID
	    WHERE it.typeID = " . (int) $typeID . ';'
        );

        $row = $res->fetch_assoc();
        $res->free();
        if (empty($row))
            throw new TypeIdNotFoundException("typeID " . (int) $typeID . " not found");
        return $row;
    }

    /**
     * Helper method to determine as which class to instantiate a certain Type.
     * to instantiate a certain type ID.
     * @param array $subtypeInfo as returned by method getSubtypeInfo()
     * @return string name of the class to instantiate
     */
    protected static function decideType($subtypeInfo) {
        if (empty($subtypeInfo))
            throw new Exception("typeID not found");
        if (!empty($subtypeInfo['inventable'])) {
            $subtype = 'inventable';
        } elseif (!empty($subtypeInfo['inventor'])) {
            $subtype = 'inventor';
        } elseif (!empty($subtypeInfo['blueprint'])) {
            $subtype = 'blueprint';
        } elseif (!empty($subtypeInfo['decryptor'])) {
            $subtype = 'decryptor';
        } elseif (!empty($subtypeInfo['manufacturable'])) {
            $subtype = 'manufacturable';
        } elseif (!empty($subtypeInfo['sellable'])) {
            $subtype = 'sellable';
        } else {
            $subtype = 'stdtype';
        }
        return iveeCoreConfig::getIveeClassName($subtype);
    }
    
    /**
     * @return int typeID
     */
    public function getTypeID() {
        return $this->typeID;
    }
    
    /**
     * @return int group ID
     */  
    public function getGroupID() {
        return $this->groupID;
    }
    
    /**
     * @return int category ID
     */  
    public function getCategoryID() {
        return $this->categoryID;
    }

    /**
     * @return string type name
     */
    public function getName() {
        return $this->typeName;
    }
    
    /**
     * @return float volume occupied by item
     */
    public function getVolume() {
        return $this->volume;
    }
    
    /**
     * @return int portion size
     */
    public function getPortionSize() {
        return $this->portionSize;
    }
    
    /**
     * @return int base price
     */    
    public function getBasePrice(){
        return $this->basePrice;
    }
    
    /**
     * @return bool if the item is reprocessable
     */    
    public function isReprocessable(){
        if(empty($this->requirements))
            return false;
        else
            return true;
    }
    
    /**
     * Returns a MaterialSet object representing the reprocessing materials of the item
     * @param int $batchSize number of items being reprocessed, needs to be multiple of portionSize
     * @param float $effectiveYield the skill, standing and station dependant reprocessing yield
     * @return MaterialSet
     * @throws NotReprocessableException if item is not reprocessable
     * @throws InvalidParameterValueException if batchSize is not multiple of portionSize or if effectiveYield is not sane
     */
    public function getReprocessingMaterialSet($batchSize, $effectiveYield){
        if(empty($this->requirements))
            throw new NotReprocessableException($this->typeName . ' is not reprocessable');
        if($batchSize < $this->portionSize OR $batchSize % $this->portionSize != 0) 
            throw new InvalidParameterValueException('Recycling batch size needs to be multiple of ' . $this->portionSize);
        if($effectiveYield > 1)
            throw new InvalidParameterValueException('Effective reprocessing yield can never be > 1.0');
        
        $materialsClass = iveeCoreConfig::getIveeClassName('materials');
        $rmat = new $materialsClass;
        
        $numPortions = $batchSize / $this->portionSize;
        foreach ($this->requirements[ProcessData::ACTIVITY_MANUFACTURING] as $mat) {
            $rmat->addMaterial($mat['typ'], $mat['qua'] * $numPortions);
        }
        return $rmat;
    }
}

?>