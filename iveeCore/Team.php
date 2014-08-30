<?php
/**
 * Team class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Team.php
 *
 */

namespace iveeCore;

/**
 * Class for representing industry teams
 * Inheritance: Team -> SdeTypeCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Team.php
 *
 */
class Team extends SdeTypeCommon
{
    /**
     * @var \iveeCore\InstancePool $instancePool used to pool (cache) Team objects
     */
    protected static $instancePool;

    /**
     * @var string $classNick holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing) and is used as part of the cache key prefix for objects of this and child classes
     */
    protected static $classNick = 'Team';

    /**
     * @var int $solarSystemID ID of the SolarSystem the Team is in
     */
    protected $solarSystemID;

    /**
     * @var int $creationTime unix timestamp of the creation date of the team
     */
    protected $creationTime;

    /**
     * @var int $expiryTime unix timestamp of the expiry date of the team
     */
    protected $expiryTime;

    /**
     * @var int $activityID ID of the activity the Team gives bonuses for
     */
    protected $activityID;

    /**
     * @var float $costModifier the salary of the Team expressed as cost factor (> 1.0)
     */
    protected $costModifier;

    /**
     * @var int $specialityID ID of the speciality of the team. Note that no actual bonuses are available for the 
     * referenced specialityIDs 0-6.
     */
    protected $specialityID;

    /**
     * @var array $bonusIDs bonus IDs for workers 0-3, where ID=0 means time bonus and ID=1 means material bonus
     */
    protected $bonusIDs;

    /**
     * @var array $bonusValues bonus values for workers 0-3, expressed as negative percentages
     */
    protected $bonusValues;

    /**
     * @var array $workerSpecialities Speciality for workers 0-3
     */
    protected $workerSpecialities;

    /**
     * Method blocked as there is no safe way to get a Team by name
     *
     * @param string $name of requested Team
     *
     * @return void
     * @throws \iveeCore\Exceptions\IveeCoreException
     */
    public static function getIdByName($name)
    {
        static::throwException('IveeCoreException', 'GetByName methods not implemented for Team');
    }

    /**
     * Constructor. Use \iveeCore\Team::getById() to instantiate Team objects instead.
     *
     * @param int $id of the Team
     *
     * @return \iveeCore\Team
     * @throws \iveeCore\Exceptions\TeamIdNotFoundException if teamID is not found
     */
    protected function __construct($id)
    {
        $this->id = (int) $id;

        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT solarSystemID, UNIX_TIMESTAMP(expiryTime) as expiryTime, UNIX_TIMESTAMP(creationTime) as
            creationTime, activityID, teamName, costModifier, specID, w0BonusID, w0BonusValue, w0SpecID, w1BonusID,
            w1BonusValue, w1SpecID, w2BonusID, w2BonusValue, w2SpecID, w3BonusID, w3BonusValue, w3SpecID
            FROM iveeTeams WHERE teamID = " .  $this->id . ";"
        )->fetch_assoc();

        if (empty($row))
            static::throwException('TeamIdNotFoundException', "teamID ". $this->id . " not found");

        //set data to attributes
        $this->solarSystemID = (int) $row['solarSystemID'];
        $this->expiryTime    = (int) $row['expiryTime'];
        $this->creationTime  = (int) $row['creationTime'];
        $this->activityID    = (int) $row['activityID'];
        $this->name          = $row['teamName'];
        $this->costModifier  = 1.0 + $row['costModifier'] / 100; //convert percent to factor
        $this->specialityID  = (int) $row['specID'];

        $this->bonusIDs = array(
            (int) $row['w0BonusID'],
            (int) $row['w1BonusID'],
            (int) $row['w2BonusID'],
            (int) $row['w3BonusID'],
        );

        $this->bonusValues = array(
            (float) $row['w0BonusValue'],
            (float) $row['w1BonusValue'],
            (float) $row['w2BonusValue'],
            (float) $row['w3BonusValue'],
        );

        $specialityClass = Config::getIveeClassName('Speciality');
        $this->workerSpecialities = array(
            $specialityClass::getById((int) $row['w0SpecID']),
            $specialityClass::getById((int) $row['w1SpecID']),
            $specialityClass::getById((int) $row['w2SpecID']),
            $specialityClass::getById((int) $row['w3SpecID'])
        );
    }

    /**
     * Gets the ID of the SolarSystem the Team is in
     * 
     * @return int
     */
    public function getSolarSystemID()
    {
        return $this->solarSystemID;
    }

    /**
     * Gets the unix timestmap of the creation date of the Team
     * 
     * @return int
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * Gets the unix timestamp of the expiry of the Team
     * 
     * @return int
     */
    public function getExpiryTime()
    {
        return $this->expiryTime;
    }

    /**
     * Gets the activityID the Team gives bonuses for
     * 
     * @return int
     */
    public function getActivityID()
    {
        return $this->activityID;
    }

    /**
     * Gets the cost modifier for using the Team as factor (>1.0)
     * 
     * @return float
     */
    public function getCostModifier()
    {
        return $this->costModifier;
    }

    /**
     * Gets the ID of the Speciality of the Team
     * 
     * @return int
     */
    public function getSpecialityID()
    {
        return $this->specialityID;
    }

    /**
     * Gets the IDs of type of bonus for workers 0-3, where bonusID=0 is a time bonus and bonusID=1 is a material bonus
     * 
     * @return array
     */
    public function getBonusIDs()
    {
        return $this->bonusIDs;
    }

    /**
     * Gets the values of the bonuses for workers 0-3, as negative percentages
     * 
     * @return array
     */
    public function getBonusValues()
    {
        return $this->bonusValues;
    }

    /**
     * Gets the Speciality's for workers 0-3
     * 
     * @return array worker => Speciality
     */
    public function getWorkerSpecialities()
    {
        return $this->workerSpecialities;
    }

    /**
     * Checks if the Team gives bonus to given Type
     * 
     * @param \iveeCore\Type $type to be checked
     * 
     * @return bool
     */
    public function isTypeCompatible(Type $type)
    {
        return $this->isGroupIDCompatible($type->getGroupID());
    }

    /**
     * Checks if the Team gives bonus to given groupID
     * 
     * @param int $groupID to be checked
     * 
     * @return bool
     */
    public function isGroupIDCompatible($groupID)
    {
        return (
            $this->workerSpecialities[0]->appliesToGroupID($groupID)
            OR $this->workerSpecialities[1]->appliesToGroupID($groupID)
            OR $this->workerSpecialities[2]->appliesToGroupID($groupID)
            OR $this->workerSpecialities[3]->appliesToGroupID($groupID)
        );
    }

    /**
     * Gets the IDs of the workers that give bonuses to given groupID
     * 
     * @param int $groupID to get worker IDs for
     * 
     * @return array
     */
    public function getWorkerIDsForGroupID($groupID)
    {
        $workerIDs = array();
        foreach ($this->workerSpecialities as $workerID => $speciality) {
            if ($speciality->appliesToGroupID($groupID))
                $workerIDs[] = $workerID;
        }
        return $workerIDs;
    }

    /**
     * Gets the modifiers for a given groupID
     * 
     * @param int $groupID to get modifiers for
     * 
     * @return array 'c' => costFactor, 'm' => materialFactor, 't' => timeFactor
     */
    public function getModifiersForGroupID($groupID)
    {
        $mods = array(
            'c' => $this->getCostModifier(),
            'm' => 1.0,
            't' => 1.0
        );
        foreach ($this->getWorkerIDsForGroupID($groupID) as $workerID) {
            if ($this->bonusIDs[$workerID] == 0) //TE bonus
                $mods['t'] = $mods['t'] * (100 + $this->bonusValues[$workerID]) / 100;
            elseif ($this->bonusIDs[$workerID] == 1) //ME bonus
                $mods['m'] = $mods['m'] * (100 + $this->bonusValues[$workerID]) / 100;
            else {
                static::throwException(
                    'UnexpectedDataException',
                    "Unknown bonus ID given for worked ID=" . $workerID . " in Team ID=" . $this->id
                );
            }
        }
        return $mods;
    }
}
