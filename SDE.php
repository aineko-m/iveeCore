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
     * @var SDE $instance contains the singleton SDE object.
     */
    protected static $instance;
    
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
     */
    protected function __construct($db) {
        $this->types = array();
        if (!isset($db)) {
            $db = new mysqli(
                iveeCoreConfig::getDbHost(), 
                iveeCoreConfig::getDbUser(), 
                iveeCoreConfig::getDbPw(), 
                iveeCoreConfig::getDbName(), 
                iveeCoreConfig::getDbPort()
            );
            if($db->connect_error){
                exit('Fatal Error: ' . $db->connect_error . PHP_EOL);
            }
        }   
        $this->db = $db;
        
        //ivee uses transactions so turn off autocommit
        $this->db->autocommit(FALSE);
        
        //eve runs on UTC time
        $this->db->query("SET time_zone='+0:00';");
        
        //init Memcached
        if(iveeCoreConfig::getUseMemcached()){
            $this->memcached = new Memcached();
            $this->memcached->addServer(iveeCoreConfig::getMemcachedHost(), iveeCoreConfig::getMemcachedPort());
        }
    }

    /**
     * Returns SDE instance.
     * @param mysqli $db is an optional reference to an existing DB connection
     * @return SDE
     */
    public static function instance($db = null) {
        if (!isset(self::$instance)) {
            self::$instance = new SDE($db);
        }
        return self::$instance;
    }
    
    /**
     * Performs a SQL query.
     * @param string $sql the query to be sent to the DB
     * @return mysql_result
     */
    public function query($sql){
        $startTime = microtime(true);
        $res = $this->db->query($sql);
        $this->addQueryTime(microtime(true) - $startTime);
        return $res;
    }
    
    /**
     * Performs multiple SQL queries. Results are flushed and not returned.
     * @param string $multiSql semicolon separated queries to be sent to the DB
     * @return boolean true on success
     */
    public function multiQuery($multiSql){
        $startTime = microtime(true);
        $success = $this->db->multi_query($multiSql);
        $this->addQueryTime(microtime(true) - $startTime);
        $this->flushDbResults();
        return $success;
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
     * @return boolean true on success
     * @throws Exception if memcached has been disabled
     */
    public function storeInCache($object, $key){
        if(iveeCoreConfig::getUseMemcached()){
            return $this->memcached->set(iveeCoreConfig::getMemcachedPrefix() . $key, $object);
        } else {
            throw new Exception('Use of Memcached has been disabled in the configuration');
        }
    }
    
    /**
     * Gets object from Memcached.
     * @param string|int $key under which the object is stored
     * @return Object
     * @throws Exception if memcached has been disabled or if key is not found
     */
    public function getFromCache($key){
        if(iveeCoreConfig::getUseMemcached()){
            $obj = $this->memcached->get(iveeCoreConfig::getMemcachedPrefix() . $key);
            if($this->memcached->getResultCode() == Memcached::RES_NOTFOUND){
                throw new Exception("Key not found in memcached.");
            }
            //count memcached hit
            $this->memcachedHit++;
            return $obj;
        } else {
            throw new Exception('Use of Memcached has been disabled in the configuration');
        }
    }
    
    /**
     * Removes object from Memcached.
     * @param string|int $key of object to be removed
     * @return boolean true on success or if memcached has been disabled
     */
    public function invalidateCache($key){
        if(iveeCoreConfig::getUseMemcached()){
            return $this->memcached->delete(iveeCoreConfig::getMemcachedPrefix() . $key);
        } else {
            return true;
        }
    }

    /**
     * Main function for getting Type object.
     * Tries caches and instantiates new objects if necessary.
     * @param int $typeID of requested Type
     * @return Type the requested Type or subclass object
     * @throws Exception if the typeID is not found
     */
    public function getType($typeID) {
        //try php Type array first
        if(isset($this->types[$typeID])){
            //count internal cache hit
            $this->internalCacheHit++;
            return $this->types[$typeID];
        } else {
            //lookup Type class
            $typeClass = iveeCoreConfig::getIveeClassName('stdtype');
            
            //try memcached
            if(iveeCoreConfig::getUseMemcached()){
                try{
                    $type = $this->getFromCache('type_' . $typeID);
                } catch(Exception $e){
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
     * Returns Type object.
     * Loads all type Names from DB or memached to PHP when first used. 
     * The names use a few MB of RAM, so avoid if you have little available.
     * @param string $typeName of requested Type
     * @return Type the requested Type or subclass object
     * @throws Exception if type name is not found
     */
    public function getTypeByName($typeName){
        //check if names have been loaded yet
        if(empty($this->typeNames)){
            //try memcached first
            try{
                $this->typeNames = $this->getFromCache('typeNames');
            } 
            //go to DB
            catch(Exception $e){
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
                $res->free();
                //store in memcached
                $this->storeInCache($this->typeNames, 'typeNames');
            }
        }
        
        //return proper Type object
        if(isset($this->typeNames[$typeName])){
            return $this->getType($this->typeNames[$typeName]);
        } else {
            throw new Exception("typeID not found");
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
     * Prints information about cache and DB queries
     */
    public function printDbStats() {
        echo $this->internalCacheHit . " internal cache hits" . PHP_EOL;
        if(iveeCoreConfig::getUseMemcached()) echo $this->memcachedHit . " memcached hits" . PHP_EOL;
        echo $this->numQueries . " queries" . PHP_EOL;
        echo $this->timeQueries . " total SQL time" . PHP_EOL;
        echo $this->getCachedTypeCount() . " types in internal cache" . PHP_EOL;
        echo ceil(memory_get_peak_usage() / 1024) . " KiB peak PHP memory" . PHP_EOL;
    }
}

?>