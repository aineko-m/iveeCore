<?php
/**
 * MarketGroupCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketGroupCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * MarketGroupCollection represents CREST responses to queries to the market group collection endpoint.
 * Inheritance: MarketGroupCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketGroupCollection.php
 */
class MarketGroupCollection extends Collection
{
    use ContentItemsHrefIndexer;

    /**
     * Returns a specific market group response.
     *
     * @param int $marketGroupId of the market group
     *
     * @return \iveeCrest\Responses\MarketGroup
     */
    public function getMarketGroup($marketGroupId)
    {
        $marketGroups = $this->gather();
        if (!isset($marketGroups[$marketGroupId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'MarketGroupID = ' . (int) $marketGroupId . ' not found in market groups collection'
            );
        }
        return static::getLastClient()->getEndpointResponse($marketGroups[$marketGroupId]->href);
    }

    /**
     * Returns all item group responses.
     *
     * @return \iveeCrest\Responses\ItemGroup[]
     */
    public function getMarketGroups()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->gather() as $item) {
            $hrefs[] = $item->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (ItemGroup $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
