<?php
/**
 * AssemblyLine class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/AssemblyLine.php
 */

namespace iveeCore;

/**
 * AssemblyLines represent the industry "slot" of a station or POS assembly array or lab. Although slots are not used in
 * EVE anymore since Crius, the restrictions or bonuses they confer still apply.
 * Inheritance: AssemblyLine -> SdeType -> CoreDataCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/AssemblyLine.php
 */
class AssemblyLine extends SdeType
{
    /**
     * @var string CLASSNICK holds the class short name which is used to lookup the configured FQDN classname in Config
     * (for dynamic subclassing)
     */
    const CLASSNICK = 'AssemblyLine';

    /**
     * @var iveeCore\InstancePool $instancePool used to pool (cache) AssemblyLine objects
     */
    protected static $instancePool;

    /**
     * @var float $baseTimeMultiplier the base time multiplier
     */
    protected $baseTimeMultiplier;

    /**
     * @var float $baseMaterialMultiplier the base material quantity multiplier
     */
    protected $baseMaterialMultiplier;

    /**
     * @var float $baseCostMultiplier the base cost multiplier
     */
    protected $baseCostMultiplier;

    /**
     * @var int $activityID the ID of the activity that can be performed with this AssemblyLine
     */
    protected $activityID;

    /**
     * @var array $groupModifiers defines which groupIDs can be used with this AssemblyLine and also stores additional
     * multipliers.
     */
    protected $groupModifiers = array();

    /**
     * @var array $categoryModifiers defines which categoryIDs can be used with this AssemblyLine and also stores
     * additional multipliers.
     */
    protected $categoryModifiers = array();

    /**
     * Gets the assemblyLineTypeIDs for the best installable labs and assembly arrays for POSes depending on system
     * security. These IDs are hardcoded as currently the SDE lacks the necessary information to map from labs and
     * assembly arrays to assemblyLineTypeIDs.
     *
     * @param float $systemSecurity defining the system security status
     *
     * @return int[] in the form activityID => assemblyLineTypeIDs[]
     */
    public static function getBestPosAssemblyLineTypeIDs($systemSecurity = 1.0)
    {
        $assemblyLineTypeIDs = array(
            1 => array(
                17, //Small Ship Assembly Array
                18, //Adv. Small Ship Assembly Array
                19, //Medium Ship Assembly Array
                20, //Adv. Medium Ship Assembly Array
                155, //Large Ship Assembly Array
                22, //Adv. Large Ship Assembly Array
                24, //Equipment Assembly Array
                25, //Ammunition Assembly Array
                26, //Drone Assembly Array
                27, //Component Assembly Array
                161 //Subsystem Assembly Array
            ),
            3 => array(
                169 //Hyasyoda TE
            ),
            4 => array(
                168 //Hyasyoda ME
            ),
            5 => array(
                146 //Design Lab Copy
            ),
            7 => array(
                3 //Experimental Lab Reverse Engineering
            ),
            8 => array(
                149 //Design Lab Invention
            )
        );

        if ($systemSecurity < 0.45 AND $systemSecurity > 0.0) {
            $assemblyLineTypeIDs[1][] = 21; //Capital Ship Assembly Array
            $assemblyLineTypeIDs[1][] = 171; //Thukker Component Assembly Array
        }

        if ($systemSecurity <= 0.0) {
            $assemblyLineTypeIDs[1][] = 10; //Supercapital Ship Assembly Array
            $assemblyLineTypeIDs[1][] = 21; //Capital Ship Assembly Array
        }
        return $assemblyLineTypeIDs;
    }

    /**
     * Gets the assemblyLineTypeIDs for the generic hisec station.
     *
     * @return int[] in the form activityID => assemblyLineTypeIDs[]
     */
    public static function getHisecStationAssemlyLineTypeIDs()
    {
        return array(
            1 => array(35),
            3 => array(8),
            4 => array(7),
            5 => array(5),
            8 => array(38)
        );
    }

    /**
     * Method blocked as there is no safe way to get an AssemblyLine by name.
     *
     * @param string $name of requested AssemblyLine
     *
     * @return void
     * @throws iveeCore\Exceptions\IveeCoreException
     */
    public static function getIdByName($name)
    {
        static::throwException('IveeCoreException', 'GetByName methods not implemented for AssemblyLine');
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
     * Constructor
     *
     * @param int $id of the AssemblyLine
     *
     * @throws iveeCore\Exceptions\AssemblyLineTypeIdNotFoundException if the $assemblyLineTypeID is not found
     */
    protected function __construct($id)
    {
        $this->id = $id;
        $this->setExpiry();
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT assemblyLineTypeName, baseTimeMultiplier, baseMaterialMultiplier, baseCostMultiplier, activityID
            FROM ramAssemblyLineTypes
            WHERE assemblyLineTypeID = " . $this->id . ";"
        )->fetch_assoc();

        if (empty($row))
            static::throwException(
                'AssemblyLineTypeIdNotFoundException',
                "assemblyLine TypeID=". $this->id . " not found"
            );

        //set data to attributes
        $this->name                   = $row['assemblyLineTypeName'];
        $this->baseTimeMultiplier     = (float) $row['baseTimeMultiplier'];
        $this->baseMaterialMultiplier = (float) $row['baseMaterialMultiplier'];
        $this->baseCostMultiplier     = (float) $row['baseCostMultiplier'];
        $this->activityID             = (int) $row['activityID'];

        //get category bonuses
        $res = $sde->query(
            "SELECT categoryID, timeMultiplier, materialMultiplier, costMultiplier
            FROM ramAssemblyLineTypeDetailPerCategory
            WHERE assemblyLineTypeID = " . $this->id . ';'
        );

        while ($row = $res->fetch_assoc())
            $this->categoryModifiers[(int) $row['categoryID']] = array(
                't' => (float) $row['timeMultiplier'],
                'm' => (float) $row['materialMultiplier'],
                'c' => (float) $row['costMultiplier']
            );

        //get group bonuses
        $res = $sde->query(
            "SELECT groupID, timeMultiplier, materialMultiplier, costMultiplier
            FROM ramAssemblyLineTypeDetailPerGroup
            WHERE assemblyLineTypeID = " . $this->id . ';'
        );

        while ($row = $res->fetch_assoc())
            $this->groupModifiers[(int) $row['groupID']] = array(
                'c' => (float) $row['costMultiplier'],
                'm' => (float) $row['materialMultiplier'],
                't' => (float) $row['timeMultiplier']
            );
        
        //Since Phoebe the SDE does not contain group or category specific blueprint compatibility and bonus data.
        //Instead, the bonuses have been merge into the base bonuses of ramAssemblyLineTypes and all blueprints are
        //allowed for research, copying and invention activities. Here we add neutral compatibility data for those
        //blueprint activities, so the compatibility checking doesn't need special casing.
        if(in_array($this->activityID, array(3, 4, 5, 8))){
            $this->categoryModifiers[9] = array(
                't' => 1,
                'm' => 1,
                'c' => 1
            );
        }

    }

    /**
     * Gets the base time multiplier of the AssemblyLine.
     *
     * @return float
     */
    public function getBaseTimeMultiplier()
    {
        return $this->baseTimeMultiplier;
    }

    /**
     * Gets the base material multiplier of the AssemblyLine.
     *
     * @return float
     */
    public function getBaseMaterialMultiplier()
    {
        return $this->baseMaterialMultiplier;
    }

    /**
     * Gets the base cost multiplier of the AssemblyLine.
     *
     * @return float
     */
    public function getBaseCostMultiplier()
    {
        return $this->baseCostMultiplier;
    }

    /**
     * Gets the ID of the activity that can be performed with the AssemblyLine.
     *
     * @return int
     */
    public function getActivityID()
    {
        return $this->activityID;
    }

    /**
     * Returns the group modifier array.
     *
     * @return array
     */
    public function getGroupModifiers()
    {
        return $this->groupModifiers;
    }

    /**
     * Returns the category modifier array.
     *
     * @return array
     */
    public function getCategoryModifiers()
    {
        return $this->categoryModifiers;
    }

    /**
     * Returns the modifiers specific to a given Type. The passed Type should be the final product of the process. This
     * means that for manufacturing, its the Blueprint product; for copying its the Blueprint itself; for invention it
     * is the product of the invented blueprint.
     *
     * @param Type $type the item to get the modifiers for
     *
     * @return float[] in the form ('c' => float, 'm' => float, 't' => float)
     * @throws iveeCore\Exceptions\TypeNotCompatibleException if the given Type is not compatible
     */
    public function getModifiersForType(Type $type)
    {
        //check if type can actually be handled in this assembly line
        if (!$this->isTypeCompatible($type))
            static::throwException(
                'TypeNotCompatibleException',
                $type->getName() . " is not compatible with " . $this->getName()
            );

        //gets the base modifiers
        $mods = array(
            'c' => $this->getBaseCostMultiplier(),
            'm' => $this->getBaseMaterialMultiplier(),
            't' => $this->getBaseTimeMultiplier()
        );

        //apply group modifiers if available, taking precedence over category modifiers
        if (isset($this->groupModifiers[$type->getGroupID()]))
            foreach ($this->groupModifiers[$type->getGroupID()] as $modifierType => $modifier)
                //base and group modifiers are multiplied together
                $mods[$modifierType] = $mods[$modifierType] * $modifier;

        //apply category modifiers if available
        elseif (isset($this->categoryModifiers[$type->getCategoryID()]))
            foreach ($this->categoryModifiers[$type->getCategoryID()] as $modifierType => $modifier)
                //base and category modifiers are multiplied together
                $mods[$modifierType] = $mods[$modifierType] * $modifier;

        return $mods;
    }

    /**
     * Checks if a Type is compatible with the AssemblyLine. The passed Type should be the final product of the process.
     * This means that for manufacturing, its the Blueprint product; for copying its the Blueprint itself; for invention
     * it is the product of the invented blueprint.
     *
     * @param Type $type the item to be checked
     *
     * @return bool
     */
    public function isTypeCompatible(Type $type)
    {
        //the type is compatible if its groupID or categoryID is listet in the modifiers array
        return (
            isset($this->groupModifiers[$type->getGroupID()])
            OR isset($this->categoryModifiers[$type->getCategoryID()])
        );
    }
}
