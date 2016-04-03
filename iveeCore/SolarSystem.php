<?php
/**
 * SolarSystem class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SolarSystem.php
 */

namespace iveeCore;

/**
 * Class for representing solar systems
 * Inheritance: SolarSystem -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SolarSystem.php
 */
class SolarSystem extends SdeType
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'SolarSystem';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) SolarSystem objects
     */
    protected static $instancePool;

    /**
     * @var int $regionId the ID of region of this SolarSystem.
     */
    protected $regionId;

    /**
     * @var int $constellationId the ID of the constellation of this SolarSystem.
     */
    protected $constellationId;

    /**
     * @var float $security the security rating of this SolarSystem.
     */
    protected $security;

    /**
     * @var int $factionId of the ruling faction in that system.
     */
    protected $factionId;

    /**
     * @var int $industryIndexDate unix timstamp for the last update to industry system indices (day granularity)
     */
    protected $industryIndexDate;

    /**
     * @var array $industryIndices the system industry indices $activityId => float
     */
    protected $industryIndices = [];

    /**
     * @var array $stationIds the IDs of Stations present in this SolarSystem
     */
    protected $stationIds = [];

    /**
     * @var \iveeCore\Station[] lazy loaded
     */
    protected $stations;

    /**
     * Loads all SolarSystem names from DB to PHP.
     *
     * @return void
     */
    protected static function loadNames()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        $res = $sdeClass::instance()->query(
            "SELECT solarSystemID, solarSystemName
            FROM mapSolarSystems;"
        );

        $namesToIds = [];
        while ($row = $res->fetch_assoc()) {
            $namesToIds[$row['solarSystemName']] = (int) $row['solarSystemID'];
        }

        static::$instancePool->setNamesToKeys(static::getClassHierarchyKeyPrefix() . 'Names', $namesToIds);
    }

    /**
     * Returns a string that is used as cache key prefix specific to a hierarchy of SDE related classes. Example:
     * Type and Blueprint are in the same hierarchy, Type and SolarSystem are not.
     *
     * @return string
     */
    public static function getClassHierarchyKeyPrefix()
    {
        return __CLASS__ . '_';
    }

    /**
     * Constructor. Use iveeCore\SolarSystem::getSolarSystem() to instantiate SolarSystem objects instead.
     *
     * @param int $id of the SolarSystem
     *
     * @throws \iveeCore\Exceptions\SolarSystemIdNotFoundException if solarSystemId is not found
     */
    protected function __construct($id)
    {
        $this->id = (int) $id;
        $this->setExpiry();
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT regionID, constellationID, solarSystemName, security, factionID
            FROM mapSolarSystems
            WHERE solarSystemID = " . $this->id . ";"
        )->fetch_assoc();

        if (empty($row)) {
            static::throwException('SystemIdNotFoundException', "SolarSystem ID=". $this->id . " not found");
        }

        //set data to attributes
        $this->regionId        = (int) $row['regionID'];
        $this->constellationId = (int) $row['constellationID'];
        $this->name            = $row['solarSystemName'];
        $this->security        = (float) $row['security'];
        $this->factionId       = (int) $row['factionID'];

        $res = $sde->query(
            "SELECT systemID, UNIX_TIMESTAMP(date) as crestIndexDate, manufacturingIndex, teResearchIndex,
            meResearchIndex, copyIndex, inventionIndex
            FROM " . Config::getIveeDbName() . ".systemIndustryIndices
            WHERE systemID = " . $this->id . "
            ORDER BY date DESC LIMIT 1;"
        )->fetch_assoc();

        if (!empty($res)) {
            if (isset($res['crestIndexDate'])) {
                $this->industryIndexDate = (int) $res['crestIndexDate'];
            }
            if (isset($res['manufacturingIndex'])) {
                $this->industryIndices[1] = (float) $res['manufacturingIndex'];
            }
            if (isset($res['teResearchIndex'])) {
                $this->industryIndices[3] = (float) $res['teResearchIndex'];
            }
            if (isset($res['meResearchIndex'])) {
                $this->industryIndices[4] = (float) $res['meResearchIndex'];
            }
            if (isset($res['copyIndex'])) {
                $this->industryIndices[5] = (float) $res['copyIndex'];
            }
            if (isset($res['inventionIndex'])) {
                $this->industryIndices[8] = (float) $res['inventionIndex'];
            }
        }

        $this->loadStations($sde);
    }

    /**
     * Loads stationIds in system.
     *
     * @param \iveeCore\SDE $sde the SDE object
     *
     * @return void
     */
    protected function loadStations(SDE $sde)
    {
        $res = $sde->query(
            "SELECT stationID
            FROM staStations
            WHERE solarSystemID = " . $this->id
            . " UNION DISTINCT
            SELECT facilityID as stationID
            FROM " . Config::getIveeDbName() . ".outposts
            WHERE solarSystemID = " . $this->id . ";"
        );

        while ($row = $res->fetch_assoc()) {
            $this->stationIds[] = $row['stationID'];
        }
    }
    
    /**
     * Gets regionId of SolarSystem.
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Gets constellationId of SolarSystem.
     *
     * @return int
     */
    public function getConstellationId()
    {
        return $this->constellationId;
    }

    /**
     * Gets security rating of SolarSystem.
     *
     * @return float
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Gets the ID of the ruling faction in the system.
     *
     * @return int
     */
    public function getFactionId()
    {
        return $this->factionId;
    }

    /**
     * Gets IDs of Stations in SolarSystem.
     *
     * @return int[]
     */
    public function getStationIds()
    {
        return $this->stationIds;
    }

    /**
     * Gets Stations in SolarSystem.
     *
     * @return \iveeCore\Station[]
     */
    public function getStations()
    {
        if (!isset($this->stations)) {
            $this->stations = [];
            $stationClass = Config::getIveeClassName("Station");
            foreach ($this->getStationIds() as $stationId) {
                $this->stations[$stationId] = $stationClass::getById($stationId);
            }
        }
        return $this->stations;
    }

    /**
     * Gets the Stations in SolarSystem with a specific service.
     *
     * @param int $serviceId to be searched for
     *
     * @return \iveeCore\Station[] in the form stationId => Station
     */
    public function getStationsWithService($serviceId)
    {
        $ret = [];
        foreach ($this->getStations() as $station) {
            if (in_array($serviceId, $station->getServiceIds())) {
                $ret[$station->getId()] = $station;
            }
        }

        return $ret;
    }

    /**
     * Gets unix timstamp for the last update to industry system indices (day granularity).
     *
     * @return int
     */
    public function getIndustryIndexDate()
    {
        if ($this->industryIndexDate > 0) {
            return $this->industryIndexDate;
        } else {
            static::throwException(
                'NoSystemDataAvailableException',
                'No CREST system data available for SolarSystem ID=' . $this->id
            );
        }
    }

    /**
     * Gets industry indices of SolarSystem.
     *
     * @param int $maxIndexDataAge maximum index data age in seconds
     *
     * @return float[] in the form activityId => float
     * @throws \iveeCore\Exceptions\CrestDataTooOldException if given max index data age is exceeded
     */
    public function getIndustryIndices($maxIndexDataAge = 172800)
    {
        if ($maxIndexDataAge > 0 and ($this->industryIndexDate + $maxIndexDataAge) < time()) {
            static::throwException('CrestDataTooOldException', 'Index data for ' . $this->getName() . ' is too old');
        }

        return $this->industryIndices;
    }

    /**
     * Gets industry indices of SolarSystem.
     *
     * @param int $activityId the ID of the activity to get industry index for
     * @param int $maxIndexDataAge maximum index data age in seconds
     *
     * @return float
     * @throws \iveeCore\Exceptions\ActivityIdNotFoundException if no index data is found for activityId in this system
     */
    public function getIndustryIndexForActivity($activityId, $maxIndexDataAge = 172800)
    {
        if (isset($this->industryIndices[$activityId])) {
            if ($maxIndexDataAge > 0 and ($this->industryIndexDate + $maxIndexDataAge) < time()) {
                static::throwException('CrestDataTooOldException', 'Index data for ' . $this->getName() . ' is too old');
            }
            return $this->industryIndices[$activityId];
        } else {
            static::throwException(
                'ActivityIdNotFoundException',
                'No industry index data found for activity ID=' . (int) $activityId
            );
        }
    }

    /**
     * Sets industry indices. Useful for wormhole systems or what-if scenarios. If called, industryIndexDate is updated.
     *
     * @param float[] $indices must be in the form activityId => float
     *
     * @return void
     */
    public function setIndustryIndices(array $indices)
    {
        $this->industryIndexDate = time();
        $this->industryIndices = $indices;
    }

    /**
     * Returns an IndustryModifier object for a POS in this system.
     *
     * @param float $tax set on the POS
     *
     * @return \iveeCore\IndustryModifier
     */
    public function getIndustryModifierForPos($tax)
    {
        $industryModifierClass = Config::getIveeClassName('IndustryModifier');
        return $industryModifierClass::getBySystemIdForPos($this->id, $tax);
    }

    /**
     * Returns an IndustryModifier object for all NPC stations in this system.
     *
     * @return \iveeCore\IndustryModifier
     */
    public function getIndustryModifierForAllNpcStations()
    {
        $industryModifierClass = Config::getIveeClassName('IndustryModifier');
        return $industryModifierClass::getBySystemIdForAllNpcStations($this->id);
    }
}
