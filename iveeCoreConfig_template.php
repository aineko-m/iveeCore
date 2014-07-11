<?php

/**
 * Main include and configuration file for iveeCore.
 * 
 * Copy and edit this file according to your environment. The edited file should be named iveeCoreConfig.php
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license public domain
 * @link https://github.com/aineko-m/iveeCore/blob/master/iveeCoreConfig_template.php
 * @package iveeCore
 */

//include all required classes with absolute path. If you extended iveeCore classes, you should add them here.
$iveeCoreClassPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
require_once($iveeCoreClassPath . 'IveeCoreDefaults.php');
require_once($iveeCoreClassPath . 'MyIveeCoreDefaults.php');
require_once($iveeCoreClassPath . 'IveeCoreExceptions.php');
require_once($iveeCoreClassPath . 'SDE.php');
require_once($iveeCoreClassPath . 'Type.php');
require_once($iveeCoreClassPath . 'Sellable.php');
require_once($iveeCoreClassPath . 'Manufacturable.php');
require_once($iveeCoreClassPath . 'Blueprint.php');
require_once($iveeCoreClassPath . 'InventorBlueprint.php');
require_once($iveeCoreClassPath . 'InventableBlueprint.php');
require_once($iveeCoreClassPath . 'Decryptor.php');
require_once($iveeCoreClassPath . 'Reaction.php');
require_once($iveeCoreClassPath . 'ReactionProduct.php');
require_once($iveeCoreClassPath . 'ProcessData.php');
require_once($iveeCoreClassPath . 'ManufactureProcessData.php');
require_once($iveeCoreClassPath . 'CopyProcessData.php');
require_once($iveeCoreClassPath . 'InventionProcessData.php');
require_once($iveeCoreClassPath . 'ReactionProcessData.php');
require_once($iveeCoreClassPath . 'SDEUtil.php');
require_once($iveeCoreClassPath . 'MaterialMap.php');
require_once($iveeCoreClassPath . 'SkillMap.php');
require_once($iveeCoreClassPath . 'FitParser.php');
require_once($iveeCoreClassPath . 'MaterialParseResult.php');
require_once($iveeCoreClassPath . 'EmdrConsumer.php');
require_once($iveeCoreClassPath . 'EmdrHistoryUpdate.php');
require_once($iveeCoreClassPath . 'EmdrPriceUpdate.php');

//eve runs on UTC time
date_default_timezone_set('UTC');

class iveeCoreConfig{
    
    /////////////////////
    // Edit below here //
    /////////////////////
    
    //DB config
    protected static $DB_HOST = 'localhost';
    protected static $DB_PORT = 3306;
    protected static $DB_USER = 'eve_sde';
    protected static $DB_PW   = 'eve_sde_pw';
    protected static $DB_NAME = 'eve_sde_kro10';
    
    //Memcached config
    protected static $USE_MEMCACHED    = TRUE;
    protected static $MEMCACHED_HOST   = 'localhost';
    protected static $MEMCACHED_PORT   = '11211';
    protected static $MEMCACHED_PREFIX = 'ivee_';
    
    //EMDR config
    //https://eve-market-data-relay.readthedocs.org/en/latest/access.html
    protected static $EMDR_RELAY_URL = "tcp://relay-eu-germany-1.eve-emdr.com:8050";
    
    //controls which classes get instantiated. If extending iveeCore via subclassing, change accordingly
    protected static $classes = array(
        'IveeCoreDefaults'       => 'MyIveeCoreDefaults',
        'Type'                   => 'Type',
        'Sellable'               => 'Sellable',
        'Manufacturable'         => 'Manufacturable',
        'Decryptor'              => 'Decryptor',
        'Blueprint'              => 'Blueprint',
        'InventorBlueprint'      => 'InventorBlueprint',
        'InventableBlueprint'    => 'InventableBlueprint',
        'Reaction'               => 'Reaction',
        'ReactionProduct'        => 'ReactionProduct',
        'ReactionProcessData'    => 'ReactionProcessData',
        'ProcessData'            => 'ProcessData',
        'ManufactureProcessData' => 'ManufactureProcessData',
        'CopyProcessData'        => 'CopyProcessData',
        'InventionProcessData'   => 'InventionProcessData',
        'SDEUtil'                => 'SDEUtil',
        'MaterialMap'            => 'MaterialMap',
        'SkillMap'               => 'SkillMap',
        'FitParser'              => 'FitParser',
        'MaterialParseResult'    => 'MaterialParseResult',
        'EmdrConsumer'           => 'EmdrConsumer',
        'EmdrPriceUpdate'        => 'EmdrPriceUpdate',
        'EmdrHistoryUpdate'      => 'EmdrHistoryUpdate'
    );
    
    ////////////////////////////
    // Do not edit below here //
    ////////////////////////////
    
    //regex patterns for input validation.
    const INT_PATTERN = '/^[0-9]{1,11}$/';
    const BIGINT_PATTERN = '/^[0-9]{1,20}$/';
    const FLOAT_PATTERN = '/^\d+(\.\d{1,14})?$/';
    const DATETIME_PATTERN = '/^(([0-9][0-9][0-9][0-9]))-((0[1-9])|(1[0-2]))-((0[1-9])|([12][0-9])|(3[01])) (([01][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])$/';
    const DATE_PATTERN = '/^(([0-9][0-9][0-9][0-9]))-((0[0-9])|(1[0-2]))-((0[0-9])|([12][0-9])|(3[01]))$/';
    const GENERICNUMERIC_PATTERN = '/^[0-9.]*$/';

    //make non-instantiable, i.e. static only
    private function __construct() {}
    
    //DB configuration getters
    public static function getDbHost(){return static::$DB_HOST;}
    public static function getDbPort(){return static::$DB_PORT;}
    public static function getDbUser(){return static::$DB_USER;}
    public static function getDbPw(){return static::$DB_PW;}
    public static function getDbName(){return static::$DB_NAME;}
    
    //memcached configuration getters
    public static function getUseMemcached(){return static::$USE_MEMCACHED;}
    public static function getMemcachedHost(){return static::$MEMCACHED_HOST;}
    public static function getMemcachedPort(){return static::$MEMCACHED_PORT;}
    public static function getMemcachedPrefix(){return static::$MEMCACHED_PREFIX;}
    
    //EMDR config getter
    public static function getEmdrRelayUrl(){return static::$EMDR_RELAY_URL;}
    
    //returns the name of classes to instantiate
    public static function getIveeClassName($baseClass){
        if(isset(static::$classes[$baseClass])){
            return static::$classes[$baseClass];
        } else {
            exit('Fatal Error: No Class configured  for "' . $baseClass . '" in iveeCoreConfig.' . PHP_EOL);
        }
    }
}

?>