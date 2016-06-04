<?php
/**
 * MarketOrderCollectionSlim class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketOrderCollectionSlim.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * MarketOrderCollectionSlim represents CREST responses to queries to the "slim" market orders collection endpoint,
 * containing data about all the market orders for all market item in a specific region.
 * Inheritance: MarketOrderCollectionSlim -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketOrderCollectionSlim.php
 */
class MarketOrderCollectionSlim extends Collection
{
    /**
     * Sets content to object, re-indexing orders by type ID and order ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        if (isset($content->items)) {
            foreach ($content->items as $item) {
                $indexedItems[(int) $item->type][(int) $item->id] = $item;
            }
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }

    /**
     * Returns the gathered items of this collection endpoint. Note that calling this method can result in heavy memory
     * use when called for regions with many orders. Also note that there will be no attempt to cache the results as
     * this method should only be used in batch processing and also because the expiry is only 5 minutes anyway.
     *
     * @param \iveeCrest\Client $client to be used
     *
     * @return array
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when an authentication scope is requested for which
     * there is no refresh token.
     * @throws \iveeCrest\Exceptions\PaginationException when trying to gather NOT starting with the first page of the
     * Collection
     */
    public function gather(Client $client = null)
    {
        //this is a single page collection endpoint theres no need to to a real gather
        if ($this->getPageCount() <= 1) {
            return $this->getElements();
        }

        if (is_null($client)) {
            $client = static::getLastClient();
        }

        return $client->gather(
            $this,
            null,
            function (array &$ret, MarketOrderCollectionSlim $collection) {
                foreach ($collection->getElements() as $typeId => $orders) {
                    //if the type child array already exists, we have to manually merge
                    if (isset($ret[$typeId])) {
                        foreach ($orders as $id => $order) {
                            $ret[$typeId][$id] = $order;
                        }
                    } else {
                        $ret[$typeId] = $orders;
                    }
                }
            },
            null,
            false
        );
    }
}
