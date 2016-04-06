<?php
/**
 * iveeCrest PHPUnit testfile
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestTests
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCrestTest.php
 *
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');

//include the iveeCrest init, expected in the iveeCrest directory, with absolute path
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php';

use iveeCore\Config;
use iveeCore\ICache;
use iveeCrest\Client;
use iveeCrest\Responses\AllianceCollection;
use iveeCrest\Responses\Alliance;
use iveeCrest\Responses\BaseResponse;
use iveeCrest\Responses\Character;
use iveeCrest\Responses\CharacterLocation;
use iveeCrest\Responses\ConstellationCollection;
use iveeCrest\Responses\Constellation;
use iveeCrest\Responses\ContactCollection;
use iveeCrest\Responses\DogmaAttributeCollection;
use iveeCrest\Responses\DogmaAttribute;
use iveeCrest\Responses\DogmaEffectCollection;
use iveeCrest\Responses\DogmaEffect;
use iveeCrest\Responses\FittingCollection;
use iveeCrest\Responses\IncursionCollection;
use iveeCrest\Responses\IndustryFacilityCollection;
use iveeCrest\Responses\IndustrySystemCollection;
use iveeCrest\Responses\ItemGroup;
use iveeCrest\Responses\ItemGroupCollection;
use iveeCrest\Responses\ItemCategory;
use iveeCrest\Responses\ItemCategoryCollection;
use iveeCrest\Responses\Killmail;
use iveeCrest\Responses\MarketOrderCollection;
use iveeCrest\Responses\MarketTypeHistoryCollection;
use iveeCrest\Responses\MarketTypePriceCollection;
use iveeCrest\Responses\MarketGroupCollection;
use iveeCrest\Responses\MarketGroup;
use iveeCrest\Responses\MarketTypeCollection;
use iveeCrest\Responses\Options;
use iveeCrest\Responses\Planet;
use iveeCrest\Responses\RegionCollection;
use iveeCrest\Responses\Region;
use iveeCrest\Responses\Root;
use iveeCrest\Responses\SovCampaignsCollection;
use iveeCrest\Responses\SovStructureCollection;
use iveeCrest\Responses\SystemCollection;
use iveeCrest\Responses\System;
use iveeCrest\Responses\TokenDecode;
use iveeCrest\Responses\Tournament;
use iveeCrest\Responses\TournamentCollection;
use iveeCrest\Responses\TournamentMatch;
use iveeCrest\Responses\TournamentPilotStatsCollection;
use iveeCrest\Responses\TournamentPilotTournamentStats;
use iveeCrest\Responses\TournamentRealtimeMatchFrame;
use iveeCrest\Responses\TournamentSeries;
use iveeCrest\Responses\TournamentSeriesCollection;
use iveeCrest\Responses\TournamentStaticSceneData;
use iveeCrest\Responses\TournamentTeam;
use iveeCrest\Responses\TournamentTeamMemberCollection;
use iveeCrest\Responses\TournamentTypeBanCollection;
use iveeCrest\Responses\War;
use iveeCrest\Responses\WarKillmails;
use iveeCrest\Responses\WarsCollection;

/**
 * PHPUnit test for iveeCrest
 *
 * The tests some parts of iveeCrest. It is mainly used to aid during the development, but can also be used to check the
 * correct working of an iveeCrest installation. Currently the tests just call methods and check for returned type.
 *
 * To run this test, you'll need to have PHPUnit installed as well as created the iveeCrest/Config.php file based on the
 * provided template. Expect the test to run for about a minute starting with a cold cache.
 *
 * @category IveeCore
 * @package  IveeCoreExtensions
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 */
class IveeCrestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \iveeCrest\Client $client
     */
    protected $client;

    /**
     * @var \iveeCrest\Responses\Root $pubRoot
     */
    protected $pubRoot;

    protected function setUp()
    {
        $cacheClass = Config::getIveeClassName('Cache');
        $cacheClass::instance()->flushCache();
        $this->client = new Client;
        $this->pubRoot = $this->client->getPublicRootEndpoint();
    }

    public function testClient()
    {
        $this->assertTrue($this->client->getCache() instanceof ICache);
        $or = $this->client->getOptions($this->client->getAuthedCrestBaseUrl());
        $this->assertTrue($or instanceof Options);
    }

    public function testRootResponses()
    {
        $pubRoot = $this->client->getPublicRootEndpoint();
        $this->assertTrue($pubRoot instanceof Root);
    }

    public function testTokenVerify()
    {
        if ($this->client->hasAuthScope('publicData')) {
            $verifyResponse = $this->client->verifyAccessToken();
            $this->assertTrue($verifyResponse instanceof BaseResponse);
        }
    }

    public function testCharacterResponses()
    {
        if ($this->client->hasAuthScope('publicData')) {
            $tokenDecode = $this->client->getTokenDecode();
            $this->assertTrue($tokenDecode instanceof TokenDecode);
            $char = $tokenDecode->getCharacter();
            $this->assertTrue($char instanceof Character);

            if ($this->client->hasAuthScope('characterContactsRead')) {
                $this->assertTrue($char->getContactsCollection() instanceof ContactCollection);
            }

            if ($this->client->hasAuthScope('characterFittingsRead')) {
                $this->assertTrue($char->getFittingCollection() instanceof FittingCollection);
            }

            if ($this->client->hasAuthScope('characterLocationRead')) {
                $this->assertTrue($char->getLocation() instanceof CharacterLocation);
            }
        }
    }

    public function testInventoryResponses()
    {
        $itemGroupCollection = $this->pubRoot->getItemGroupCollection();
        $this->assertTrue($itemGroupCollection instanceof ItemGroupCollection);
        $itemGroup = $itemGroupCollection->getItemGroup(26);
        $this->assertTrue($itemGroup instanceof ItemGroup);
        $itemCategory = $itemGroup->getCategory();
        $this->assertTrue($itemCategory instanceof ItemCategory);
        $this->assertTrue($itemCategory->getGroups()[358] instanceof ItemGroup);

        $itemCategoryCollection = $this->pubRoot->getItemCategoryCollection();
        $this->assertTrue($itemCategoryCollection instanceof ItemCategoryCollection);
        $this->assertTrue($itemCategoryCollection->getItemCategory(65) instanceof ItemCategory);
        $this->assertTrue($itemCategoryCollection->getItemCategories()[23] instanceof ItemCategory);
    }

    public function testAllianceResponses()
    {
        $allianceCollection = $this->pubRoot->getAllianceCollection();
        $this->assertTrue($allianceCollection instanceof AllianceCollection);
        $gatheredAlliances = $allianceCollection->gather();
        $this->assertTrue(is_array($gatheredAlliances));
        $alliance = $allianceCollection->getAlliance(key($gatheredAlliances));
        $this->assertTrue($alliance instanceof Alliance);
    }

    public function testUniverseLocationResponses()
    {
        $regionCollection = $this->pubRoot->getRegionCollection();
        $this->assertTrue($regionCollection instanceof RegionCollection);
        $region = $regionCollection->getRegion(10000006);
        $this->assertTrue($region instanceof Region);
        $constellation = $region->getConstellation(20000081);
        $this->assertTrue($constellation instanceof Constellation);
        $system = $constellation->getSystem(30000559);
        $this->assertTrue($system instanceof System);
        $planet = $system->getPlanet(40034969);
        $this->assertTrue($planet instanceof Planet);

        $systemCollection = $this->pubRoot->getSystemCollection();
        $this->assertTrue($systemCollection instanceof SystemCollection);
        $this->assertTrue($systemCollection->getSystem(30002576) instanceof System);
        $constellationCollection = $this->pubRoot->getConstellationCollection();
        $this->assertTrue($constellationCollection instanceof ConstellationCollection);
        $this->assertTrue($constellationCollection->getConstellation(20000246) instanceof Constellation);
    }

    public function testDogmaResponses()
    {
        $dogmaAttributeCollection = $this->pubRoot->getDogmaAttributeCollection();
        $this->assertTrue($dogmaAttributeCollection instanceof DogmaAttributeCollection);
        $this->assertTrue($dogmaAttributeCollection->getDogmaAttribute(4) instanceof DogmaAttribute);
        $dogmaEffectCollection = $this->pubRoot->getDogmaEffectCollection();
        $this->assertTrue($dogmaEffectCollection instanceof DogmaEffectCollection);
        $this->assertTrue($dogmaEffectCollection->getDogmaEffect(4) instanceof DogmaEffect);
    }

    public function testIndustryResponses()
    {
        $marketTypePricesCollection = $this->pubRoot->getMarketTypePriceCollection();
        $this->assertTrue($marketTypePricesCollection instanceof MarketTypePriceCollection);
        $industryFacilityCollection = $this->pubRoot->getIndustryFacilityCollection();
        $this->assertTrue($industryFacilityCollection instanceof IndustryFacilityCollection);
        $industrySystemCollection = $this->pubRoot->getIndustrySystemCollection();
        $this->assertTrue($industrySystemCollection instanceof IndustrySystemCollection);
    }

    public function testMarketResponses()
    {
        $marketGroupCollection = $this->pubRoot->getMarketGroupCollection();
        $this->assertTrue($marketGroupCollection instanceof MarketGroupCollection);
        $marketGroup = $marketGroupCollection->getMarketGroup(4);
        $this->assertTrue($marketGroup instanceof MarketGroup);
        $marketTypesCollection = $marketGroup->getMarketTypeCollection();
        $this->assertTrue($marketTypesCollection instanceof MarketTypeCollection);

        $region = $this->pubRoot->getRegionCollection()->getRegion(10000006);
        $marketOrders = $region->getMarketOrders(34);
        $this->assertTrue(isset($marketOrders->sellOrders) and is_array($marketOrders->buyOrders));
        $history = $region->getMarketHistory(34);
        $this->assertTrue($history instanceof MarketTypeHistoryCollection);

        $multiOrders = [];
        $region->getMultiMarketOrders(
            [35, 36],
            function (MarketOrderCollection $response) use (&$multiOrders) {
                $multiOrders[] = $response;
            }
        );
        $this->assertTrue(count($multiOrders) == 4); //two responses per item due to buy and sell

        $multiHistory = [];
        $region->getMultiMarketHistory(
            [35, 36],
            function (MarketTypeHistoryCollection $response) use (&$multiHistory) {
                $multiHistory[] = $response;
            }
        );
        $this->assertTrue(count($multiHistory) == 2);
    }

    public function testIncursionResponses()
    {
        $incursionCollection = $this->pubRoot->getIncursionCollection();
        $this->assertTrue($incursionCollection instanceof IncursionCollection);
        $this->assertTrue(is_array($incursionCollection->gather()));
    }

    public function testSovResponses()
    {
        $sovCampaigns = $this->pubRoot->getSovereigntyCampaingCollection();
        $this->assertTrue($sovCampaigns instanceof SovCampaignsCollection);
        $sovStructures = $this->pubRoot->getSovereigntyStructureCollection();
        $this->assertTrue($sovStructures instanceof SovStructureCollection);
    }

    public function testTournamentResponses()
    {
        $tournamentsCollection = $this->pubRoot->getTournamentCollection();
        $this->assertTrue($tournamentsCollection instanceof TournamentCollection);
        $tournament = $tournamentsCollection->getTournament(6);
        $this->assertTrue($tournament instanceof Tournament);

        $teams = $tournament->getTournamentTeams();
        $this->assertTrue(is_array($teams) and count($teams) > 0);
        $team = $teams[131];
        $this->assertTrue($team instanceof TournamentTeam);
        $seriesCollection = $tournament->getTournamentSeriesCollection();
        $this->assertTrue($seriesCollection instanceof TournamentSeriesCollection);
        $series = $seriesCollection->getContent()->items[0]->getSeries();
        $this->assertTrue($series instanceof TournamentSeries);

        $teamMemberCollection = $team->getTeamMemberCollection();
        $this->assertTrue($teamMemberCollection instanceof TournamentTeamMemberCollection);
        $teamMatches = $team->getTournamentMatches();
        $match = $teamMatches[0];
        $this->assertTrue($match instanceof TournamentMatch);
        $matchBans = $match->getBans();
        $this->assertTrue($matchBans instanceof TournamentTypeBanCollection);
        $pilotStatsCollection = $match->getPilotStatsCollection();
        $this->assertTrue($pilotStatsCollection instanceof TournamentPilotStatsCollection);
        $pilotTournamentStats = reset($pilotStatsCollection->getContent()->items)->getPilotTournamentStats();
        $this->assertTrue($pilotTournamentStats instanceof TournamentPilotTournamentStats);
        $staticSceneData = $match->getStaticSceneData();
        $this->assertTrue($staticSceneData instanceof TournamentStaticSceneData);
        $firstReplayFrame = $match->getFirstReplayFrame();
        $this->assertTrue($firstReplayFrame instanceof TournamentRealtimeMatchFrame);
    }

    public function testWarResponses()
    {
        $wars = $this->pubRoot->getWarsCollection();
        $this->assertTrue($wars instanceof WarsCollection);
        $war = $wars->getWar(1);
        $this->assertTrue($war instanceof War);
        $warKillmails = $war->getKillmailsCollection();
        $this->assertTrue($warKillmails instanceof WarKillmails);

        $km = $this->client->getEndpointResponse(
            'https://public-crest.eveonline.com/killmails/30290604/787fb3714062f1700560d4a83ce32c67640b1797/'
        );
        $this->assertTrue($km instanceof Killmail);
    }
}
