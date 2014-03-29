<?php

/**
 * MaterialParseResult objects are used to return material parsing results.
 * It's just a wrapper around a MaterialMap plus an array to hold unparseable strings.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/MaterialParseResult.php
 * @package iveeCore
 */
class MaterialParseResult {
    
    /**
     * @var MaterialMap $parsedMaterialMap holds the MaterialMap with the parsed materials
     */
    private $parsedMaterialMap;
    
    /**
     * @var array $unparseables holds whatever strings couldn't be parsed or an error description.
     */
    private $unparseables;

    public function __construct() {
        $materialMapClass = iveeCoreConfig::getIveeClassName('MaterialMap');
        $this->parsedMaterialMap = new $materialMapClass;
        $this->unparseables = array();
    }
    
    /**
     * @return MaterialMap
     */
    public function getMaterialMap(){
        return $this->parsedMaterialMap;
    }
    
    /**
     * Add an unparseable string or error description.
     * Strings are passed through htmlspecialchars() to thwart injection attempts.
     * @param string $unparseable
     */
    public function addUnparseable($unparseable){
        $this->unparseables[] = htmlspecialchars($unparseable);
    }
    
    /**
     * Returns the array with the unparseables.
     * @return array
     */
    public function getUnparseables(){
        return $this->unparseables;
    }
}

?>