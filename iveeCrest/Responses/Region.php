<?php
/**
 * Region class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Region.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * Region represents responses of queries to the CREST region endpoint.
 * Inheritance: Region -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Region.php
 */
class Region extends EndpointItem
{
    /**
     * Sets content to object, re-indexing constellations by ID given in "href" stdClass object.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->constellations as $constellation) {
            $indexedItems[(int) $constellation->id] = $constellation;
        }
        $this->content = $content;
        $this->content->constellations = $indexedItems;
    }

    /**
     * Returns a specific constellation response.
     *
     * @param int $constellationId of the constellation
     *
     * @return \iveeCrest\Responses\Constellation
     */
    public function getConstellation($constellationId)
    {
        if (!isset($this->content->constellations[$constellationId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'ConstellationID = ' . (int) $constellationId . ' not found in region'
            );
        }

        return static::getLastClient()->getEndpointResponse($this->content->constellations[$constellationId]->href);
    }

    /**
     * Returns all constellation responses.
     *
     * @return \iveeCrest\Responses\Constellation[]
     */
    public function getConstellations()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->content->constellations as $item) {
            $hrefs[] = $item->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (Constellation $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }

    /**
     * Gets market history for a type in this region.
     *
     * @param int $typeId of the item type
     * @param bool $cache whether the result of this call should be cached. If another caching layer is present, caching
     * in this call should be disabled
     *
     * @return \iveeCrest\Responses\MarketTypeHistoryCollection
     */
    public function getMarketHistory($typeId, $cache = true)
    {
        $client = static::getLastClient();
        return $client->getEndpointResponse(
            //Here we have to construct the URL because there's no navigable way to reach this data via CREST hrefs
            $client->getCrestBaseUrl() . 'market/' . $this->getId() . '/types/' . (int) $typeId . '/history/',
            false,
            null,
            $cache
        );
    }

    /**
     * Gets market history for multiple types in this region asynchronously, using the passed callback functions for
     * processing CREST responses.
     *
     * @param array $typeIds of the item types to be queried
     * @param callable $callback a function expecting one iveeCrest\Responses\MarketTypeHistoryCollection object as
     * argument, called for every successful response
     * @param callable $errCallback a function expecting one iveeCrest\Responses\BaseResponse object as argument, called
     * for every non-successful response
     * @param bool $cache if the individual query responses should be cached
     *
     * @return void
     */
    public function getMultiMarketHistory(
        array $typeIds,
        callable $callback,
        callable $errCallback = null,
        $cache = true
    ) {
        $client = static::getLastClient();
        $hrefs = [];
        foreach (array_unique($typeIds) as $typeId) {
            //Here we have to construct the URL because there's no navigable way to reach this data via CREST hrefs
            $hrefs[] = $client->getCrestBaseUrl() . 'market/' . $this->getId() . '/types/' . (int) $typeId
                . '/history/';
        }

        //run the async queries
        $client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            $callback,
            $errCallback,
            null,
            $cache
        );
    }

    /**
     * Gets orders for a type in this region.
     *
     * @param string $marketTypeHref the href of the market type
     *
     * @return \iveeCrest\Responses\MarketOrderCollection
     */
    public function getMarketOrdersByHref($marketTypeHref)
    {
        $client = static::getLastClient();
        return $client->getEndpointResponse($this->content->marketOrders->href . '?type=' . $marketTypeHref);
    }

    /**
     * Gets sell orders for a type in this region.
     *
     * @param string $marketTypeHref the href of the market type
     *
     * @return \iveeCrest\Responses\MarketOrderCollection
     */
    public function getMarketSellOrdersByHref($marketTypeHref)
    {
        $client = static::getLastClient();
        return $client->getEndpointResponse($this->content->marketSellOrders->href . '?type=' . $marketTypeHref);
    }

    /**
     * Gets buy orders for a type in this region.
     *
     * @param string $marketTypeHref the href of the market type
     *
     * @return \iveeCrest\Responses\MarketOrderCollection
     */
    public function getMarketBuyOrdersByHref($marketTypeHref)
    {
        $client = static::getLastClient();
        return $client->getEndpointResponse($this->content->marketBuyOrders->href . '?type=' . $marketTypeHref);
    }

    /**
     * Gets market orders for a type in this region.
     *
     * @param int $typeId of the market type
     *
     * @return \iveeCrest\Responses\MarketOrderCollection
     */
    public function getMarketOrders($typeId)
    {
        $marketTypeHrefs = static::getLastClient()->getRootEndpoint()->getMarketTypeCollection()->gatherHrefs();
        if (!isset($marketTypeHrefs[$typeId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('TypeID = ' . (int) $typeId . ' not found in market types');
        }
        return $this->getMarketOrdersByHref($marketTypeHrefs[$typeId]);
        ;
    }

    /**
     * Gets market orders for multiple types in this region asynchronously, using the passed callback functions for
     * processing CREST responses. If the data for each type/region is requested less frequently than the 5 minute cache
     * TTL, it is advisable to disable caching via argument. Otherwise it will cause unnecessary cache trashing.
     *
     * @param array $typeIds of the item types to be queried
     * @param callable $callback a function expecting one iveeCrest\Responses\MarketOrderCollection object as argument,
     * called for every successful response
     * @param callable $errCallback a function expecting one iveeCrest\Responses\BaseResponse object as argument, called
     * for every non-successful response
     * @param bool $cache if the individual query responses should be cached
     *
     * @return void
     */
    public function getMultiMarketOrders(
        array $typeIds,
        callable $callback,
        callable $errCallback = null,
        $cache = true
    ) {
        $client = static::getLastClient();
        $marketTypeHrefs = $client->getRootEndpoint()->getMarketTypeCollection()->gatherHrefs();
        $hrefs = [];
        foreach (array_unique($typeIds) as $typeId) {
            if (!isset($marketTypeHrefs[$typeId])) {
                $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
                throw new $invalidArgumentExceptionClass('TypeID=' . (int) $typeId . ' not found in market types');
            }
            $hrefs[] = $this->content->marketOrders->href . '?type=' . $marketTypeHrefs[$typeId];
        }

        //run the async queries
        $client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            $callback,
            $errCallback,
            null,
            $cache
        );
    }
}
