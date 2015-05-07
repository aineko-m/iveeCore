<?php
/**
 * MemcachedWrapper class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MemcachedWrapper.php
 */

namespace iveeCore;

/**
 * MemcachedWrapper provides caching functionality for iveeCore based on php5-memcached.
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
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MemcachedWrapper.php
 */
class MemcachedWrapper implements ICache
{
    /**
     * @var \iveeCore\MemcachedWrapper $instance holds the singleton MemcachedWrapper object.
     */
    protected static $instance;

    /**
     * @var \Memcached $memcached holds the Memcached connections.
     */
    protected $memcached;

    /**
     * @var int $hits stores the number of hits on memcached.
     */
    protected $hits = 0;

    /**
     * Returns MemcachedWrapper instance.
     *
     * @return \iveeCore\MemcachedWrapper
     */
    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static();
        return static::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->memcached = new \Memcached;
        $this->memcached->addServer(Config::getCacheHost(), Config::getCachePort());
        $this->memcached->setOption(\Memcached::OPT_PREFIX_KEY, Config::getCachePrefix());
    }

    /**
     * Stores item in Memcached.
     *
     * @param \iveeCore\ICacheable $item to be stored
     *
     * @return boolean true on success
     */
    public function setItem(ICacheable $item)
    {
        return $this->memcached->set($item->getKey(), $item, $item->getCacheTTL());
    }

    /**
     * Gets item from Memcached.
     *
     * @param string $key under which the item is stored
     *
     * @return \iveeCore\ICacheable
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
     */
    public function getItem($key)
    {
        $item = $this->memcached->get($key);
        if ($this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
            $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
            throw new $exceptionClass("Key not found in memcached.");
        }
        //count memcached hit
        $this->hits++;
        return $item;
    }

    /**
     * Removes item from Memcached.
     *
     * @param string $key of object to be removed
     *
     * @return bool true on success or if memcached has been disabled
     */
    public function deleteItem($key)
    {
        return $this->memcached->delete($key);
    }

    /**
     * Removes multiple items from Memcached.
     * This method requires php5-memcached package version >=2.0!
     * If using HHVM, the call has to be emulated via multiple deleteItem calls.
     *
     * @param string[] $keys of items to be removed
     *
     * @return bool true on success, also if memcached has been disabled
     */
    public function deleteMulti(array $keys)
    {
        if(defined('HHVM_VERSION')){
            foreach ($keys as $key)
                $this->deleteItem ($key);
            return true;
        }
        return $this->memcached->deleteMulti($keys);
    }

    /**
     * Clears all stored items in memcached.
     *
     * @return boolean true on success, also if memcached has been disabled.
     */
    public function flushCache()
    {
        return $this->memcached->flush();
    }

    /**
     * Gets the number of hits the cache wrapper registered.
     *
     * @return int the number of hits
     */
    public function getHits()
    {
        return $this->hits; 
    }
}
