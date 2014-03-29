<?php

error_reporting(E_STRICT);
ini_set('display_errors', 'on');

//include the iveeCore configuration, expected in the iveeCore directory, with absolute path
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreConfig.php');

/**
 * PHPUnit test for iveeCore
 * 
 * The tests cover different parts of iveeCore and focus on the trickier cases. It is mainly used to aid during the 
 * development, but can also be used to check the correct working of an iveeCore installation.
 * 
 * To run this test, you'll need to have PHPUnit isntalled as well as created the iveeCoreConfig.php file based on the
 * provided template.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 * @package iveeCore
 */
class IveeCoreTest extends PHPUnit_Framework_TestCase {
    
    protected function setUp(){
        SDE::instance()->flushCache();
    }
    
    public function testSde(){
        $this->assertTrue(SDE::instance() instanceof SDE);
    }
    
    public function testBasicTypeMethods(){
        $type = SDE::instance()->getType(22);
        $this->assertTrue($type instanceof Sellable);
        $this->assertTrue($type->getTypeID() == 22);
        $this->assertTrue($type->getGroupID() == 450);
        $this->assertTrue($type->getCategoryID() == 25);
        $this->assertTrue($type->getName() == 'Arkonor');
        $this->assertTrue($type->getVolume() == 16);
        $this->assertTrue($type->getPortionSize() == 200);
        $this->assertTrue($type->getBasePrice() == 2133490.0);
        $this->assertTrue($type->isReprocessable());
        $this->assertTrue(is_array($type->getTypeMaterials()));
    }
    
    public function testBasicSDEUtilMethods(){
        $this->assertTrue(SDEUtil::calcReprocessingYield(0.5, 0, 0) == 0.875);
        $this->assertTrue(SDEUtil::calcReprocessingTaxFactor(0) == 0.95);
    }
    
    public function testGetTypeAndCache(){
        //can't test cache with cache disabled
        if(!iveeCoreConfig::getUseMemcached())
            return;
        
        //empty cache entry for type
        $sde = SDE::instance();
        $sde->invalidateCache('type_' . 645);
        //get type
        $type = $sde->getType(645);
        $this->assertTrue($type instanceof Manufacturable);
        $this->assertTrue($type == $sde->getFromCache('type_' . 645));
        $this->assertTrue($type == $sde->getTypeByName('Dominix'));
    }
    
    public function testBasicBlueprintMethods(){
        $type = SDE::instance()->getType(2047);
        $this->assertTrue($type instanceof Blueprint);
        //stubs
        $type->manufacture();
        $type->copy();
        $type->getTypeRequirements();
        $type->getProduct();
        $type->getProductionTime();
        $type->getTechLevel();
        $type->getResearchProductivityTime();
        $type->getResearchMaterialTime();
        $type->getResearchCopyTime();
        $type->getResearchTechTime();
        $type->getProductivityModifier();
        $type->getMaterialModifier();
        $type->getMaxProductionLimit();
        $type->calcMaterialFactor();
        $type->calcProductionTime();
        $type->calcCopyTime();
        $type->calcPEResearchTime();
        $type->calcMEResearchTime();
    }
    
    public function testManufacturing(){
        //Dominix - Test if extra materials are handled correctly when PE skill level < 5
        $mpd = SDE::instance()->getType(645)->getBlueprint()->manufacture(1, 10, 5, false, 4);
        $this->assertTrue($mpd->getProducedType()->getTypeID() == 645);
        $this->assertTrue($mpd->getTime() == 12000);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(34, 10967499);
        $materialTarget->addMaterial(35, 2743561);
        $materialTarget->addMaterial(36, 690738);
        $materialTarget->addMaterial(37, 171858);
        $materialTarget->addMaterial(38, 42804);
        $materialTarget->addMaterial(39, 9789);
        $materialTarget->addMaterial(40, 3583);
        $this->assertTrue($mpd->getMaterialMap() == $materialTarget);
        
        //Improved Cloaking Device II - Tests if materials with recycle flag are handled correctly
        $mpd = SDE::instance()->getTypeByName('Improved Cloaking Device II')->getBlueprint()->manufacture(1, -4, 0, false, 4);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(9840, 10);
        $materialTarget->addMaterial(9842, 5);
        $materialTarget->addMaterial(11370, 1);
        $materialTarget->addMaterial(11483, 0.15);
        $materialTarget->addMaterial(11541, 10);
        $materialTarget->addMaterial(11693, 10);
        $materialTarget->addMaterial(11399, 16);
        $this->assertTrue($mpd->getMaterialMap() == $materialTarget);
        
        //test recursive building and adding ManufactureProcessData objects to ProcessData objects as sub-processes
        $pd = new ProcessData();
        $pd->addSubProcessData(SDE::instance()->getTypeByName('Archon')->getBlueprint()->manufacture(1, 2, 1, true, 5));
        $pd->addSubProcessData(SDE::instance()->getTypeByName('Rhea')->getBlueprint()->manufacture(1, -2, 1, true, 5));
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(34, 173107652);
        $materialTarget->addMaterial(35, 28768725);
        $materialTarget->addMaterial(36, 10581008);
        $materialTarget->addMaterial(37, 1620852);
        $materialTarget->addMaterial(38, 461986);
        $materialTarget->addMaterial(39, 79255);
        $materialTarget->addMaterial(40, 31920);
        $materialTarget->addMaterial(3828, 1950);
        $materialTarget->addMaterial(11399, 3250);
        $materialTarget->addMaterial(16671, 9362621);
        $materialTarget->addMaterial(16681, 33210);
        $materialTarget->addMaterial(16682, 11520);
        $materialTarget->addMaterial(17317, 13460);
        $materialTarget->addMaterial(16680, 62220);
        $materialTarget->addMaterial(16683, 11330);
        $materialTarget->addMaterial(33362, 36600);
        $materialTarget->addMaterial(16679, 915915);
        $materialTarget->addMaterial(16678, 2444601);
        $this->assertTrue($pd->getTotalMaterialMap() == $materialTarget);
        //check skill handling
        $skillTarget = new SkillMap();
        $skillTarget->addSkill(22242, 4);
        $skillTarget->addSkill(3380, 5);
        $skillTarget->addSkill(11452, 4);
        $skillTarget->addSkill(11454, 4);
        $skillTarget->addSkill(11453, 4);
        $skillTarget->addSkill(11446, 4);
        $skillTarget->addSkill(11448, 4);
        $skillTarget->addSkill(11443, 4);
        $skillTarget->addSkill(11529, 4);
        $this->assertTrue($pd->getTotalSkillMap() == $skillTarget);
    }
    
    public function testReprocessing(){      
        $sde = SDE::instance();
        $rmap = $sde->getTypeByName('Arkonor')->getReprocessingMaterialMap(200, 0.8825, 1);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(34, 8825);
        $materialTarget->addMaterial(39, 146);
        $materialTarget->addMaterial(40, 294);
        $this->assertTrue($rmap == $materialTarget);
        
        $rmap = $sde->getTypeByName('Zealot')->getReprocessingMaterialMap(1, 0.8825, 0.95);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(34, 41704);
        $materialTarget->addMaterial(35, 21779);
        $materialTarget->addMaterial(36, 7503);
        $materialTarget->addMaterial(37, 3752);
        $materialTarget->addMaterial(39, 251);
        $materialTarget->addMaterial(40, 47);
        $materialTarget->addMaterial(3828, 84);
        $materialTarget->addMaterial(11399, 84);
        $materialTarget->addMaterial(11532, 42);
        $materialTarget->addMaterial(11537, 222);
        $materialTarget->addMaterial(11539, 754);
        $materialTarget->addMaterial(11543, 3144);
        $materialTarget->addMaterial(11549, 25);
        $materialTarget->addMaterial(11554, 252);
        $materialTarget->addMaterial(11557, 252);
        $this->assertTrue($rmap == $materialTarget);
        
        $rmap = $sde->getTypeByName('Ark')->getReprocessingMaterialMap(1, 0.8825, 0.95);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(3828, 1258);
        $materialTarget->addMaterial(11399, 2096);
        $materialTarget->addMaterial(21025, 17);
        $materialTarget->addMaterial(29039, 434);
        $materialTarget->addMaterial(29053, 353);
        $materialTarget->addMaterial(29067, 376);
        $materialTarget->addMaterial(29073, 590);
        $materialTarget->addMaterial(29095, 371);
        $materialTarget->addMaterial(29103, 590);
        $materialTarget->addMaterial(29109, 849);
        $this->assertTrue($rmap == $materialTarget);
    }
    
    public function testCopying(){
        //test copying of BPs that consume materials
        $cpd = SDE::instance()->getTypeByName('Prototype Cloaking Device I')->getBlueprint()->copy(3, 'max', true);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(3812, 6000);
        $materialTarget->addMaterial(36, 24000);
        $materialTarget->addMaterial(37, 45000);
        $materialTarget->addMaterial(38, 21600);
        $this->assertTrue($cpd->getTotalMaterialMap() == $materialTarget);
        $this->assertTrue($cpd->getTotalTime() == 2830800);
    }
    
    public function testInventing(){
        $ipd = SDE::instance()->getTypeByName('Ishtar Blueprint')->invent(23185);
        $this->assertTrue($ipd->getInventionChance() == 0.312);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(23185, 1);
        $materialTarget->addMaterial(20410, 8);
        $materialTarget->addMaterial(20424, 8);
        $materialTarget->addMaterial(25855, 0);
        $this->assertTrue($ipd->getTotalMaterialMap() == $materialTarget);
    }
    
    public function testCopyInventManufacture(){
        $cimpd = SDE::instance()->getTypeByName('Ishtar Blueprint')->copyInventManufacture(23185);
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(38, 9320.4);
        $materialTarget->addMaterial(3828, 420);
        $materialTarget->addMaterial(11399, 420);
        $materialTarget->addMaterial(16670, 767760);
        $materialTarget->addMaterial(16680, 19530);
        $materialTarget->addMaterial(16683, 1470);
        $materialTarget->addMaterial(16681, 9933);
        $materialTarget->addMaterial(16682, 2226);
        $materialTarget->addMaterial(33359, 10080);
        $materialTarget->addMaterial(16678, 167580);
        $materialTarget->addMaterial(17317, 210);
        $materialTarget->addMaterial(16679, 12600);
        $materialTarget->addMaterial(34, 1697862);
        $materialTarget->addMaterial(35, 373872);
        $materialTarget->addMaterial(36, 117906);
        $materialTarget->addMaterial(37, 29842.8);
        $materialTarget->addMaterial(39, 1770);
        $materialTarget->addMaterial(40, 480);
        $materialTarget->addMaterial(23185, 3.2051282051282);
        $materialTarget->addMaterial(20410, 25.641025641026);
        $materialTarget->addMaterial(20424, 25.641025641026);
        $materialTarget->addMaterial(25855, 0);
        
        //use array_diff to compare, as otherwise the floats never match
        $this->assertTrue(
            array_diff(
                $cimpd->getTotalMaterialMap()->getMaterials(), 
                $materialTarget->getMaterials()
            ) == array()
        );
    }
    
    public function testReaction(){
        $reactionProduct = SDE::instance()->getTypeByName('Platinum Technite');
        $this->assertTrue($reactionProduct instanceof ReactionProduct);
        //test correct handling of reaction products that can result from alchemy + refining
        $this->assertTrue($reactionProduct->getReactionIDs() == array(17952, 32831));

        //test handling of alchemy reactions with refining + feedback
        $rpd = SDE::instance()->getTypeByName('Unrefined Platinum Technite Reaction')->react(24 * 30, true, true, 1, 1);
        $inTarget = new MaterialMap();
        $inTarget->addMaterial(16640, 72000);
        $inTarget->addMaterial(16644, 7200);
        $this->assertTrue($rpd->getInputMaterialMap()->getMaterials() == $inTarget->getMaterials());
        $outTarget = new MaterialMap();
        $outTarget->addMaterial(16662, 14400);
        $this->assertTrue($rpd->getOutputMaterialMap()->getMaterials() == $outTarget->getMaterials());
    }
    
    public function testEftFitParsing(){
        $fit = "
            
            [Naglfar, My Nag]
Republic Fleet Gyrostabilizer
Republic Fleet Gyrostabilizer

Tracking Computer II,Tracking Speed Script
Tracking Computer II,Optimal Range Script

6x2500mm Repeating Cannon I,Arch Angel Nuclear XL x1234
6x2500mm Repeating Cannon I,Arch Angel Nuclear XL
Siege Module II
";
        $pr = FitParser::parseEftFit($fit);
        
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(19722, 1);
        $materialTarget->addMaterial(15806, 2);
        $materialTarget->addMaterial(1978, 2);
        $materialTarget->addMaterial(29001, 1);
        $materialTarget->addMaterial(28999, 1);
        $materialTarget->addMaterial(20452, 2);
        $materialTarget->addMaterial(20745, 1235);
        $materialTarget->addMaterial(4292, 1);
        
        $this->assertTrue($pr->getMaterialMap() == $materialTarget);
    }
    
    public function testScanParsing(){
        $scanResult = "
            10MN Afterburner I
Inertia Stabilizers II
Expanded Cargohold II

1 Improved Cloaking Device II
9 Hobgoblin I
1 Siege Warfare Link - Shield Efficiency II
1 Siege Warfare Link - Active Shielding II
10  Salvage Drone I

            ";
        $pr = FitParser::parseScanResult($scanResult);
        
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(12056, 1);
        $materialTarget->addMaterial(1405, 1);
        $materialTarget->addMaterial(1319, 1);
        $materialTarget->addMaterial(11577, 1);
        $materialTarget->addMaterial(2454, 9);
        $materialTarget->addMaterial(4282, 1);
        $materialTarget->addMaterial(4280, 1);
        $materialTarget->addMaterial(32787, 10);
        
        $this->assertTrue($pr->getMaterialMap() == $materialTarget);
    }
    
    public function testXmlFitParsing(){
        $fitDom = new DOMDocument();
        $fitDom->loadXML('<?xml version="1.0" ?>
	<fittings>
            <fitting name="Abadong">
                <description value=""/>
                <shipType value="Abaddon"/>
                <hardware slot="low slot 0" type="Damage Control II"/>
                <hardware slot="low slot 1" type="Heat Sink II"/>
                <hardware slot="low slot 2" type="1600mm Reinforced Rolled Tungsten Plates I"/>
                <hardware slot="hi slot 7" type="Mega Pulse Laser II"/>
                <hardware slot="rig slot 2" type="Large Trimark Armor Pump I"/>
                <hardware qty="5" slot="drone bay" type="Hammerhead II"/>
                <hardware qty="5" slot="drone bay" type="Warrior II"/>
            </fitting>
        </fittings>');
        
        $pr = FitParser::parseXmlFit($fitDom);
        
        $materialTarget = new MaterialMap();
        $materialTarget->addMaterial(24692, 1);
        $materialTarget->addMaterial(2048, 1);
        $materialTarget->addMaterial(2364, 1);
        $materialTarget->addMaterial(11325, 1);
        $materialTarget->addMaterial(3057, 1);
        $materialTarget->addMaterial(25894, 1);
        $materialTarget->addMaterial(2185, 5);
        $materialTarget->addMaterial(2488, 5);
        
        $this->assertTrue($pr->getMaterialMap() == $materialTarget);
    }
}
?>