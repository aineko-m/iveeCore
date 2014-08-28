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
 * Class for the PHP internal object pool (cache)
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
     * @var string $cachePrefix prefix to be used for keys of objects in external cache
     */
    protected $cachePrefix;

    /**
     * @var string $namesCacheKey key to be used for name to ID arrays in external cache
     */
    protected $namesCacheKey;

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
     * @param string $cachePrefix prefix to be used for keys of objects in external cache
     * @param string $namesCacheKey key to be used for name to ID arrays in external cache
     *
     * @return \iveeCore\InstancePool
     */
    public function __construct($cachePrefix, $namesCacheKey = null)
    {
        $this->cachePrefix   = $cachePrefix;
        $this->namesCacheKey = $namesCacheKey;
        if (Config::getUseCache()) {
            //lookup Cache class
            $cacheClass = Config::getIveeClassName('Cache');
            $this->cache = $cacheClass::instance();
        }
    }

    /**
     * Stores object in pool under given id, also in external cache if configured
     * 
     * @param int $id under whhich the object shall be stored
     * @param object $obj to be stored
     *
     * @return void
     */
    public function setIdObj($id, $obj)
    {
        $this->idToObj[(int) $id] = $obj;
        if ($this->cache instanceof ICache)
            $this->cache->setItem($obj, $this->cachePrefix . $id);
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
           $this->cache->setItem($namesToId, $this->namesCacheKey);
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
            $obj = $this->cache->getItem($this->cachePrefix . $id);
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
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if the names to ID array isn't found in external cache
     * @throws \iveeCore\Exceptions\TypeNameNotFoundException if the given name is not found in the mapping array
     */
    public function getIdByName($name)
    {
        if (empty($this->nameToId) and $this->cache instanceof ICache)
            $this->nameToId = $this->cache->getItem($this->namesCacheKey);
        
        if (isset($this->nameToId[$name]))
            return $this->nameToId[$name];
        
        $exceptionClass = Config::getIveeClassName('TypeNameNotFoundException');
        throw new $exceptionClass('Type name not found');
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
    public function getObjCount()
    {
        return count($this->idToObj);
    }
}
