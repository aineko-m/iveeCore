<?php
/**
 * iveeCore PHPUnit testfile
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreTests
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');

use \iveeCore\Config;
use \iveeCore\SDE;
use \iveeCore\Type;
use \iveeCore\Manufacturable;
use \iveeCore\Blueprint;
use \iveeCore\IndustryModifier;
use \iveeCore\AssemblyLine;
use \iveeCore\MaterialMap;
use \iveeCore\ReactionProduct;
use \iveeCore\FitParser;

//include the iveeCore init, expected in the iveeCore directory, with absolute path
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php');

/**
 * PHPUnit test for iveeCore
 *
 * The tests cover different parts of iveeCore and focus on the trickier cases. It is mainly used to aid during the
 * development, but can also be used to check the correct working of an iveeCore installation.
 *
 * To run this test, you'll need to have PHPUnit isntalled as well as created the iveeCore/Config.php file based on the
 * provided template.
 *
 * @category IveeCore
 * @package  IveeCoreExtensions
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 */
class IveeCoreTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $cacheClass = Config::getIveeClassName('Cache');
        $cacheClass::instance()->flushCache();
    }

    public function testSde()
    {
        $this->assertTrue(SDE::instance() instanceof SDE);
    }

    public function testBasicTypeMethods()
    {
        $type = Type::getById(22);
        $this->assertTrue($type->onMarket());
        $this->assertTrue($type->getId() == 22);
        $this->assertTrue($type->getGroupID() == 450);
        $this->assertTrue($type->getCategoryID() == 25);
        $this->assertTrue($type->getName() == 'Arkonor');
        $this->assertTrue($type->getVolume() == 16);
        $this->assertTrue($type->getPortionSize() == 100);
        $this->assertTrue($type->getBasePrice() == 2174386.0);
        $this->assertTrue($type->isReprocessable());
        $this->assertTrue(is_array($type->getMaterials()));
    }

    public function testGetTypeAndCache()
    {
        //empty cache entry for type
        $cacheClass = Config::getIveeClassName('Cache');
        $cacheInstance = $cacheClass::instance();
        $cacheInstance->deleteItem('iveeCore\Type_645');

        //get type
        $type = Type::getById(645);
        $this->assertTrue($type instanceof Manufacturable);
        $this->assertTrue($type == $cacheInstance->getItem('iveeCore\Type_645'));
        $this->assertTrue($type == Type::getByName('Dominix'));
    }

    /**
     * Tests for error-free execution, not correctness
     */
    public function testBasicBlueprintMethods()
    {
        $type = Type::getById(2047); //DC I Blueprint
        $this->assertTrue($type instanceof Blueprint);
        //stubs
        $type->getProduct();
        $type->getMaxProductionLimit();
        $type->getProductBaseCost(0);
        $this->assertTrue($type->calcResearchMultiplier(0, 2) * 105 == 250);

        //IndustryModifier for Itamo
        $iMod = IndustryModifier::getBySystemIdForPos(30000119);
        $type->manufacture($iMod);
        $type->copy($iMod);
        $type->invent($iMod);
        $type->researchME($iMod, 0, 10);
        $type->researchTE($iMod, 0, 20);
    }

    public function testAssemblyLine()
    {
        //test supercap modifiers on supercap assembly array
        $assLine = AssemblyLine::getById(10);
        $type = Type::getById(23919);
        $this->assertTrue(array('t' => 1, 'm' => 1, 'c' => 1) == $assLine->getModifiersForType($type));

        //test battleship modifiers on large ship assembly array
        $type = Type::getById(645);
        $assLine = AssemblyLine::getById(155);
        $this->assertTrue(array('t' => 0.75, 'm' => 0.98, 'c' => 1) == $assLine->getModifiersForType($type));
    }

    /**
     * Test that a supercapital cannot be built in a Capital Ship Assembly Array
     * @expectedException \iveeCore\Exceptions\TypeNotCompatibleException
     */
    public function testAssemblyLineException()
    {
        $assLine = AssemblyLine::getById(21);
        $type = Type::getById(23919);
        $assLine->getModifiersForType($type);
    }

    public function testReprocessing()
    {
        $rmap = Type::getByName('Arkonor')->getReprocessingMaterialMap(100, 0.5, 0.95, 1.01);
        $materialTarget = new MaterialMap;
        $materialTarget->addMaterial(34, 4610);
        $materialTarget->addMaterial(36, 853);
        $materialTarget->addMaterial(39, 77);
        $materialTarget->addMaterial(40, 154);
        $this->assertTrue($rmap == $materialTarget);

        $rmap = Type::getByName('Ark')->getReprocessingMaterialMap(1, 0.5, 1.0, 1.0);
        $materialTarget = new MaterialMap;
        $materialTarget->addMaterial(3828, 1238);
        $materialTarget->addMaterial(11399, 2063);
        $materialTarget->addMaterial(21009, 12);
        $materialTarget->addMaterial(21017, 9);
        $materialTarget->addMaterial(21025, 17);
        $materialTarget->addMaterial(21027, 46);
        $materialTarget->addMaterial(21037, 29);
        $materialTarget->addMaterial(29039, 427);
        $materialTarget->addMaterial(29053, 348);
        $materialTarget->addMaterial(29067, 371);
        $materialTarget->addMaterial(29073, 581);
        $materialTarget->addMaterial(29095, 366);
        $materialTarget->addMaterial(29103, 581);
        $materialTarget->addMaterial(29109, 836);
        $this->assertTrue($rmap == $materialTarget);
    }

    public function testCopyInventManufacture()
    {
        //IndustryModifier for Veisto
        $iMod = IndustryModifier::getBySystemIdForAllNpcStations(30001363);
        $cimpd = Type::getByName('Eagle Blueprint')->copyInventManufacture($iMod, 34206, false);
        $materialTarget = new MaterialMap;
        $materialTarget->addMaterial(623, 3);
        $materialTarget->addMaterial(3828, 548);
        $materialTarget->addMaterial(11399, 437);
        $materialTarget->addMaterial(11478, 53);
        $materialTarget->addMaterial(11533, 219);
        $materialTarget->addMaterial(11534, 481);
        $materialTarget->addMaterial(11540, 3929);
        $materialTarget->addMaterial(11544, 16369);
        $materialTarget->addMaterial(11550, 131);
        $materialTarget->addMaterial(11552, 1746);
        $materialTarget->addMaterial(11558, 1310);
        $materialTarget->addMaterial(34206, 2.6373626373626);
        $materialTarget->addMaterial(20424, 21.098901098901);
        $materialTarget->addMaterial(25887, 21.098901098901);

        //use array_diff to compare, as otherwise the floats never match
        $this->assertTrue(
            array_diff(
                $cimpd->getTotalMaterialMap()->getMaterials(),
                $materialTarget->getMaterials()
            ) == array()
        );
    }

    public function testReverseEngineering()
    {
        $relic = Type::getByName('Intact Hull Section');
        $iMod = IndustryModifier::getBySystemIdForPos(30000119);
        $red = $relic->invent($iMod, 29985);

        $materialTarget = new MaterialMap;
        $materialTarget->addMaterial(20412, 3);
        $materialTarget->addMaterial(20424, 3);
        $materialTarget->addMaterial(30752, 1);
        $this->assertTrue($red->getTotalMaterialMap() == $materialTarget);
        $this->assertTrue(abs($red->getProbability() - 0.49583333333333) < 0.00000000001);
    }

    public function testReaction()
    {
        $reactionProduct = Type::getByName('Platinum Technite');
        $this->assertTrue($reactionProduct instanceof ReactionProduct);
        //test correct handling of reaction products that can result from alchemy + refining
        $this->assertTrue($reactionProduct->getReactionIDs() == array(17952, 32831));

        //test handling of alchemy reactions with refining + feedback
        $rpd = Type::getByName('Unrefined Platinum Technite Reaction')->react(24 * 30, true, true, 0.5, 1);
        $inTarget = new MaterialMap;
        $inTarget->addMaterial(16640, 72000);
        $inTarget->addMaterial(16644, 7200);
        $this->assertTrue($rpd->getInputMaterialMap()->getMaterials() == $inTarget->getMaterials());
        $outTarget = new MaterialMap;
        $outTarget->addMaterial(16662, 14400);
        $this->assertTrue($rpd->getOutputMaterialMap()->getMaterials() == $outTarget->getMaterials());
    }

    public function testEftFitParsing()
    {
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

        $materialTarget = array(
            19722 => 1,
            15806 => 2,
            1978  => 2,
            29001 => 1,
            28999 => 1,
            20452 => 2,
            20745 => 1235,
            4292  => 1
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }

    public function testScanParsing()
    {
        $scanResult = "
            10MN Afterburner I
Inertial Stabilizers II
Expanded Cargohold II

1 Improved Cloaking Device II
9 Hobgoblin I
1 Siege Warfare Link - Shield Efficiency II
1 Siege Warfare Link - Active Shielding II
10  Salvage Drone I

            ";
        $pr = FitParser::parseScanResult($scanResult);

        $materialTarget = array(
            12056 => 1,
            1405  => 1,
            1319  => 1,
            11577 => 1,
            2454  => 9,
            4282  => 1,
            4280  => 1,
            32787 => 10
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }

    public function testXmlFitParsing()
    {
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

        $materialTarget = array(
            24692 => 1,
            2048  => 1,
            2364  => 1,
            11325 => 1,
            3057  => 1,
            25894 => 1,
            2185  => 5,
            2488  => 5
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }
}
