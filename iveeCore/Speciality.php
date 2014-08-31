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
 * Inheritance: Speciality -> SdeTypeCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Speciality.php
 *
 */
class Speciality extends SdeTypeCommon
{
    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) Speciality objects
     */
    protected static $instancePool;

    /**
     * @var string $classNick holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing) and is used as part of the cache key prefix for objects of this and child classes
     */
    protected static $classNick = 'Speciality';

    /**
     * @var array $specialityGroupIDs holds the groupIDs of bonused Types
     */
    protected $specialityGroupIDs = array();

    /**
     * Method blocked as there is no safe way to get a Speciality by name
     *
     * @param string $name of requested Speciality
     *
     * @return void
     * @throws \iveeCore\Exceptions\IveeCoreException
     */
    public static function getIdByName($name)
    {
        static::throwException('IveeCoreException', 'GetByName methods not implemented for Speciality');
    }

    /**
     * Constructor. Use \iveeCore\Speciality::getById() to instantiate Speciality objects instead.
     *
     * @param int $id of requested Speciality
     *
     * @return \iveeCore\Speciality
     * @throws \iveeCore\Exceptions\SpecialityIdNotFoundException if the specialityID is not found
     */
    protected function __construct($id)
    {
        $this->id = (int) $id;
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT specialityName
            FROM iveeSpecialities
            WHERE specialityID = " . $this->id . ';'
        )->fetch_assoc();

        if (empty($row))
            static::throwException('SpecialityIdNotFoundException', "Speciality ID=". $this->id . " not found");

        //set data to attributes
        $this->name = $row['specialityName'];

        $res = $sde->query(
            "SELECT groupID
            FROM iveeSpecialityGroups
            WHERE specialityID = " . $this->id . ';'
        );

        while ($row = $res->fetch_assoc())
            $this->specialityGroupIDs[(int) $row['groupID']] = 1;
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
