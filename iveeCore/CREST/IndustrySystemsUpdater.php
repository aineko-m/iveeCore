<?php
/**
 * IndustrySystemsUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustrySystemsUpdater.php
 *
 */

namespace iveeCore\CREST;
use iveeCore\Config, iveeCrest\EndpointHandler;

/**
 * IndustrySystemsUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustrySystemsUpdater.php
 *
 */
class IndustrySystemsUpdater extends CrestDataUpdater
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

        if (!isset($item->solarSystem->id))
            throw new $exceptionClass('systemID missing in Industry Systems CREST data');
        $systemID = (int) $item->solarSystem->id;

        $update = array();

        foreach ($item->systemCostIndices as $indexObj) {
            if (!isset($indexObj->activityID))
                throw new $exceptionClass(
                    'activityID missing in Industry Systems CREST data for systemID ' . $systemID);
            if (!isset($indexObj->costIndex))
                throw new $exceptionClass(
                    'costIndex missing in Industry Systems CREST data for systemID ' . $systemID);

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
            default :
                throw new $exceptionClass(
                    'Unknown activityID received from Industry Systems CREST data for systemID ' . $systemID);
            }
        }
        $insert = $update;
        $insert['systemID'] = $systemID;
        $insert['date'] = date('Y-m-d');

        $this->updatedIDs[] = $systemID;

        $sdeClass = Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery(Config::getIveeDbName() . '.systemIndustryIndices', $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        $assemblyLineClass  = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass::deleteFromCache($this->updatedIDs);
    }

    /**
     * Fetches the data from CREST.
     *
     * @param iveeCrest\EndpointHandler $eph to be used
     *
     * @return array
     */
    protected static function getData(EndpointHandler $eph)
    {
        //we dont set the cache flag because the data normally won't be read again
        return $eph->getIndustrySystems(false);
    }
}
