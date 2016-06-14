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
use iveeCrest\Responses\CharacterOpportunitiesCollection;
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
use iveeCrest\Responses\InsurancePricesCollection;
use iveeCrest\Responses\ItemGroup;
use iveeCrest\Responses\ItemGroupCollection;
use iveeCrest\Responses\ItemCategory;
use iveeCrest\Responses\ItemCategoryCollection;
use iveeCrest\Responses\Killmail;
use iveeCrest\Responses\LoyaltyPointsCollection;
use iveeCrest\Responses\LoyaltyStoreOffersCollection;
use iveeCrest\Responses\MarketOrderCollection;
use iveeCrest\Responses\MarketOrderCollectionSlim;
use iveeCrest\Responses\MarketTypeHistoryCollection;
use iveeCrest\Responses\MarketTypePriceCollection;
use iveeCrest\Responses\MarketGroupCollection;
use iveeCrest\Responses\MarketGroup;
use iveeCrest\Responses\MarketTypeCollection;
use iveeCrest\Responses\Moon;
use iveeCrest\Responses\NPCCorporationsCollection;
use iveeCrest\Responses\OpportunityGroup;
use iveeCrest\Responses\OpportunityGroupsCollection;
use iveeCrest\Responses\OpportunityTasksCollection;
use iveeCrest\Responses\Options;
use iveeCrest\Responses\Planet;
use iveeCrest\Responses\RegionCollection;
use iveeCrest\Responses\Region;
use iveeCrest\Responses\Root;
use iveeCrest\Responses\SovCampaignsCollection;
use iveeCrest\Responses\SovStructureCollection;
use iveeCrest\Responses\Stargate;
use iveeCrest\Responses\Station;
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
 * The test covers key parts of iveeCrest. It is mainly used to aid during the development, but can also be used to
 * check the correct working of an iveeCrest installation. Currently the tests just call methods and check for returned
 * type.
 *
 * To run this test, you'll need to have PHPUnit installed. Expect the test to run for roughly a minute starting with a
 * cold cache.
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
     * @var \iveeCrest\Responses\Root $root
     */
    protected $root;

    protected function setUp()
    {
        $cacheClass = Config::getIveeClassName('Cache');
        $cacheClass::instance()->flushCache();
        $this->client = new Client;
        $this->root = $this->client->getRootEndpoint();
    }

    public function testClient()
    {
        $this->assertTrue($this->client->getCache() instanceof ICache);
        $or = $this->client->getOptions($this->client->getCrestBaseUrl());
        $this->assertTrue($or instanceof Options);
    }

    public function testRootResponses()
    {
        $root = $this->client->getRootEndpoint();
        $this->assertTrue($root instanceof Root);
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

            if ($this->client->hasAuthScope('characterLoyaltyPointsRead')) {
                $this->assertTrue($char->getLoyaltyPoints() instanceof LoyaltyPointsCollection);
            }

            if ($this->client->hasAuthScope('characterOpportunitiesRead')) {
                $this->assertTrue($char->getOpportunities() instanceof CharacterOpportunitiesCollection);
            }
        }
    }

    public function testInventoryResponses()
    {
        $itemGroupCollection = $this->root->getItemGroupCollection();
        $this->assertTrue($itemGroupCollection instanceof ItemGroupCollection);
        $itemGroup = $itemGroupCollection->getItemGroup(26);
        $this->assertTrue($itemGroup instanceof ItemGroup);
        $itemCategory = $itemGroup->getCategory();
        $this->assertTrue($itemCategory instanceof ItemCategory);
        $this->assertTrue($itemCategory->getGroups()[358] instanceof ItemGroup);

        $itemCategoryCollection = $this->root->getItemCategoryCollection();
        $this->assertTrue($itemCategoryCollection instanceof ItemCategoryCollection);
        $this->assertTrue($itemCategoryCollection->getItemCategory(65) instanceof ItemCategory);
        $this->assertTrue($itemCategoryCollection->getItemCategories()[23] instanceof ItemCategory);
    }

    public function testAllianceResponses()
    {
        $allianceCollection = $this->root->getAllianceCollection();
        $this->assertTrue($allianceCollection instanceof AllianceCollection);
        $gatheredAlliances = $allianceCollection->gather();
        $this->assertTrue(is_array($gatheredAlliances));
        $alliance = $allianceCollection->getAlliance(key($gatheredAlliances));
        $this->assertTrue($alliance instanceof Alliance);
    }

    public function testUniverseLocationResponses()
    {
        $regionCollection = $this->root->getRegionCollection();
        $this->assertTrue($regionCollection instanceof RegionCollection);
        $region = $regionCollection->getRegion(10000006);
        $this->assertTrue($region instanceof Region);
        $constellation = $region->getConstellation(20000081);
        $this->assertTrue($constellation instanceof Constellation);
        $system = $constellation->getSystem(30000559);
        $this->assertTrue($system instanceof System);
        $planet = $system->getPlanet(40034969);
        $this->assertTrue($planet instanceof Planet);
        $system->getPlanets();
        $moon = $system->getMoon(40034996);
        $this->assertTrue($moon instanceof Moon);
        $system->getMoons();
        $stargate = $system->getStargate(50000589);
        $this->assertTrue($stargate instanceof Stargate);
        $system->getStargates();
        $station = $this->root->getStation(60003760);
        $this->assertTrue($station instanceof Station);

        $systemCollection = $this->root->getSystemCollection();
        $this->assertTrue($systemCollection instanceof SystemCollection);
        $this->assertTrue($systemCollection->getSystem(30002576) instanceof System);
        $constellationCollection = $this->root->getConstellationCollection();
        $this->assertTrue($constellationCollection instanceof ConstellationCollection);
        $this->assertTrue($constellationCollection->getConstellation(20000246) instanceof Constellation);
    }

    public function testDogmaResponses()
    {
        $dogmaAttributeCollection = $this->root->getDogmaAttributeCollection();
        $this->assertTrue($dogmaAttributeCollection instanceof DogmaAttributeCollection);
        $this->assertTrue($dogmaAttributeCollection->getDogmaAttribute(4) instanceof DogmaAttribute);
        $dogmaEffectCollection = $this->root->getDogmaEffectCollection();
        $this->assertTrue($dogmaEffectCollection instanceof DogmaEffectCollection);
        $this->assertTrue($dogmaEffectCollection->getDogmaEffect(4) instanceof DogmaEffect);
    }

    public function testIndustryResponses()
    {
        $marketTypePricesCollection = $this->root->getMarketTypePriceCollection();
        $this->assertTrue($marketTypePricesCollection instanceof MarketTypePriceCollection);
        $industryFacilityCollection = $this->root->getIndustryFacilityCollection();
        $this->assertTrue($industryFacilityCollection instanceof IndustryFacilityCollection);
        $industrySystemCollection = $this->root->getIndustrySystemCollection();
        $this->assertTrue($industrySystemCollection instanceof IndustrySystemCollection);
    }

    public function testInsuranceResponses()
    {
        $insurancePricesCollection = $this->root->getInsurancePricesCollection();
        $this->assertTrue($insurancePricesCollection instanceof InsurancePricesCollection);
    }

    public function testLoyaltyPointResponses()
    {
        $npcCorporationsCollection = $this->root->getNPCCorporationsCollection();
        $this->assertTrue($npcCorporationsCollection instanceof NPCCorporationsCollection);
        $loyaltyPointStoreOffersColleciont = $npcCorporationsCollection->getCorporationLoyaltyStore(1000035);
        $this->assertTrue($loyaltyPointStoreOffersColleciont instanceof LoyaltyStoreOffersCollection);
    }

    public function testMarketResponses()
    {
        $marketGroupCollection = $this->root->getMarketGroupCollection();
        $this->assertTrue($marketGroupCollection instanceof MarketGroupCollection);
        $marketGroup = $marketGroupCollection->getMarketGroup(4);
        $this->assertTrue($marketGroup instanceof MarketGroup);
        $marketTypesCollection = $marketGroup->getMarketTypeCollection();
        $this->assertTrue($marketTypesCollection instanceof MarketTypeCollection);

        $region = $this->root->getRegionCollection()->getRegion(10000002);
        $marketOrders = $region->getMarketOrders(34);
        $this->assertTrue($marketOrders instanceof MarketOrderCollection);
        $history = $region->getMarketHistory(34);
        $this->assertTrue($history instanceof MarketTypeHistoryCollection);

        $multiOrders = [];
        $region->getMultiMarketOrders(
            [35, 36],
            function (MarketOrderCollection $response) use (&$multiOrders) {
                $multiOrders[] = $response;
            }
        );
        $this->assertTrue(count($multiOrders) == 2); //two responses expected

        $multiHistory = [];
        $region->getMultiMarketHistory(
            [35, 36],
            function (MarketTypeHistoryCollection $response) use (&$multiHistory) {
                $multiHistory[] = $response;
            }
        );
        $this->assertTrue(count($multiHistory) == 2);
        //get just the first page of the collection to avoid memory problems
        $marketOrdersSlim = $region->getAllMarketOrdersCollection();
        $this->assertTrue($marketOrdersSlim instanceof MarketOrderCollectionSlim);
    }

    public function testOpportunityResponses()
    {
        $opportunityTasksCollection = $this->root->getOpportunityTasksCollection();
        $this->assertTrue($opportunityTasksCollection instanceof OpportunityTasksCollection);
        $opportunityGroupsCollection = $this->root->getOpportunityGroupsCollection();
        $this->assertTrue($opportunityGroupsCollection instanceof OpportunityGroupsCollection);
        $items = $opportunityGroupsCollection->gather();
        $opportunityGroup = $this->client->getEndpointResponse($items[key($items)]->achievementTasks[0]->href);
        $this->assertTrue($opportunityGroup instanceof OpportunityGroup);
    }

    public function testIncursionResponses()
    {
        $incursionCollection = $this->root->getIncursionCollection();
        $this->assertTrue($incursionCollection instanceof IncursionCollection);
        $this->assertTrue(is_array($incursionCollection->gather()));
    }

    public function testSovResponses()
    {
        $sovCampaigns = $this->root->getSovereigntyCampaingCollection();
        $this->assertTrue($sovCampaigns instanceof SovCampaignsCollection);
        $sovStructures = $this->root->getSovereigntyStructureCollection();
        $this->assertTrue($sovStructures instanceof SovStructureCollection);
    }

    public function testTournamentResponses()
    {
        $tournamentsCollection = $this->root->getTournamentCollection();
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
        //static scene data is apparently broken on CCPs side atm
//        $staticSceneData = $match->getStaticSceneData();
//        $this->assertTrue($staticSceneData instanceof TournamentStaticSceneData);
//        $firstReplayFrame = $match->getFirstReplayFrame();
//        $this->assertTrue($firstReplayFrame instanceof TournamentRealtimeMatchFrame);
    }

    public function testWarResponses()
    {
        $wars = $this->root->getWarsCollection();
        $this->assertTrue($wars instanceof WarsCollection);
        $war = $wars->getWar(1);
        $this->assertTrue($war instanceof War);
        $warKillmails = $war->getKillmailsCollection();
        $this->assertTrue($warKillmails instanceof WarKillmails);

        $km = $this->client->getEndpointResponse(
            'https://crest-tq.eveonline.com/killmails/30290604/787fb3714062f1700560d4a83ce32c67640b1797/'
        );
        $this->assertTrue($km instanceof Killmail);
    }
}
