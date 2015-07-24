<?php
/**
 * MemcachedWrapper class file.
 *
 * PHP version 5.4
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
 * immutable except when affected by updates from CREST, using an object cache is the natural and easy way to greatly
 * improve performance of iveeCore. The CREST updaters automatically clear the cache for the objects that have had their
 * DB entry updated, or the caching expiry time is set to reasonably short values.
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
     * @var iveeCore\MemcachedWrapper $instance holds the singleton MemcachedWrapper object.
     */
    protected static $instance;

    /**
     * @var Memcached $memcached holds the Memcached connections.
     */
    protected $memcached;

    /**
     * @var int $hits stores the number of hits on memcached.
     */
    protected $hits = 0;

    /**
     * @var bool $hasMultiDelete if the memcached extension supports the deleteMulti call.
     */
    protected $hasMultiDelete;

    /**
     * Returns MemcachedWrapper instance.
     *
     * @return iveeCore\MemcachedWrapper
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

        //determine if deleteMulti() is supported
        if (defined('HHVM_VERSION'))
            $this->hasMultiDelete = false;
        else {
            $ext = new \ReflectionExtension('memcached');
            $this->hasMultiDelete = version_compare($ext->getVersion(), '2.0.0', '>=');
        }
    }

    /**
     * Stores item in Memcached.
     *
     * @param iveeCore\ICacheable $item to be stored
     *
     * @return boolean true on success
     */
    public function setItem(ICacheable $item)
    {
        $ttl = $item->getCacheExpiry() - time();
        if ($ttl < 1)
            return false;
        return $this->memcached->set($item->getKey(), $item, $ttl);
    }

    /**
     * Gets item from Memcached.
     *
     * @param string $key under which the item is stored
     *
     * @return iveeCore\ICacheable
     * @throws iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
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
     *
     * @param string[] $keys of items to be removed
     *
     * @return bool true on success
     */
    public function deleteMulti(array $keys)
    {
        if ($this->hasMultiDelete)
            return $this->memcached->deleteMulti($keys);

        foreach ($keys as $key)
            $this->deleteItem ($key);
        return true;
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
