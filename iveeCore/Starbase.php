<?php
/**
 * Starbase class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Starbase.php
 *
 */

namespace iveeCore;

/**
 * Class for starbases ("Player Owned Stations", "Towers").
 * Inheritance: Starbase -> Manufacturable -> Type -> SdeType -> CacheableCommon
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Starbase.php
 *
 */
class Starbase extends Manufacturable
{
    /**
     * @var array $onlineCycleFuelReq holding raw fuel requirement data.
     */
    protected $onlineCycleFuelReq = array();

    /**
     * @var array $reinforcedCycleFuelReq holding raw reinforcement fuel requirement data.
     */
    protected $reinforcedCycleFuelReq = array();

    /**
     * Constructor. Use \iveeCore\Type::getById() to instantiate Starbase objects instead.
     *
     * @param int $id of the Type
     *
     * @return \iveeCore\Starbase
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($id)
    {
        parent::__construct($id);

        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');

        //get typeMaterials, if any
        $fuelReqs = $sdeClass::instance()->query(
            'SELECT resourceTypeID, quantity, purpose, minSecurityLevel, factionID
            FROM invControlTowerResources
            WHERE controlTowerTypeID = ' . (int) $this->id . ';'
        );

        if ($fuelReqs->num_rows > 0) {
            while ($req = $fuelReqs->fetch_assoc()) {
                //online requirements
                if ($req['purpose'] == 1) {
                    $this->onlineCycleFuelReq[(int) $req['resourceTypeID']] = array(
                        'quantity' => (int) $req['quantity'],
                        'minSecurityLevel' =>
                            isset($req['minSecurityLevel']) ? (float) $req['minSecurityLevel'] : null,
                        'factionId' => isset($req['factionID']) ? (int) $req['factionID'] : null
                    );
                } elseif ($req['purpose'] == 4) {
                    //reinforced requirements
                    $this->reinforcedCycleFuelReq[(int) $req['resourceTypeID']] = array(
                        'quantity' => (int) $req['quantity']
                    );
                }
            }
        }
    }

    /**
     * Gets the fuel requirements per cycle in a given solar system.
     *
     * @param int $solarSystemId of the system
     *
     * @return \iveeCore\MaterialMap
     */
    public function getOnlineCycleFuelReqs($solarSystemId)
    {
        $systemClass = Config::getIveeClassName('SolarSystem');
        $solarSystem = $systemClass::getById($solarSystemId);

        $materialMapClass = Config::getIveeClassName('MaterialMap');
        $mats = new $materialMapClass;

        foreach ($this->onlineCycleFuelReq as $typeId => $data) {
            if (isset($data['factionId'])) {
                if ($solarSystem->getFactionId() == $data['factionId']
                    AND $solarSystem->getSecurity() > $data['minSecurityLevel']
                ) {
                    $mats->addMaterial($typeId, $data['quantity']);
                }
            } else {
                $mats->addMaterial($typeId, $data['quantity']);
            }
        }
        return $mats;
    }

    /**
     * Gets the reinforcement fuel requirements per cycle.
     *
     * @return \iveeCore\MaterialMap
     */
    public function getReinforcedCycleFuelReq()
    {
        $materialMapClass = Config::getIveeClassName('MaterialMap');
        $mats = new $materialMapClass;

        foreach ($this->reinforcedCycleFuelReq as $typeId => $data)
            $mats->addMaterial($typeId, $data['quantity']);

        return $mats;
    }
}
