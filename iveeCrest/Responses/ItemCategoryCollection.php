<?php
/**
 * ItemCategoryCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemCategoryCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * ItemCategoryCollection represents CREST responses to queries to the item category collection endpoint.
 * Inheritance: ItemCategoryCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemCategoryCollection.php
 */
class ItemCategoryCollection extends Collection
{
    use ContentItemsHrefIndexer;

    /**
     * Returns a specific item category response.
     *
     * @param int $categoryId of the category
     *
     * @return \iveeCrest\Responses\ItemCategory
     */
    public function getItemCategory($categoryId)
    {
        $categories = $this->gather();
        if (!isset($categories[$categoryId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'CategoryID = ' . (int) $categoryId . ' not found in item categories collection'
            );
        }
        return static::getLastClient()->getEndpointResponse($categories[$categoryId]->href);
    }

    /**
     * Returns all item category responses.
     *
     * @return \iveeCrest\Responses\ItemCategory[]
     */
    public function getItemCategories()
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
            function (ItemCategory $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
