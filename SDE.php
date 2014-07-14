<?php

/**
 * SDE Class file.
 * This class is used for handling DB and cache interaction.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/SDE.php
 * @package iveeCore
 */
class SDE {
    
    /**
     * @var SDE $instance holds the singleton SDE object.
     */
    protected static $instance;
    
    /**
     * @var IveeCoreDefaults $defaults holds the singleton (My)IveeCoreDefaults object.
     */
    public $defaults;
    
    /**
     * @var mysqli $db holds the DB connections.
     */
    protected $db;
    
    /**
     * @var Memcached $memcached holds the Memcached connections.
     */
    protected $memcached;
    
    /**
     * Acts as internal Type object cache.
     * @var array $types; points from typeID => Type object.
     */
    protected $types;
    
    /**
     * Acts as a lazyloaded typeName to ID lookup table.
     * @var array $typeNames; points from typeName => typeID.
     */
    protected $typeNames;
    
    /**
     * @var int $numQueries stores the number of queries run against the DB.
     */
    protected $numQueries = 0;
    
    /**
     * @var float $timeQueries stores the sum of time spent waiting for DB queries to return.
     */
    protected $timeQueries = 0.0;
    
    /**
     * @var int $internalCacheHit stores the number of hits on the internal type cache.
     */
    protected $internalCacheHit = 0;
    
    /**
     * @var int $memcachedHit stores the number of hits on memcached.
     */
    protected $memcachedHit = 0;

    /**
     * Constructor. Implements the singleton pattern.
     * @param mysqli $db is an optional reference to an existing DB connection object
     * @param Memcached $memcached is an optional reference to an existing Memcached object.
     */
    protected function __construct(mysqli $db = null, Memcached $memcached = null) {
        $defaultsClass = iveeCoreConfig::getIveeClassName('IveeCoreDefaults');
        $this->defaults = $defaultsClass::instance();
        $this->types = array();
        if (!isset($db)) {
            $db = new mysqli(
                iveeCoreConfig::getDbHost(), 
                iveeCoreConfig::getDbUser(), 
                iveeCoreConfig::getDbPw(), 
                iveeCoreConfig::getDbName(), 
                iveeCoreConfig::getDbPort()
            );
            if($db->connect_error)
                exit('Fatal Error: ' . $db->connect_error . PHP_EOL);
        } elseif(!($db instanceof mysqli)) 
            exit('Fatal Error: parameter given is not a mysqli object' . PHP_EOL);
        $this->db = $db;
        
        //ivee uses transactions so turn off autocommit
        $this->db->autocommit(FALSE);
        
        //eve runs on UTC time
        $this->db->query("SET time_zone='+0:00';");
        
        //init Memcached
        if(iveeCoreConfig::getUseMemcached()){
            if(isset($memcached)){
                if (count($memcached->getServerList() < 1)) 
                    throw new InvalidParameterValueException("No memcache server defined in given Memcached object.");
                $this->memcached = $memcached;
            } else {
                $this->memcached = new Memcached();
                $this->memcached->addServer(iveeCoreConfig::getMemcachedHost(), iveeCoreConfig::getMemcachedPort());
            }
        }
    }

    /**
     * Returns SDE instance.
     * @param mysqli $db is an optional reference to an existing DB connection object
     * @param Memcached $memcached is an optional reference to an existing Memcached object.
     * @return SDE
     */
    public static function instance(mysqli $db = null, Memcached $memcached = null) {
        if (!isset(static::$instance))
            static::$instance = new static($db, $memcached);
        return static::$instance;
    }
    
    /**
     * Performs a SQL query.
     * @param string $sql the query to be sent to the DB
     * @return mysql_result
     * @throws SQLErrorException when the query execution errors out
     */
    public function query($sql){
        $startTime = microtime(true);
        $res = $this->db->query($sql);
        if ($this->db->error)
            throw new SQLErrorException($this->db->error . "\nQuery: " . $sql, $this->db->errno); 
        $this->addQueryTime(microtime(true) - $startTime);
        return $res;
    }
    
    /**
     * Performs multiple SQL queries. Results are flushed and not returned. Useful for multiple updates and inserts.
     * @param string $multiSql semicolon separated queries to be sent to the DB
     * @throws SQLErrorException when the query execution errors out
     */
    public function multiQuery($multiSql){
        $startTime = microtime(true);
        if(!$this->db->multi_query($multiSql))
            throw new SQLErrorException($this->db->error . "\nQuery: " . $sql, $this->db->errno); 
        $this->addQueryTime(microtime(true) - $startTime);
        $this->flushDbResults();
    }
    
    /**
     * Flushes any remaining result sets from previous (multi) queries.
     * Necessary so subsequent queries don't fail.
     */
    public function flushDbResults(){
        while($this->db->more_results() && $this->db->next_result());
    }
    
    /**
     * Commits pending DB transactions.
     * @return boolean true on success
     */
    public function commit(){
        $startTime = microtime(true);
        $ret = $this->db->commit();
        $this->addQueryTime(microtime(true) - $startTime);
        return $ret;
    }
    
    /**
     * Rollback pending DB transactions.
     * @return boolean true on success
     */
    public function rollback(){
        $startTime = microtime(true);
        $ret = $this->db->rollback();
        $this->addQueryTime(microtime(true) - $startTime);
        return $ret;
    }
    
    /**
     * Stores object in Memcached.
     * @param object $object to be stored
     * @param string|int $key under which the object will be stored
     * @param int $expiration Time To Live of the stored object
     * @return boolean true on success
     * @throws MemcachedDisabledException if memcached has been disabled
     */
    public function storeInCache($object, $key, $expiration = 0){
        if(iveeCoreConfig::getUseMemcached())
            return $this->memcached->set(iveeCoreConfig::getMemcachedPrefix() . $key, $object, $expiration);
        else
            throw new MemcachedDisabledException('Use of Memcached has been disabled in the configuration');
    }
    
    /**
     * Gets object from Memcached.
     * @param string|int $key under which the object is stored
     * @return Object
     * @throws KeyNotFoundInMemcachedException if key is not found
     * @throws MemcachedDisabledException if memcached has been disabled
     */
    public function getFromCache($key){
        if(iveeCoreConfig::getUseMemcached()){
            $obj = $this->memcached->get(iveeCoreConfig::getMemcachedPrefix() . $key);
            if($this->memcached->getResultCode() == Memcached::RES_NOTFOUND)
                throw new KeyNotFoundInMemcachedException("Key not found in memcached.");
            //count memcached hit
            $this->memcachedHit++;
            return $obj;
        } else
            throw new MemcachedDisabledException('Use of Memcached has been disabled in the configuration');
    }
    
    /**
     * Removes object from Memcached.
     * @param string|int $key of object to be removed
     * @return boolean true on success or if memcached has been disabled
     */
    public function invalidateCache($key){
        if(iveeCoreConfig::getUseMemcached())
            return $this->memcached->delete(iveeCoreConfig::getMemcachedPrefix() . $key);
        else
            return true;
    }
    
    /**
     * Clears all stored objects in memcached.
     * @return boolean true on success or if memcached has been disabled.
     */
    public function flushCache(){
        if(iveeCoreConfig::getUseMemcached())
            return $this->memcached->flush();
        else
            return true;
    }

    /**
     * Main function for getting Type object.
     * Tries caches and instantiates new objects if necessary.
     * @param int $typeID of requested Type
     * @return Type the requested Type or subclass object
     * @throws TypeIdNotFoundException if the typeID is not found
     */
    public function getType($typeID) {
        //try php Type array first
        if(isset($this->types[$typeID])){
            //count internal cache hit
            $this->internalCacheHit++;
            return $this->types[$typeID];
        } else {
            //lookup Type class
            $typeClass = iveeCoreConfig::getIveeClassName('Type');
            
            //try memcached
            if(iveeCoreConfig::getUseMemcached()){
                try{
                    $type = $this->getFromCache('type_' . $typeID);
                } catch(KeyNotFoundInMemcachedException $e){
                    //go to DB
                    $type = $typeClass::factory($typeID);
                    //store type object in memcached
                    $this->storeInCache($type, 'type_' . $typeID);
                }
            } else { 
                //not using memcached, go to DB
                $type = $typeClass::factory($typeID);
            }
            
            //store type object in internal cache
            $this->types[$typeID] = $type;
            return $type;
        }
    }

    /**
     * Returns Type ID for a TypeName
     * Loads all type names from DB or memached to PHP when first used. 
     * Note that populating the name => id array takes time and uses a few MBs of RAM
     * @param string $typeName of requested Type
     * @return int the ID of the requested Type
     * @throws TypeNameNotFoundException if type name is not found
     */
    public function getTypeIdByName($typeName){
        //check if names have been loaded yet
        if(empty($this->typeNames)){
            //try memcached
            if(iveeCoreConfig::getUseMemcached()){
                try{
                    $this->typeNames = $this->getFromCache('typeNames');
                } catch(KeyNotFoundInMemcachedException $e){
                    //load names from DB
                    $this->loadTypeNames();
                    //store in memcached
                    $this->storeInCache($this->typeNames, 'typeNames');
                }
            } else {
                //load names from DB
                $this->loadTypeNames();
            }
        }
        
        $typeName = trim($typeName);
        //return ID if type exists
        if(isset($this->typeNames[$typeName]))
            return $this->typeNames[$typeName];
        else
            throw new TypeNameNotFoundException("type name not found");
    }
    
    /**
     * Returns Type object.
     * @param string $typeName of requested Type
     * @return Type the requested Typeobject
     * @throws TypeNameNotFoundException if type name is not found
     */
    public function getTypeByName($typeName){
        return $this->getType($this->getTypeIdByName($typeName));
    }
    
    /**
     * Loads all type names from DB to PHP 
     */
    protected function loadTypeNames(){
        $startTime = microtime(true);
        $res = $this->db->query(
            "SELECT typeID, typeName 
            FROM invTypes
            WHERE published = 1;"
        );
        $this->addQueryTime(microtime(true) - $startTime);
        while ($row = $res->fetch_assoc()) {
            $this->typeNames[$row['typeName']] = (int) $row['typeID'];
        }
    }

    /**
     * Returns the number of types in internal cache
     * @return int count
     */
    public function getCachedTypeCount(){
        return count($this->types);
    }
    
    /**
     * Add SQL query time for performance tracking
     * @param float $time to add
     */
    protected function addQueryTime($time){
        $this->timeQueries += $time;
        $this->numQueries++;
    }
    
    /**
     * Returns various statistics for caches, database and queries
     * @return array
     */
    public function getStats() {
        $stats = array(
            'internalCacheHit' => $this->internalCacheHit,
            'numQueries'       => $this->numQueries,
            'timeQueries'      => $this->timeQueries,
            'cachedTypesCount' => $this->getCachedTypeCount(),
            'peakMemoryUsage'  => memory_get_peak_usage(true),
            'mysqlStats'       => $this->db->get_connection_stats()
        );
        if(iveeCoreConfig::getUseMemcached()){
            $stats['memcachedHits']  = $this->memcachedHit;
            $stats['memcachedStats'] = $this->memcached->getStats();
        }
        return $stats;
    }
}

?>