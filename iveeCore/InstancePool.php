<?php
/**
 * InstancePool class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InstancePool.php
 */

namespace iveeCore;

/**
 * Class for the object pool (PHP-internal cache) with second level external cache.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InstancePool.php
 */
class InstancePool
{
    /**
     * @var \iveeCore\ICache $cache object for caching external to PHP
     */
    protected $cache;

    /**
     * @var \iveeCore\ICacheable[] $keyToObj containing the pooled objects in the form key => obj
     */
    protected $keyToObj = array();

    /**
     * @var string[] $nameToKey containing the name => key mapping
     */
    protected $nameToKey = array();

    /**
     * @var int $hits counter for object pool hits
     */
    protected $hits = 0;

    /**
     * Contructor.
     *
     * @return \iveeCore\InstancePool
     */
    public function __construct()
    {
        //lookup Cache class
        $cacheClass = Config::getIveeClassName('Cache');
        $this->cache = $cacheClass::instance();
    }

    /**
     * Stores object in pool under its id, also in external cache.
     *
     * @param \iveeCore\ICacheable $obj to be stored
     *
     * @return void
     */
    public function setItem(ICacheable $obj)
    {
        $this->keyToObj[$obj->getKey()] = $obj;
        if ($this->cache instanceof ICache)
            $this->cache->setItem($obj);
    }

    /**
     * Sets the name to key mapping array and also stores in external cache.
     *
     * @param string $classKey under which the names to id array will be cached
     * @param string[] $nameToKey in the form name => key
     *
     * @return void
     */
    public function setNamesToKeys($classKey, array $nameToKey)
    {
        $this->nameToKey[$classKey] = $nameToKey;
        $cacheableArrayClass = Config::getIveeClassName('CacheableArray');
        $cacheableArray = new $cacheableArrayClass($classKey, 3600 * 24);
        $cacheableArray->data = $nameToKey;

        if ($this->cache instanceof ICache)
           $this->cache->setItem($cacheableArray);
    }

    /**
     * Gets the object for key from pool or external cache.
     *
     * @param string $key of the object to be returned
     *
     * @return \iveeCore\ICacheable
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the key cannot be found
     */
    public function getItem($key)
    {
        if (isset($this->keyToObj[$key])) {
            $this->hits++;
            return $this->keyToObj[$key];
        } elseif ($this->cache instanceof ICache) {
            $obj = $this->cache->getItem($key);
            $this->keyToObj[$key] = $obj;
            return $obj;
        }
        $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
        throw new $exceptionClass('Key not found in InstancePool');
    }

    /**
     * Gets the key for a given name.
     *
     * @param string $classTypeNamesKey specific to the class of objects we want to look in
     * @param string $name for which the key should be returned
     *
     * @return string
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the names to key array isn't found in pool or cache
     * @throws \iveeCore\Exceptions\TypeNameNotFoundException if the given name is not found in the mapping array
     * @throws \iveeCore\Exceptions\WrongTypeException if object returned from cache is not CacheableArray
     */
    public function getKeyByName($classTypeNamesKey, $name)
    {
        if (empty($this->nameToKey[$classTypeNamesKey])) {
            if ($this->cache instanceof ICache) {
                //try getting mapping array from cache, will throw an exception if not found
                $cacheableArray = $this->cache->getItem($classTypeNamesKey);
                if(!$cacheableArray instanceof CacheableArray) {
                    $WrongTypeExceptionClass = Config::getIveeClassName('WrongTypeException');
                    throw new $WrongTypeExceptionClass('Object given is not CacheableArray');
                }
                $this->nameToKey[$classTypeNamesKey] = $cacheableArray->data;
            } else {
                $KeyNotFoundInCacheExceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
                throw new $KeyNotFoundInCacheExceptionClass('Names not found in pool');
            }
        }
        if (isset($this->nameToKey[$classTypeNamesKey][$name]))
            return $this->nameToKey[$classTypeNamesKey][$name];

        $typeNameNotFoundExceptionClass = Config::getIveeClassName('TypeNameNotFoundException');
        throw new $typeNameNotFoundExceptionClass('Type name not found');
    }

    /**
     * Removes single object from pool and cache.
     *
     * @param string $key of the objects to be removed
     *
     * @return void
     */
    public function deleteItem($key) {
        $this->cache->deleteItem($key);
        if (isset($this->keyToObj[$key]))
            unset($this->keyToObj[$key]);
    }

    /**
     * Removes objects from pool and cache.
     *
     * @param string[] $keys of the objects to be removed
     *
     * @return void
     */
    public function deleteMulti(array $keys)
    {
        $this->cache->deleteMulti($keys);
        foreach ($keys as $key)
            if (isset($this->keyToObj[$key]))
                unset($this->keyToObj[$key]);
    }

    /**
     * Returns the number of hits on the object pool.
     *
     * @return int the number of hits
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Returns the number of objects in the pool.
     *
     * @return int count
     */
    public function getPooledTypeCount()
    {
        return count($this->keyToObj);
    }
}
