<?php
/**
 * EMDR for IVEE client file.
 * 
 * PHP version 5.3
 *
 * Connects to a relay, processes order and history data and stores it to iveeCore's DB tables.
 *
 * Requires Zero-MQ and php-zmq binding. See README for build instructions.
 *
 * @category IveeCore
 * @package  IveeCoreScripts
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/emdr.php
 */

echo " _____ __  __ ____  ____     __              _____     _______ _____
| ____|  \/  |  _ \|  _ \   / _| ___  _ __  |_ _\ \   / / ____| ____|
|  _| | |\/| | | | | |_) | | |_ / _ \| '__|  | | \ \ / /|  _| |  _|
| |___| |  | | |_| |  _ <  |  _| (_) | |     | |  \ V / | |___| |___
|_____|_|  |_|____/|_| \_\ |_|  \___/|_|    |___|  \_/  |_____|_____|" . PHP_EOL;

error_reporting(E_ALL);
ini_set('display_errors', 'on');

DEFINE('VERBOSE', 1);

//include the iveeCore init file, expected to be in the same directory, with absolute path
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'iveeCoreInit.php';

//get EmdrConsumer class name
$EmdrConsumerClass = \iveeCore\Config::getIveeClassName('EmdrConsumer');

//instantiate and run
$ec = $EmdrConsumerClass::instance();
$ec->run();
