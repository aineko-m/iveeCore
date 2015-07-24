<?php

/**
 * This is the single include file required for using iveeCore in web applications or command line scripts. All other
 * required classes are loaded via autoloader.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreInit
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/iveeCoreInit.php
 *
 */

//Check PHP version
if (version_compare(PHP_VERSION, '5.4') < 0)
    exit('PHP Version 5.4 or higher required. Currently ' . PHP_VERSION . PHP_EOL);

//Check for 64 bit PHP
//Integers used in iveeCore can easily exceed the maximum 2^31 of 32 bit PHP
if (PHP_INT_SIZE < 8)
    exit('64 bit PHP required. Currently ' . PHP_INT_SIZE * 8 . ' bit' . PHP_EOL);

//eve runs on UTC time
date_default_timezone_set('UTC');

//include Config_template if Config doesn't exist
$iveeCoreClassesBasePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'iveeCore' . DIRECTORY_SEPARATOR;
if(!file_exists($iveeCoreClassesBasePath . 'Config.php'))
    require_once $iveeCoreClassesBasePath . 'Config_template.php';

//register iveeCores's class loader
spl_autoload_register('iveeClassLoader');

/**
 * Auto class loader. Improved from http://www.php-fig.org/psr/psr-0/
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
    if(file_exists($fileName))
        require_once $fileName;
}
