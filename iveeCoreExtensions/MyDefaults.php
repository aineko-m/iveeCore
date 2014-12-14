<?php
/**
 * MyDefaults class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreExtensions
 * @author   Aineko Macx <ai@sknop.net>
 * @license  public domain
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCoreExtensions/MyDefaults.php
 *
 */

namespace iveeCoreExtensions;

/**
 * This file is intended for customizing the defaults provided by \IveeCore\Defaults. It also serves as an example of
 * the intended way of extending iveeCore with custom classes.
 *
 * Overwrite attributes and methods as required by your application or eve industrial setup.
 * This could even be extended to make the values completely dynamic, if the application demands it.
 *
 * This file is intentionally not put under the LGPL so users can freely modify it.
 *
 * @category IveeCore
 * @package  IveeCoreExtensions
 * @author   Aineko Macx <ai@sknop.net>
 * @license  public domain
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCoreExtensions/MyDefaults.php
 */
class MyDefaults extends \iveeCore\Defaults
{
    /**
     * @var MyIveeCoreDefaults $instance might seem redundant, however it is needed as a workaround to make inherited
     * singleton instantiation work properly.
     */
    protected static $instance = null;

    /**
     * @var array $trackedMarketRegionIDs defines the regions for which market data should by gathered by the EMDR
     * client
     */
    protected $trackedMarketRegionIDs = array(
        10000002 //The Forge
    );
}
