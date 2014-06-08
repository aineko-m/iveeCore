<?php

/**
 * EMDR for IVEE client.
 * 
 * Connects to a relay, processes order and history data and stores it to iveeCore's DB tables.
 * 
 * Requires Zero-MQ and php-zmq binding. See README for build instructions.
 * 
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/emdr.php
 * @package iveeCore
 */

echo " _____ __  __ ____  ____     __              _____     _______ _____ 
| ____|  \/  |  _ \|  _ \   / _| ___  _ __  |_ _\ \   / / ____| ____|
|  _| | |\/| | | | | |_) | | |_ / _ \| '__|  | | \ \ / /|  _| |  _|  
| |___| |  | | |_| |  _ <  |  _| (_) | |     | |  \ V / | |___| |___ 
|_____|_|  |_|____/|_| \_\ |_|  \___/|_|    |___|  \_/  |_____|_____|\n";

error_reporting(E_ALL);
ini_set('display_errors', 'on');

DEFINE('VERBOSE', 1);

//include the iveeCore configuration, expected to be in the same directory, with absolute path
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'iveeCoreConfig.php');

set_time_limit(3600);

//instantiate and run
$ec = EmdrConsumer::instance();
$ec->run();

?>