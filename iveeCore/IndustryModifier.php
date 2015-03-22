<?php
/**
 * IndustryModifier Class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IndustryModifier.php
 *
 */

namespace iveeCore;

/**
 * IndustryModifier objects are used to aggregate objects and factors that modify the cost, time and material
 * requirements of performing industrial activities (manufacturing, TE research, ME research, copying, reverse
 * engineering and invention). Namely, these are solar system industry indices, assembly lines (of stations or POSes),
 * station taxes, skills affecting time and implants affecting time.
 *
 * A number of convenience functions are provided that help in instantiating IndustryModifier objects, automatically
 * passing the required arguments based on a specific NPC station, a POS in a system, all NPC stations in a system or
 * a system plus manual assembly line type definition (necessary for player built outposts and wormholes).
 *
 * IndustryModifier objects are passed as argument to the Blueprint methods calculating the industrial activity. They
 * can be reused.
 *
 * For a given industry activityID and Type object, IndustryModifier objects can calculate the cost, material and time
 * factors, considering all of the modifiers.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IndustryModifier.php
 *
 */
class IndustryModifier
{
    /**
     * @var array $assemblyLines holds AssemblyLine objects
     */
    protected $assemblyLines;

    /**
     * @var SolarSystem $solarSystem
     */
    protected $solarSystem;

    /**
     * @var float $tax the relevant industry tax, in the form "0.1" as 10%
     */
    protected $tax;

    /**
     * @var array $skillTimeModifiers the skill-dependent time modifiers in the form $activityID => 0.75 (meaning 25%
     * bonus). These are looked up in Defaults for each skillID.
     */
    protected $skillTimeModifiers;

    /**
     * @var array $implantTimeModifiers the implant-dependent time modifiers in the form $activityID => 0.98 (meaning 2%
     * bonus). These are looked up in Defaults but can be overriden.
     */
    protected $implantTimeModifiers;

    /**
     * Returns a IndustryModifier object for a specific NPC station. This method can't be used for player built
     * outsposts as they aren't in the SDE. You need to use getBySystemIdWithAssembly(...) in that case.
     *
     * @param int $stationID of Station to use to get all the data
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\StationIdNotFoundException if the stationID is not found
     */
    public static function getByNpcStationID($stationID)
    {
        $stationClass = Config::getIveeClassName('Station');
        //instantiate station from ID
        $station = $stationClass::getById($stationID);

        return static::getBySystemIdWithAssembly(
            $station->getSolarSystemID(),
            $station->getAssemblyLineTypeIDs(),
            $station->getTax()
        );
    }

    /**
     * Returns a IndustryModifier object for a POS in a specific system. The AssemblyLines for the best available POS
     * assembly arrays (i.e. AssemblyLines) will be set, respecting system security limits, for instance, no capital
     * manufacturing in hisec.
     *
     * @param int $solarSystemID of the SolarSystem to get data for
     * @param float $tax if the POS has a tax set, in the form "0.1" as 10%
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemID is not found
     */
    public static function getBySystemIdForPos($solarSystemID, $tax = 0.0)
    {
        $systemClass       = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass = Config::getIveeClassName('AssemblyLine');
        //instantiate system from ID
        $system = $systemClass::getById($solarSystemID);

        return static::getBySystemIdWithAssembly(
            $solarSystemID,
            $assemblyLineClass::getBestPosAssemblyLineTypeIDs($system->getSecurity()),
            $tax
        );
    }

    /**
     * Similar to getByNpcStationID(...), but returns a IndustryModifier object with the AssembyLines of all NPC
     * stations in the system.
     *
     * @param int $solarSystemID of the SolarSystem to get data for
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemID is not found
     */
    public static function getBySystemIdForAllNpcStations($solarSystemID)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //get the assemblyLineTypeIDs in system
        $res = $sde->query(
            "SELECT DISTINCT rals.assemblyLineTypeID, activityID
            FROM ramAssemblyLineStations as rals
            JOIN ramAssemblyLineTypes as ralt ON ralt.assemblyLineTypeID = rals.assemblyLineTypeID
            WHERE solarSystemID = " . (int) $solarSystemID . ";"
        );

        if ($res->num_rows < 1) {
            $exceptionClass = Config::getIveeClassName('AssemblyLineTypeIdNotFoundException');
            throw new $exceptionClass("No assembly lines found for solarSystemID=" . (int) $solarSystemID);
        }

        $assemblyLineTypeIDs = array();
        while ($row = $res->fetch_assoc())
            $assemblyLineTypeIDs[$row['activityID']][] = (int) $row['assemblyLineTypeID'];

        return static::getBySystemIdWithAssembly(
            $solarSystemID,
            $assemblyLineTypeIDs,
            0.1
        );
    }

    /**
     * Returns an IndustryModifier object for a specific system, but allowing for manual setting of AssemblyLine IDs.
     * This is required for player built outposts or wormholes. The latter will additionally require manually setting
     * the system industry indices, as no data for them is provided by CREST.
     *
     * @param int $solarSystemID of the SolarSystem to get data for
     * @param array $assemblyLineTypeIDs IDs of the type of AssemblyLine to set
     * @param float $tax if the POS has a tax set, in the form "0.1" as 10%
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemID is not found
     */
    public static function getBySystemIdWithAssembly($solarSystemID, array $assemblyLineTypeIDs, $tax = 0.1)
    {
        $systemClass       = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass = Config::getIveeClassName('AssemblyLine');

        //instantiate system from ID
        $system = $systemClass::getById($solarSystemID);

        //instantiate AssemblyLines from IDs
        $assemblyLines = array();
        foreach ($assemblyLineTypeIDs as $activity => $activityAssemblyLineTypeIDs)
            foreach ($activityAssemblyLineTypeIDs as $assemblyLineTypeID)
                $assemblyLines[$activity][$assemblyLineTypeID]
                    = $assemblyLineClass::getById($assemblyLineTypeID);

        return new static(
            $system,
            $assemblyLines,
            $tax
        );
    }

    /**
     * Constructor. Note available convenience functions for helping with instantiation.
     *
     * @param \iveeCore\SolarSystem $system which this IndustryModifier is being instantiated for
     * @param array $assemblyLines of \iveeCore\AssemblyLines
     * @param float $tax in the form "0.1" for 10% tax
     *
     * @return \iveeCore\IndustryModifier
     */
    public function __construct(SolarSystem $system, array $assemblyLines, $tax)
    {
        $this->solarSystem = $system;
        $this->assemblyLines = $assemblyLines;
        $this->tax = $tax;

        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        //get implant time modifiers from Defaults
        $this->implantTimeModifiers = $defaults->getIndustryImplantTimeModifiers();

        //get skill level dependent time modifiers
        $this->skillTimeModifiers = array(
            //Industry and Advanced Industry skills
            1 => (1.0 - 0.04 * $defaults->getSkillLevel(3380)) * (1.0 - 0.03 * $defaults->getSkillLevel(3388)),
            //Research and Advanced Industry skill
            3 => (1.0 - 0.05 * $defaults->getSkillLevel(3403)) * (1.0 - 0.03 * $defaults->getSkillLevel(3388)),
            //Metallurgy and Advanced Industry skill
            4 => (1.0 - 0.05 * $defaults->getSkillLevel(3409)) * (1.0 - 0.03 * $defaults->getSkillLevel(3388)),
            //Science and Advanced Industry skill
            5 => (1.0 - 0.05 * $defaults->getSkillLevel(3402)) * (1.0 - 0.03 * $defaults->getSkillLevel(3388)),
            //Advanced Industry skill
            7 => 1.0 - 0.03 * $defaults->getSkillLevel(3388)
        );
    }

    /**
     * Returns all available AssemblyLines
     *
     * @return array in the form activityID => assemblyLineTypeID => AssemblyLine
     */
    public function getAssemblyLines()
    {
        return $this->assemblyLines;
    }

    /**
     * Returns all available AssemblyLines for a given activityID
     *
     * @param int $activityID the activity to get AssemblyLines for
     *
     * @return array in the form assemblyLineTypeID => AssemblyLine
     */
    public function getAssemblyLinesForActivity($activityID)
    {
        if (isset($this->assemblyLines[$activityID]))
            return $this->assemblyLines[$activityID];
        else
            return array();
    }

    /**
     * Returns the SolarSystem
     *
     * @return \iveeCore\SolarSystem
     */
    public function getSolarSystem()
    {
        return $this->solarSystem;
    }

    /**
     * Returns the tax in the form "0.1" for 10%
     *
     * @return float
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Returns the tax in the form "1.1" for 10%
     *
     * @return float
     */
    public function getTaxFactor()
    {
        return 1.0 + $this->tax;
    }

    /**
     * Returns the implant dependent industry activity time modifiers
     *
     * @return array in the form activityID => float, "0.95" for 5% bonus
     */
    public function getImplantTimeModifiers()
    {
        return $this->implantTimeModifiers;
    }

    /**
     * Returns the implant dependent industry activity time modifiers
     *
     * @param int $activityID optional
     *
     * @return float the specific factor in the form "0.95" for 5% bonus
     */
    public function getImplantTimeModifierForActivity($activityID)
    {
        if (isset($this->implantTimeModifiers[$activityID]))
            return $this->implantTimeModifiers[$activityID];
        else
            return 1.0;
    }

    /**
     * Allows setting the implant time modifiers, overriding the defaults looked up during instantiation
     *
     * @param float $modifier the time factor, in the form 0.95 for 5% bonus
     * @param int $activityID the ID of the activity this time bonus is for
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if $modifier is not sane
     */
    public function setImplantTimeModifierForActivity($modifier, $activityID)
    {
        if ($modifier <= 1.0 AND $modifier >= 0.9)
            $this->implantTimeModifiers[(int) $activityID] = (float) $modifier;
        else {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Invalid modifier given");
        }
    }

    /**
     * Allows setting the implant time modifiers, overriding the defaults looked up during instantiation
     *
     * @param array $modifiers in the form activityID => float
     *
     * @return void
     */
    public function setImplantTimeModifiers(array $modifiers)
    {
        $this->implantTimeModifiers = $modifiers;
    }

    /**
     * Returns the skill dependent industry activity time modifiers
     *
     * @return array in the form activityID => float
     */
    public function getSkillTimeModifiers()
    {
        return $this->skillTimeModifiers;
    }

    /**
     * Returns the skill dependent industry activity time modifier
     *
     * @param int $activityID the ID of the activity
     *
     * @return float
     */
    public function getSkillTimeModifierForActivity($activityID)
    {
        if (isset($this->skillTimeModifiers[$activityID]))
            return $this->skillTimeModifiers[$activityID];
        else
            return 1.0;
    }

    /**
     * Allows setting the skill dependent time modifiers, overriding the defaults looked up during instantiation
     *
     * @param array $modifiers in the form activityID => 0.98 (for 2% bonus)
     *
     * @return void
     */
    public function setSkillTimeModifiers(array $modifiers)
    {
        $this->skillTimeModifiers = $modifiers;
    }

    /**
     * Allows setting the skill dependent time modifiers, overriding the defaults looked up during instantiation
     *
     * @param float $modifier in the form 0.98 for 2% bonus
     * @param int $activityID the ID of the activity
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if $modifier is not sane
     */
    public function setSkillTimeModifierForActivity($modifier, $activityID)
    {
        if ($modifier <= 1.0 AND $modifier >= 0.75)
            $this->skillTimeModifiers[(int) $activityID] = (float) $modifier;
        else {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass("Given modifier is not sane");
        }
    }

    /**
     * Test if a certain activity can be performed with a certain Type with the current IndustryModifier object.
     * It's always the final output item that needs to be checked. This means that for manufacturing, its the Blueprint
     * product; for copying its the Blueprint itself; for invention it is the product of the invented blueprint.
     *
     * @param int $activityID the activity to check
     * @param Type $type the item to check
     *
     * @return bool
     */
    public function isActivityPossible($activityID, Type $type)
    {
        if (!isset($this->assemblyLines[$activityID]))
            return false;

        foreach ($this->assemblyLines[$activityID] as $assemblyLine)
            if ($assemblyLine->isTypeCompatible($type))
                return true;

        return false;
    }

    /**
     * Gets the total combined modifiers for cost, materials and time for a given activity and Type considering all the
     * variables.
     *
     * @param int $activityID ID of the activity to get modifiers for
     * @param Type $type It's the final output item that needs to be given for checking. This means that for
     * manufacturing, its the Blueprint product; for copying its the Blueprint itself; for invention it is the product
     * of the invented blueprint. Only for reverse engineering the input Relic must be checked.
     *
     * @return array
     */
    public function getModifier($activityID, Type $type)
    {
        $activityID = (int) $activityID;
        if (!$this->isActivityPossible($activityID, $type)) {
            $exceptionClass = Config::getIveeClassName('TypeNotCompatibleException');
            throw new $exceptionClass("No compatible assemblyLine for activityID=" . $activityID . " with "
                . $type->getName() . " found in the given IndustryModifier object");
        }

        //get the compatible assembly line with the best bonuses. Where ME > TE > cost bonus.
        $bestAssemblyLine = $this->getBestAssemblyLineForActivity($activityID, $type);

        $modifiers = $bestAssemblyLine->getModifiersForType($type);
        $modifiers['assemblyLineTypeID'] = $bestAssemblyLine->getId();
        $modifiers['solarSystemID'] = $this->getSolarSystem()->getId();
        //get initial cost factor as system industry index and tax
        $modifiers['c'] = $modifiers['c']
            * $this->getSolarSystem()->getIndustryIndexForActivity($activityID) * $this->getTaxFactor();

        //factor in skill time modifiers if available
        if (isset($this->skillTimeModifiers[$activityID]))
            $modifiers['t'] = $modifiers['t'] * $this->skillTimeModifiers[$activityID];

        //factor in implant time modifiers if available
        if (isset($this->implantTimeModifiers[$activityID]))
            $modifiers['t'] = $modifiers['t'] * $this->implantTimeModifiers[$activityID];

        return $modifiers;
    }

    /**
     * Gets the best compatible assemblyLine for the activity and Type.
     * Bonuses are ranked as material bonus > time bonus > cost bonus
     *
     * @param int $activityID the ID of the activity to get AssemblyLines for
     * @param Type $type It's always the final output item that needs to be given. This means that for manufacturing,
     * its the Blueprint product; for copying its the Blueprint itself; for invention it is the product of the
     * invented blueprint.
     *
     * @return AssemblyLine|null
     */
    public function getBestAssemblyLineForActivity($activityID, Type $type)
    {
        $bestAssemblyLine = null;
        $bestModifier = null;
        foreach ($this->getAssemblyLinesForActivity($activityID) as $candidateAssemblyLine) {
            //skip incompatible assemblyLines
            if (!$candidateAssemblyLine->isTypeCompatible($type))
                continue;

            //compare candidate assemblyLine with current best
            elseif (is_null($bestAssemblyLine)) {
                $bestAssemblyLine = $candidateAssemblyLine;
                $bestModifier = $bestAssemblyLine->getModifiersForType($type);
            } else {
                $candidateModifier = $candidateAssemblyLine->getModifiersForType($type);

                //Modifiers are ranked with priority order for material, time then cost modifiers (lower is better!)
                if ($bestModifier['m'] < $candidateModifier['m'])
                    continue;
                elseif ($bestModifier['m'] > $candidateModifier['m']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                } elseif ($bestModifier['t'] < $candidateModifier['t'])
                    continue;
                elseif ($bestModifier['t'] > $candidateModifier['t']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                } elseif ($bestModifier['c'] < $candidateModifier['c'])
                    continue;
                elseif ($bestModifier['c'] > $candidateModifier['c']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                }
            }
        }
        return $bestAssemblyLine;
    }
}
