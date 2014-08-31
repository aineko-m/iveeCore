<?php
/**
 * SdeTypeCommon class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SdeTypeCommon.php
 *
 */

namespace iveeCore;

/**
 * SdeTypeCommon is a base class to all classes that instantiate objects based on data from the SDE, providing common 
 * functionality, mostly related to instantiation and caching.
 * 
 * Classes that inherit from SdeTypeCommons must define the static attributes $instancePool and $classNick.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SdeTypeCommon.php
 *
 */
abstract class SdeTypeCommon
{   
    /**
     * @var int $id of the SdeTypeCommon
     */
    protected $id;

    /**
     * @var string $name of the SdeTypeCommon
     */
    protected $name;
    
    /**
     * Initializes static InstancePool
     *
     * @return void
     */
    protected static function init()
    {
        if (!isset(static::$instancePool)) {
            $ipoolClass = Config::getIveeClassName('InstancePool');
            static::$instancePool = new $ipoolClass(static::$classNick);
        }
    }
    
    /**
     * Main function for getting SdeTypeCommon objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $id of type
     *
     * @return \iveeCore\SdeTypeCommon
     */
    public static function getById($id)
    {
        if (!isset(static::$instancePool))
            static::init();

        $id = (int) $id;

        try {
            return static::$instancePool->getObjById($id);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $typeClass = Config::getIveeClassName(static::$classNick);
            $type = new $typeClass($id);
            //store SdeTypeCommon object in instance pool (and cache if configured)
            static::$instancePool->setObj($type);
            
            return $type;
        }
    }
    
    /**
     * Returns ID for a given SdeTypeCommon name
     * Loads all names from DB or cache to PHP when first used.
     * Note that populating the name => id array takes time and uses a few MBs of RAM
     *
     * @param string $name of requested SdeTypeCommon
     *
     * @return int the ID of the requested SdeTypeCommon
     */
    public static function getIdByName($name)
    {
        if (!isset(static::$instancePool))
            static::init();

        $name = trim($name);
        try {
            return static::$instancePool->getIdByName($name);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //load names from DB
            static::loadNames();
            return static::$instancePool->getIdByName($name);
        }
    }
    
    /**
     * Returns SdeTypeCommon object by name.
     *
     * @param string $name of requested SdeTypeCommon
     *
     * @return \iveeCore\SdeTypeCommon the requested SdeTypeCommon object
     */
    public static function getByName($name)
    {
        $typeClass = Config::getIveeClassName(static::$classNick);
        return $typeClass::getById($typeClass::getIdByName($name));
    }
    
    /**
     * Loads all names from DB to PHP
     *
     * @return void
     */
    protected static function loadNames()
    {
        //Stub
    }

    /**
     * Returns the Instance Pool
     *
     * @return \iveeCore\InstancePool
     */
    public static function getInstancePool()
    {
        if (!isset(static::$instancePool))
            static::init();
        return static::$instancePool;
    }

    /**
     * Convenience method for throwing iveeCore Exceptions
     *
     * @param string $exceptionName nickname of the exception as configured in Config
     * @param string $message to be passed to the exception
     * @param int $code the exception code
     * @param Exception $previous the previous exception used for chaining
     *
     * @return void
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected static function throwException($exceptionName, $message = "", $code = 0, $previous = null)
    {
        $exceptionClass = Config::getIveeClassName($exceptionName);
        throw new $exceptionClass($message, $code, $previous);
    }

    /**
     * Returns the id of the SdeTypeCommon object
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the name of the SdeTypeCommon object
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
