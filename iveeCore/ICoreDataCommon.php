<?php
/**
 * ICoreDataCommon interface file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICoreDataCommon.php
 */

namespace iveeCore;

/**
 * ICoreDataCommon supplements the abstract class CoreDataCommon, defining methods that need to be implemented by
 * classes inheriting from it.
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/ICoreDataCommon.php
 */
interface ICoreDataCommon
{
    /**
     * Returns a string that is used as cache key prefix specific to a hierarchy of SDE related classes. Example:
     * Type and Blueprint are in the same hierarchy, Type and SolarSystem are not.
     *
     * @return string
     */
    public static function getClassHierarchyKeyPrefix();
}
