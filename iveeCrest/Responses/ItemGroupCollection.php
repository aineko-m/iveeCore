<?php
/**
 * ItemGroupCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemGroupCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * ItemGroupCollection represents CREST responses to queries to the item group collection endpoint.
 * Inheritance: ItemGroupCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemGroupCollection.php
 */
class ItemGroupCollection extends Collection
{
    use ContentItemsHrefIndexer;

    /**
     * Returns a specific item group response.
     *
     * @param int $groupId of the group
     *
     * @return \iveeCrest\Responses\ItemGroup
     */
    public function getItemGroup($groupId)
    {
        $groups = $this->gather();
        if (!isset($groups[$groupId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'GroupID = ' . (int) $groupId . ' not found in item groups collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($groups[$groupId]->href);
    }

    /**
     * Returns all item group responses.
     *
     * @return \iveeCrest\Responses\ItemGroup[]
     */
    public function getItemGroups()
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
