<?php
/**
 * iveeCore PHPUnit testfile
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreTests
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');

//include the iveeCore init, expected in the iveeCore directory, with absolute path
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php';

use iveeCore\Config;
use iveeCore\SDE;
use iveeCore\Type;
use iveeCore\Manufacturable;
use iveeCore\Blueprint;
use iveeCore\IndustryModifier;
use iveeCore\AssemblyLine;
use iveeCore\MaterialMap;
use iveeCore\ReactionProduct;
use iveeCore\FitParser;
use iveeCore\ProcessData;
use iveeCore\ManufactureProcessData;
use iveeCore\InventionProcessData;
use iveeCore\CopyProcessData;
use iveeCore\ReactionProcessData;

/**
 * PHPUnit test for iveeCore
 *
 * The tests cover different parts of iveeCore and focus on the trickier cases. It is mainly used to aid during the
 * development, but can also be used to check the correct working of an iveeCore installation.
 *
 * To run this test, you'll need to have PHPUnit installed as well as created the iveeCore/Config.php file based on the
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
        $this->assertTrue($type->getBasePrice() == 3068504.0);
        $this->assertTrue($type->isReprocessable());
        $this->assertTrue(is_array($type->getMaterials()));
    }

    /**
     * @expectedException iveeCore\Exceptions\KeyNotFoundInCacheException
     */
    public function testGetTypeAndCache()
    {
        //get cache object for direct calls
        $cacheClass = Config::getIveeClassName('Cache');
        $cacheInstance = $cacheClass::instance();
        //empty cache entry for type
        $cacheInstance->deleteItem('iveeCore\Type_645');

        //get type via Type
        $type = Type::getById(645);
        $this->assertTrue($type instanceof Manufacturable);
        //fetch item directly from cache
        $this->assertTrue($type == $cacheInstance->getItem('iveeCore\Type_645'));
        $this->assertTrue($type == Type::getByName('Dominix'));

        //test cache invalidation
        Type::deleteFromCache(array(645));
        //this should throw an exception
        $cacheInstance->getItem('iveeCore\Type_645');
    }

    /**
     * Creates a process tree 3 levels deep and tests calculations
     */
    public function testProcessData()
    {
        //IndustryModifier for Jita 4-4
        $marketContext = IndustryModifier::getByStationId(60003760);
        $mm = new MaterialMap;

        //create ProcessData and direct children
        $topPd = new ProcessData;
        $ipd = new InventionProcessData(0, 100, 100, 0.5, 1, 0, 0, 0, 0);
        $topPd->addSubProcessData($ipd);
        $mpd = new ManufactureProcessData(34, 1, 100, 100, 0, 0, 0, 0);
        $topPd->addSubProcessData($mpd);
        $rpd = new ReactionProcessData(0, $mm, new MaterialMap, 0);
        $topPd->addSubProcessData($rpd);
        $cpd = new CopyProcessData(0, 1, 1, 100, 100, 0, 0);
        $topPd->addSubProcessData($cpd);
        $this->assertTrue($topPd->getTotalCost($marketContext) == 400);

        //add children to ipd
        $iipd = new InventionProcessData(0, 100, 100, 0.5, 1, 0, 0, 0, 0);
        $ipd->addSubProcessData($iipd);
        $impd = new ManufactureProcessData(34, 1, 100, 100, 0, 0, 0, 0);
        $ipd->addSubProcessData($impd);
        $irpd = new ReactionProcessData(0, $mm, new MaterialMap, 0);
        $ipd->addSubProcessData($irpd);
        $icpd = new CopyProcessData(0, 1, 1, 100, 100, 0, 0);
        $ipd->addSubProcessData($icpd);
        $this->assertTrue($ipd->getTotalAttemptCost($marketContext) == 500);
        $this->assertTrue($ipd->getTotalCost($marketContext) == 1000);

        //add children to mpd
        $mipd = new InventionProcessData(0, 100, 100, 0.5, 1, 0, 0, 0, 0);
        $mpd->addSubProcessData($mipd);
        $mmpd = new ManufactureProcessData(34, 1, 100, 100, 0, 0, 0, 0);
        $mpd->addSubProcessData($mmpd);
        $mrpd = new ReactionProcessData(0, $mm, new MaterialMap, 0);
        $mpd->addSubProcessData($mrpd);
        $mcpd = new CopyProcessData(0, 1, 1, 100, 100, 0, 0);
        $mpd->addSubProcessData($mcpd);
        $this->assertTrue($mpd->getTotalCost($marketContext) == 500);

        //add children to rpd
        $ripd = new InventionProcessData(0, 100, 100, 0.5, 1, 0, 0, 0, 0);
        $rpd->addSubProcessData($ripd);
        $rmpd = new ManufactureProcessData(34, 1, 100, 100, 0, 0, 0, 0);
        $rpd->addSubProcessData($rmpd);
        $rrpd = new ReactionProcessData(0, $mm, new MaterialMap, 0);
        $rpd->addSubProcessData($rrpd);
        $rcpd = new CopyProcessData(0, 1, 1, 100, 100, 0, 0);
        $rpd->addSubProcessData($rcpd);
        $this->assertTrue($rpd->getTotalCost($marketContext) == 400);

        //add children to cpd
        $cipd = new InventionProcessData(0, 100, 100, 0.5, 1, 0, 0, 0, 0);
        $cpd->addSubProcessData($cipd);
        $cmpd = new ManufactureProcessData(34, 1, 100, 100, 0, 0, 0, 0);
        $cpd->addSubProcessData($cmpd);
        $crpd = new ReactionProcessData(0, $mm, new MaterialMap, 0);
        $cpd->addSubProcessData($crpd);
        $ccpd = new CopyProcessData(0, 1, 1, 100, 100, 0, 0);
        $cpd->addSubProcessData($ccpd);
        $this->assertTrue($cpd->getTotalCost($marketContext) == 500);

        //all together
        $this->assertTrue($topPd->getTotalCost($marketContext) == 2400);

        //now test materials
        $mm->addMaterial(34, 100);

        $ipd->addMaterial(35, 100);
        $mpd->addMaterial(35, 100);
        $cpd->addMaterial(35, 100);

        $iipd->addMaterial(36, 100);
        $impd->addMaterial(36, 100);
        $icpd->addMaterial(36, 100);

        $mipd->addMaterial(37, 100);
        $mmpd->addMaterial(37, 100);
        $mcpd->addMaterial(37, 100);

        $ripd->addMaterial(38, 100);
        $rmpd->addMaterial(38, 100);
        $rcpd->addMaterial(38, 100);

        $cipd->addMaterial(39, 100);
        $cmpd->addMaterial(39, 100);
        $ccpd->addMaterial(39, 100);

        $totalMm = $topPd->getTotalMaterialMap();
        $this->assertTrue($totalMm->getMaterials() == [
            34 => 600,
            35 => 400,
            36 => 800,
            37 => 400,
            38 => 400,
            39 => 400
        ]);
        $this->assertTrue($totalMm->getMaterialVolume() == 30);
    }

    /**
     * Tests for error-free execution, not correctness
     */
    public function testBasicBlueprintMethods()
    {
        $bp = Type::getById(2047); //DC I Blueprint
        $this->assertTrue($bp instanceof Blueprint);
        //stubs
        $bp->getProduct();
        $bp->getMaxProductionLimit();
        $bp->getProductBaseCost(null);
        $this->assertTrue($bp->calcResearchMultiplier(0, 2) * 105 == 250);

        //IndustryModifier for Itamo
        $iMod = IndustryModifier::getBySystemIdForPos(30000119);
        $iMod->setMaxPriceDataAge(null); //disable live price update
        $bp->manufacture($iMod)->getTotalProfit($iMod);
        $bp->copy($iMod);
        $bp->invent($iMod);
        $bp->researchME($iMod, 0, 10);
        $bp->researchTE($iMod, 0, 20);
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
     * @expectedException iveeCore\Exceptions\TypeNotCompatibleException
     */
    public function testAssemblyLineException()
    {
        $assLine = AssemblyLine::getById(21);
        $type = Type::getById(23919);
        $assLine->getModifiersForType($type);
    }

    public function testReprocessing()
    {
        //IndustryModifier for Itamo
        $iMod = IndustryModifier::getBySystemIdForPos(30000119);
        $rmap = Type::getByName('Arkonor')->getReprocessingMaterialMap($iMod, 100);
        $materialTarget = new MaterialMap;
        $materialTarget->addMaterial(34, 15919);
        $materialTarget->addMaterial(36, 1809);
        $materialTarget->addMaterial(40, 232);
        $this->assertTrue($rmap == $materialTarget);

        $rmap = Type::getByName('Ark')->getReprocessingMaterialMap($iMod, 1);
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
            ) == []
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
        $this->assertTrue($red->getTotalAttemptMaterialMap() == $materialTarget);
        $this->assertTrue(abs($red->getProbability() - 0.49583333333333) < 0.00000000001);
    }

    public function testReaction()
    {
        $reactionProduct = Type::getByName('Platinum Technite');
        $this->assertTrue($reactionProduct instanceof ReactionProduct);
        //test correct handling of reaction products that can result from alchemy + refining
        $this->assertTrue($reactionProduct->getReactionIDs() == array(17952, 32831));

        //test handling of alchemy reactions with refining + feedback
        $iMod = IndustryModifier::getBySystemIdForPos(30000119);
        $rpd = Type::getByName('Unrefined Platinum Technite Reaction')->react($iMod, 24 * 30, true, true);
        $inTarget = new MaterialMap;
        $inTarget->addMaterial(16640, 72000);
        $inTarget->addMaterial(16644, 7200);
        $this->assertTrue($rpd->getMaterialMap()->getMaterials() == $inTarget->getMaterials());
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

Hexa 2500mm Repeating Cannon I,Arch Angel Nuclear XL x1234
Hexa 2500mm Repeating Cannon I,Arch Angel Nuclear XL
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
                <hardware slot="low slot 2" type="1600mm Rolled Tungsten Compact Plates"/>
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
