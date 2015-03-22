<?php
/**
 * SdeType class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SdeType.php
 *
 */

namespace iveeCore;

/**
 * SdeType is a base class to all classes that instantiate objects based on data from the SDE, providing common
 * functionality, mostly related to instantiation and caching.
 *
 * Classes that inherit from SdeTypes must define the static attribute $instancePool.
 *
 * Inheritance: SdeType -> CacheableCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SdeType.php
 *
 */
abstract class SdeType extends CacheableCommon
{
    /**
     * @var string $name of the SdeType
     */
    protected $name;

    /**
     * Main function for getting SdeType objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $id of type
     *
     * @return \iveeCore\SdeType
     */
    public static function getById($id)
    {
        if (!isset(static::$instancePool))
            static::init();

        try {
            return static::$instancePool->getObjByKey(static::getClassHierarchyKeyPrefix() . (int) $id);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $typeClass = Config::getIveeClassName(static::getClassNick());
            $type = new $typeClass((int) $id);
            //store SdeType object in instance pool (and cache if configured)
            static::$instancePool->setObj($type);

            return $type;
        }
    }

    /**
     * Returns ID for a given SdeType name
     * Loads all names from DB or cache to PHP when first used.
     * Note that populating the name => id array takes time and uses a few MBs of RAM
     *
     * @param string $name of requested SdeType
     *
     * @return int the ID of the requested SdeType
     */
    public static function getIdByName($name)
    {
        if (!isset(static::$instancePool))
            static::init();

        $namesKey = static::getClassHierarchyKeyPrefix() . 'Names';
        try {
            return static::$instancePool->getKeyByName($namesKey, trim($name));
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //load names from DB
            static::loadNames();
            return static::$instancePool->getKeyByName($namesKey, trim($name));
        }
    }

    /**
     * Returns SdeType object by name.
     *
     * @param string $name of requested SdeType
     *
     * @return \iveeCore\SdeType the requested SdeType object
     */
    public static function getByName($name)
    {
        $typeClass = Config::getIveeClassName(static::getClassNick());
        return $typeClass::getById($typeClass::getIdByName($name));
    }

    /**
     * Returns the name of the SdeType object
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
