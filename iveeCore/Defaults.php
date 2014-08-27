<?php
/**
 * Defaults class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Defaults.php
 *
 */

namespace iveeCore;

/**
 * This file defines a number of default values for use within iveeCore.
 * To adapt them, don't edit this file directly, instead  modify \iveeCoreExtensions\MyIveeCoreDefaults.php, which is 
 * intended precisely for this, overwriting attributes and methods as you require.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Defaults.php
 *
 */
class Defaults
{
    /**
     * @var \iveeCore\Defaults $_instance holds the singleton
     */
    protected static $instance;

    /**
     * @var int $DEFAULT_REGIONID defines the default market region
     */
    protected $defaultRegionID  = 10000002; //The Forge

    /**
     * @var array $TRACKED_MARKET_REGION_IDS defines the regions for which market data should by gathered by the EMDR 
     * client
     */
    protected $trackedMarketRegionIDs = array(
        10000001, //Derelik
        10000002, //The Forge
        10000003, //Vale of the Silent
        10000004, //UUA-F4
        10000005, //Detorid
        10000006, //Wicked Creek
        10000007, //Cache
        10000008, //Scalding Pass
        10000009, //Insmother
        10000010, //Tribute
        10000011, //Great Wildlands
        10000012, //Curse
        10000013, //Malpais
        10000014, //Catch
        10000015, //Venal
        10000016, //Lonetrek
        10000017, //J7HZ-F
        10000018, //The Spire
        10000019, //A821-A
        10000020, //Tash-Murkon
        10000021, //Outer Passage
        10000022, //Stain
        10000023, //Pure Blind
        10000025, //Immensea
        10000027, //Etherium Reach
        10000028, //Molden Heath
        10000029, //Geminate
        10000030, //Heimatar
        10000031, //Impass
        10000032, //Sinq Laison
        10000033, //The Citadel
        10000034, //The Kalevala Expanse
        10000035, //Deklein
        10000036, //Devoid
        10000037, //Everyshore
        10000038, //The Bleak Lands
        10000039, //Esoteria
        10000040, //Oasa
        10000041, //Syndicate
        10000042, //Metropolis
        10000043, //Domain
        10000044, //Solitude
        10000045, //Tenal
        10000046, //Fade
        10000047, //Providence
        10000048, //Placid
        10000049, //Khanid
        10000050, //Querious
        10000051, //Cloud Ring
        10000052, //Kador
        10000053, //Cobalt Edge
        10000054, //Aridia
        10000055, //Branch
        10000056, //Feythabolis
        10000057, //Outer Ring
        10000058, //Fountain
        10000059, //Paragon Soul
        10000060, //Delve
        10000061, //Tenerifis
        10000062, //Omist
        10000063, //Period Basis
        10000064, //Essence
        10000065, //Kor-Azor
        10000066, //Perrigen Falls
        10000067, //Genesis
        10000068, //Verge Vendor
        10000069  //Black Rise
    );

    /**
     * @var float $DefaultBuyTaxFactor defines the tax factor used for cost calculations
     */
    protected $defaultBuyTaxFactor = 1.015;  // = 100% + 0.75% broker fee + 0.75% transaction tax
    
    /**
     * @var float $DefaultSellTaxFactor defines the tax factor used for profit calculations
     */
    protected $defaultSellTaxFactor = 0.985; // = 100% - (0.75% broker fee + 0.75% transaction tax)

    /**
     * @var int $defaultBpoMe defines the default BPO ME level to be used when explicit values have not been defined
     * in $bpMeLevels
     */
    protected $defaultBpoMe = -10;
    
    /**
     * @var int $defaultBpoTe defines the default BPO TE level to be used when explicit values have not been defined
     * in $bpTeLevels
     */
    protected $defaultBpoTe = -20;

    /**
     * @var int $maxPriceDataAge defines the maximum acceptable price data age in seconds. 0 for unlimited.
     * If values greater than 0 are used, methods will start throwing exceptions if the relevant price data is too old.
     */
    protected $maxPriceDataAge = 0;

    /**
     * @var array $bpMeLevels holds the default ME levels of specific blueprints
     */
    protected $bpMeLevels = array(
        23758 => -9, //Archon BP
        23920 => -9, //Aeon BP
        11568 => -9, //Avatar BP
        23912 => -9, //Thanatos BP
        23914 => -9, //Nyx BP
        1002  => -9, //Erebus BP
        23916 => -9, //Chimera BP
        23918 => -9, //Wyvern BP
        3765  => -9, //Leviathan BP
        24484 => -9, //Nidhoggur BP
        22853 => -9, //Hel BP
        23774 => -9, //Regnarok BP
        19721 => -9, //Revelation BP
        19725 => -9, //Moros BP
        19727 => -9, //Phoenix BP
        19723 => -9, //Naglfar BP
        28353 => -9, //Rorqual BP
        28607 => -9, //Orca BP
        20184 => -9, //Providence BP
        20188 => -9, //Obelisk BP
        20186 => -9, //Charon BP
        20190 => -9, //Fenrir BP
    );

    /**
     * @var array $bpteLevels holds the default TE levels of specific blueprints
     */
    protected $bpTeLevels = array(
        23758 => -10, //Archon BP
        23920 => -10, //Aeon BP
        11568 => -10, //Avatar BP
        23912 => -10, //Thanatos BP
        23914 => -10, //Nyx BP
        1002  => -10, //Erebus BP
        23916 => -10, //Chimera BP
        23918 => -10, //Wyvern BP
        3765  => -10, //Leviathan BP
        24484 => -10, //Nidhoggur BP
        22853 => -10, //Hel BP
        23774 => -10, //Regnarok BP
        19721 => -10, //Revelation BP
        19725 => -10, //Moros BP
        19727 => -10, //Phoenix BP
        19723 => -10, //Naglfar BP
        28353 => -10, //Rorqual BP
        28607 => -10, //Orca BP
        20184 => -10, //Providence BP
        20188 => -10, //Obelisk BP
        20186 => -10, //Charon BP
        20190 => -10, //Fenrir BP
    );

    /**
     * Returns Defaults instance.
     * 
     * @return \iveCore\Defaults
     */
    public static function instance()
    {
        if (!isset(static::$instance))
            static::$instance = new static;
        return static::$instance;
    }

    /**
     * The following getters are not implemented as magic __get method for cleaner access control and overwriting in 
     * subclasses
     */
    
    /**
     * Returns the default market region ID
     * 
     * @return int
     */
    public function getDefaultRegionID()
    {
        return $this->defaultRegionID;
    }

    /**
     * Returns the tracked market regionIDs
     * 
     * @return array with the regionIDs as keys
     */
    public function getTrackedMarketRegionIDs()
    {
        return array_flip($this->trackedMarketRegionIDs);
    }

    /**
     * Returns the default buy tax factor in the form 1.015 for 1.5% total tax
     * 
     * @return float
     */
    public function getDefaultBuyTaxFactor()
    {
        return $this->defaultBuyTaxFactor;
    }
    
    /**
     * Returns the default sell tax factor in the form 0.985 for 1.5% total tax
     * 
     * @return float
     */
    public function getDefaultSellTaxFactor()
    {
        return $this->defaultSellTaxFactor;
    }

    /**
     * Returns the default BPO ME level
     * 
     * @return int
     */
    public function getDefaultBpoMe()
    {
        return $this->defaultBpoMe;
    }

    /**
     * Returns the default BPO TE level
     * 
     * @return int
     */
    public function getDefaultBpoTe()
    {
        return $this->defaultBpoTe;
    }

    /**
     * Returns the maximum price data age in seconds
     * 
     * @return int
     */
    public function getMaxPriceDataAge()
    {
        return $this->maxPriceDataAge;
    }

    /**
     * Returns the skill level for a certain skill. This is a stub implementation.
     * 
     * @param int $skillID the ID of the skill being looked up
     * 
     * @return int skill level
     */
    public function getSkillLevel($skillID)
    {
        return 5;
    }

    /**
     * Returns the implant dependent time modifiers for industry activities
     * 
     * @return array in the form activityID => float (0.98 for 2% bonus)
     */
    public function getIndustryImplantTimeModifiers()
    {
        return array(
            1 => 1.0,
            3 => 1.0,
            4 => 1.0,
            5 => 1.0,
        );
    }

    /**
     * Returns the default ME level for a specific blueprint
     * 
     * @param int $bpID the ID of the blueprint being looked up
     * 
     * @return int blueprint ME level
     */
    public function getBpMeLevel($bpID)
    {
        if (isset($this->bpMeLevels[(int) $bpID]))
            return $this->bpMeLevels[(int) $bpID];
        else
            return $this->getDefaultBpoMe();
    }

    /**
     * Sets a default ME level for a blueprint
     * 
     * @param int $bpID the ID of the blueprint
     * @param int $meLevel blueprint ME level to be set
     * 
     * @return void
     */
    public function setBpMeLevel($bpID, $meLevel)
    {
        $this->bpMeLevels[(int) $bpID] = (int) $meLevel;
    }

    /**
     * Returns the default TE level for a specific blueprint
     * 
     * @param int $bpID the ID of the blueprint being looked up
     * 
     * @return int blueprint TE level
     */
    public function getBpTeLevel($bpID)
    {
        if (isset($this->bpTeLevels[(int) $bpID]))
            return $this->bpTeLevels[(int) $bpID];
        else
            return $this->getDefaultBpoTe();
    }

    /**
     * Sets a default TE level for a blueprint
     * 
     * @param int $bpID the ID of the blueprint
     * @param int $teLevel blueprint TE level to be set
     * 
     * @return void
     */
    public function setBpTeLevel($bpID, $teLevel)
    {
        $this->bpTeLevels[(int) $bpID] = (int) $teLevel;
    }

}
