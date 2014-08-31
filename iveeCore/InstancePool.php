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
 * Class for the object pool (PHP-internal cache)
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
     * @var string $classNick holds the class short name for which this InstancePool will hold objects. It is used as 
     * part of the cache key prefix.
     */
    protected $classNick;

    /**
     * @var \iveeCore\ICache $cache object for caching external to PHP
     */
    protected $cache;

    /**
     * @var array $idToObj containing the pooled objects in the form ID => obj
     */
    protected $idToObj = array();

    /**
     * @var array $nameToId containing the name => ID mapping
     */
    protected $nameToId = array();

    /**
     * @var int $poolHits counter for object pool hits
     */
    protected $poolHits = 0;

    /**
     * Contructor
     * 
     * @param string $classNick class short name for which this InstacePool will hold objects for
     *
     * @return \iveeCore\InstancePool
     */
    public function __construct($classNick)
    {
        $this->classNick = $classNick;
        if (Config::getUseCache()) {
            //lookup Cache class
            $cacheClass = Config::getIveeClassName('Cache');
            $this->cache = $cacheClass::instance();
        }
    }

    /**
     * Stores object in pool under its id, also in external cache if configured
     * 
     * @param SdeTypeCommon $obj to be stored
     *
     * @return void
     */
    public function setObj(SdeTypeCommon $obj)
    {
        $this->idToObj[$obj->getId()] = $obj;
        if ($this->cache instanceof ICache)
            $this->cache->setItem($obj, $this->classNick . '_' . $obj->getId());
    }

    /**
     * Sets the name to ID mapping array and also stores in external cache if configured
     * 
     * @param array &$namesToId in the form name => ID
     *
     * @return void
     */
    public function setNamesToIds(array &$namesToId)
    {
        $this->nameToId = $namesToId;
        if ($this->cache instanceof ICache)
           $this->cache->setItem($namesToId, $this->classNick . 'Names');
    }

    /**
     * Gets the object for id from pool, or external cache if configured
     * 
     * @param int $id of the object to be returned
     *
     * @return object
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the key cannot be found
     */
    public function getObjById($id)
    {
        if (isset($this->idToObj[(int) $id])) {
            $this->poolHits++;
            return $this->idToObj[(int) $id];
        } elseif ($this->cache instanceof ICache) {
            $obj = $this->cache->getItem($this->classNick . '_' . $id);
            $this->idToObj[(int) $id] = $obj;
            return $obj;
        }
        $exceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
        throw new $exceptionClass('Key not found in InstancePool');
    }

    /**
     * Gets the id for a given name
     * 
     * @param string $name for which the ID should be returned
     *
     * @return int
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the names to ID array isn't found in pool or cache
     * @throws \iveeCore\Exceptions\TypeNameNotFoundException if the given name is not found in the mapping array
     */
    public function getIdByName($name)
    {
        if (empty($this->nameToId)) {
            if ($this->cache instanceof ICache)
                //try getting mapping array from cache, will throw an exception if not found
                $this->nameToId = $this->cache->getItem($this->classNick . 'Names');
            else {
                $KeyNotFoundInCacheExceptionClass = Config::getIveeClassName('KeyNotFoundInCacheException');
                throw new $KeyNotFoundInCacheExceptionClass('Names not found in pool');
            }
        }
        if (isset($this->nameToId[$name]))
            return $this->nameToId[$name];
        
        $typeNameNotFoundExceptionClass = Config::getIveeClassName('TypeNameNotFoundException');
        throw new $typeNameNotFoundExceptionClass('Type name not found');
    }

    /**
     * Removes objects from pool and from cache, if the later is being used
     * 
     * @param int &$ids of the objects to be removed
     *
     * @return void
     */
    public function deleteFromCache(&$ids)
    {
        $c = $this->cache instanceof ICache;
        foreach ($ids as $id) {
            if (isset($this->idToObj[$id]))
                unset($this->idToObj[$id]);
            if ($c)
                $this->cache->deleteItem($this->classNick . '_' . $id);
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
        return count($this->idToObj);
    }
}
