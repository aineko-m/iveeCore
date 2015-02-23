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
 *
 */

namespace iveeCore;

/**
 * Class for the object pool (PHP-internal cache) with second level external, persistent cache.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InstancePool.php
 *
 */
class InstancePool
{
    /**
     * @var string $className holds the class short name for which this InstancePool will hold objects. It is used as
     * part of the cache key prefix.
     */
    protected $className;

    /**
     * @var \iveeCore\ICache $cache object for caching external to PHP
     */
    protected $cache;

    /**
     * @var array $keyToObj containing the pooled objects in the form key => obj
     */
    protected $keyToObj = array();

    /**
     * @var array $nameToKey containing the name => key mapping
     */
    protected $nameToKey = array();

    /**
     * @var int $poolHits counter for object pool hits
     */
    protected $poolHits = 0;

    /**
     * Contructor
     *
     * @param string $className class short name for which this InstacePool will hold objects for
     *
     * @return \iveeCore\InstancePool
     */
    public function __construct($className)
    {
        $this->className = $className;
        if (Config::getUseCache()) {
            //lookup Cache class
            $cacheClass = Config::getIveeClassName('Cache');
            $this->cache = $cacheClass::instance();
        }
    }

    /**
     * Stores object in pool under its id, also in external cache if configured
     *
     * @param ICacheable $obj to be stored
     *
     * @return void
     */
    public function setObj(ICacheable $obj)
    {
        $this->keyToObj[$obj->getKey()] = $obj;
        if ($this->cache instanceof ICache)
            $this->cache->setItem($obj, $this->className . '_' . $obj->getKey(), $obj->getCacheTTL());
    }

    /**
     * Sets the name to key mapping array and also stores in external cache if configured
     *
     * @param array $nameToKey in the form name => key
     *
     * @return void
     */
    public function setNamesToKeys(array $nameToKey)
    {
        $this->nameToKey = $nameToKey;
        if ($this->cache instanceof ICache)
           $this->cache->setItem($nameToKey, $this->className . 'Names');
    }

    /**
     * Gets the object for key from pool, or external cache if configured
     *
     * @param string $key of the object to be returned
     *
     * @return object
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the key cannot be found
     */
    public function getObjByKey($key)
    {
        if (isset($this->keyToObj[$key])) {
            $this->poolHits++;
            return $this->keyToObj[$key];
        } elseif ($this->cache instanceof ICache) {
            $obj = $this->cache->getItem($this->className . '_' . $key);
            $this->keyToObj[$key] = $obj;
            return $obj;
        }
        $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
        throw new $exceptionClass('Key not found in InstancePool');
    }

    /**
     * Gets the key for a given name
     *
     * @param string $name for which the key should be returned
     *
     * @return string
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the names to key array isn't found in pool or cache
     * @throws \iveeCore\Exceptions\TypeNameNotFoundException if the given name is not found in the mapping array
     */
    public function getKeyByName($name)
    {
        if (empty($this->nameToKey)) {
            if ($this->cache instanceof ICache)
                //try getting mapping array from cache, will throw an exception if not found
                $this->nameToKey = $this->cache->getItem($this->className . 'Names');
            else {
                $KeyNotFoundInCacheExceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
                throw new $KeyNotFoundInCacheExceptionClass('Names not found in pool');
            }
        }
        if (isset($this->nameToKey[$name]))
            return $this->nameToKey[$name];

        $typeNameNotFoundExceptionClass = Config::getIveeClassName('TypeNameNotFoundException');
        throw new $typeNameNotFoundExceptionClass('Type name not found');
    }

    /**
     * Removes objects from pool and from cache, if the later is being used
     *
     * @param array $keys of the objects to be removed
     *
     * @return void
     */
    public function deleteFromCache(array $keys)
    {
        $c = $this->cache instanceof ICache;
        foreach ($keys as $key) {
            if (isset($this->keyToObj[$key]))
                unset($this->keyToObj[$key]);
            if ($c)
                $this->cache->deleteItem($this->className . '_' . $key);
        }
    }

    /**
     * Returns the number of hits on the object pool
     *
     * @return int the number of hits
     */
    public function getPoolHits()
    {
        return $this->poolHits;
    }

    /**
     * Returns the number of objects in the pool
     *
     * @return int count
     */
    public function getPooledTypeCount()
    {
        return count($this->keyToObj);
    }
}
