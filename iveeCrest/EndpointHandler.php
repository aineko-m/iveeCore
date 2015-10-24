<?php
/**
 * EndpointHandler class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/EndpointHandler.php
 */

namespace iveeCrest;
use iveeCore\Config;

/**
 * EndpointHandler implements methods for handling specific endpoints. All endpoints reachable from CREST root are
 * supported, plus a few of the endpoints deeper in the tree.
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/EndpointHandler.php
 */
class EndpointHandler
{
    //the used representations
    const ALLIANCE_COLLECTION_REPRESENTATION            = 'vnd.ccp.eve.AllianceCollection-v1+json';
    const ALLIANCE_REPRESENTATION                       = 'vnd.ccp.eve.Alliance-v1+json';
    const CONSTELLATION_REPRESENTATION                  = 'vnd.ccp.eve.Constellation-v1+json';
    const INCURSION_COLLECTION_REPRESENTATION           = 'vnd.ccp.eve.IncursionCollection-v1+json';
    const INDUSTRY_SYSTEM_COLLECTION_REPRESENTATION     = 'vnd.ccp.eve.IndustrySystemCollection-v1';
    const INDUSTRY_FACILITY_COLLECTION_REPRESENTATION   = 'vnd.ccp.eve.IndustryFacilityCollection-v1';
    const ITEM_CATEGORY_COLLECTION_REPRESENTATION       = 'vnd.ccp.eve.ItemCategoryCollection-v1+json';
    const ITEM_CATEGORY_REPRESENTATION                  = 'vnd.ccp.eve.ItemCategory-v1+json';
    const ITEM_GROUP_COLLECTION_REPRESENTATION          = 'vnd.ccp.eve.ItemGroupCollection-v1+json';
    const ITEM_GROUP_REPRESENTATION                     = 'vnd.ccp.eve.ItemGroup-v1+json';
    const ITEM_TYPE_COLLECTION_REPRESENTATION           = 'vnd.ccp.eve.ItemTypeCollection-v1';
    const ITEM_TYPE_REPRESENTATION                      = 'vnd.ccp.eve.ItemType-v2+json';
    const KILLMAIL_REPRESENTATION                       = 'vnd.ccp.eve.Killmail-v1+json';
    const MARKET_GROUP_COLLECTION_REPRESENTATION        = 'vnd.ccp.eve.MarketGroupCollection-v1+json';
    const MARKET_GROUP_REPRESENTATION                   = 'vnd.ccp.eve.MarketGroup-v1+json';
    const MARKET_ORDER_COLLECTION_REPRESENTATION        = 'vnd.ccp.eve.MarketOrderCollection-v1+json';
    const MARKET_TYPE_COLECTION_REPRESENTATION          = 'vnd.ccp.eve.MarketTypeCollection-v1+json';
    const MARKET_TYPE_HISTORY_COLLECTION_REPRESENTATION = 'vnd.ccp.eve.MarketTypeHistoryCollection-v1+json';
    const MARKET_TYPE_PRICE_COLLECTION_REPRESENTATION   = 'vnd.ccp.eve.MarketTypePriceCollection-v1';
    const PLANET_REPRESENTATION                         = 'vnd.ccp.eve.Planet-v1+json';
    const REGION_COLLECTION_REPRESENTATION              = 'vnd.ccp.eve.RegionCollection-v1+json';
    const REGION_REPRESENTATION                         = 'vnd.ccp.eve.Region-v1+json';
    const SOV_CAMPAIGNS_COLLECTION_REPRESENTATION       = 'vnd.ccp.eve.SovCampaignsCollection-v1+json';
    const SOV_STRUCTURE_COLLECTION_REPRESENTATION       = 'vnd.ccp.eve.SovStructureCollection-v1+json';
    const SYSTEM_REPRESENTATION                         = 'vnd.ccp.eve.System-v1+json';
    const TOKEN_DECODE_REPRESENTATION                   = 'vnd.ccp.eve.TokenDecode-v1+json';
    const TOURNAMENT_COLLECTION_REPRESENTATION          = 'vnd.ccp.eve.TournamentCollection-v1+json';
    const WARS_COLLECTION_REPRESENTATION                = 'vnd.ccp.eve.WarsCollection-v1+json';
    const WAR_REPRESENTATION                            = 'vnd.ccp.eve.War-v1+json';

    /**
     * @var \iveeCrest\Client $client for CREST
     */
    protected $client;

    /**
     * @var array $marketTypeHrefs holds hrefs for market types.
     */
    protected $marketTypeHrefs;

    /**
     * Constructs an EndpointHandler.
     *
     * @param \iveeCrest\Client $client to be used
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Parses a trailing ID from a given URL. This is useful to index data returned from CREST which doesn't contain the
     * ID of the object it refers to, but provides a href which contains it.
     *
     * @param string $url to be parsed
     *
     * @return int
     */
    public static function parseTrailingIdFromUrl($url)
    {
        $trimmed = rtrim($url, '/');
        return (int) substr($trimmed, strrpos($trimmed, '/') + 1);
    }

    
    /**
     * Verifies the access token, returning data about the character linked to it.
     *
     * @param string $authScope the CREST authentication scope whose access token shall be verified
     * @param bool $cache whether the response of the verification should be cached
     *
     * @return stdClass
     */
    public function verifyAccessToken($authScope = 'publicData', $cache = false)
    {
        return $this->client->getEndpoint(
            //no path to the verify endpoint is exposed, so we need construct it
            str_replace('token', 'verify', $this->client->getAuthedRootEndpoint()->authEndpoint->href),
            $authScope,
            null,
            $cache
        );
    }

    /**
     * "decodes" the access token, returning a href to the character endpoint.
     *
     * @param string $authScope the CREST authentication scope whose access token shall be "decoded"
     *
     * @return stdClass
     */
    public function tokenDecode($authScope = 'publicData')
    {
        return $this->client->getEndpoint(
            $this->client->getAuthedRootEndpoint()->decode->href,
            $authScope,
            static::TOKEN_DECODE_REPRESENTATION
        );
    }

    /**
     * Gathers the marketTypes endpoint.
     *
     * @return array
     */
    public function getMarketTypes()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->marketTypes->href,
            false,
            function (\stdClass $marketType) {
                return (int) $marketType->type->id;
            },
            null,
            static::MARKET_TYPE_COLECTION_REPRESENTATION
        );
    }

    /**
     * Gathers the market types hrefs.
     *
     * @return array in the form typeId => href
     */
    public function getMarketTypeHrefs()
    {
        if (!isset($this->marketTypeHrefs)) {
            //gather all the hrefs into one compact array, indexed by item id
            $this->marketTypeHrefs = $this->client->gather(
                $this->client->getPublicRootEndpoint()->marketTypes->href,
                false,
                function (\stdClass $marketType) {
                    return (int) $marketType->type->id;
                },
                function ($marketType) {
                    return $marketType->type->href;
                },
                static::MARKET_TYPE_COLECTION_REPRESENTATION,
                true,
                86400,
                'hrefsOnly'
            );
        }
        return $this->marketTypeHrefs;
    }

    /**
     * Gathers the regions endpoint.
     *
     * @return array
     */
    public function getRegions()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->regions->href,
            false,
            function (\stdClass $region) {
                return static::parseTrailingIdFromUrl($region->href);
            },
            null,
            static::REGION_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for a region.
     *
     * @param int $regionId of the region
     *
     * @return stdClass
     */
    public function getRegion($regionId)
    {
        $regions = $this->getRegions();
        if (!isset($regions[$regionId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('RegionID=' . (int) $regionId . ' not found in regions');
        }

        return $this->client->getEndpoint(
            $regions[$regionId]->href,
            false,
            static::REGION_REPRESENTATION
        );
    }

    /**
     * Returns an array with all constellation hrefs. Note that this method will cause a CREST call for every region if
     * not already cached.
     *
     * @return array in the form constellationId => href
     */
    public function getConstellationHrefs()
    {
        $dataKey = 'gathered:constellationHrefs';
        try {
            $dataObj = $this->client->getCache()->getItem($dataKey);
        } catch (\iveeCore\Exceptions\KeyNotFoundInCacheException $e){
            //get region hrefs
            $hrefs = [];
            foreach ($this->getRegions() as $region)
                $hrefs[] = $region->href;

            //instantiate Response object
            $cacheableArrayClass = Config::getIveeClassName('CacheableArray');
            $dataObj = new $cacheableArrayClass($dataKey, time() + 24 * 3600);

            //run the async queries
            $this->client->asyncGetMultiEndpointResponses(
                $hrefs,
                false,
                function (Response $res) use ($dataObj) {
                    foreach ($res->content->constellations as $constellation)
                        $dataObj->data[EndpointHandler::parseTrailingIdFromUrl($constellation->href)]
                            = $constellation->href;
                },
                null,
                static::REGION_REPRESENTATION
            );
            $this->client->getCache()->setItem($dataObj);
        }
        return $dataObj->data;
    }

    /**
     * Gets the endpoint for a constellation.
     *
     * @param int $constellationId of the constellation
     *
     * @return stdClass
     */
    public function getConstellation($constellationId)
    {
        $constellations = $this->getConstellationHrefs();
        if (!isset($constellations[$constellationId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'ConstellationID=' . (int) $constellationId . ' not found in constellations'
            );
        }

        return $this->client->getEndpoint(
            $constellations[$constellationId],
            false,
            static::CONSTELLATION_REPRESENTATION
        );
    }

    /**
     * Returns an array with all solar system hrefs. When response time is critical, using this call is not recommended
     * due to it causing over 1100 calls to CREST when not already cached.
     *
     * @return array in the form solarSystemId => href
     */
    public function getSolarSystemHrefs()
    {
        $dataKey = 'gathered:solarSystemHrefs';
        try {
            $dataObj = $this->client->getCache()->getItem($dataKey);
        } catch (\iveeCore\Exceptions\KeyNotFoundInCacheException $e) {
            //instantiate data object
            $cacheableArrayClass = Config::getIveeClassName('CacheableArray');
            $dataObj = new $cacheableArrayClass($dataKey, time() + 24 * 3600);

            //run the async queries
            $this->client->asyncGetMultiEndpointResponses(
                $this->getConstellationHrefs(),
                false,
                function (Response $res) use ($dataObj) {
                    foreach ($res->content->systems as $system)
                        $dataObj->data[EndpointHandler::parseTrailingIdFromUrl($system->href)] = $system->href;
                },
                null,
                static::CONSTELLATION_REPRESENTATION
            );
            $this->client->getCache()->setItem($dataObj);
        }
        return $dataObj->data;
    }

    /**
     * Gets the endpoint for a solar system.
     *
     * @param int $systemId of the solar system
     *
     * @return stdClass
     */
    public function getSolarSystem($systemId)
    {
        return $this->client->getEndpoint(
            //Here we intentionally disregard CREST principles by constructing the URL as the official alternative is 
            //impracticable by virtue of requiring over a thousand calls to the constellation endpoint
            $this->client->getPublicCrestBaseUrl() . '/solarsystems/' . (int) $systemId . '/',
            false,
            static::SYSTEM_REPRESENTATION
        );
    }

    /**
     * Gets buy and sell orders for a type in a region.
     *
     * @param int $typeId of the item type
     * @param int $regionId of the region
     *
     * @return stdClass
     */
    public function getMarketOrders($typeId, $regionId)
    {
        $region = $this->getRegion($regionId);
        $marketTypeHrefs = $this->getMarketTypeHrefs();
        if (!isset($marketTypeHrefs[$typeId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('TypeID=' . (int) $typeId . ' not found in market types');
        }

        $ret = new \stdClass();
        $ret->sellOrders = $this->client->gather(
            $region->marketSellOrders->href . '?type=' . $marketTypeHrefs[$typeId],
            false,
            null,
            null,
            static::MARKET_ORDER_COLLECTION_REPRESENTATION,
            false
        );
        $ret->buyOrders = $this->client->gather(
            $region->marketBuyOrders->href . '?type=' . $marketTypeHrefs[$typeId],
            false,
            null,
            null,
            static::MARKET_ORDER_COLLECTION_REPRESENTATION,
            false
        );

        return $ret;
    }

    /**
     * Gets market orders for multiple types in a region asynchronously, using the passed callback functions for
     * processing CREST responses. If the data for each type/region is requested less frequently than the 5 minute cache
     * TTL, it is advisable to disable caching via argument. Otherwise it will cause unnecessary cache trashing.
     *
     * @param array $typeIds of the item types to be queried
     * @param int $regionId of the region to be queried
     * @param callable $callback a function expecting one iveeCrest\Response object as argument, called for every
     * successful response
     * @param callable $errCallback a function expecting one iveeCrest\Response object as argument, called for every
     * non-successful response
     * @param bool $cache if the individual query Responses should be cached
     *
     * @return void
     */
    public function getMultiMarketOrders(array $typeIds, $regionId, callable $callback, callable $errCallback = null,
        $cache = true
    ) {
        //check for wormhole regions
        if ($regionId > 11000000) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass("Invalid regionId. Wormhole regions have no market.");
        }

        //get the necessary hrefs
        $region = $this->getRegion($regionId);
        $marketTypeHrefs = $this->getMarketTypeHrefs();
        $hrefs = [];
        foreach (array_unique($typeIds) as $typeId) {
            if (!isset($marketTypeHrefs[$typeId])) {
                $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
                throw new $invalidArgumentExceptionClass('TypeID=' . (int) $typeId . ' not found in market types');
            }
            $hrefs[] = $region->marketSellOrders->href . '?type=' . $marketTypeHrefs[$typeId];
            $hrefs[] = $region->marketBuyOrders->href  . '?type=' . $marketTypeHrefs[$typeId];
        }

        //run the async queries
        $this->client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            $callback,
            $errCallback,
            static::MARKET_ORDER_COLLECTION_REPRESENTATION,
            $cache
        );
    }

    /**
     * Gets market history for a type in a region.
     *
     * @param int $typeId of the item type
     * @param int $regionId of the region
     * @param bool $cache whether the result of this call should be cached. If another caching layer is present, caching
     * in this call should be disabled
     *
     * @return array indexed by the midnight timestamp of each day
     */
    public function getMarketHistory($typeId, $regionId, $cache = true)
    {
        $ttl = mktime(0, 5, 0) - time();
        return $this->client->gather(
            //Here we have to construct the URL because there's no navigable way to reach this data from CREST root
            $this->client->getPublicCrestBaseUrl() . 'market/' . (int) $regionId . '/types/' . (int) $typeId
            . '/history/',
            false,
            function (\stdClass $history) {
                return strtotime($history->date);
            },
            null,
            static::MARKET_TYPE_HISTORY_COLLECTION_REPRESENTATION,
            $cache,
            $ttl > 0 ? $ttl : $ttl + 24 * 3600 //time cache TTL to 5 minutes past midnight
        );
    }

    /**
     * Gets market history for multiple types in a region asynchronously, using the passed callback functions for
     * processing CREST responses. If the market history for each type/region is only called once per day (for instance
     * when persisted in a DB), it is advisable to disable caching via argument. Otherwise it can quickly overflow the
     * cache.
     *
     * @param array $typeIds of the item types
     * @param int $regionId of the region
     * @param callable $callback a function expecting one iveeCrest\Response object as argument, called for every
     * successful response
     * @param callable $errCallback a function expecting one iveeCrest\Response object as argument, called for every
     * non-successful response
     * @param bool $cache if the individual query Responses should be cached
     *
     * @return void
     */
    public function getMultiMarketHistory(array $typeIds, $regionId, callable $callback, callable $errCallback = null,
        $cache = true
    ) {
        //check for wormhole regions
        if ($regionId > 11000000) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass("Invalid regionId. Wormhole regions have no market.");
        }

        //Here we have to construct the URLs because there's no navigable way to reach this data from CREST root
        $hrefs = [];
        $rootUrl = $this->client->getPublicCrestBaseUrl();
        foreach (array_unique($typeIds) as $typeId)
            $hrefs[] = $rootUrl . 'market/' . (int) $regionId . '/types/' . (int) $typeId . '/history/';

        //run the async queries
        $this->client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            $callback,
            $errCallback,
            static::MARKET_TYPE_HISTORY_COLLECTION_REPRESENTATION,
            $cache
        );
    }

    /**
     * Gets the endpoint for a industry systems, containing industry indices.
     *
     * @param bool $cache whether the Response should be cached
     *
     * @return array
     */
    public function getIndustrySystems($cache = true)
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->industry->systems->href,
            false,
            function (\stdClass $system) {
                return (int) $system->solarSystem->id;
            },
            null,
            static::INDUSTRY_SYSTEM_COLLECTION_REPRESENTATION,
            $cache
        );
    }

    /**
     * Gets the endpoint for a market prices, containing global average and adjusted prices (not orders or history).
     *
     * @param bool $cache whether the Response should be cached
     *
     * @return array
     */
    public function getMarketPrices($cache = true)
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->marketPrices->href,
            false,
            function (\stdClass $price) {
                return (int) $price->type->id;
            },
            null,
            static::MARKET_TYPE_PRICE_COLLECTION_REPRESENTATION,
            $cache
        );
    }

    /**
     * Gets the endpoint for a industry facilities.
     *
     * @param bool $cache whether the Response should be cached
     *
     * @return array
     */
    public function getIndustryFacilities($cache = true)
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->industry->facilities->href,
            false,
            function (\stdClass $facility) {
                return (int) $facility->facilityID;
            },
            null,
            static::INDUSTRY_FACILITY_COLLECTION_REPRESENTATION,
            $cache
        );
    }

    /**
     * Gathers the item groups endpoint.
     *
     * @return array
     */
    public function getItemGroups()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->itemGroups->href,
            false,
            function (\stdClass $group) {
                return static::parseTrailingIdFromUrl($group->href);
            },
            null,
            static::ITEM_GROUP_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for an item group.
     *
     * @param int $groupId of the item group.
     *
     * @return stdClass
     */
    public function getItemGroup($groupId)
    {
        $groups = $this->getItemGroups();
        if (!isset($groups[$groupId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('GroupID=' . (int) $groupId . ' not found in groups');
        }

        return $this->client->getEndpoint(
            $groups[$groupId]->href,
            false,
            static::ITEM_GROUP_REPRESENTATION
        );
    }

    /**
     * Gathers the alliances endpoint.
     *
     * @return array
     */
    public function getAlliances()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->alliances->href,
            false,
            function (\stdClass $alliance) {
                return (int) $alliance->href->id;
            },
            function (\stdClass $alliance) {
                return $alliance->href;
            },
            static::ALLIANCE_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for an alliance.
     *
     * @param int $allianceId of the alliance
     *
     * @return stdClass
     */
    public function getAlliance($allianceId)
    {
        $alliances = $this->getAlliances();
        if (!isset($alliances[$allianceId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('AllianceID=' . (int) $allianceId . ' not found in alliances');
        }
        return $this->client->getEndpoint(
            $alliances[$allianceId]->href,
            false,
            static::ALLIANCE_REPRESENTATION
        );
    }

    /**
     * Gathers the item type endpoint.
     *
     * @return array
     */
    public function getItemTypes()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->itemTypes->href,
            false,
            function (\stdClass $type) {
                return static::parseTrailingIdFromUrl($type->href);
            },
            null,
            static::ITEM_TYPE_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for an item type.
     *
     * @param int $typeId of the type
     *
     * @return stdClass
     */
    public function getType($typeId)
    {
        $itemTypes = $this->getItemTypes();
        if (!isset($itemTypes[$typeId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('TypeID=' . (int) $typeId . ' not found in types');
        }

        return $this->client->getEndpoint(
            $itemTypes[$typeId]->href,
            false,
            static::ITEM_TYPE_REPRESENTATION
        );
    }

    /**
     * Gathers the item categories endpoint.
     *
     * @return array
     */
    public function getItemCategories()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->itemCategories->href,
            false,
            function (\stdClass $category) {
                return static::parseTrailingIdFromUrl($category->href);
            },
            null,
            static::ITEM_CATEGORY_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for an item category.
     *
     * @param int $categoryId of the category
     *
     * @return stdClass
     */
    public function getItemCategory($categoryId)
    {
        $categories = $this->getItemCategories();
        if (!isset($categories[$categoryId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('CategoryID=' . (int) $categoryId . ' not found in categories');
        }

        return $this->client->getEndpoint(
            $categories[$categoryId]->href,
            false,
            static::ITEM_CATEGORY_REPRESENTATION
        );
    }

    /**
     * Gathers the item market groups endpoint.
     *
     * @return array
     */
    public function getMarketGroups()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->marketGroups->href,
            false,
            function (\stdClass $group) {
                return static::parseTrailingIdFromUrl($group->href);
            },
            null,
            static::MARKET_GROUP_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for a market group.
     *
     * @param int $marketGroupId of the market group
     *
     * @return stdClass
     */
    public function getMarketGroup($marketGroupId)
    {
        $marketGroups = $this->getMarketGroups();
        if (!isset($marketGroups[$marketGroupId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'MarketGroupId=' . (int) $marketGroupId . ' not found in market groups'
            );
        }

        return $this->client->getEndpoint(
            $marketGroups[$marketGroupId]->href,
            false,
            static::MARKET_GROUP_REPRESENTATION
        );
    }

    /**
     * Gets the types for a market group.
     *
     * @param int $marketGroupId of the market group
     *
     * @return array
     */
    public function getMarketGroupTypes($marketGroupId)
    {
        return $this->client->gather(
            $this->getMarketGroup($marketGroupId)->types->href,
            false,
            function (\stdClass $type) {
                return (int) $type->type->id;
            },
            null,
            static::MARKET_TYPE_COLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the tournaments endpoint.
     *
     * @return array
     */
    public function getTournaments()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->tournaments->href,
            false,
            function (\stdClass $tournament) {
                return static::parseTrailingIdFromUrl($tournament->href->href);
            },
            function (\stdClass $tournament) {
                return $tournament->href;
            },
            static::TOURNAMENT_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Returns all wars. Using this method is not recommended. The number of wars is in the hundreds of thousands, and
     * the result exceeds the default maximum cacheable data size of memcached, which is 1MB. If you must use it,
     * consider increasing memcached max item size to 4MB by setting the option "-I 4m" in its configuration.
     *
     * @return array in the form id => href
     */
    public function getWarHrefs()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->wars->href,
            false,
            function (\stdClass $war) {
                return (int) $war->id;
            },
            function (\stdClass $war) {
                return $war->href;
            },
            static::WARS_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the endpoint for a war.
     *
     * @param int $warId of the war
     *
     * @return stdClass
     */
    public function getWar($warId)
    {
        //we don't use the wars collection here due to it's huge size
        return $this->client->getEndpoint(
            $this->client->getPublicRootEndpoint()->wars->href . (int) $warId . '/',
            false,
            static::WAR_REPRESENTATION
        );
    }

    /**
     * Gets the incursions endpoint.
     *
     * @return array
     */
    public function getIncursions()
    {
        return $this->client->getEndpoint(
            $this->client->getPublicRootEndpoint()->incursions->href,
            false,
            static::INCURSION_COLLECTION_REPRESENTATION
        );
    }

    /**
     * Gets the sovereignty campaigns endpoint.
     *
     * @return array
     */
    public function getSovCampaigns()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->sovereignty->campaigns->href,
            false,
            function (\stdClass $campaign) {
                return (int) $campaign->campaignID;
            },
            null,
            static::SOV_CAMPAIGNS_COLLECTION_REPRESENTATION,
            true,
            300
        );
    }

    /**
     * Gets the sovereignty structures endpoint.
     *
     * @return array
     */
    public function getSovStructures()
    {
        return $this->client->gather(
            $this->client->getPublicRootEndpoint()->sovereignty->structures->href,
            false,
            function (\stdClass $structure) {
                return (int) $structure->structureID;
            },
            null,
            static::SOV_STRUCTURE_COLLECTION_REPRESENTATION,
            true,
            300
        );
    }

    /**
     * Gets a Killmail. The domain of the passed href is adapted to the currently used CREST root, so all hrefs in the
     * response are relative to that.
     *
     * @param string $killmailHref in the form 
     * http://public-crest.eveonline.com/killmails/30290604/787fb3714062f1700560d4a83ce32c67640b1797/
     *
     * @return stdClass
     */
    public function getKillmail($killmailHref)
    {
        return $this->client->getEndpoint(
            $this->client->getPublicCrestBaseUrl() . ltrim(parse_url($killmailHref, PHP_URL_PATH), '/'),
            false,
            static::KILLMAIL_REPRESENTATION
        );
    }
}
