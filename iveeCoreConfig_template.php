<?php

/**
 * Main include and configuration file for iveeCore.
 * 
 * Copy and edit this file according to your environment and industrial setup in eve.
 * The edited file should be named iveeCoreConfig.php
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license public domain
 * @link https://github.com/aineko-m/iveeCore/blob/master/iveeCoreConfig.php
 * @package iveeCore
 */

//include all required classes with absolute path. If you extended iveeCore classes, you'll want to add them here.
$iveeCoreClassPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
require_once($iveeCoreClassPath . 'iveeCoreExceptions.php');
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
    protected static $DB_NAME = 'eve_sde_ody12';
    
    //Memcached config
    protected static $USE_MEMCACHED    = TRUE;
    protected static $MEMCACHED_HOST   = 'localhost';
    protected static $MEMCACHED_PORT   = '11211';
    protected static $MEMCACHED_PREFIX = 'ivee_';
    
    //EMDR config
    //https://eve-market-data-relay.readthedocs.org/en/latest/access.html
    protected static $EMDR_RELAY_URL = "tcp://relay-eu-germany-1.eve-emdr.com:8050";
    
    //default market region/system/station
    protected static $DEFAULT_REGIONID  = 10000002; //The Forge
    protected static $DEFAULT_SYSTEMID  = 30000142; //Jita
    protected static $DEFAULT_STATIONID = 60003760; //Jita 4-4 CNAP

    //tax factors
    protected static $DEFAULT_BUY_TAX_FACTOR = 1.015;  // = 100% + 0.75% broker fee + 0.75% transaction tax
    protected static $DEFAULT_SELL_TAX_FACTOR = 0.985; // = 100% - (0.75% broker fee + 0.75% transaction tax)

    //default BPO ME/PE to use
    //note that default values for specific BPs can be configured in SDEUtil
    protected static $DEFAULT_BPO_ME = 120;
    protected static $DEFAULT_BPO_PE = 10;

    //POS slot configuration
    //set the average utilization factor (for slot cost estimation) 
    protected static $POS_SLOT_UTILIZATION_FACTOR = 0.5;
    
    protected static $USE_POS_MANUFACTURING = FALSE;
    protected static $POS_MANUFACTURE_SLOT_TIME_FACTOR = 0.75;
    
    protected static $USE_POS_COPYING = TRUE;
    protected static $POS_COPY_SLOT_TIME_FACTOR = 0.65;
    
    protected static $USE_POS_INVENTION = FALSE;
    protected static $POS_INVENTION_SLOT_TIME_FACTOR = 0.5;
    
    protected static $USE_POS_ME_RESEARCH = TRUE;
    protected static $POS_ME_RESEARCH_SLOT_TIME_FACTOR = 0.75;
    
    protected static $USE_POS_PE_RESEARCH = TRUE;
    protected static $POS_PE_RESEARCH_SLOT_TIME_FACTOR = 0.75;

    //number of available slots (character or POS (if using), whichever is lower)
    protected static $NUM_MANUFACTURE_SLOTS = 20;
    protected static $NUM_COPY_SLOTS = 20;
    protected static $NUM_INVENTION_SLOTS = 10;
    protected static $NUM_ME_RESEARCH_SLOTS = 9;
    protected static $NUM_PE_RESEARCH_SLOTS = 9;

    //default station slot cost
    protected static $STATION_MANUFACTURING_HOUR_COST = 333;
    protected static $STATION_COPYING_HOUR_COST = 2000; //rough estimate, as values vary a lot
    protected static $STATION_INVENTION_HOUR_COST = 416.67;
    protected static $STATION_ME_RESEARCH_HOUR_COST = 2000;
    protected static $STATION_PE_RESEARCH_HOUR_COST = 2000;

    //maximum acceptable price data age
    protected static $MAX_PRICE_DATA_AGE = 86400; //1 day

    //add all itemID => quantity consumed hourly by POS(es) etc.
    //this is used to calculate an approximate cost for using POS slots 
    protected static $hourlyMaterials = array(
        4051 => 40, //caldari fuel block
        24593 => 1  //caldari empire starbase charter
    );
    
    //controls which classes get instantiated. If extending iveeCore via subclassing, change accordingly
    protected static $classes = array(
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
        'SkillMap'               => 'SkillMap'
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
    public static function getDbHost(){return self::$DB_HOST;}
    public static function getDbPort(){return self::$DB_PORT;}
    public static function getDbUser(){return self::$DB_USER;}
    public static function getDbPw(){return self::$DB_PW;}
    public static function getDbName(){return self::$DB_NAME;}
    
    //memcached configuration getters
    public static function getUseMemcached(){return self::$USE_MEMCACHED;}
    public static function getMemcachedHost(){return self::$MEMCACHED_HOST;}
    public static function getMemcachedPort(){return self::$MEMCACHED_PORT;}
    public static function getMemcachedPrefix(){return self::$MEMCACHED_PREFIX;}
    
    //EMDR config getter
    public static function getEmdrRelayUrl(){return self::$EMDR_RELAY_URL;}
    
    //defaults configuration
    public static function getDefaultRegionID(){return self::$DEFAULT_REGIONID;}
    public static function getDefaultSystemID(){return self::$DEFAULT_SYSTEMID;}
    public static function getDefaultStationID(){return self::$DEFAULT_STATIONID;}
    
    public static function getDefaultBuyTaxFactor(){return self::$DEFAULT_BUY_TAX_FACTOR;}
    public static function getDefaultSellTaxFactor(){return self::$DEFAULT_SELL_TAX_FACTOR;}
    
    public static function getDefaultBpoMe(){return self::$DEFAULT_BPO_ME;}
    public static function getDefaultBpoPe(){return self::$DEFAULT_BPO_PE;}
    
    //POS configuration getters
    public static function getPosSlotUtilizationFactor(){return self::$POS_SLOT_UTILIZATION_FACTOR;}
    
    public static function getUsePosManufacturing(){return self::$USE_POS_MANUFACTURING;}
    public static function getPosManufactureSlotTimeFactor(){return self::$POS_MANUFACTURE_SLOT_TIME_FACTOR;}
    
    public static function getUsePosCopying(){return self::$USE_POS_COPYING;}
    public static function getPosCopySlotTimeFactor(){return self::$POS_COPY_SLOT_TIME_FACTOR;}
    
    public static function getUsePosInvention(){return self::$USE_POS_INVENTION;}
    public static function getPosInventionSlotTimeFactor(){return self::$POS_INVENTION_SLOT_TIME_FACTOR;}
    
    public static function getUsePosMeResearch(){return self::$USE_POS_ME_RESEARCH;}
    public static function getPosMeResearchSlotTimeFactor(){return self::$POS_ME_RESEARCH_SLOT_TIME_FACTOR;}

    public static function getUsePosPeResearch(){return self::$USE_POS_PE_RESEARCH;}
    public static function getPosPeResearchSlotTimeFactor(){return self::$POS_PE_RESEARCH_SLOT_TIME_FACTOR;}
    
    public static function getNumManufactureSlots(){return self::$NUM_MANUFACTURE_SLOTS;}
    public static function getNumCopySlots(){return self::$NUM_COPY_SLOTS;}
    public static function getNumInventionSlots(){return self::$NUM_INVENTION_SLOTS;}
    public static function getNumMeResearchSlots(){return self::$NUM_ME_RESEARCH_SLOTS;}
    public static function getNumPeResearchSlots(){return self::$NUM_PE_RESEARCH_SLOTS;}
    
    //station slot cost getters
    public static function getStationManufacturingCostPerSecond(){
        return self::$STATION_MANUFACTURING_HOUR_COST / 3600;}
    public static function getStationCopyingCostPerSecond(){return self::$STATION_COPYING_HOUR_COST / 3600;}
    public static function getStationInventionCostPerSecond(){return self::$STATION_INVENTION_HOUR_COST / 3600;}
    public static function getStationMeResearchCostPerSecond(){return self::$STATION_ME_RESEARCH_HOUR_COST / 3600;}
    public static function getStationPeResearchCostPerSecond(){return self::$STATION_PE_RESEARCH_HOUR_COST / 3600;}
    
    public static function getMaxPriceDataAge(){return self::$MAX_PRICE_DATA_AGE;}
    
    public static function getHourlyMaterials(){return self::$hourlyMaterials;}
    
    //returns the name of classes to instantiate
    public static function getIveeClassName($baseClass){
        if(isset(self::$classes[$baseClass])){
            return self::$classes[$baseClass];
        } else {
            exit('Fatal Error: No Class configured  for "' . $baseClass . '" in iveeCoreConfig.' . PHP_EOL);
        }
    }
}

?>