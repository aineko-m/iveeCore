<?php
/**
 * IndustryIndicesUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryIndicesUpdater.php
 *
 */

namespace iveeCore\CREST;

use iveeCore\Config;
use iveeCore\SDE;
use iveeCrest\Responses\Root;

/**
 * IndustryIndicesUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryIndicesUpdater.php
 *
 */
class IndustryIndicesUpdater extends CrestDataUpdater
{
    /**
     * Processes data objects to SQL
     *
     * @param stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = Config::getIveeClassName('CrestException');

        if (!isset($item->solarSystem->id)) {
            throw new $exceptionClass('systemID missing in Industry Systems CREST data');
        }
        $systemId = (int) $item->solarSystem->id;

        $update = [];

        foreach ($item->systemCostIndices as $indexObj) {
            if (!isset($indexObj->activityID)) {
                throw new $exceptionClass(
                    'activityID missing in Industry Systems CREST data for systemID ' . $systemId);
            }
            if (!isset($indexObj->costIndex)) {
                throw new $exceptionClass(
                    'costIndex missing in Industry Systems CREST data for systemID ' . $systemId);
            }

            switch ($indexObj->activityID) {
                case 1:
                    $update['manufacturingIndex'] = (float) $indexObj->costIndex;
                    break;
                case 3:
                    $update['teResearchIndex'] = (float) $indexObj->costIndex;
                    break;
                case 4:
                    $update['meResearchIndex'] = (float) $indexObj->costIndex;
                    break;
                case 5:
                    $update['copyIndex'] = (float) $indexObj->costIndex;
                    break;
                case 7:
                    $update['reverseIndex'] = (float) $indexObj->costIndex;
                    break;
                case 8:
                    $update['inventionIndex'] = (float) $indexObj->costIndex;
                    break;
                default:
                    throw new $exceptionClass(
                    'Unknown activityID received from Industry Systems CREST data for systemID ' . $systemId);
            }
        }
        $insert = $update;
        $insert['systemID'] = $systemId;
        $insert['date'] = date('Y-m-d');

        $this->updatedIds[] = $systemId;

        $sdeClass = Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery(Config::getIveeDbName() . '.systemIndustryIndices', $insert, $update);
    }

    /**
     * Finalizes the update.
     *
     * @return void
     */
    protected function completeUpdate()
    {
        $sql = SDE::makeUpsertQuery(
            Config::getIveeDbName() . '.trackedCrestUpdates',
            array(
                'name' => 'industryIndices',
                'lastUpdate' => date('Y-m-d H:i:s', time())
            ),
            array('lastUpdate' => date('Y-m-d H:i:s', time()))
        );
        SDE::instance()->multiQuery($sql . ' COMMIT;');

        //invalidate solar system cache
        $assemblyLineClass  = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass::deleteFromCache($this->updatedIds);
    }

    /**
     * Fetches the data from CREST.
     *
     * @param \iveeCrest\Responses\Root $root to be used
     *
     * @return array
     */
    protected static function getData(Root $root)
    {
        return $root->getIndustrySystemCollection()->gather();
    }
}
