<?php
/**
 * IndustryFacilitiesUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryFacilitiesUpdater.php
 *
 */

namespace iveeCore\CREST;

/**
 * IndustryFacilities specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryFacilitiesUpdater.php
 *
 */
class IndustryFacilitiesUpdater extends CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = 'industry/facilities/';
    
    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.IndustryFacilityCollection-v1';

    /**
     * Processes data objects to SQL
     * 
     * @param \stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = \iveeCore\Config::getIveeClassName('CrestException');

        $update = array();

        if (!isset($item->facilityID))
            throw new $exceptionClass("facilityID missing from facilities CREST data");
        $facilityID = (int) $item->facilityID;

        if (!isset($item->owner))
            throw new $exceptionClass("owner missing from facilities CREST data");
        $update['owner'] = (int) $item->owner->id;

        if (isset($item->tax))
            $update['tax'] = (float) $item->tax;

        $insert = $update;
        $insert['facilityID'] = $facilityID;

        $this->updatedIDs[] = $facilityID;

        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery('iveeFacilities', $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        $cachePrefix = \iveeCore\Config::getCachePrefix();
        $cacheClass  = \iveeCore\Config::getIveeClassName('Cache');
        $cache = $cacheClass::instance();
        foreach ($this->updatedIDs as $stationID)
            $cache->deleteItem($cachePrefix . 'station_' . $stationID);
    }
}
