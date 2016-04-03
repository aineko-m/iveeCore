<?php
/**
 * ContentItemsIdIndexer class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContentItemsIdIndexer.php
 */

namespace iveeCrest\Responses;

/**
 * ContentItemsIdIndexer is a trait for indexing response items.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContentItemsIdIndexer.php
 */
trait ContentItemsIdIndexer
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
            $indexedItems[(int) $item->id] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
