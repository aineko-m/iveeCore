<?php
/**
 * Station class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Station.php
 *
 */

namespace iveeCore;

/**
 * Class for representing stations
 * Inheritance: Station -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Station.php
 *
 */
class Station extends SdeType
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'Station';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) Station objects
     */
    protected static $instancePool;

    /**
     * @var int $solarSystemID the ID of SolarSystem this Station is in.
     */
    protected $solarSystemID;

    /**
     * @var int $operationID the ID of operation for this Station, this implies the available station services.
     */
    protected $operationID;

    /**
     * @var int $stationTypeID the ID of type of station.
     */
    protected $stationTypeID;

    /**
     * @var int $corporationID the ID of owning corporation.
     */
    protected $corporationID;

    /**
     * @var float $reprocessingEfficiency the reprocessing efficiency of this Station as factor (<1.0)
     */
    protected $reprocessingEfficiency;

    /**
     * @var float $tax the reprocessing efficiency of this Station as percentage / 100.
     */
    protected $tax;

    /**
     * @var array $assemblyLineTypeIDs the IDs of the available assemblyLineTypes, this determines possible industrial
     * activities (depending on the activity output item) as well as bonuses.
     */
    protected $assemblyLineTypeIDs = array();

    /**
     * Loads all Station names from DB to PHP
     *
     * @return void
     */
    protected static function loadNames()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        $res = $sdeClass::instance()->query(
            "SELECT stationID, stationName
            FROM staStations;"
        );

        $namesToIds = array();
        while ($row = $res->fetch_assoc())
            $namesToIds[$row['stationName']] = (int) $row['stationID'];

        static::$instancePool->setNamesToKeys(static::getClassHierarchyKeyPrefix() . 'Names', $namesToIds);
    }

    /**
     * Returns a string that is used as cache key prefix specific to a hierarchy of SdeType classes. Example:
     * Type and Blueprint are in the same hierarchy, Type and SolarSystem are not.
     *
     * @return string
     */
    public static function getClassHierarchyKeyPrefix()
    {
        return __CLASS__ . '_';
    }

    /**
     * Constructor. Use \iveeCore\Station::getById() to instantiate Station objects instead.
     *
     * @param int $id of the Station
     *
     * @return \iveeCore\Station
     * @throws \iveeCore\Exceptions\StationIdNotFoundException if stationID is not found
     * @throws \iveeCore\Exceptions\IveeCoreException if trying to instantiate a player built outpost
     */
    protected function __construct($id)
    {
        $this->id = (int) $id;
        if ($this->id >= 61000000)
            static::throwException('IveeCoreException', "iveeCore currently can't handle player built outposts.");

        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT operationID, stationTypeID, corporationID, solarSystemID, stationName, reprocessingEfficiency
            FROM staStations
            WHERE stationID = " . $this->id . ';'
        )->fetch_assoc();

        if (empty($row))
            static::throwException('StationIdNotFoundException', "Station ID=". $this->id . " not found");

        //set data to attributes
        $this->operationID   = (int) $row['operationID'];
        $this->stationTypeID = (int) $row['stationTypeID'];
        $this->corporationID = (int) $row['corporationID'];
        $this->solarSystemID = (int) $row['solarSystemID'];
        $this->name          = $row['stationName'];
        $this->reprocessingEfficiency   = (float) $row['reprocessingEfficiency'];

        //get assembly lines in station
        $res = $sde->query(
            "SELECT rals.assemblyLineTypeID, ralt.activityID
            FROM ramAssemblyLineStations as rals
            JOIN ramAssemblyLineTypes as ralt ON ralt.assemblyLineTypeID = rals.assemblyLineTypeID
            WHERE stationID = " . $this->id . ';'
        );

        while ($row = $res->fetch_assoc()) {
            $this->assemblyLineTypeIDs[$row['activityID']][] = $row['assemblyLineTypeID'];
        }
    }

    /**
     * Gets solarSystemID
     *
     * @return int
     */
    public function getSolarSystemID()
    {
        return $this->solarSystemID;
    }

    /**
     * Gets SolarSystem
     *
     * @return \iveeCore\SolarSystem
     */
    public function getSolarSystem()
    {
        $systemClass = Config::getIveeClassName('SolarSystem');
        return $systemClass::getSolarSystem($this->getSolarSystemID());
    }

    /**
     * Gets oprationID
     *
     * @return int
     */
    public function getOperationID()
    {
        return $this->operationID;
    }

    /**
     * Gets stationTypeID
     *
     * @return int
     */
    public function getStationTypeID()
    {
        return $this->stationTypeID;
    }

    /**
     * Gets owning corporationID
     *
     * @return int
     */
    public function getCorporationID()
    {
        return $this->corporationID;
    }

    /**
     * Gets station reprocessing efficiency
     *
     * @return float
     */
    public function getReprocessingEfficiency()
    {
        return $this->reprocessingEfficiency;
    }

    /**
     * Gets station tax
     *
     * @return float
     * @throws \iveeCore\Exceptions\IveeCoreException if trying to get tax from player built outpost
     */
    public function getTax()
    {
        if ($this->id >= 61000000) {
            $exceptionClass = Config::getIveeClassName('IveeCoreException');
            throw new $exceptionClass("iveeCore currently can't handle player built outposts.");
        }
        if (isset($this->tax)) {
            return $this->tax;
        }
        return 0.1;
    }

    /**
     * Gets a stations assemblyLineTypeIDs
     *
     * @return array $activityID => array(id1, id2...)
     */
    public function getAssemblyLineTypeIDs()
    {
        return $this->assemblyLineTypeIDs;
    }

    /**
     * Gets a stations assemblyLineTypeIDs
     *
     * @param int $activityID to get assemblyLineTypeIDs for
     *
     * @return array in the form array(id1, id2...)
     */
    public function getAssemblyLineTypeIDsForActivity($activityID)
    {
        if (isset($this->assemblyLineTypeIDs[$activityID]))
            return $this->assemblyLineTypeIDs[$activityID];
        else
            return array();
    }

    /**
     * Returns an IndustryModifier object for this station
     *
     * @return \iveeCore\IndustryModifier
     */
    public function getIndustryModifier()
    {
        $industryModifierClass = Config::getIveeClassName('IndustryModifier');
        return $industryModifierClass::getByNpcStationID($this->id);
    }
}
