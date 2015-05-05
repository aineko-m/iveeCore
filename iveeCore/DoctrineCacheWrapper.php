<?php
/**
 * DoctrineCacheWrapper class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/DoctrineCacheWrapper.php
 */

namespace iveeCore;

use Doctrine\Common\Cache\CacheProvider;

class DoctrineCacheWrapper implements ICache
{
    /**
     * @var \iveeCore\DoctrineCacheWrapper $instance holds the singleton DoctrineCacheWrapper object.
     */
    protected static $instance;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider $cache holds the Doctrine Cache Provider.
     */
    protected $cache;
    
    /**
     * @var int $hits stores the number of hits.
     */
    protected $hits = 0;

    /**
     * Returns DoctrineCacheWrapper instance.
     *
     * @return \iveeCore\DoctrineCacheWrapper
     */
    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static();
        return static::$instance;
    }

    /**
     * Set the Doctrine Cache Provider.
     * 
     * @param \Doctrine\Common\Cache\CacheProvider $cache Doctrine Cache Provider to use.
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Stores item in Cache.
     *
     * @param \iveeCore\ICacheable $item to be stored
     *
     * @return boolean true on success.
     */
    public function setItem(ICacheable $item)
    {
        return $this->cache->save($item->getKey(), $item, $item->getCacheTTL());
    }

    /**
     * Gets item from Cache.
     *
     * @param string $key under which the item is stored
     *
     * @return \iveeCore\ICacheable
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
     */
    public function getItem($key)
    {
        if (!$this->cache->contains($key)) {
            $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
            throw new $exceptionClass("Key not found in cache.");
        }
        $item = $this->cache->fetch($key);
        $this->hits++;
        return $item;
    }

    /**
     * Removes item from Cache.
     *
     * @param string $key of object to be removed
     *
     * @return bool true on success.
     */
    public function deleteItem($key)
    {
        return $this->cache->delete($key);
    }

    /**
     * Removes multiple items from Cache.
     *
     * @param string[] $keys of items to be removed
     *
     * @return bool true on success.
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->cache->delete($key)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Clears all stored items in Cache.
     *
     * @return boolean true on success.
     */
    public function flushCache()
    {
        return $this->cache->flushAll();
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
