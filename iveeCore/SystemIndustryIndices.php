<?php
/**
 * SystemIndustryIndices class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SystemIndustryIndices.php
 */

namespace iveeCore;

use iveeCore\Exceptions\CrestDataTooOldException;
use iveeCore\Exceptions\KeyNotFoundInCacheException;

/**
 * Class for representing solar systems industry indices.
 * Inheritance: SystemIndustryIndices -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SystemIndustryIndices.php
 */
class SystemIndustryIndices extends CoreDataCommon
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'SystemIndustryIndices';

    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) objects
     */
    protected static $instancePool;

    /**
     * @var int $updateTs unix timstamp for the last update to industry system indices
     */
    protected $updateTs;

    /**
     * @var float[] $industryIndices the system industry indices $activityId => float
     */
    protected $industryIndices = [];

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
     * Main function for getting SystemIndustryIndices objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $systemId of the solar system
     * @param int $maxDataAge the maximum acceptable CREST industry index data age.
     *
     * @return \iveeCore\SystemIndustryIndices
     */
    public static function getById($systemId, $maxDataAge = 3600)
    {
        //setup instance pool if needed
        if (!isset(static::$instancePool)) {
            static::init();
        }

        //try instance pool and cache
        try {
            $si = static::$instancePool->getItem(
                static::getClassHierarchyKeyPrefix() . (int) $systemId
            );
            if (!$si->isTooOld($maxDataAge)) {
                return $si;
            }
        } catch (KeyNotFoundInCacheException $e) { //empty as we are using Exceptions for flow control here
        }

        $lastUpdateTs = static::getLastUpdateTs();

        //if the last update is too long ago, do another
        if ($lastUpdateTs + $maxDataAge < time()) {
            //fetch data from CREST and update DB for all systems
            $crestIndustryIndicesUpdaterClass = Config::getIveeClassName('CrestIndustryIndicesUpdater');
            $crestIndustryIndicesUpdaterClass::doUpdate();

            //since the CREST update affects all system indices, clear the instance pool
            static::$instancePool->clearPool();
        }

        //instantiate new SystemIndustryIndices
        $siClass = Config::getIveeClassName(static::getClassNick());
        $si = new $siClass($systemId, $lastUpdateTs, $maxDataAge);

        //store object in instance pool and cache
        static::$instancePool->setItem($si);
        return $si;
    }

    /**
     * Constructor. Use getById() instead.
     *
     * @param int $systemId of the solar system
     * @param int $lastUpdateTs the timestamp of the last performed update from CREST
     * @param int $maxDataAge the maximum acceptable index data age.
     *
     * @throws \vieeCore\Exceptions\SystemIdNotFoundException when a systemId is given for which there is no data
     */
    protected function __construct($systemId, $lastUpdateTs, $maxDataAge)
    {
        $this->id = (int) $systemId;
        $this->updateTs = $lastUpdateTs;
        $this->expiry = $this->updateTs + $maxDataAge;

        $sdeClass = Config::getIveeClassName('SDE');
        $res = $sdeClass::instance()->query(
            "SELECT systemID, UNIX_TIMESTAMP(date) as crestIndexDate, manufacturingIndex, teResearchIndex,
            meResearchIndex, copyIndex, inventionIndex
            FROM " . Config::getIveeDbName() . ".systemIndustryIndices
            WHERE systemID = " . $this->id . "
            ORDER BY date DESC LIMIT 1;"
        )->fetch_assoc();

        if (!empty($res)) {
            $this->industryIndices[1] = (float) $res['manufacturingIndex'];
            $this->industryIndices[3] = (float) $res['teResearchIndex'];
            $this->industryIndices[4] = (float) $res['meResearchIndex'];
            $this->industryIndices[5] = (float) $res['copyIndex'];
            $this->industryIndices[8] = (float) $res['inventionIndex'];
        } else {
            $exceptionClass = Config::getIveeClassName('SystemIdNotFoundException');
            throw new $exceptionClass('No solar system industry index data found for system ID = ' . $this->id);
        }
    }

    /**
     * Gets the timestamp for the last performed update of the indices via CREST.
     *
     * @return int
     */
    public static function getLastUpdateTs()
    {
        $sdeClass = Config::getIveeClassName('SDE');

        //get most recent update date
        $res = $sdeClass::instance()->query(
            "SELECT UNIX_TIMESTAMP(lastUpdate) as lastUpdateTs
            FROM " . Config::getIveeDbName() . ".trackedCrestUpdates
            WHERE name = 'industryIndices';"
        );

        if ($res->num_rows > 0) {
            return (int) $res->fetch_assoc()['lastUpdateTs'];
        } else {
            return 0;
        }
    }

    /**
     * Gets whether the current data is too old.
     *
     * @param int $maxDataAge specifies the maximum CREST data age in seconds.
     *
     * @return bool
     */
    public function isTooOld($maxDataAge = 3600)
    {
        return $this->updateTs + $maxDataAge < time();
    }

    /**
     * Returns the unix timestamp of when the industry indices were last updated.
     *
     * @return int
     */
    public function getIndustryIndexUpdateTs()
    {
        return $this->updateTs;
    }

    /**
     * Returns the industry cost indices in the form activityId => float
     *
     * @return float[]
     */
    public function getIndices()
    {
        return $this->industryIndices;
    }

    /**
     * Returns the industry cost indices in the form activityId => float
     *
     * @param int $activityId to be looked up
     *
     * @return float
     */
    public function getIndexForActivity($activityId)
    {
        if (isset($this->industryIndices[$activityId])) {
            return $this->industryIndices[$activityId];
        } else {
            $exceptionClass = Config::getIveeClassName('ActivityIdNotFoundException');
            throw new $exceptionClass('Activity with id = ' . (int) $activityId . ' not found.');
        }
    }

    /**
     * Sets industry indices. Useful for wormhole systems or what-if scenarios. If called, updateTs is updated.
     *
     * @param float[] $indices must be in the form activityId => float
     *
     * @return void
     */
    public function setIndustryIndices(array $indices)
    {
        $this->updateTs = time();
        $this->industryIndices = $indices;
    }

    /**
     * Gets the SolarSystem which these indices belong to.
     *
     * @return \iveeCore\SolarSystem
     */
    public function getSolarSystem()
    {
        $solarSystemClass = Config::getIveeClassName('SolarSystem');
        return $solarSystemClass::getById($this->id);
    }
}
