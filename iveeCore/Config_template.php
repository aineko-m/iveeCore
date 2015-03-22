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

    //SDE DB config
    protected static $sdeDbHost = 'localhost';
    protected static $sdeDbPort = 3306;
    protected static $sdeDbUser = 'eve_sde';
    protected static $sdeDbPw   = 'eve_sde_pw';
    protected static $sdeDbName = 'eve_sde_tia10';

    //iveeCore DB config
    protected static $iveeDbName = 'iveeCore';

    //Cache config
    protected static $useCache    = true;
    protected static $cachePrefix = 'iveeCore_';

    //Memcached specific settings
    protected static $cacheHost   = 'localhost';
    protected static $cachePort   = '11211';

    //EMDR config
    //https://eve-market-data-relay.readthedocs.org/en/latest/access.html
    protected static $emdrRelayUrl = 'tcp://relay-eu-germany-1.eve-emdr.com:8050';

    //EVE CREST base URL. Needs trailing slash.
    protected static $crestBaseUrl = 'http://public-crest.eveonline.com/';

    //change the application name in the parenthesis to your application. It is used when accessing the CREST API.
    protected static $userAgent = 'iveeCore/2.4 (unknown application)';

    //To enable developers to extend iveeCore with their own classes (inheriting from iveeCore), it dynamically lookups
    //up class names before instantiating them. This array maps from class "nicknames" to fully qualified names, which
    //can then be used by the autoloader. Change according to your needs.
    protected static $classes = array(
        'AssemblyLine'           => '\iveeCore\AssemblyLine',
        'Blueprint'              => '\iveeCore\Blueprint',
//        'Cache'                  => '\iveeCore\MemcachedWrapper',
        'Cache'                  => '\iveeCore\RedisWrapper',
        'CacheableArray'         => '\iveeCore\CacheableArray',
        'CacheableCommon'        => '\iveeCore\CacheableCommon',
        'CopyProcessData'        => '\iveeCore\CopyProcessData',
        'Decryptor'              => '\iveeCore\Decryptor',
        'Defaults'               => '\iveeCoreExtensions\MyDefaults',
        'FitParser'              => '\iveeCore\FitParser',
        'GlobalPriceData'        => '\iveeCore\GlobalPriceData',
        'ICache'                 => '\iveeCore\ICache',
        'ICacheable'             => '\iveeCore\ICacheable',
        'IndustryModifier'       => '\iveeCore\IndustryModifier',
        'InstancePool'           => '\iveeCore\InstancePool',
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
        'RegionMarketData'       => '\iveeCore\RegionMarketData',
        'Relic'                  => '\iveeCore\Relic',
        'ResearchMEProcessData'  => '\iveeCore\ResearchMEProcessData',
        'ResearchTEProcessData'  => '\iveeCore\ResearchTEProcessData',
        'SDE'                    => '\iveeCore\SDE',
        'SdeType'                => '\iveeCore\SdeType',
        'SkillMap'               => '\iveeCore\SkillMap',
        'SolarSystem'            => '\iveeCore\SolarSystem',
        'Starbase'               => '\iveeCore\Starbase',
        'Station'                => '\iveeCore\Station',
        'T3Blueprint'            => '\iveeCore\T3Blueprint',
        'Type'                   => '\iveeCore\Type',
        'Util'                   => '\iveeCore\Util',
        'CrestDataUpdate'                => '\iveeCore\CREST\CrestDataUpdater',
        'CrestFetcher'                   => '\iveeCore\CREST\Fetcher',
        'CrestIndustryFacilitiesUpdater' => '\iveeCore\CREST\IndustryFacilitiesUpdater',
        'CrestIndustrySystemsUpdater'    => '\iveeCore\CREST\IndustrySystemsUpdater',
        'CrestMarketPricesUpdater'       => '\iveeCore\CREST\MarketPricesUpdater',
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
        'NotResearchableException'            => '\iveeCore\Exceptions\NotResearchableException',
        'NotReverseEngineerableException'     => '\iveeCore\Exceptions\NotReverseEngineerableException',
        'PriceDataTooOldException'            => '\iveeCore\Exceptions\PriceDataTooOldException',
        'StationIdNotFoundException'          => '\iveeCore\Exceptions\StationIdNotFoundException',
        'SystemDataTooOldException'           => '\iveeCore\Exceptions\SystemDataTooOldException',
        'SQLErrorException'                   => '\iveeCore\Exceptions\SQLErrorException',
        'StationIdNotFoundException'          => '\iveeCore\Exceptions\StationIdNotFoundException',
        'StationNameNotFoundException'        => '\iveeCore\Exceptions\StationNameNotFoundException',
        'SystemIdNotFoundException'           => '\iveeCore\Exceptions\SystemIdNotFoundException',
        'SystemNameNotFoundException'         => '\iveeCore\Exceptions\SystemNameNotFoundException',
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
     * Returns configured SDE database host
     *
     * @return string
     */
    public static function getSdeDbHost()
    {
        return static::$sdeDbHost;
    }

    /**
     * Returns configured SDE database port
     *
     * @return int
     */
    public static function getSdeDbPort()
    {
        return static::$sdeDbPort;
    }

    /**
     * Returns configured SDE database user
     *
     * @return string
     */
    public static function getSdeDbUser()
    {
        return static::$sdeDbUser;
    }

    /**
     * Returns configured SDE database password
     *
     * @return string
     */
    public static function getSdeDbPw()
    {
        return static::$sdeDbPw;
    }

    /**
     * Returns configured SDE database name
     *
     * @return string
     */
    public static function getSdeDbName()
    {
        return static::$sdeDbName;
    }

    /**
     * Returns configured iveeCore database name
     *
     * @return string
     */
    public static function getIveeDbName()
    {
        return static::$iveeDbName;
    }

    /**
     * Returns if cache use is configured or not
     *
     * @return bool
     */
    public static function getUseCache()
    {
        return static::$useCache;
    }

    /**
     * Returns configured cache prefix for keys stored by iveeCore
     *
     * @return string
     */
    public static function getCachePrefix()
    {
        return static::$cachePrefix;
    }

    /**
     * Returns configured cache host name
     *
     * @return string
     */
    public static function getCacheHost()
    {
        return static::$cacheHost;
    }

    /**
     * Returns configured cache port
     *
     * @return int
     */
    public static function getCachePort()
    {
        return static::$cachePort;
    }

    /**
     * Returns configured EMDR URL
     *
     * @return string
     */
    public static function getEmdrRelayUrl()
    {
        return static::$emdrRelayUrl;
    }

    /**
     * Returns configured CREST base URL
     *
     * @return string
     */
    public static function getCrestBaseUrl()
    {
        return static::$crestBaseUrl;
    }

    /**
     * Returns configured user agent to be used by the CREST client
     *
     * @return string
     */
    public static function getUserAgent()
    {
        return static::$userAgent;
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
            exit('Fatal Error: No class configured  for "' . $classNickname . '" in iveeCore' . DIRECTORY_SEPARATOR
                . 'Config.php' . PHP_EOL);
    }
}
