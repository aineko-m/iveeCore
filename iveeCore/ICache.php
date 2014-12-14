<?php
/**
 * ICache interface file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICache.php
 *
 */

namespace iveeCore;

/**
 * Interface for caches
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICache.php
 *
 */
interface ICache
{
    /**
     * Returns ICache instance.
     *
     * @return \iveeCore\ICache
     */
    public static function instance();

    /**
     * Stores item in cache.
     *
     * @param mixed $item to be stored
     * @param string $key under which the object will be stored
     * @param int $expiration Time To Live of the stored object in seconds
     *
     * @return bool true on success
     * @throws \iveeCore\Exceptions\CacheDisabledException if cache use has been disabled in configuration
     */
    public function setItem($item, $key, $expiration);

    /**
     * Gets item from cache.
     *
     * @param string $key under which the item is stored
     *
     * @return mixed
     * @throws \iveeCore\Exceptions\KeyNotFoundInCacheException if key is not found
     * @throws \iveeCore\Exceptions\CacheDisabledException if cache use has been disabled in configuration
     */
    public function getItem($key);

    /**
     * Removes item from cache.
     *
     * @param string $key of object to be removed
     *
     * @return bool true on success or if cache use has been disabled
     */
    public function deleteItem($key);

    /**
     * Removes multiple items from cache.
     *
     * @param array $keys of items to be removed
     *
     * @return bool true on success, also if cache use has been disabled
     */
    public function deleteMulti(array $keys);

    /**
     * Clears all stored items in cache or all iveeCore-related items.
     *
     * @return boolean true on success, also if cache use has been disabled.
     */
    public function flushCache();
}
