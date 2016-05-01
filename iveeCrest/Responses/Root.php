<?php
/**
 * Root class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Root.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * Root represents responses of queries to the base CREST URLs. The resulting object offers methods for calling the
 * referenced collections as well as for token "decoding" and verification.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Root.php
 */
class Root extends BaseResponse
{
    /**
     * Returns the first page of alliances collection.
     *
     * @return \iveeCrest\Responses\AllianceCollection
     */
    public function getAllianceCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->alliances->href);
    }

    /**
     * Returns the Character response for the character associated with the used access token.
     *
     * @param \iveeCrest\Client $client to be used, optional
     *
     * @return \iveeCrest\Responses\Character
     */
    public function getCharacter(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getTokenDecode()->getCharacter();
    }

    /**
     * Returns the first page of constellations collection.
     *
     * @return \iveeCrest\Responses\ConstellationCollection
     */
    public function getConstellationCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->constellations->href);
    }

    /**
     * Returns the first page of dogma attribute collection.
     *
     * @return \iveeCrest\Responses\DogmaAttributeCollection
     */
    public function getDogmaAttributeCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->dogma->attributes->href);
    }

    /**
     * Returns the first page of dogma effects collection.
     *
     * @return \iveeCrest\Responses\DogmaEffectCollection
     */
    public function getDogmaEffectCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->dogma->effects->href);
    }

    /**
     * Returns the first page of incrusion collection.
     *
     * @return \iveeCrest\Responses\IncursionCollection
     */
    public function getIncursionCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->incursions->href);
    }

    /**
     * Returns the first page of industry facility collection, the industry "slots" in each system.
     *
     * @return \iveeCrest\Responses\IndustryFacilityCollection
     */
    public function getIndustryFacilityCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->industry->facilities->href);
    }

    /**
     * Returns the first page of industry system collection, containing the indiustry indices.
     *
     * @return \iveeCrest\Responses\IndustrySystemCollection
     */
    public function getIndustrySystemCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->industry->systems->href);
    }

    /**
     * Returns the first page of insurance prices collection.
     *
     * @return \iveeCrest\Responses\InsurancePricesCollection
     */
    public function getInsurancePricesCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->insurancePrices->href);
    }

    /**
     * Returns the first page of item category collection.
     *
     * @return \iveeCrest\Responses\ItemCategoryCollection
     */
    public function getItemCategoryCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->itemCategories->href);
    }

    /**
     * Returns the first page of ItemGroups collection.
     *
     * @return \iveeCrest\Responses\ItemGroupsCollection
     */
    public function getItemGroupCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->itemGroups->href);
    }

    /**
     * Returns the first page of item types collection.
     *
     * @return \iveeCrest\Responses\ItemTypeCollection
     */
    public function getItemTypeCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->itemTypes->href);
    }

    /**
     * Returns the first page of market groups collection.
     *
     * @return \iveeCrest\Responses\MarketGroupCollection
     */
    public function getMarketGroupCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->marketGroups->href);
    }

    /**
     * Returns the first page of market types collection.
     *
     * @return \iveeCrest\Responses\MarketTypeCollection
     */
    public function getMarketTypeCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->marketTypes->href);
    }

    /**
     * Returns the first page of market type prices collection (the global adjusted and average prices).
     *
     * @return \iveeCrest\Responses\MarketTypePriceCollection
     */
    public function getMarketTypePriceCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->marketPrices->href);
    }

    /**
     * Returns the first page of NPC corporations collection.
     *
     * @return \iveeCrest\Responses\NPCCorporationsCollection
     */
    public function getNPCCorporationsCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->npcCorporations->href);
    }

    /**
     * Returns the first page of regions collection.
     *
     * @return \iveeCrest\Responses\RegionCollection
     */
    public function getRegionCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->regions->href);
    }

    /**
     * Returns the first page of sovereignty campaigns collection.
     *
     * @return \iveeCrest\Responses\SovereigntyCampaingCollection
     */
    public function getSovereigntyCampaingCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->sovereignty->campaigns->href);
    }

        /**
     * Returns the first page of sovereignty strcutures collection.
     *
     * @return \iveeCrest\Responses\SovereigntyStructureCollection
     */
    public function getSovereigntyStructureCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->sovereignty->structures->href);
    }

    /**
     * Returns the first page of systems collection.
     *
     * @return \iveeCrest\Responses\SystemCollection
     */
    public function getSystemCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->systems->href);
    }

    /**
     * Returns the first page of tournaments collection.
     *
     * @return \iveeCrest\Responses\TournamentCollection
     */
    public function getTournamentCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->tournaments->href);
    }

    /**
     * Returns the first page of wars collection.
     *
     * @return \iveeCrest\Responses\WarsCollection
     */
    public function getWarsCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->wars->href);
    }

    /**
     * Returns the CREST root endpoint.
     *
     * @return \iveeCrest\Responses\Root
     */
    public static function getRootEndpoint()
    {
        return static::getLastClient()->getRootEndpoint();
    }
}
