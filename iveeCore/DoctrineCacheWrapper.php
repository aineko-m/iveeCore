<?php
namespace iveeCore;

use Doctrine\Common\Cache\CacheProvider;

class DoctrineCacheWrapper implements ICache
{
    protected static $instance;

    protected $cache;
    protected $hits = 0;

    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static();
        return static::$instance;
    }

    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    public function setItem(ICacheable $item)
    {
        return $this->cache->save($item->getKey(), $item, $item->getCacheTTL());
    }

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

    public function deleteItem($key)
    {
        return $this->cache->delete($key);
    }

    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->cache->delete($key)) {
                return false;
            }
        }
        
        return true;
    }

    public function flushCache()
    {
        return $this->cache->flushAll();
    }

    public function getHits()
    {
        return $this->hits; 
    }
}
