<?php
/**
 * PredisWrapper class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/PredisWrapper.php
 *
 */

namespace iveeCore;

require_once 'Predis/Autoloader.php';
\Predis\Autoloader::register();

/**
 * PredisWrapper provides caching functionality for iveeCore based on Redis/Predis: https://github.com/nrk/predis
 *
 * Instantiating iveeCore objects that need to pull data from the SDE DB is a relatively expensive process. This is the
 * case for all Type objects and it's descendants, AssemblyLine, SolarSystem, Station and market data. Since these are
 * immutable except when affected by updates from CREST or EMDR, using an object cache is the natural and easy way
 * to greatly improve performance of iveeCore. The EMDR client and CREST updaters automatically clear the cache for the
 * objects that have been update by them in the DB.
 *
 * Note that objects that have already been loaded in a running iveeCore program do not get updated by changes to
 * the DB or cache by another process or iveeCore script. This might be an issue for long running scripts. For web
 * applications it should be irrelevant, since they get instantiated and will fetch the objects from DB or cache on each
 * client request.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/PredisWrapper.php
 *
 */
class PredisWrapper implements ICache
{
    /**
     * @var \iveeCore\PredisWrapper $instance holds the singleton PredisWrapper object.
     */
    protected static $instance;

    /**
     * @var \Predis\Client $predis holds the Predis connection object.
     */
    protected $predis;

    /**
     * @var int $hit stores the number of hits on the cache.
     */
    protected $hit = 0;

    /**
     * Constructor.
     *
     * @return \iveeCore\PredisWrapper
     * @throws \iveeCore\Exceptions\CacheDisabledException if cache use is disabled in configuration
     */
    protected function __construct()
    {
        if (Config::getUseCache()) {
            $this->predis = new \Predis\Client(
                Config::getPredisConnectionString(),
                array('prefix' => Config::getCachePrefix())
            );
        } else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass;
        }
    }

    /**
     * Returns PredisWrapper instance.
     *
     * @return \iveeCore\PredisWrapper
     */
    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static();
        return static::$instance;
    }

    /**
     * Stores item in Memcached.
     *
     * @param mixed $item to be stored
     * @param string $key under which the object will be stored
     * @param int $expiration Time To Live of the stored object in seconds
     *
     * @return boolean true on success
     * @throws \iveeCore\Exceptions\CacheDisabledException if memcached has been disabled
     */
    public function setItem($item, $key, $expiration = 86400)
    {
        if (Config::getUseCache())
            return $this->predis->transaction()
                ->set($key, serialize($item))
                ->expire($key, $expiration)
                ->execute();
        else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass('Use of Predis has been disabled in the configuration');
        }
    }

    /**
     * Gets item from Predis.
     *
     * @param string $key under which the item is stored
     *
     * @return mixed
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
     * @throws \iveeCore\Exceptions\CacheDisabledException if cache has been disabled
     */
    public function getItem($key)
    {
        if (Config::getUseCache()) {
            $cacheResponse = $this->predis->get($key);
            if(isset($cacheResponse)){
                $this->hit++;
                return unserialize($cacheResponse);
            }

            $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
                throw new $exceptionClass("Key not found in Predis.");
        } else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass('Use of Predis has been disabled in the configuration');
        }
    }

    /**
     * Removes item from Predis.
     *
     * @param string $key of object to be removed
     *
     * @return bool true on success or if cache has been disabled
     */
    public function deleteItem($key)
    {
        if (Config::getUseCache())
            return $this->predis->del($key);
        else
            return true;
    }

    /**
     * Removes multiple items from Predis.
     *
     * @param array $keys of items to be removed
     *
     * @return bool true on success, also if cache has been disabled
     */
    public function deleteMulti(array $keys)
    {
        if (Config::getUseCache())
            return $this->predis->del($keys);
        else
            return true;
    }

    /**
     * Clears all stored items in memcached.
     *
     * @return boolean true on success, also if memcached has been disabled.
     */
    public function flushCache()
    {
        if (Config::getUseCache())
            return $this->predis->flushdb();
        else
            return true;
    }
}
