<?php
/**
 * CacheableArray class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CacheableArray.php
 */

namespace iveeCore;

/**
 * CacheableArray provides a minimal implementation of the ICacheable interface to allow arrays to be cached.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CacheableArray.php
 */
class CacheableArray extends \stdClass implements ICacheable
{
    /**
     * @var string $key under which the object will be stored in cache
     */
    protected $key;

    /**
     * @var int $expiry the objects absolute cache expiry as unix timestamp. Should be set during construction.
     */
    protected $expiry;

    /**
     * @var array $data payload
     */
    public $data = [];

    /**
     * Construct a CacheableArray object.
     *
     * @param string $key under which the object will be stored in cache
     * @param int $expiry the absolute cache expiry time as unix timestamp
     */
    public function __construct($key, $expiry)
    {
        $this->key = $key;
        $this->expiry = (int) $expiry;
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
     * Returns the key under which the object is cached.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
