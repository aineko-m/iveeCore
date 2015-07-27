<?php
/**
 * CoreDataCommon class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CoreDataCommon.php
 */

namespace iveeCore;

/**
 * CoreDataCommon is a base class to all classes that instantiate objects which can get cached by InstancePool.
 *
 * Classes that inherit from CoreDataCommon must define the static attributes $instancePool.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CoreDataCommon.php
 */
abstract class CoreDataCommon implements ICacheable, ICoreDataCommon
{
    /**
     * @var int $id of the CoreDataCommon.
     */
    protected $id;

    /**
     * @var int $expiry the objects absolute cache expiry as unix timestamp. Should be set during construction.
     */
    protected $expiry;

    /**
     * Returns the class short name which is used to lookup the configured FQDN classname in Config (for dynamic
     * subclassing).
     *
     * @return string
     */
    public static function getClassNick()
    {
        return static::CLASSNICK;
    }

    /**
     * Initializes static InstancePool.
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
     * Returns the Instance Pool.
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
     * Invalidate instancePool and cache entries.
     *
     * @param string[] $keys
     *
     * @return void
     */
    public static function deleteFromCache(array $keys)
    {
        $classPrefix = static::getClassHierarchyKeyPrefix();
        $fullKeys = array();
        foreach ($keys as $key) {
            $fullKeys[] = $classPrefix . $key;
        }
        static::getInstancePool()->deleteMulti($fullKeys);
    }

    /**
     * Returns the id of the CoreDataCommon object.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the key under which the object is stored.
     *
     * @return string
     */
    public function getKey() {
        return static::getClassHierarchyKeyPrefix() . $this->getId();
    }

    /**
     * Gets the objects absolute cache expiry time as unix timestamp.
     *
     * @return int
     */
    public function getCacheExpiry()
    {
        return $this->expiry;
    }

    /**
     * Convenience method for throwing iveeCore Exceptions.
     *
     * @param string $exceptionName nickname of the exception as configured in Config
     * @param string $message to be passed to the exception
     * @param int $code the exception code
     * @param Exception $previous the previous exception used for chaining
     *
     * @return void
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeId is not found
     */
    protected static function throwException($exceptionName, $message = "", $code = 0, $previous = null)
    {
        $exceptionClass = Config::getIveeClassName($exceptionName);
        throw new $exceptionClass($message, $code, $previous);
    }
}
