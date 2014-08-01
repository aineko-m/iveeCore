<?php
/**
 * MemcachedWrapper class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   aineko-m <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MemcachedWrapper.php
 *
 */

namespace iveeCore;

/**
 * MemcachedWrapper provides caching functionality for iveeCore based on php5-memcached
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   aineko-m <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MemcachedWrapper.php
 *
 */
class MemcachedWrapper implements ICache
{
    /**
     * @var iveeCoreCache $instance holds the singleton iveeCoreCache object.
     */
    protected static $instance;

    /**
     * @var Memcached $memcached holds the Memcached connections.
     */
    protected $memcached;

    /**
     * @var int $memcachedHit stores the number of hits on memcached.
     */
    protected $memcachedHit = 0;

    /**
     * Constructor
     *
     * @return \iveeCore\MemcachedWrapper
     * @throws \iveeCore\Exceptions\CacheDisabledException if cache use is disabled in configuration
     */
    protected function __construct()
    {
        if (Config::getUseCache()) {
            $this->memcached = new \Memcached;
            $this->memcached->addServer(Config::getCacheHost(), Config::getCachePort());
        } else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass;
        }
    }

    /**
     * Returns MemcachedWrapper instance.
     * 
     * @return \iveCore\MemcachedWrapper
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
            return $this->memcached->set(Config::getCachePrefix() . $key, $item, $expiration);
        else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass('Use of Memcached has been disabled in the configuration');
        }
    }

    /**
     * Gets item from Memcached.
     * 
     * @param string $key under which the item is stored
     * 
     * @return mixed
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
     * @throws \iveeCore\Exceptions\CacheDisabledException if memcached has been disabled
     */
    public function getItem($key)
    {
        if (Config::getUseCache()) {
            $item = $this->memcached->get(Config::getCachePrefix() . $key);
            if ($this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
                $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
                throw new $exceptionClass("Key not found in memcached.");
            }
            //count memcached hit
            $this->memcachedHit++;
            return $item;
        } else {
            $exceptionClass = Config::getIveeClassName('CacheDisabledException');
            throw new $exceptionClass('Use of Memcached has been disabled in the configuration');
        }
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
        if (Config::getUseCache())
            return $this->memcached->delete(Config::getCachePrefix() . $key);
        else
            return true;
    }

    /**
     * Removes multiple items from Memcached.
     * If using memcached, this method requires php5-memcached package version >=2.0!
     * 
     * @param array $keys of items to be removed
     * 
     * @return bool true on success, also if memcached has been disabled
     */
    public function deleteMulti(array $keys)
    {
        if (Config::getUseCache())
            return $this->memcached->deleteMulti($keys);
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
            return $this->memcached->flush();
        else
            return true;
    }
}
