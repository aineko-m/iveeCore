<?php

/**
 * This file defines a number of default values for use within iveeCore. 
 * To adapt them, don't edit this file directly, instead  modify MyIveeCoreDefaults.php, which is intended precisely 
 * for this, overwriting attributes and methods as you require.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/IveeCoreDefaults.php
 * @package iveeCore
 */
class IveeCoreDefaults {
    
    /**
     * @var IveeCoreDefaults $instance holds the singleton
     */
    protected static $instance;
    
    //default market region/system/station
    protected $DEFAULT_REGIONID  = 10000002; //The Forge
    protected $DEFAULT_SYSTEMID  = 30000142; //Jita
    protected $DEFAULT_STATIONID = 60003760; //Jita 4-4 CNAP
    
    //this defines the regions for which market data should by gathered by the EMDR client
    protected $TRACKED_MARKET_REGION_IDS = array(
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
        10000069, //Black Rise
        11000001, //A-R00001
        11000002, //A-R00002
        11000003, //A-R00003
        11000004, //B-R00004
        11000005, //B-R00005
        11000006, //B-R00006
        11000007, //B-R00007
        11000008, //B-R00008
        11000009, //C-R00009
        11000010, //C-R00010
        11000011, //C-R00011
        11000012, //C-R00012
        11000013, //C-R00013
        11000014, //C-R00014
        11000015, //C-R00015
        11000016, //D-R00016
        11000017, //D-R00017
        11000018, //D-R00018
        11000019, //D-R00019
        11000020, //D-R00020
        11000021, //D-R00021
        11000022, //D-R00022
        11000023, //D-R00023
        11000024, //E-R00024
        11000025, //E-R00025
        11000026, //E-R00026
        11000027, //E-R00027
        11000028, //E-R00028
        11000029, //E-R00029
        11000030 //F-R00030
    );

    //tax factors, used in cost and profit calculations
    protected $DEFAULT_BUY_TAX_FACTOR = 1.015;  // = 100% + 0.75% broker fee + 0.75% transaction tax
    protected $DEFAULT_SELL_TAX_FACTOR = 0.985; // = 100% - (0.75% broker fee + 0.75% transaction tax)

    //default BPO ME/PE to use when explicit values have not been defined in $bpMeLevels and $bpPeLevels
    protected $DEFAULT_BPO_ME = 120;
    protected $DEFAULT_BPO_PE = 10;

    //POS slot configuration
    //Number of available slots (character or POS (if using), whichever is lower).
    protected $NUM_MANUFACTURE_SLOTS = 41;
    protected $NUM_COPY_SLOTS = 30;
    protected $NUM_INVENTION_SLOTS = 11;
    protected $NUM_ME_RESEARCH_SLOTS = 14;
    protected $NUM_PE_RESEARCH_SLOTS = 6;
    
    //set the average utilization factor (for estimating the POS slot cost in different processes) 
    protected $POS_SLOT_UTILIZATION_FACTOR = 0.5;
    
    //if you use POS for manufacturing, the slot time factor will be considered
    protected $USE_POS_MANUFACTURING = FALSE;
    protected $POS_MANUFACTURE_SLOT_TIME_FACTOR = 0.75;
    
    //if you use POS for copying, the slot time factor will be considered
    protected $USE_POS_COPYING = TRUE;
    protected $POS_COPY_SLOT_TIME_FACTOR = 0.65;
    
    //if you use POS for invention, the slot time factor will be considered
    protected $USE_POS_INVENTION = FALSE;
    protected $POS_INVENTION_SLOT_TIME_FACTOR = 0.5;
    
    //if you use POS for ME research, the slot time factor will be considered
    protected $USE_POS_ME_RESEARCH = TRUE;
    protected $POS_ME_RESEARCH_SLOT_TIME_FACTOR = 0.75;
    
    //if you use POS for PE research, the slot time factor will be considered
    protected $USE_POS_PE_RESEARCH = TRUE;
    protected $POS_PE_RESEARCH_SLOT_TIME_FACTOR = 0.75;

    //default station slot cost
    protected $STATION_MANUFACTURING_HOUR_COST = 333;
    protected $STATION_COPYING_HOUR_COST = 2000; //rough estimate, as values vary a lot
    protected $STATION_INVENTION_HOUR_COST = 416.67;
    protected $STATION_ME_RESEARCH_HOUR_COST = 2000;
    protected $STATION_PE_RESEARCH_HOUR_COST = 2000;

    //maximum acceptable price data age in seconds
    protected $MAX_PRICE_DATA_AGE = 86400; //1 day

    //add all itemID => quantity consumed hourly by POS(es) etc.
    //this is used to calculate an approximate cost for using POS slots.
    protected $hourlyMaterials = array(
        4247 => 40, //amarr fuel block
        24593 => 1  //caldari empire starbase charter
    );
    
    /**
     * @var float $posSlotCost caches the estimated cost of a POS slot per second
     */
    protected $posSlotCost = null;
    
    /**
     * @var array $bpMeLevels holds the default ME levels of specific blueprints
     */
    protected $bpMeLevels = array(
            23758 => 2, //Archon BP
            23920 => 2, //Aeon BP
            11568 => 2, //Avatar BP
            23912 => 2, //Thanatos BP
            23914 => 2, //Nyx BP
            1002  => 2, //Erebus BP
            23916 => 2, //Chimera BP
            23918 => 2, //Wyvern BP
            3765  => 2, //Leviathan BP
            24484 => 2, //Nidhoggur BP
            22853 => 2, //Hel BP
            23774 => 2, //Regnarok BP
            19721 => 2, //Revelation BP
            19725 => 2, //Moros BP
            19727 => 2, //Phoenix BP
            19723 => 2, //Naglfar BP
            28353 => 2, //Rorqual BP
            28607 => 2, //Orca BP
            20184 => 2, //Providence BP
            20188 => 2, //Obelisk BP
            20186 => 2, //Charon BP
            20190 => 2, //Fenrir BP
        );
    
    /**
     * @var array $bpPeLevels holds the default PE levels of specific blueprints
     */
    protected $bpPeLevels = array(
            23758 => 1, //Archon BP
            23920 => 1, //Aeon BP
            11568 => 1, //Avatar BP
            23912 => 1, //Thanatos BP
            23914 => 1, //Nyx BP
            1002  => 1, //Erebus BP
            23916 => 1, //Chimera BP
            23918 => 1, //Wyvern BP
            3765  => 1, //Leviathan BP
            24484 => 1, //Nidhoggur BP
            22853 => 1, //Hel BP
            23774 => 1, //Regnarok BP
            19721 => 1, //Revelation BP
            19725 => 1, //Moros BP
            19727 => 1, //Phoenix BP
            19723 => 1, //Naglfar BP
            28353 => 1, //Rorqual BP
            28607 => 1, //Orca BP
            20184 => 1, //Providence BP
            20188 => 1, //Obelisk BP
            20186 => 1, //Charon BP
            20190 => 1, //Fenrir BP
        );
    
    public static function instance() {
        if (!isset(static::$instance)){
            static::$instance = new static;
        }
        return static::$instance;
    }
    
    //Getters. Not implemented as magic __get method for cleaner access control and overwriting in subclasses
    public function getDefaultRegionID(){return $this->DEFAULT_REGIONID;}
    public function getDefaultSystemID(){return $this->DEFAULT_SYSTEMID;}
    public function getDefaultStationID(){return $this->DEFAULT_STATIONID;}
    
    public function getTrackedMarketRegionIDs(){return array_flip($this->TRACKED_MARKET_REGION_IDS);}
    
    public function getDefaultBuyTaxFactor(){return $this->DEFAULT_BUY_TAX_FACTOR;}
    public function getDefaultSellTaxFactor(){return $this->DEFAULT_SELL_TAX_FACTOR;}
    
    public function getDefaultBpoMe(){return $this->DEFAULT_BPO_ME;}
    public function getDefaultBpoPe(){return $this->DEFAULT_BPO_PE;}
    
    //POS configuration getters
    public function getPosSlotUtilizationFactor(){return $this->POS_SLOT_UTILIZATION_FACTOR;}
    
    public function getUsePosManufacturing(){return $this->USE_POS_MANUFACTURING;}
    public function getPosManufactureSlotTimeFactor(){return $this->POS_MANUFACTURE_SLOT_TIME_FACTOR;}
    
    public function getUsePosCopying(){return $this->USE_POS_COPYING;}
    public function getPosCopySlotTimeFactor(){return $this->POS_COPY_SLOT_TIME_FACTOR;}
    
    public function getUsePosInvention(){return $this->USE_POS_INVENTION;}
    public function getPosInventionSlotTimeFactor(){return $this->POS_INVENTION_SLOT_TIME_FACTOR;}
    
    public function getUsePosMeResearch(){return $this->USE_POS_ME_RESEARCH;}
    public function getPosMeResearchSlotTimeFactor(){return $this->POS_ME_RESEARCH_SLOT_TIME_FACTOR;}

    public function getUsePosPeResearch(){return $this->USE_POS_PE_RESEARCH;}
    public function getPosPeResearchSlotTimeFactor(){return $this->POS_PE_RESEARCH_SLOT_TIME_FACTOR;}
    
    public function getNumManufactureSlots(){return $this->NUM_MANUFACTURE_SLOTS;}
    public function getNumCopySlots(){return $this->NUM_COPY_SLOTS;}
    public function getNumInventionSlots(){return $this->NUM_INVENTION_SLOTS;}
    public function getNumMeResearchSlots(){return $this->NUM_ME_RESEARCH_SLOTS;}
    public function getNumPeResearchSlots(){return $this->NUM_PE_RESEARCH_SLOTS;}
    
    //station slot cost getters
    public function getStationManufacturingCostPerSecond(){return $this->STATION_MANUFACTURING_HOUR_COST / 3600;}
    public function getStationCopyingCostPerSecond(){return $this->STATION_COPYING_HOUR_COST / 3600;}
    public function getStationInventionCostPerSecond(){return $this->STATION_INVENTION_HOUR_COST / 3600;}
    public function getStationMeResearchCostPerSecond(){return $this->STATION_ME_RESEARCH_HOUR_COST / 3600;}
    public function getStationPeResearchCostPerSecond(){return $this->STATION_PE_RESEARCH_HOUR_COST / 3600;}
    
    public function getMaxPriceDataAge(){return $this->MAX_PRICE_DATA_AGE;}
    
    public function getHourlyMaterials(){return $this->hourlyMaterials;}
    
    /**
     * Returns the skill level for a certain skill.
     * This is a stub implementation.
     * @param int $skillID the ID of the skill being looked up
     * @return int skill level
     */
    public function getSkillLevel($skillID){
        return 5;
    }
    
    /**
     * Returns the default blueprint ME level.
     * @param int $bpID the ID of the blueprint being looked up
     * @return int blueprint ME level
     */
    public function getBpMeLevel($bpID){

        if(isset($this->bpMeLevels[$bpID]))
            return $this->bpMeLevels[$bpID];
        else
            return $this->getDefaultBpoMe();
    }
    
    /**
     * Sets a default ME level for a blueprint.
     * @param int $bpID the ID of the blueprint
     * @param int $meLevel blueprint ME level to be set
     */
    public function setBpMeLevel($bpID, $meLevel){
        $this->bpMeLevels[$bpID] = (int) $meLevel;
    }
    
    /**
     * Returns the default blueprint PE level.
     * @param int $bpID the ID of the blueprint being looked up
     * @return int blueprint PE level
     */
    public function getBpPeLevel($bpID){
 
        if(isset($this->bpPeLevels[$bpID]))
            return $this->bpPeLevels[$bpID];
        else
            return $this->getDefaultBpoPe();
    }
    
    /**
     * Sets a default PE level for a blueprint.
     * @param int $bpID the ID of the blueprint
     * @param int $peLevel blueprint PE level to be set
     */
    public function setBpPeLevel($bpID, $peLevel){
        $this->bpPeLevels[$bpID] = (int) $peLevel;
    }
    
    /**
     * Returns a hipothetical POS slots cost assuming the configured hourly fuel materials, number of active slots 
     * and use factor.
     * @return float the estimated cost per POS slot per second
     */
    public function getPosSlotCostPerSecond() {
        //avoid calculating the cost twice
        if (isset($this->posSlotCost))
            return $this->posSlotCost;

        //get number of configured POS slots
        $numSlots = 
            $this->getUsePosManufacturing() ? $this->getNumManufactureSlots() : 0
            + $this->getUsePosCopying()     ? $this->getNumCopySlots() : 0
            + $this->getUsePosInvention()   ? $this->getNumInventionSlots() : 0
            + $this->getUsePosMeResearch()  ? $this->getNumMeResearchSlots() : 0
            + $this->getUsePosPeResearch()  ? $this->getNumPeResearchSlots() : 0;
        if($numSlots < 1){
            $this->posSlotCost = 0;
            return $this->posSlotCost;
        }
        
        //sum hourly fuel material costs
        $hourlySum = 0;
        foreach ($this->getHourlyMaterials() as $typeID => $amount) {
            $type = SDE::instance()->getType($typeID);
            if ($type instanceof Manufacturable)
                //we build manufacturable materials
                $hourlySum += $type->getBlueprint()->manufacture($amount)->getTotalCost();
            else
                //we buy non-manufacturables
                $hourlySum += $amount * $type->getBuyPrice() * $this->getDefaultBuyTaxFactor();
        }
        //destribute cost over all configured slots, account for slot underutilization
        $this->posSlotCost = $hourlySum / ($numSlots * 3600) / $this->getPosSlotUtilizationFactor();
        return $this->posSlotCost;
    }
}

?>