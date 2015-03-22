<?php
/**
 * CREST for IVEE client file.
 *
 * PHP version 5.3
 *
 * This command line PHP script pulls industry related data from EVE's CREST API and stored it to iveeCore's DB tables.
 * You should set it up to run every few hours (as a cronjob, for instance) to have updated system industry indices and
 * adjustedPrice data available at all times.
 *
 * @category IveeCore
 * @package  IveeCoreScripts
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/update_crest.php
 */

echo "  ____ ____  _____ ____ _____    __              _____     _______ _____
 / ___|  _ \| ____/ ___|_   _|  / _| ___  _ __  |_ _\ \   / / ____| ____|
| |   | |_) |  _| \___ \ | |   | |_ / _ \| '__|  | | \ \ / /|  _| |  _|
| |___|  _ <| |___ ___) || |   |  _| (_) | |     | |  \ V / | |___| |___
 \____|_| \_\_____|____/ |_|   |_|  \___/|_|    |___|  \_/  |_____|_____|" . PHP_EOL;

error_reporting(E_ALL);
ini_set('display_errors', 'on');

//include the iveeCore configuration, expected to be in the same directory, with absolute path
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'iveeCoreInit.php';

//do industry systems update
$crestIndustrySystemsUpdaterClass = \iveeCore\Config::getIveeClassName('CrestIndustrySystemsUpdater');
$crestIndustrySystemsUpdaterClass::doUpdate();

//do market prices update
$crestMarketPricesUpdaterClass = \iveeCore\Config::getIveeClassName('CrestMarketPricesUpdater');
$crestMarketPricesUpdaterClass::doUpdate();

//do industry facilities update
$crestIndustryFacilitiesUpdaterClass = \iveeCore\Config::getIveeClassName('CrestIndustryFacilitiesUpdater');
$crestIndustryFacilitiesUpdaterClass::doUpdate();

echo 'Peak memory usage: ' . ceil(memory_get_peak_usage(true) / 1024) . 'KiB' . PHP_EOL;
