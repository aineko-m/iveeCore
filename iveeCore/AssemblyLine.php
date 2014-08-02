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
 *
 */

namespace iveeCore;

/**
 * AssemblyLines represent the industry "slot" of a station or POS assembly array or lab. Although slots are not used in
 * EVE anymore since Crius, the restrictions or bonuses they confer still apply.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/AssemblyLine.php
 *
 */
class AssemblyLine
{
    /**
     * @var array $_assemblyLines acts as internal object cache, assemblyLineTypeID => AssemblyLine.
     */
    private static $_assemblyLines;

    /**
     * @var int $_internalCacheHit stores the number of hits on the internal AssemblyLine cache.
     */
    private static $_internalCacheHit = 0;

    /**
     * @var int $assemblyLineTypeID the ID of the type of AssemblyLine
     */
    protected $assemblyLineTypeID;

    /**
     * @var string $assemblyLineTypeName the name of the type of AssemblyLine
     */
    protected $assemblyLineTypeName;

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
    protected $groupModifiers;

    /**
     * @var array $categoryModifiers defines which categoryIDs can be used with this AssemblyLine and also stores 
     * additional multipliers.
     */
    protected $categoryModifiers;

    /**
     * Gets AssemblyLine objects. Tries caches and instantiates new objects if necessary.
     *
     * @param int $assemblyLineTypeID of requested AssemblyLine
     *
     * @return \iveeCore\AssemblyLine
     * @throws \iveeCore\Exceptions\AssemblyLineTypeIdNotFoundException if the $assemblyLineTypeID is not found
     */
    public static function getAssemblyLine($assemblyLineTypeID)
    {
        $assemblyLineTypeID = (int) $assemblyLineTypeID;
        //try php array first
        if (isset(static::$_assemblyLines[$assemblyLineTypeID])) {
            //count internal cache hit
            static::$_internalCacheHit++;
            return static::$_assemblyLines[$assemblyLineTypeID];
        } else {
            $assemblyLineClass = Config::getIveeClassName('AssemblyLine');
            //try cache
            if (Config::getUseCache()) {
                //lookup Cache class
                $cacheClass = Config::getIveeClassName('Cache');
                $cache = $cacheClass::instance();
                try {
                    $assemblyLine = $cache->getItem('assemblyLine_' . $assemblyLineTypeID);
                } catch (Exceptions\KeyNotFoundInCacheException $e) {
                    //go to DB
                    $assemblyLine = new $assemblyLineClass($assemblyLineTypeID);
                    //store object in cache
                    $cache->setItem($assemblyLine, 'assemblyLine_' . $assemblyLineTypeID);
                }
            } else
                //not using cache, go to DB
                $assemblyLine = new $assemblyLineClass($assemblyLineTypeID);

            //store object in internal cache
            static::$_assemblyLines[$assemblyLineTypeID] = $assemblyLine;
            return $assemblyLine;
        }
    }

    /**
     * Gets the assemblyLineTypeIDs for the best installable labs and assembly arrays for POSes depending on system
     * security. These IDs are hardcoded as currently the SDE lacks the necessary information to map from labs and
     * assembly arrays to assemblyLineTypeIDs.
     * 
     * @param float $systemSecurity defining the system security status
     * 
     * @return array in the form activityID => assemblyLineTypeIDs[]
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
     * @return array in the form activityID => assemblyLineTypeIDs[]
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
     * Constructor
     *
     * @param int $assemblyLineTypeID of the AssemblyLine
     *
     * @return \iveeCore\AssemblyLine
     * @throws \iveeCore\Exceptions\AssemblyLineTypeIdNotFoundException if the $assemblyLineTypeID is not found
     */
    protected function __construct($assemblyLineTypeID)
    {
        $this->assemblyLineTypeID = $assemblyLineTypeID;
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        $row = $sde->query(
            "SELECT assemblyLineTypeName, baseTimeMultiplier, baseMaterialMultiplier, baseCostMultiplier, activityID
            FROM ramAssemblyLineTypes
            WHERE assemblyLineTypeID = " . $this->assemblyLineTypeID . ";"
        )->fetch_assoc();

        if (empty($row)) {
            $exceptionClass = Config::getIveeClassName('AssemblyLineTypeIdNotFoundException');
            throw new $exceptionClass("assemblyLineTypeID ". $this->assemblyLineTypeID . " not found");
        }

        //set data to attributes
        $this->assemblyLineTypeName   = $row['assemblyLineTypeName'];
        $this->baseTimeMultiplier     = (float) $row['baseTimeMultiplier'];
        $this->baseMaterialMultiplier = (float) $row['baseMaterialMultiplier'];
        $this->baseCostMultiplier     = (float) $row['baseCostMultiplier'];
        $this->activityID             = (int) $row['activityID'];

        //get category bonuses
        $res = $sde->query(
            "SELECT categoryID, timeMultiplier, materialMultiplier, costMultiplier
            FROM ramAssemblyLineTypeDetailPerCategory
            WHERE assemblyLineTypeID = " . $this->assemblyLineTypeID . ';'
        );

        $this->categoryModifiers = array();
        while ($row = $res->fetch_assoc())
            $this->categoryModifiers[$row['categoryID']] = array(
                't' => (float) $row['timeMultiplier'],
                'm' => (float) $row['materialMultiplier'],
                'c' => (float) $row['costMultiplier']
            );

        //get group bonuses
        $res = $sde->query(
            "SELECT groupID, timeMultiplier, materialMultiplier, costMultiplier
            FROM ramAssemblyLineTypeDetailPerGroup
            WHERE assemblyLineTypeID = " . $this->assemblyLineTypeID . ';'
        );

        $this->groupModifiers = array();
        while ($row = $res->fetch_assoc())
            $this->groupModifiers[$row['groupID']] = array(
                'c' => (float) $row['costMultiplier'],
                'm' => (float) $row['materialMultiplier'],
                't' => (float) $row['timeMultiplier']
            );
    }

    /**
     * Gets the ID of the AssemblyLine
     * 
     * @return int
     */
    public function getAssemblyLineTypeID()
    {
        return $this->assemblyLineTypeID;
    }

    /**
     * Gets the name of the AssemblyLine
     * 
     * @return string
     */
    public function getAssemblyLineTypeName()
    {
        return $this->assemblyLineTypeName;
    }

    /**
     * Gets the base time multiplier of the AssemblyLine
     * 
     * @return float
     */
    public function getBaseTimeMultiplier()
    {
        return $this->baseTimeMultiplier;
    }

    /**
     * Gets the base material multiplier of the AssemblyLine
     * 
     * @return float
     */
    public function getBaseMaterialMultiplier()
    {
        return $this->baseMaterialMultiplier;
    }

    /**
     * Gets the base cost multiplier of the AssemblyLine
     * 
     * @return float
     */
    public function getBaseCostMultiplier()
    {
        return $this->baseCostMultiplier;
    }

    /**
     * Gets the ID of the activity that can be performed with the AssemblyLine
     * 
     * @return int
     */
    public function getActivityID()
    {
        return $this->activityID;
    }

    /**
     * Returns the group modifier array
     * 
     * @return array
     */
    public function getGroupModifiers()
    {
        return $this->groupModifiers;
    }

    /**
     * Returns the category modifier array
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
     * @return array in the form ('c' => float, 'm' => float, 't' => float)
     * @throws \iveeCore\Exceptions\TypeNotCompatibleException if the given Type is not compatible
     */
    public function getModifiersForType(Type $type)
    {
        //check if type can actually be handled in this assembly line
        if (!$this->isTypeCompatible($type)) {
             $exceptionClass = Config::getIveeClassName('TypeNotCompatibleException');
             throw new $exceptionClass($type->getName() . " is not compatible with "
                 . $this->getAssemblyLineTypeName());
        }

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
