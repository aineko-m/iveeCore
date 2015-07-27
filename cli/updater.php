<?php
/**
 * iveeCore CREST updater CLI driver.
 *
 * PHP version 5.4
 *
 * To run from the command line interface, use 'php updater.php'.
 * If using HHVM instead of PHP use 'hhvm -v Eval.Jit=1 -v Eval.JitProfileInterpRequests=0 updater.php'.
 * The program will print further options how to control the updates to be run.
 *
 * @category IveeCore
 * @package  IveeCoreScripts
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/cli/updater.php
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php';
use iveeCore\Config;

$iveeUpdaterClass = Config::getIveeClassName('CrestIveeUpdater');
$iu = new $iveeUpdaterClass;
$iu->run($argv, Config::getTrackedMarketRegionIds());