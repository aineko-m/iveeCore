<?php
/**
 * LoyaltyPointsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/LoyaltyPointsCollection/Alliance.php
 */

namespace iveeCrest\Responses;

/**
 * LoyaltyPointsCollection represents responses of queries to the character loyalty points collection CREST endpoint.
 * Inheritance: LoyaltyPointsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/LoyaltyPointsCollection.php
 */
class LoyaltyPointsCollection extends Collection
{
    /**
     * Sets content to object, re-indexing loyalty points by corporation ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->corporation->id] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
