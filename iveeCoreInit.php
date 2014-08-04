<?php

/**
 * This is the single include file required for using iveeCore in web applications or command line scripts. All other 
 * required classes are loaded via autoloader.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreInit
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/iveeCoreInit.php
 *
 */

//Check PHP version
//iveeCore makes use of namespaces and late static binding, thus version 5.3 is the minimum requirement.
//Note that using 5.4 or later reduces memory usage by about a third compared to 5.3
if (version_compare(PHP_VERSION, '5.3') < 0)
    exit('PHP Version 5.3 or higher required. Currently ' . PHP_VERSION . PHP_EOL);

//Check for 64 bit PHP
//Integers used in iveeCore can easily exceed the maximum 2^31 of 32 bit PHP
if (PHP_INT_SIZE < 8)
    exit('64 bit PHP required. Currently ' . PHP_INT_SIZE * 8 . ' bit' . PHP_EOL);

//eve runs on UTC time
date_default_timezone_set('UTC');

//register iveeCores's class loader
spl_autoload_register('iveeClassLoader');

/**
 * Auto class loader
 * 
 * @param string $className the fully qualified class name. The loader relies on PSR compliant namespacing and class
 * file directory structuring to find and load the required files.
 * 
 * @return void
 */
function iveeClassLoader($className)
{
    $className = ltrim($className, '\\');
    $fileName  = dirname(__FILE__) . DIRECTORY_SEPARATOR;
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    include_once $fileName;
}
