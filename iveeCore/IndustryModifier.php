<?php
/**
 * IndustryModifier Class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IndustryModifier.php
 */

namespace iveeCore;

/**
 * IndustryModifier objects are used to aggregate objects and factors that modify the cost, time and material
 * requirements of performing industrial activities (manufacturing, TE research, ME research, copying, reverse
 * engineering and invention), or market activities. Namely, these are solar system industry indices, assembly lines
 * (of stations or POSes), station industry taxes. The contained CharacterModifier allows for lookup of skills, time and
 * implant factors, standings, taxes and efficiencies based on skills and standings. BlueprintModifier is used for
 * blueprint research level lookup.
 *
 * A number of convenience functions are provided that help in instantiating IndustryModifier objects, automatically
 * passing the required arguments based on a specific NPC station, a POS in a system, all NPC stations in a system or
 * a system plus manual assembly line type definition (necessary for wormholes or hypothetical scenarios).
 *
 * IndustryModifier objects are passed as argument to the Blueprint methods calculating the industrial activity. They
 * can be reused.
 *
 * For a given industry activityId and Type object, IndustryModifier objects can calculate the cost, material and time
 * factors, considering all of the modifiers.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IndustryModifier.php
 */
class IndustryModifier
{
    /**
     * @var array $assemblyLines holds the available AssemblyLine objects by activityId in arrays
     */
    protected $assemblyLines;

    /**
     * @var \iveeCore\SolarSystem $solarSystem
     */
    protected $solarSystem;

    /**
     * @var float $tax the relevant industry tax, in the form "0.1" as 10%
     */
    protected $tax;

    /**
     * @var \iveeCore\ICharacterModifier $characterModifier holding the character specific data like skills and implants
     */
    protected $characterModifier;

    /**
     * @var \iveeCore\IBlueprintModifier $blueprintModifier holding the blueprint specific research levels
     */
    protected $blueprintModifier;

    /**
     * @var \iveeCore\Station[] $marketStations all stations in system with market service, lazy loaded
     */
    protected $marketStations;

    /**
     * @var int $preferredMarketStationId defines if and which station should be preferred for market operations
     */
    protected $preferredMarketStationId;

    /**
     * @var int $maxPriceDataAge defines the maximum acceptable price data age in seconds. null for unlimited.
     */
    protected $maxPriceDataAge;

    /**
     * Returns a IndustryModifier object for a specific station or outpost.
     * Note that for player built outposts no programatically accessible data tells about upgrades or taxes, thus they
     * have to be set manually. For this the optional arguments assemblyLineTypeIds and tax exist.
     *
     * @param int $stationId of Station to use to get all the data
     * @param array $assemblyLineTypeIds per activityId, overrides Ids defined in SDE, useful for upgraded outposts
     * @param float $tax the industry tax, used only for instantiating player built outpost, ignored otherwise
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\StationIdNotFoundException if the stationId is not found
     */
    public static function getByStationId($stationId, array $assemblyLineTypeIds = null, $tax = 0.0)
    {
        $stationClass = Config::getIveeClassName('Station');
        //instantiate station from ID
        $station = $stationClass::getById($stationId);

        return static::getBySystemIdWithAssembly(
            $station->getSolarSystemId(),
            is_null($assemblyLineTypeIds) ? $station->getAssemblyLineTypeIds() : $assemblyLineTypeIds,
            $stationId > 61000000 ? $tax : $station->getTax()
        );
    }

    /**
     * Returns a IndustryModifier object for a POS in a specific system. The AssemblyLines for the best available POS
     * assembly arrays (i.e. AssemblyLines) will be set, respecting system security limits, for instance, no capital
     * manufacturing in hisec.
     *
     * @param int $solarSystemId of the SolarSystem to get data for
     * @param float $tax if the POS has a tax set, in the form "0.1" as 10%
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemId is not found
     */
    public static function getBySystemIdForPos($solarSystemId, $tax = 0.0)
    {
        $systemClass       = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass = Config::getIveeClassName('AssemblyLine');
        //instantiate system from ID
        $system = $systemClass::getById($solarSystemId);

        return static::getBySystemIdWithAssembly(
            $solarSystemId,
            $assemblyLineClass::getBestPosAssemblyLineTypeIds($system->getSecurity()),
            $tax
        );
    }

    /**
     * Similar to getByStationId(...), but returns a IndustryModifier object with the AssembyLines of all NPC
     * stations in the system.
     *
     * @param int $solarSystemId of the SolarSystem to get data for
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemId is not found
     */
    public static function getBySystemIdForAllNpcStations($solarSystemId)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //get the assemblyLineTypeIds in system
        $res = $sde->query(
            "SELECT DISTINCT rals.assemblyLineTypeID, activityID
            FROM ramAssemblyLineStations as rals
            JOIN ramAssemblyLineTypes as ralt ON ralt.assemblyLineTypeID = rals.assemblyLineTypeID
            WHERE solarSystemID = " . (int) $solarSystemId . ";"
        );

        if ($res->num_rows < 1) {
            $exceptionClass = Config::getIveeClassName('AssemblyLineTypeIdNotFoundException');
            throw new $exceptionClass("No assembly lines found for solarSystemId=" . (int) $solarSystemId);
        }

        $assemblyLineTypeIds = [];
        while ($row = $res->fetch_assoc()) {
            $assemblyLineTypeIds[$row['activityID']][] = (int) $row['assemblyLineTypeID'];
        }

        return static::getBySystemIdWithAssembly(
            $solarSystemId,
            $assemblyLineTypeIds,
            0.1
        );
    }

    /**
     * Returns an IndustryModifier object with AssemblyLines of a certain installationType (e.g. a player owned stationType)
     *
     * @param int $solarSystemId of the SolarSystem to get data for
     * @param int $installationTypeId of the installation (e.g. a stationTypeId)
     * @param float $tax to use
     *
     * @return \iveeCore\IndustryModifier
     */
    public static function getBySystemIdForInstallationType($solarSystemId, $installationTypeId, $tax = 0.0)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        // get the assemblyLineTypeIds for the stationTypeId
        $res = $sde->query(
            "SELECT DISTINCT ralt.assemblyLineTypeID, activityID
            FROM ramInstallationTypeContents ritc
            JOIN ramAssemblyLineTypes as ralt ON ralt.assemblyLineTypeID = ritc.assemblyLineTypeID
            WHERE installationTypeID = " . (int) $installationTypeId . ";"
        );

        if ($res->num_rows < 1) {
            $exceptionClass = Config::getIveeClassName('AssemblyLineTypeIdNotFoundException');
            throw new $exceptionClass("No assembly lines found for installationTypeId=" . (int) $installationTypeId);
        }

        $assemblyLineTypeIds = [];
        while ($row = $res->fetch_assoc()) {
            $assemblyLineTypeIds[$row['activityID']][] = (int) $row['assemblyLineTypeID'];
        }

        return static::getBySystemIdWithAssembly(
            $solarSystemId,
            $assemblyLineTypeIds,
            $tax
        );
    }

    /**
     * Returns an IndustryModifier object for a specific system, but allowing for manual setting of AssemblyLine IDs.
     * This is required for player built outposts or wormholes. The latter will additionally require manually setting
     * the system industry indices, as no data for them is provided by CREST.
     *
     * @param int $solarSystemId of the SolarSystem to get data for
     * @param array $assemblyLineTypeIds IDs of the type of AssemblyLine to set by activityId
     * @param float $tax if the POS has a tax set, in the form "0.1" as 10%
     *
     * @return \iveeCore\IndustryModifier
     * @throws \iveeCore\Exceptions\SystemIdNotFoundException if the systemId is not found
     */
    public static function getBySystemIdWithAssembly($solarSystemId, array $assemblyLineTypeIds, $tax = 0.1)
    {
        $systemClass       = Config::getIveeClassName('SolarSystem');
        $assemblyLineClass = Config::getIveeClassName('AssemblyLine');

        //instantiate system from ID
        $system = $systemClass::getById($solarSystemId);

        //instantiate AssemblyLines from IDs
        $assemblyLines = [];
        foreach ($assemblyLineTypeIds as $activity => $activityAssemblyLineTypeIds) {
            foreach ($activityAssemblyLineTypeIds as $assemblyLineTypeId) {
                $assemblyLines[$activity][$assemblyLineTypeId] = $assemblyLineClass::getById($assemblyLineTypeId);
            }
        }

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
     * @param array $assemblyLines the available AssemblyLines by activityId
     * @param float $tax in the form "0.1" for 10% tax
     */
    public function __construct(SolarSystem $system, array $assemblyLines, $tax)
    {
        $this->solarSystem = $system;
        $this->assemblyLines = $assemblyLines;
        $this->tax = $tax;

        $charModClass = Config::getIveeClassName('CharacterModifier');
        $this->characterModifier = new $charModClass;
        
        $bpModClass = Config::getIveeClassName('BlueprintModifier');
        $this->blueprintModifier = new $bpModClass;

        $this->maxPriceDataAge = Config::getMaxPriceDataAge();
    }

    /**
     * Gets the set ICharacterModifier object.
     *
     * @return \iveeCore\ICharacterModifier
     */
    public function getCharacterModifier()
    {
        return $this->characterModifier;
    }

    /**
     * Sets a new ICharacterModifier.
     *
     * @param \iveeCore\ICharacterModifier $charMod to be set
     *
     * @return void
     */
    public function setCharacterModifier(ICharacterModifier $charMod)
    {
        $this->characterModifier = $charMod;
    }

    /**
     * Gets the set IBlueprintModifier object.
     *
     * @return \iveeCore\IBlueprintModifier
     */
    public function getBlueprintModifier()
    {
        return $this->blueprintModifier;
    }

    /**
     * Sets a new IBlueprintModifier.
     *
     * @param \iveeCore\IBlueprintModifier $bpMod to be set
     *
     * @return void
     */
    public function setBlueprintModifier(IBlueprintModifier $bpMod)
    {
        $this->blueprintModifier = $bpMod;
    }

    /**
     * Sets a preferred station for market operations.
     *
     * @param int $stationId to be used
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when an invalid stationId is given
     */
    public function setPreferredMarketStation($stationId)
    {
        if (!in_array($stationId, $this->getSolarSystem()->getStationIds())) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass((int) $stationId . ' is not a Station in this SolarSystem');
        }
        $this->preferredMarketStationId = (int) $stationId;
    }

    /**
     * Gets the maximum acceptable price data age in seconds.
     *
     * @return int
     */
    public function getMaxPriceDataAge()
    {
        return $this->maxPriceDataAge;
    }

    /**
     * Sets the maximum acceptable price data age in seconds.
     *
     * @param int $maxPriceDataAge the time in seconds. Set to null for unlimited age (no updates or exceptions).
     *
     * @return void
     */
    public function setMaxPriceDataAge($maxPriceDataAge)
    {
        $this->maxPriceDataAge = $maxPriceDataAge;
    }

    /**
     * Returns all available AssemblyLines.
     *
     * @return array in the form activityId => assemblyLineTypeId => AssemblyLine
     */
    public function getAssemblyLines()
    {
        return $this->assemblyLines;
    }

    /**
     * Returns all available AssemblyLines for a given activityId.
     *
     * @param int $activityId the activity to get AssemblyLines for
     *
     * @return \iveeCore\AssemblyLine[] in the form assemblyLineTypeId => AssemblyLine
     */
    public function getAssemblyLinesForActivity($activityId)
    {
        if (isset($this->assemblyLines[$activityId])) {
            return $this->assemblyLines[$activityId];
        } else {
            return [];
        }
    }

    /**
     * Returns the SolarSystem.
     *
     * @return \iveeCore\SolarSystem
     */
    public function getSolarSystem()
    {
        return $this->solarSystem;
    }

    /**
     * Returns the tax in the form "0.1" for 10%.
     *
     * @return float
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Returns the tax in the form "1.1" for 10%.
     *
     * @return float
     */
    public function getTaxFactor()
    {
        return 1.0 + $this->tax;
    }

    /**
     * Test if a certain activity can be performed with a certain Type with the current IndustryModifier object.
     * It's always the final output item that needs to be checked. This means that for manufacturing, its the Blueprint
     * product; for copying its the Blueprint itself; for invention it is the product of the invented blueprint.
     *
     * @param int $activityId the activity to check
     * @param Type $type the item to check
     *
     * @return bool
     */
    public function isActivityPossible($activityId, Type $type)
    {
        if (!isset($this->assemblyLines[$activityId])) {
            return false;
        }

        foreach ($this->assemblyLines[$activityId] as $assemblyLine) {
            if ($assemblyLine->isTypeCompatible($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the total combined modifiers for cost, materials and time for a given activity and Type considering all the
     * variables.
     *
     * @param int $activityId ID of the activity to get modifiers for
     * @param \iveeCore\Type $type It's the final output item that needs to be given for checking. This means that for
     * manufacturing, its the Blueprint product; for copying its the Blueprint itself; for invention it is the product
     * of the invented blueprint. Only for reverse engineering the input Relic must be checked.
     *
     * @return float[]
     */
    public function getModifier($activityId, Type $type)
    {
        $activityId = (int) $activityId;
        if (!$this->isActivityPossible($activityId, $type)) {
            $exceptionClass = Config::getIveeClassName('TypeNotCompatibleException');
            throw new $exceptionClass("No compatible assemblyLine for activityId=" . $activityId . " with "
                . $type->getName() . " found in the given IndustryModifier object");
        }

        //get the compatible assembly line with the best bonuses. Where ME > TE > cost bonus.
        $bestAssemblyLine = $this->getBestAssemblyLineForActivity($activityId, $type);

        $modifiers = $bestAssemblyLine->getModifiersForType($type);
        $modifiers['assemblyLineTypeId'] = $bestAssemblyLine->getId();
        $modifiers['solarSystemId'] = $this->getSolarSystem()->getId();
        //get initial cost factor as system industry index and tax
        $modifiers['c'] = $modifiers['c']
            * $this->getSolarSystem()->getIndustryIndexForActivity($activityId) * $this->getTaxFactor();

        //apply skill and implant time factors
        $modifiers['t'] = $modifiers['t'] * $this->characterModifier->getIndustrySkillTimeFactor($activityId)
            * $this->characterModifier->getIndustryImplantTimeFactor($activityId);

        return $modifiers;
    }

    /**
     * Gets the best compatible assemblyLine for the activity and Type.
     * Bonuses are ranked as material bonus > time bonus > cost bonus.
     *
     * @param int $activityId the ID of the activity to get AssemblyLines for
     * @param \iveeCore\Type $type It's always the final output item that needs to be given. This means that for
     * manufacturing, its the Blueprint product; for copying its the Blueprint itself; for invention it is the product
     * of the invented blueprint.
     *
     * @return \iveeCore\AssemblyLine|null
     */
    public function getBestAssemblyLineForActivity($activityId, Type $type)
    {
        $bestAssemblyLine = null;
        $bestModifier = null;

        foreach ($this->getAssemblyLinesForActivity($activityId) as $candidateAssemblyLine) {
            //skip incompatible assemblyLines
            if (!$candidateAssemblyLine->isTypeCompatible($type)) {
                continue;
            } //compare candidate assemblyLine with current best
            elseif (is_null($bestAssemblyLine)) {
                $bestAssemblyLine = $candidateAssemblyLine;
                $bestModifier = $bestAssemblyLine->getModifiersForType($type);
            } else {
                $candidateModifier = $candidateAssemblyLine->getModifiersForType($type);

                //Modifiers are ranked with priority order for material, time then cost modifiers (lower is better!)
                if ($bestModifier['m'] < $candidateModifier['m']) {
                    continue;
                } elseif ($bestModifier['m'] > $candidateModifier['m']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                } elseif ($bestModifier['t'] < $candidateModifier['t']) {
                    continue;
                } elseif ($bestModifier['t'] > $candidateModifier['t']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                } elseif ($bestModifier['c'] < $candidateModifier['c']) {
                    continue;
                } elseif ($bestModifier['c'] > $candidateModifier['c']) {
                    $bestAssemblyLine = $candidateAssemblyLine;
                    $bestModifier = $candidateModifier;
                }
            }
        }
        return $bestAssemblyLine;
    }

    /**
     * Gets the best station for market trading in the system, based on the tax, which is dependent on the standings
     * from it's corporation and faction to the character. If multiple stations have the same effective tax, the first
     * of those is returned. If a preferred market station has been set, it is returned.
     *
     * @return \iveeCore\Station
     * @throws \iveeCore\Exceptions\NoRelevantDataException when there is no station with Market service (64) in system
     */
    public function getBestMarketStation()
    {
        $bestStation = null;
        $lowestBrokerTax = 100;
        if (!isset($this->marketStations)) {
            $this->marketStations = $this->getSolarSystem()->getStationsWithService(64);
        }

        //check if preferred station is among them
        if (isset($this->preferredMarketStationId) and isset($this->marketStations[$this->preferredMarketStationId])) {
            return $this->marketStations[$this->preferredMarketStationId];
        }

        foreach ($this->marketStations as $station) {
            $tax = $this->getCharacterModifier()->getBrokerTax($station->getFactionId(), $station->getCorporationId());
            if ($tax < $lowestBrokerTax) {
                $lowestBrokerTax = $tax;
                $bestStation = $station;
            }
        }
        if (is_null($bestStation)) {
            $exceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $exceptionClass('No Station with Market service in System');
        }
        return $bestStation;
    }

    /**
     * Gets the best station for reprocessing in the system, based on the yield, which is dependent on the base
     * reprocessing efficiency and the standings from it's corporation to the character. If multiple stations have the
     * same yield, the first of those is returned.
     *
     * @return \iveeCore\Station
     * @throws \iveeCore\Exceptions\NoRelevantDataException when there is no station with Reprocessing Plant (16)
     * service in system
     */
    public function getBestReprocessingStation()
    {
        $bestStation = null;
        $bestYield = 0.0;
        foreach ($this->getSolarSystem()->getStationsWithService(16) as $station) {
            $yield = $station->getReprocessingEfficiency()
                * $this->getCharacterModifier()->getReprocessingTaxFactor($station->getCorporationId());
            if ($yield > $bestYield) {
                $bestYield = $yield;
                $bestStation = $station;
            }
        }
        if (is_null($bestStation)) {
            $exceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $exceptionClass('No Station with Reprocessing Plant service in System');
        }
        return $bestStation;
    }

    /**
     * Gets the station (standings) and skill dependant total market sell order tax.
     *
     * @return float in the form 0.988 for 1.2% total tax
     */
    public function getSellTaxFactor()
    {
        $station = $this->getBestMarketStation();
        return $this->getCharacterModifier()->getSellTaxFactor($station->getFactionId(), $station->getCorporationId());
    }

    /**
     * Gets the station (standings) and skill dependant total market buy order tax.
     *
     * @return float in the form 1.012 for 1.2% total tax
     */
    public function getBuyTaxFactor()
    {
        $station = $this->getBestMarketStation();
        return $this->getCharacterModifier()->getBuyTaxFactor($station->getFactionId(), $station->getCorporationId());
    }
}
