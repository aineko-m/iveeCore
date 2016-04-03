<?php
/**
 * ItemTypeCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemTypeCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * ItemTypeCollection represents CREST responses to queries to the item type collection endpoint.
 * Inheritance: ItemTypeCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemTypeCollection.php
 */
class ItemTypeCollection extends Collection
{
    use ContentItemsHrefIndexer;

    /**
     * Returns a specific item type response.
     *
     * @param int $itemId of the item type
     *
     * @return \iveeCrest\Responses\ItemType
     */
    public function getItemType($itemId)
    {
        $items = $this->gather();
        if (!isset($items[$itemId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'ItemId = ' . (int) $itemId . ' not found in item types collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($items[$itemId]->href);
    }
}
