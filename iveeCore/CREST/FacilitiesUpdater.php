<?php
/**
 * FacilitiesUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/FacilitiesUpdater.php
 */

namespace iveeCore\CREST;

use iveeCore\Config;
use iveeCore\Station;
use iveeCrest\Responses\Root;

/**
 * IndustryFacilities specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/FacilitiesUpdater.php
 */
class FacilitiesUpdater extends CrestDataUpdater
{
    /**
     * Saves the data to the database.
     *
     * @return void
     */
    public function insertIntoDB()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sdeDb = $sdeClass::instance();
        $sql = '';
        $count = 0;

        foreach ($this->data as $system) {
            foreach ($system as $item) {
                $sql .= $this->processDataItemToSQL($item);
                $count++;
                if ($count % 100 == 0 or $count == count($this->data)) {
                    $sdeDb->multiQuery($sql . ' COMMIT;');
                    $sql = '';
                }
            }
        }

        $this->invalidateCaches();
        $this->updatedIds = [];
    }

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

        $update = [];

        if (!isset($item->facilityID)) {
            throw new $exceptionClass("facilityID missing from facilities CREST data");
        }
        $facilityId = (int) $item->facilityID;

        if (!isset($item->owner)) {
            throw new $exceptionClass("owner missing from facilities CREST data");
        }
        $update['ownerID'] = (int) $item->owner->id;

        //only store if station is conquerable
        if ($facilityId >= 61000000 or ($facilityId >= 60014861 and $facilityId <= 60014928)) {
            $update['solarSystemID'] = (int) $item->solarSystem->id;
            $update['stationName']   = $item->name;
            $update['stationTypeID'] = (int) $item->type->id;
            $table = Config::getIveeDbName() . '.outposts';
        } else {
            return '';
        }

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
     * @param \iveeCrest\Responses\Root $pubRoot to be used
     *
     * @return array
     */
    protected static function getData(Root $pubRoot)
    {
        return $pubRoot->getIndustryFacilityCollection()->gather();
    }
}
