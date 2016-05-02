<?php
/**
 * CharacterOpportunitiesCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CharacterOpportunitiesCollection.php
 */

namespace iveeCrest\Responses;

/**
 * CharacterOpportunitiesCollection represents responses of queries to the character opportunities collection CREST
 * endpoint.
 * Inheritance: CharacterOpportunitiesCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CharacterOpportunitiesCollection.php
 */
class CharacterOpportunitiesCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by ID given in item.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->task->id] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
