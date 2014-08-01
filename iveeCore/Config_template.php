<?php
/**
 * Main configuration file for iveeCore.
 *
 * Copy and edit this file according to your environment. The edited file should be saved as Config.php
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Config_template.php
 *
 */

namespace iveeCore;

/**
 * The Config class holds the basic iveeCore configuration for database, cache, classnames, EMDR and CREST.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Config_template.php
 *
 */
class Config
{
    /////////////////////
    // Edit below here //
    /////////////////////

    //DB config
    protected static $DB_HOST = 'localhost';
    protected static $DB_PORT = 3306;
    protected static $DB_USER = 'eve_sde';
    protected static $DB_PW   = 'eve_sde_pw';
    protected static $DB_NAME = 'eve_sde_cri10';

    //Cache config
    protected static $USE_CACHE    = true;
    protected static $CACHE_HOST   = 'localhost';
    protected static $CACHE_PORT   = '11211';
    protected static $CACHE_PREFIX = 'ivee_';

    //EMDR config
    //https://eve-market-data-relay.readthedocs.org/en/latest/access.html
    protected static $EMDR_RELAY_URL = 'tcp://relay-eu-germany-1.eve-emdr.com:8050';

    //EVE CREST base URL. Needs trailing slash.
    protected static $CREST_BASE_URL = 'http://public-crest.eveonline.com/';

    //change the application name in the parenthesis to your application. It is used when accessing the CREST API.
    protected static $USER_AGENT = 'iveeCore/2.0 (unknown application)';

    //To enable developers to extend iveeCore with their own classes (inheriting from iveeCore), it dynamically lookups
    //up class names before instantiating them. This array maps from class "nicknames" to fully qualified names, which
    //can then be used by the autoloader. Change according to your needs.
    protected static $classes = array(
        'AssemblyLine'           => '\iveeCore\AssemblyLine',
        'Blueprint'              => '\iveeCore\Blueprint',
        'Cache'                  => '\iveeCore\MemcachedWrapper',
        'CopyProcessData'        => '\iveeCore\CopyProcessData',
        'Decryptor'              => '\iveeCore\Decryptor',
        'Defaults'               => '\iveeCoreExtensions\MyDefaults',
        'FitParser'              => '\iveeCore\FitParser',
        'ICache'                 => '\iveeCore\ICache',
        'IndustryModifier'       => '\iveeCore\IndustryModifier',
        'InventionProcessData'   => '\iveeCore\InventionProcessData',
        'InventorBlueprint'      => '\iveeCore\InventorBlueprint',
        'InventableBlueprint'    => '\iveeCore\InventableBlueprint',
        'Manufacturable'         => '\iveeCore\Manufacturable',
        'ManufactureProcessData' => '\iveeCore\ManufactureProcessData',
        'MaterialMap'            => '\iveeCore\MaterialMap',
        'MaterialParseResult'    => '\iveeCore\MaterialParseResult',
        'ProcessData'            => '\iveeCore\ProcessData',
        'Reaction'               => '\iveeCore\Reaction',
        'ReactionProcessData'    => '\iveeCore\ReactionProcessData',
        'ReactionProduct'        => '\iveeCore\ReactionProduct',
        'ResearchMEProcessData'  => '\iveeCore\ResearchMEProcessData',
        'ResearchTEProcessData'  => '\iveeCore\ResearchTEProcessData',
        'SDE'                    => '\iveeCore\SDE',
        'Sellable'               => '\iveeCore\Sellable',
        'SkillMap'               => '\iveeCore\SkillMap',
        'SolarSystem'            => '\iveeCore\SolarSystem',
        'Speciality'             => '\iveeCore\Speciality',
        'Station'                => '\iveeCore\Station',
        'Team'                   => '\iveeCore\Team',
        'Type'                   => '\iveeCore\Type',
        'Util'                   => '\iveeCore\Util',
        'CrestDataUpdate'                => '\iveeCore\CREST\CrestDataUpdater',
        'CrestFetcher'                   => '\iveeCore\CREST\Fetcher',
        'CrestIndustryFacilitiesUpdater' => '\iveeCore\CREST\IndustryFacilitiesUpdater',
        'CrestIndustrySystemsUpdater'    => '\iveeCore\CREST\IndustrySystemsUpdater',
        'CrestMarketPricesUpdater'       => '\iveeCore\CREST\MarketPricesUpdater',
        'CrestSpecialitiesUpdater'       => '\iveeCore\CREST\SpecialitiesUpdater',
        'CrestTeamsUpdater'              => '\iveeCore\CREST\TeamsUpdater',
        'EmdrConsumer'       => '\iveeCore\EMDR\Consumer',
        'EmdrPriceUpdater'   => '\iveeCore\EMDR\PriceUpdater',
        'EmdrHistoryUpdater' => '\iveeCore\EMDR\HistoryUpdater',
        'ActivityIdNotFoundException'         => '\iveeCore\Exceptions\ActivityIdNotFoundException',
        'AssemblyLineTypeIdNotFoundException' => '\iveeCore\Exceptions\AssemblyLineTypeIdNotFoundException',
        'CacheDisabledException'              => '\iveeCore\Exceptions\CacheDisabledException',
        'CrestDataTooOldException'            => '\iveeCore\Exceptions\CrestDataTooOldException',
        'CrestException'                      => '\iveeCore\Exceptions\CrestException',
        'CurlException'                       => '\iveeCore\Exceptions\CurlException',
        'InvalidArgumentException'            => '\iveeCore\Exceptions\InvalidArgumentException',
        'InvalidDecryptorGroupException'      => '\iveeCore\Exceptions\InvalidDecryptorGroupException',
        'InvalidParameterValueException'      => '\iveeCore\Exceptions\InvalidParameterValueException',
        'IveeCoreException'                   => '\iveeCore\Exceptions\IveeCoreException',
        'KeyNotFoundInCacheException'         => '\iveeCore\Exceptions\KeyNotFoundInCacheException',
        'NoMaterialRequirementsException'     => '\iveeCore\Exceptions\NoMaterialRequirementsException',
        'NoOutputItemException'               => '\iveeCore\Exceptions\NoOutputItemException',
        'NoPriceDataAvailableException'       => '\iveeCore\Exceptions\NoPriceDataAvailableException',
        'NoRelevantDataException'             => '\iveeCore\Exceptions\NoRelevantDataException',
        'NoSystemDataAvailableException'      => '\iveeCore\Exceptions\NoSystemDataAvailableException',
        'NotInventableException'              => '\iveeCore\Exceptions\NotInventableException',
        'NotOnMarketException'                => '\iveeCore\Exceptions\NotOnMarketException',
        'NotReprocessableException'           => '\iveeCore\Exceptions\NotReprocessableException',
        'PriceDataTooOldException'            => '\iveeCore\Exceptions\PriceDataTooOldException',
        'StationIdNotFoundException'          => '\iveeCore\Exceptions\StationIdNotFoundException',
        'SystemDataTooOldException'           => '\iveeCore\Exceptions\SystemDataTooOldException',
        'SQLErrorException'                   => '\iveeCore\Exceptions\SQLErrorException',
        'SpecialityIdNotFoundException'       => '\iveeCore\Exceptions\SpecialityIdNotFoundException',
        'SystemIdNotFoundException'           => '\iveeCore\Exceptions\SystemIdNotFoundException',
        'TeamIdNotFoundException'             => '\iveeCore\Exceptions\TeamIdNotFoundException',
        'TypeIdNotFoundException'             => '\iveeCore\Exceptions\TypeIdNotFoundException',
        'TypeNameNotFoundException'           => '\iveeCore\Exceptions\TypeNameNotFoundException',
        'TypeNotCompatibleException'          => '\iveeCore\Exceptions\TypeNotCompatibleException',
        'UnexpectedDataException'             => '\iveeCore\Exceptions\UnexpectedDataException',
        'WrongTypeException'                  => '\iveeCore\Exceptions\WrongTypeException'
    );

    ////////////////////////////
    // Do not edit below here //
    ////////////////////////////

    //regex patterns for input validation.
    const INT_PATTERN      = '/^[0-9]{1,11}$/';
    const BIGINT_PATTERN   = '/^[0-9]{1,20}$/';
    const FLOAT_PATTERN    = '/^\d+(\.\d{1,14})?$/';
    const DATETIME_PATTERN = '/^(([0-9][0-9][0-9][0-9]))-((0[1-9])|(1[0-2]))-((0[1-9])|([12][0-9])|(3[01])) (([01][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])$/';
    const DATE_PATTERN     = '/^(([0-9][0-9][0-9][0-9]))-((0[0-9])|(1[0-2]))-((0[0-9])|([12][0-9])|(3[01]))$/';
    const GENERICNUMERIC_PATTERN = '/^[0-9.]*$/';
    const CREST_CONTENT_TYPE_REPRESENTATION_PATTERN = '/^application\/(.*)\+json; charset=utf-8$/im';
    //when using this pattern for strings in SQL queries, you MUST encase them in double quotes, as single quotes are 
    //allowed!
    const SANITIZE_STRING_PATTERN = "/[^0-9a-zA-Z()_&':-\s]/";

    /**
     * Instantiates Config object. Private so this class is only used as static.
     * 
     * @return Config
     */
    private function __construct()
    {
    }

    /**
     * Returns configured database host
     * 
     * @return string
     */
    public static function getDbHost()
    {
        return static::$DB_HOST;    
    }
    
    /**
     * Returns configured database port
     * 
     * @return int
     */
    public static function getDbPort()
    {
        return static::$DB_PORT;
    }
    
    /**
     * Returns configured database user
     * 
     * @return string
     */
    public static function getDbUser()
    {
        return static::$DB_USER;
    }
    
    /**
     * Returns configured database password
     * 
     * @return string
     */
    public static function getDbPw()
    {
        return static::$DB_PW;
    }
    
    /**
     * Returns configured database name
     * 
     * @return string
     */
    public static function getDbName()
    {
        return static::$DB_NAME;
    }

    /**
     * Returns if cache use is configured or not
     * 
     * @return bool
     */
    public static function getUseCache()
    {
        return static::$USE_CACHE;
    }
    
    /**
     * Returns configured cache host name
     * 
     * @return string
     */
    public static function getCacheHost()
    {
        return static::$CACHE_HOST;
    }
    
    /**
     * Returns configured cache port
     * 
     * @return int
     */
    public static function getCachePort()
    {
        return static::$CACHE_PORT;
    }
    
    /**
     * Returns configured cache prefix for keys stored by iveeCore
     * 
     * @return string
     */
    public static function getCachePrefix()
    {
        return static::$CACHE_PREFIX;
    }

    /**
     * Returns configured EMDR URL
     * 
     * @return string
     */
    public static function getEmdrRelayUrl()
    {
        return static::$EMDR_RELAY_URL;
    }
    
    /**
     * Returns configured CREST base URL
     * 
     * @return string
     */
    public static function getCrestBaseUrl()
    {
        return static::$CREST_BASE_URL;
    }
    
    /**
     * Returns configured user agent to be used by the CREST client
     * 
     * @return string
     */
    public static function getUserAgent()
    {
        return static::$USER_AGENT;
    }

    /**
     * Returns the fully qualified name of classes to instantiate for a given class nickname. This is used extensively
     * in iveeCore to allow for configurable class instantiation
     * 
     * @param string $classNickname a short name for the class
     * 
     * @return string
     */
    public static function getIveeClassName($classNickname)
    {
        if (isset(static::$classes[$classNickname]))
            return static::$classes[$classNickname];
        else
            exit('Fatal Error: No Class configured  for "' . $classNickname . '" in iveeCore' . DIRECTORY_SEPARATOR
                . 'Config.php' . PHP_EOL);
    }
}
