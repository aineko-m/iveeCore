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
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Station.php
 *
 */
class Station
{
   /**
     * @var \iveeCore\InstancePool $instancePool (internal cache) for instantiated AssemblyLine objects
     */
    private static $instancePool;

    /**
     * @var int $stationID the ID of this Station.
     */
    protected $stationID;

    /**
     * @var string $stationName the name of this Station.
     */
    protected $stationName;

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
     * Initializes static InstancePool
     *
     * @return void
     */
    private static function init()
    {
        if (!isset(self::$instancePool)) {
            $ipoolClass = Config::getIveeClassName('InstancePool');
            self::$instancePool = new $ipoolClass('station_');
        }
    }

    /**
     * Main function for getting Station objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $stationID of requested Station
     *
     * @return \iveeCore\Station
     * @throws \iveeCore\Exceptions\StationIdNotFoundException if the stationID is not found
     */
    public static function getStation($stationID)
    {
        if (!isset(self::$instancePool))
            self::init();

        $stationID = (int) $stationID;
        try {
            return self::$instancePool->getObjById($stationID);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $stationClass = Config::getIveeClassName('Station');
            $station = new $stationClass($stationID);
            //store Station object in instance pool (and cache if configured)
            self::$instancePool->setIdObj($stationID, $station);
            
            return $station;
        }
    }

    /**
     * Removes all stationIDs given in array from cache. 
     * If using memcached, requires php5-memcached version >= 2.0.0
     *
     * @param array &$stationIDs with all the IDs to be removed from cache
     *
     * @return bool on success
     */
    public static function deleteStationsFromCache(&$stationIDs)
    {
        $cacheKeysToDelete = array();
        $cachePrefix = Config::getCachePrefix();
        foreach ($stationIDs as $stationID)
            $cacheKeysToDelete[] = $cachePrefix . 'station_' . $stationID;

        $cacheClass = \iveeCore\Config::getIveeClassName('Cache');
        return $cacheClass::instance()->deleteMulti($cacheKeysToDelete);
    }

    /**
     * Constructor. Use \iveeCore\Station::getStation() to instantiate Station objects instead.
     *
     * @param int $stationID of the Station
     *
     * @return \iveeCore\Station
     * @throws \iveeCore\Exceptions\StationIdNotFoundException if stationID is not found
     * @throws \iveeCore\Exceptions\IveeCoreException if trying to instantiate a player built outpost
     */
    protected function __construct($stationID)
    {
        $this->stationID = (int) $stationID;
        if ($this->stationID >= 61000000) {
            $exceptionClass = Config::getIveeClassName('IveeCoreException');
            throw new $exceptionClass("iveeCore currently can't handle player built outposts.");
        }
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT operationID, stationTypeID, corporationID, solarSystemID, stationName, reprocessingEfficiency
            FROM staStations
            WHERE stationID = " . $this->stationID . ';'
        )->fetch_assoc();

        if (empty($row)) {
            $exceptionClass = Config::getIveeClassName('StationIdNotFoundException');
            throw new $exceptionClass("stationID ". $this->stationID . " not found");
        }

        //set data to attributes
        $this->operationID   = (int) $row['operationID'];
        $this->stationTypeID = (int) $row['stationTypeID'];
        $this->corporationID = (int) $row['corporationID'];
        $this->solarSystemID = (int) $row['solarSystemID'];
        $this->stationName   = $row['stationName'];
        $this->reprocessingEfficiency   = (float) $row['reprocessingEfficiency'];

        //get assembly lines in station
        $res = $sde->query(
            "SELECT rals.assemblyLineTypeID, ralt.activityID
            FROM ramAssemblyLineStations as rals
            JOIN ramAssemblyLineTypes as ralt ON ralt.assemblyLineTypeID = rals.assemblyLineTypeID
            WHERE stationID = " . $this->stationID . ';'
        );

        while ($row = $res->fetch_assoc()) {
            $this->assemblyLineTypeIDs[$row['activityID']][] = $row['assemblyLineTypeID'];
        }
    }

    /**
     * Gets stationID
     * 
     * @return int
     */
    public function getStationID()
    {
        return $this->stationID;
    }

    /**
     * Gets station name
     * 
     * @return string
     */
    public function getStationName()
    {
        return $this->stationName;
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
        if ($this->stationID >= 61000000) {
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
}
