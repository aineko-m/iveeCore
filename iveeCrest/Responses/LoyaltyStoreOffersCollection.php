<?php
/**
 * LoyaltyStoreOffersCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/LoyaltyStoreOffersCollection/Alliance.php
 */

namespace iveeCrest\Responses;

/**
 * LoyaltyStoreOffersCollection represents responses of queries to the loyalty point store offers collection CREST
 * endpoint.
 * Inheritance: LoyaltyStoreOffersCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/LoyaltyStoreOffersCollection.php
 */
class LoyaltyStoreOffersCollection extends Collection
{
    /**
     * Sets content to object, re-indexing offers by item ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        //We use a two-dimensional array as the same item can appear in multiple offers
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->item->id][] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
