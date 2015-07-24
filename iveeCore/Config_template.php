<?php
/**
 * Main configuration file for iveeCore.
 *
 * Copy and edit this file according to your environment. The edited file should be saved as Config.php
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Config_template.php
 */

namespace iveeCore;

/**
 * The Config class holds the basic iveeCore configuration for database, cache, classnames and CREST.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Config_template.php
 */
class Config
{
    const VERSION = 'iveeCore/2.6';

    /////////////////////
    // Edit below here //
    /////////////////////

    //SDE DB config
    protected static $sdeDbHost = 'localhost'; //if DB connection fails try 127.0.0.1 instead of localhost
    protected static $sdeDbPort = 3306;
    protected static $sdeDbUser = 'eve_sde';
    protected static $sdeDbPw   = 'eve_sde_pw';
    protected static $sdeDbName = 'eve_sde_mos10';

    //iveeCore DB config
    protected static $iveeDbName = 'iveeCore';

    //Cache config
    protected static $cachePrefix = 'iveeCore_';

    //Memcached specific settings
    protected static $cacheHost   = 'localhost';
    protected static $cachePort   = 11211; //memcached default: 11211, redis default: 6379

    //CREST config
    protected static $authedCrestBaseUrl      = 'https://crest-tq.eveonline.com/';
    protected static $crestClientId           = 'myclientid';
    protected static $crestClientSecret       = 'myclientsecret';
    protected static $crestClientRefreshToken = 'myclientrefreshtoken';

    //set the name of your application. It is used as part of the User Agent when accessing the CREST API.
    protected static $applicationName = 'unknown application';

    //Defines the region for pricing operations when no regionId is specified
    protected static $defaultMarketRegionId = 10000002; //The Forge

    /**
     * Defines the maximum acceptable price data age in seconds.
     * When using pricing methods and there is no relevant price data available or its age is greater than this value,
     * it will trigger a lookup to CREST.
     */
    protected static $maxPriceDataAge = 21600;

    //Defines the regions that will have their market data collected via CREST
    protected static $trackedMarketRegionIds = array(
//        10000001, //Derelik
        10000002, //The Forge
//        10000003, //Vale of the Silent
//        10000004, //UUA-F4
//        10000005, //Detorid
//        10000006, //Wicked Creek
//        10000007, //Cache
//        10000008, //Scalding Pass
//        10000009, //Insmother
//        10000010, //Tribute
//        10000011, //Great Wildlands
//        10000012, //Curse
//        10000013, //Malpais
//        10000014, //Catch
//        10000015, //Venal
        10000016, //Lonetrek
//        10000017, //J7HZ-F
//        10000018, //The Spire
//        10000019, //A821-A
//        10000020, //Tash-Murkon
//        10000021, //Outer Passage
//        10000022, //Stain
//        10000023, //Pure Blind
//        10000025, //Immensea
//        10000027, //Etherium Reach
//        10000028, //Molden Heath
//        10000029, //Geminate
        10000030, //Heimatar
//        10000031, //Impass
        10000032, //Sinq Laison
//        10000033, //The Citadel
//        10000034, //The Kalevala Expanse
//        10000035, //Deklein
//        10000036, //Devoid
//        10000037, //Everyshore
//        10000038, //The Bleak Lands
//        10000039, //Esoteria
//        10000040, //Oasa
//        10000041, //Syndicate
        10000042, //Metropolis
        10000043, //Domain
//        10000044, //Solitude
//        10000045, //Tenal
//        10000046, //Fade
//        10000047, //Providence
//        10000048, //Placid
//        10000049, //Khanid
//        10000050, //Querious
//        10000051, //Cloud Ring
//        10000052, //Kador
//        10000053, //Cobalt Edge
//        10000054, //Aridia
//        10000055, //Branch
//        10000056, //Feythabolis
//        10000057, //Outer Ring
//        10000058, //Fountain
//        10000059, //Paragon Soul
//        10000060, //Delve
//        10000061, //Tenerifis
//        10000062, //Omist
//        10000063, //Period Basis
//        10000064, //Essence
//        10000065, //Kor-Azor
//        10000066, //Perrigen Falls
//        10000067, //Genesis
//        10000068, //Verge Vendor
//        10000069  //Black Rise
    );

    //To enable developers to extend iveeCore with their own classes (inheriting from iveeCore), it dynamically lookups
    //up class names before instantiating them. This array maps from class "nicknames" to fully qualified names, which
    //can then be used by the autoloader. Change according to your needs.
    protected static $classes = array(
        'AssemblyLine'           => '\iveeCore\AssemblyLine',
        'Blueprint'              => '\iveeCore\Blueprint',
        'BlueprintModifier'      => '\iveeCore\BlueprintModifier',
        'Cache'                  => '\iveeCore\MemcachedWrapper',
        //'Cache'                  => '\iveeCore\RedisWrapper',
        'CacheableArray'         => '\iveeCore\CacheableArray',
        'CharacterModifier'      => '\iveeCore\CharacterModifier',
        'CoreDataCommon'         => '\iveeCore\CoreDataCommon',
        'CopyProcessData'        => '\iveeCore\CopyProcessData',
        'Decryptor'              => '\iveeCore\Decryptor',
        'FitParser'              => '\iveeCore\FitParser',
        'GlobalPriceData'        => '\iveeCore\GlobalPriceData',
        'IndustryModifier'       => '\iveeCore\IndustryModifier',
        'InstancePool'           => '\iveeCore\InstancePool',
        'InventionProcessData'   => '\iveeCore\InventionProcessData',
        'InventorBlueprint'      => '\iveeCore\InventorBlueprint',
        'InventableBlueprint'    => '\iveeCore\InventableBlueprint',
        'Manufacturable'         => '\iveeCore\Manufacturable',
        'ManufactureProcessData' => '\iveeCore\ManufactureProcessData',
        'MarketHistory'          => '\iveeCore\MarketHistory',
        'MarketPrices'           => '\iveeCore\MarketPrices',
        'MarketProcessor'        => '\iveeCore\CREST\MarketProcessor',
        'MaterialMap'            => '\iveeCore\MaterialMap',
        'MaterialParseResult'    => '\iveeCore\MaterialParseResult',
        'ProcessData'            => '\iveeCore\ProcessData',
        'Reaction'               => '\iveeCore\Reaction',
        'ReactionProcessData'    => '\iveeCore\ReactionProcessData',
        'ReactionProduct'        => '\iveeCore\ReactionProduct',
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
        'CrestIndustryFacilitiesUpdater' => '\iveeCore\CREST\IndustryFacilitiesUpdater',
        'CrestIndustrySystemsUpdater'    => '\iveeCore\CREST\IndustrySystemsUpdater',
        'CrestIveeUpdater'               => '\iveeCore\CREST\IveeUpdater',
        'CrestMarketPricesUpdater'       => '\iveeCore\CREST\MarketPricesUpdater',
        'CrestMarketProcessor'           => '\iveeCore\CREST\MarketProcessor',
        'CrestPriceEstimator'            => '\iveeCore\CREST\PriceEstimator',
        'ActivityIdNotFoundException'         => '\iveeCore\Exceptions\ActivityIdNotFoundException',
        'AssemblyLineTypeIdNotFoundException' => '\iveeCore\Exceptions\AssemblyLineTypeIdNotFoundException',
        'CrestDataTooOldException'            => '\iveeCore\Exceptions\CrestDataTooOldException',
        'CrestException'                      => '\iveeCore\Exceptions\CrestException',
        'CurlException'                       => '\iveeCore\Exceptions\CurlException',
        'InvalidArgumentException'            => '\iveeCore\Exceptions\InvalidArgumentException',
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
        'WrongTypeException'                  => '\iveeCore\Exceptions\WrongTypeException',

        'Client'                => '\iveeCrest\Client',
        'CurlWrapper'           => '\iveeCrest\CurlWrapper',
        'EndpointHandler'       => '\iveeCrest\EndpointHandler',
        'Response'              => '\iveeCrest\Response',
        'IveeCrestException'    => '\iveeCrest\Exceptions\IveeCrestException'
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
    const SANITIZE_STRING_PATTERN = "/[^0-9a-zA-Z()_&':\s-]/";

    /**
     * Instantiates Config object. Private so this class is only used as static.
     */
    private function __construct()
    {
    }

    /**
     * Returns configured SDE database host.
     *
     * @return string
     */
    public static function getSdeDbHost()
    {
        return static::$sdeDbHost;
    }

    /**
     * Configure SDE database host.
     *
     * @param string $sdeDbHost for the SDE DB connection
     *
     * @return void
     */
    public static function setSdeDbHost($sdeDbHost)
    {
        static::$sdeDbHost = $sdeDbHost;
    }

    /**
     * Returns configured SDE database port.
     *
     * @return int
     */
    public static function getSdeDbPort()
    {
        return static::$sdeDbPort;
    }

    /**
     * Configure SDE database port.
     *
     * @param int $sdeDbPort for the SDE DB connection
     *
     * @return void
     */
    public static function setSdeDbPort($sdeDbPort)
    {
        static::$sdeDbPort = $sdeDbPort;
    }

    /**
     * Returns configured SDE database user.
     *
     * @return string
     */
    public static function getSdeDbUser()
    {
        return static::$sdeDbUser;
    }

    /**
     * Configure SDE database user.
     *
     * @param string $sdeDbUser for the SDE DB connection
     *
     * @return void
     */
    public static function setSdeDbUser($sdeDbUser)
    {
        static::$sdeDbUser = $sdeDbUser;
    }

    /**
     * Returns configured SDE database password.
     *
     * @return string
     */
    public static function getSdeDbPw()
    {
        return static::$sdeDbPw;
    }

    /**
     * Configure SDE database password.
     *
     * @param string $sdeDbPw for the SDE DB connection
     *
     * @return void
     */
    public static function setSdeDbPw($sdeDbPw)
    {
        static::$sdeDbPw = $sdeDbPw;
    }

    /**
     * Returns configured SDE database name.
     *
     * @return string
     */
    public static function getSdeDbName()
    {
        return static::$sdeDbName;
    }

    /**
     * Configure SDE database name.
     *
     * @param string $sdeDbName for the SDE DB connection
     *
     * @return void
     */
    public static function setSdeDbName($sdeDbName)
    {
        static::$sdeDbName = $sdeDbName;
    }

    /**
     * Returns configured iveeCore database name.
     *
     * @return string
     */
    public static function getIveeDbName()
    {
        return static::$iveeDbName;
    }

    /**
     * Configure iveeCore database name.
     *
     * @param string $iveeDbName for the ivee DB connection
     *
     * @return void
     */
    public static function setIveeDbName($iveeDbName)
    {
        static::$iveeDbName = $iveeDbName;
    }

    /**
     * Returns configured cache prefix for keys stored by iveeCore.
     *
     * @return string
     */
    public static function getCachePrefix()
    {
        return static::$cachePrefix;
    }

    /**
     * Configure cache prefix for keys stored by iveeCore.
     *
     * @param string $cachePrefix to be used
     *
     * @return void
     */
    public static function setCachePrefix($cachePrefix)
    {
        static::$cachePrefix = $cachePrefix;
    }

    /**
     * Returns configured cache host name.
     *
     * @return string
     */
    public static function getCacheHost()
    {
        return static::$cacheHost;
    }

    /**
     * Configure cache host name.
     *
     * @param string $cacheHost for the cache connection
     *
     * @return void
     */
    public static function setCacheHost($cacheHost)
    {
        static::$cacheHost = $cacheHost;
    }

    /**
     * Returns configured cache port.
     *
     * @return int
     */
    public static function getCachePort()
    {
        return static::$cachePort;
    }

    /**
     * Configure cache port.
     *
     * @param int $cachePort for the cache connection
     *
     * @return void
     */
    public static function setCachePort($cachePort)
    {
        static::$cachePort = $cachePort;
    }

    /**
     * Returns configured CREST base URL.
     *
     * @return string
     */
    public static function getCrestBaseUrl()
    {
        return static::$crestBaseUrl;
    }

    /**
     * Configure CREST base URL.
     *
     * @param string $crestBaseUrl for the CREST API
     *
     * @return void
     */
    public static function setCrestBaseUrl($crestBaseUrl)
    {
        static::$crestBaseUrl = $crestBaseUrl;
    }

    /**
     * Returns configured authed CREST base URL.
     *
     * @return string
     */
    public static function getAuthedCrestBaseUrl()
    {
        return static::$authedCrestBaseUrl;
    }

    /**
     * Sets the authed CREST base URL.
     *
     * @param string $authedCrestBaseUrl the URL
     *
     * @return void
     */
    public static function setAuthedCrestBaseUrl($authedCrestBaseUrl)
    {
        static::$authedCrestBaseUrl = $authedCrestBaseUrl;
    }

    /**
     * Returns configured CREST client ID.
     *
     * @return string
     */
    public static function getCrestClientId()
    {
        return static::$crestClientId;
    }

    /**
     * Sets the CREST client id.
     *
     * @param string $crestClientId the client id
     *
     * @return void
     */
    public static function setCrestClientId($crestClientId)
    {
        static::$crestClientId = $crestClientId;
    }

    /**
     * Returns configured CREST client secret.
     *
     * @return string
     */
    public static function getCrestClientSecret()
    {
        return static::$crestClientSecret;
    }

    /**
     * Sets the CREST client secret.
     *
     * @param string $crestClientSecret the client secret
     *
     * @return void
     */
    public static function setCrestClientSecret($crestClientSecret)
    {
        return static::$crestClientSecret = $crestClientSecret;
    }

    /**
     * Returns configured CREST user specific refresh token.
     *
     * @return string
     */
    public static function getCrestClientRefreshToken()
    {
        return static::$crestClientRefreshToken;
    }

    /**
     * Sets the CREST user specific refresh token.
     *
     * @param string $crestClientRefreshToken
     *
     * @return void
     */
    public static function setCrestClientRefreshToken($crestClientRefreshToken)
    {
        return static::$crestClientRefreshToken = $crestClientRefreshToken;
    }

    /**
     * Returns configured user agent to be used by the CREST client.
     *
     * @return string
     */
    public static function getUserAgent()
    {
        return static::VERSION . ' (' . static::$applicationName . ')';
    }

    /**
     * Sets the name of your application. It is used as part of the User Agent when accessing the CREST API.
     *
     * @param string $appName to be used
     *
     * @return void
     */
    public static function setApplicationName($appName)
    {
        static::$applicationName = $appName;
    }

    /**
     * Gets the default market region ID, which is used when no region ID is specified in pricing operations.
     *
     * @return int the region ID
     */
    public static function getDefaultMarketRegionId()
    {
        return static::$defaultMarketRegionId;
    }

    /**
     * Sets the default market region ID.
     *
     * @param int $regionId of the region to be used as default
     *
     * @return void
     */
    public static function setDefaultMarketRegionId($regionId)
    {
        static::$defaultMarketRegionId = (int) $regionId;
    }

    /**
     * Returns the maximum price data age in seconds, which is at least 5 minutes long.
     *
     * @return int
     */
    public static function getMaxPriceDataAge()
    {
        if (static::$maxPriceDataAge < 300)
            return 300;
        return static::$maxPriceDataAge;
    }

    /**
     * Sets the maximum price data age in seconds.
     *
     * @param int $maxPriceDataAge the max age in seconds, 0 for unlimited
     *
     * @return void
     */
    public static function setMaxPriceDataAge($maxPriceDataAge)
    {
        static::$maxPriceDataAge = $maxPriceDataAge;
    }

    /**
     * Gets the tracked market region IDs, whose market data will be collected via CREST
     *
     * @return array of region IDs
     */
    public static function getTrackedMarketRegionIds()
    {
        return static::$trackedMarketRegionIds;
    }

    /**
     * Sets the tracked market region IDs, whose market data will be collected via CREST
     *
     * @param array $regionIds to be set
     *
     * @return void
     */
    public static function setTracketMarketRegionIds(array $regionIds)
    {
        static::$trackedMarketRegionIds = $regionIds;
    }

    /**
     * Returns the fully qualified name of classes to instantiate for a given class nickname. This is used extensively
     * in iveeCore to allow for configurable class instantiation.
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

    /**
     * Configure a fully qualified name of a class to instantiate for a given class nickname.
     *
     * @param string $classNickname a short name for the class
     * @param string $className the full class name to use
     *
     * @return void
     */
    public static function setIveeClassName($classNickname, $className)
    {
        static::$classes[$classNickname] = $className;
    }

}
