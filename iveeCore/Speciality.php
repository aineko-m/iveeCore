<?php
/**
 * Speciality class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Speciality.php
 *
 */

namespace iveeCore;

/**
 * Class for representing industry team specialities
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Speciality.php
 *
 */
class Speciality
{
    /**
     * @var array $_specialities acts as internal Speciality object cache, specialityID => Speciality.
     */
    private static $_specialities;

    /**
     * @var int $_internalCacheHit counter for the internal Speciality cache hits
     */
    private static $_internalCacheHit = 0;

    /**
     * @var int $specialityID ID of the Speciality
     */
    protected $specialityID;

    /**
     * @var string $specialityName name of the Speciality
     */
    protected $specialityName;

    /**
     * @var array $specialityGroupIDs holds the groupIDs of bonused Types
     */
    protected $specialityGroupIDs = array();

    /**
     * Main function for getting Speciality objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $specialityID of requested Speciality
     *
     * @return \iveeCore\Speciality
     * @throws \iveeCore\Exceptions\SpecialityIdNotFoundException if the specialityID is not found
     */
    public static function getSpeciality($specialityID)
    {
        $specialityID = (int) $specialityID;
        //try php array first
        if (isset(static::$_specialities[$specialityID])) {
            //count internal cache hit
            static::$_internalCacheHit++;
            return static::$_specialities[$specialityID];
        } else {
            //try cache
            if (Config::getUseCache()) {
                //lookup Cache class
                $cacheClass = Config::getIveeClassName('Cache');
                $cache = $cacheClass::instance();
                try {
                    $speciality = $cache->getItem('speciality_' . $specialityID);
                } catch (Exceptions\KeyNotFoundInCacheException $e) {
                    //go to DB
                    $speciality = new static($specialityID);
                    //store object in cache
                    $cache->setItem($speciality, 'speciality_' . $specialityID);
                }
            } else
                //not using cache, go to DB
                $speciality = new static($specialityID);

            //store object in internal cache
            static::$_specialities[$specialityID] = $speciality;
            return $speciality;
        }
    }

    /**
     * Constructor. Use \iveeCore\Speciality::getType() to instantiate Speciality objects instead.
     *
     * @param int $specialityID of requested Speciality
     *
     * @return \iveeCore\Speciality
     * @throws \iveeCore\Exceptions\SpecialityIdNotFoundException if the specialityID is not found
     */
    protected function __construct($specialityID)
    {
        $this->specialityID = (int) $specialityID;
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT specialityName
            FROM iveeSpecialities
            WHERE specialityID = " . $this->specialityID . ';'
        )->fetch_assoc();

        if (empty($row)) {
            $exceptionClass = Config::getIveeClassName('SpecialityIdNotFoundException');
            throw new $exceptionClass("specialityID=". $this->specialityID . " not found");
        }

        //set data to attributes
        $this->specialityName = $row['specialityName'];

        $res = $sde->query(
            "SELECT groupID
            FROM iveeSpecialityGroups
            WHERE specialityID = " . $this->specialityID . ';'
        );

        while ($row = $res->fetch_assoc())
            $this->specialityGroupIDs[(int) $row['groupID']] = 1;
    }

    /**
     * Gets specialityID
     * 
     * @return int
     */
    public function getSpecialityID()
    {
        return $this->specialityID;
    }

    /**
     * Gets speciality name
     * 
     * @return string
     */
    public function getSpecialityName()
    {
        return $this->specialityName;
    }

    /**
     * Gets groupIDs of Types bonused by this Speciality
     * 
     * @return array
     */
    public function getSpecialtyGroupIDs()
    {
        return $this->specialityGroupIDs;
    }

    /**
     * Returns if a Speciality applies bonuses to a given groupID
     * 
     * @param int $groupID the groupID to test
     * 
     * @return bool
     */
    public function appliesToGroupID($groupID)
    {
        return isset($this->specialityGroupIDs[(int) $groupID]);
    }
}
