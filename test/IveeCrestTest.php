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
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php');

use iveeCore\Config, iveeCore\ICache, iveeCrest\Client, iveeCrest\EndpointHandler;

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
     * @var iveeCrest\Client $client
     */
    protected $client;

    /**
     * @var iveeCrest\EndpointHandler $handler
     */
    protected $handler;

    protected function setUp()
    {
        $this->client = new Client(
            Config::getAuthedCrestBaseUrl(),
            Config::getCrestClientId(),
            Config::getCrestClientSecret(),
            Config::getCrestClientRefreshToken(),
            Config::getUserAgent()
        );

        $this->handler = new EndpointHandler($this->client);
    }

    public function testClient()
    {
        $this->assertTrue($this->client->getRootEndpoint() instanceof stdClass);
        $this->assertTrue($this->client->getCache() instanceof ICache);
        $this->assertTrue($this->client->getOptions($this->client->getRootEndpointUrl()) instanceof stdClass);
    }

    public function testHandler()
    {
        $this->assertTrue($this->handler->verifyAccessToken() instanceof stdClass);
        $this->assertTrue($this->handler->tokenDecode() instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getMarketTypes()));
        $this->assertTrue(is_array($this->handler->getMarketTypeHrefs()));
        $this->assertTrue(is_array($this->handler->getRegions()));
        $this->assertTrue($this->handler->getRegion(10000002) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getConstellationHrefs()));
        $this->assertTrue($this->handler->getConstellation(21000316) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getSolarSystemHrefs()));
        $this->assertTrue($this->handler->getSolarSystem(31000054) instanceof stdClass);
        $this->assertTrue($this->handler->getMarketOrders(34, 10000002) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getMarketHistory(34, 10000002)));
        $this->assertTrue(is_array($this->handler->getIndustrySystems()));
        $this->assertTrue(is_array($this->handler->getMarketPrices()));
        $this->assertTrue(is_array($this->handler->getIndustryFacilities()));
        $this->assertTrue(is_array($this->handler->getItemGroups()));
        $this->assertTrue($this->handler->getItemGroup(40) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getAlliances()));
        $this->assertTrue($this->handler->getAlliance(99000652) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getItemTypes()));
        $this->assertTrue($this->handler->getType(35) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getItemCategories()));
        $this->assertTrue($this->handler->getItemCategory(5) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getMarketGroups()));
        $this->assertTrue($this->handler->getMarketGroup(2) instanceof stdClass);
        $this->assertTrue(is_array($this->handler->getMarketGroupTypes(2)));
        $this->assertTrue(is_array($this->handler->getTournaments()));
        $this->assertTrue($this->handler->getWar(1) instanceof stdClass);
        $this->assertTrue($this->handler->getKillmail(
            'http://public-crest.eveonline.com/killmails/30290604/787fb3714062f1700560d4a83ce32c67640b1797/'
            ) instanceof stdClass
        );
    }
}
