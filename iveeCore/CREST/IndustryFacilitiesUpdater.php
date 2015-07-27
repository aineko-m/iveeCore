<?php
/**
 * IndustryFacilitiesUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryFacilitiesUpdater.php
 */

namespace iveeCore\CREST;
use iveeCore\Config, iveeCrest\EndpointHandler, iveeCore\Station;

/**
 * IndustryFacilities specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustryFacilitiesUpdater.php
 */
class IndustryFacilitiesUpdater extends CrestDataUpdater
{
    /**
     * Processes data for facilities (stations and player built outposts)
     *
     * @param stdClass $item to be processed
     *
     * @return string the UPSERT SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = Config::getIveeClassName('CrestException');
        $sdeClass = Config::getIveeClassName('SDE');

        $update = array();

        if (!isset($item->facilityID))
            throw new $exceptionClass("facilityID missing from facilities CREST data");
        $facilityId = (int) $item->facilityID;

        if (!isset($item->owner))
            throw new $exceptionClass("owner missing from facilities CREST data");
        $update['ownerID'] = (int) $item->owner->id;

        //only store if station is conquerable
        if ($facilityId >= 61000000 OR ($facilityId >= 60014861 AND $facilityId <= 60014928)) {
            $update['solarSystemID'] = (int) $item->solarSystem->id;
            $update['stationName']   = $item->name;
            $update['stationTypeID'] = (int) $item->type->id;
            $table = Config::getIveeDbName() . '.outposts';
        } else
            return '';

        $insert = $update;
        $insert['facilityID'] = $facilityId;
        $this->updatedIds[] = $facilityId;

        return $sdeClass::makeUpsertQuery($table, $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        $assemblyLineClass  = Config::getIveeClassName('AssemblyLine');
        $assemblyLineClass::deleteFromCache($this->updatedIds);

        //we also need to invalidate the Station names cache as Outpost names can change
        $cacheClass = Config::getIveeClassName('Cache');
        $cache = $cacheClass::instance();
        $cache->deleteItem(Station::getClassHierarchyKeyPrefix() . 'Names');
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
        return $eph->getIndustryFacilities(false);
    }
}
