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
     * @var \iveeCore\InstancePool $instancePool (internal cache) for instantiated Team objects
     */
    private static $instancePool;

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
     * Initializes static InstancePool
     *
     * @return void
     */
    private static function init()
    {
        if (!isset(self::$instancePool)) {
            $ipoolClass = Config::getIveeClassName('InstancePool');
            self::$instancePool = new $ipoolClass('speciality_');
        }
    }

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
        if (!isset(self::$instancePool))
            self::init();

        $specialityID = (int) $specialityID;
        try {
            return self::$instancePool->getObjById($specialityID);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //go to DB
            $specialityClass = Config::getIveeClassName('Speciality');
            $speciality = new $specialityClass($specialityID);
            //store Speciality object in instance pool (and cache if configured)
            self::$instancePool->setIdObj($specialityID, $speciality);
            
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
