<?php

/**
 * CacheableCommon class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CacheableCommon.php
 *
 */

namespace iveeCore;

/**
 * CacheableCommon is a base class to all classes that instantiate objects which can get cached by InstancePool
 *
 * Classes that inherit from CacheableCommon must define the static attributes $instancePool.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CacheableCommon.php
 *
 */
abstract class CacheableCommon implements ICacheable
{
    /**
     * @var int $id of the CacheableCommon
     */
    protected $id;

    /**
     * Initializes static InstancePool
     *
     * @return void
     */
    protected static function init()
    {
        if (!isset(static::$instancePool)) {
            $ipoolClass = Config::getIveeClassName('InstancePool');
            static::$instancePool = new $ipoolClass(get_called_class());
        }
    }

    /**
     * Returns the Instance Pool
     *
     * @return \iveeCore\InstancePool
     */
    protected static function getInstancePool()
    {
        if (!isset(static::$instancePool))
            static::init();
        return static::$instancePool;
    }

    /**
     * Invalidate instancePool and cache entries
     *
     * @param array $keys
     *
     * @return void
     */
    public static function deleteFromCache(array $keys)
    {
        static::getInstancePool()->deleteFromCache($keys);
    }

    /**
     * Returns the id of the CacheableCommon object
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the key of the CacheableCommon object
     *
     * @return string
     */
    public function getKey()
    {
        return (string)$this->getId();
    }

    /**
     * Gets the objects cache time to live
     *
     * @return int
     */
    public function getCacheTTL()
    {
        return 24 * 3600;
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
}
