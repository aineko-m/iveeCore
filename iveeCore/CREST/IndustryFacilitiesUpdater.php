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
 */

namespace iveeCore\CREST;
use \iveeCore\Config;

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
     * @var string $path holds the CREST path
     */
    protected static $path = 'industry/facilities/';

    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.IndustryFacilityCollection-v1';

    /**
     * Processes data for facilities (stations and player built outposts)
     *
     * @param \stdClass $item to be processed
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
        $facilityID = (int) $item->facilityID;

        if (!isset($item->owner))
            throw new $exceptionClass("owner missing from facilities CREST data");
        $update['owner'] = (int) $item->owner->id;

        //branch depending if station is conquerable
        if ($facilityID >= 61000000 OR ($facilityID >= 60014861 AND $facilityID <= 60014928)) {
            $update['solarSystemID'] = (int) $item->solarSystem->id;
            $update['stationName']   = $item->name;
            $update['stationTypeID'] = (int) $item->type->id;
            $table = Config::getIveeDbName() . '.iveeOutposts';
        } else {
            if (isset($item->tax))
                $update['tax'] = (float) $item->tax;
            $table = Config::getIveeDbName() . '.iveeFacilities';
        }

        $insert = $update;
        $insert['facilityID'] = $facilityID;
        $this->updatedIDs[] = $facilityID;

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
        $assemblyLineClass::deleteFromCache($this->updatedIDs);

        //we also need to invalidate the Station names cache as Outpost names can change
        $cacheClass = Config::getIveeClassName('Cache');
        $cache = $cacheClass::instance();
        $cache->deleteItem(\iveeCore\Station::getClassHierarchyKeyPrefix() . 'Names');
    }
}
