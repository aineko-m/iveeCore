<?php
/**
 * ItemCategory class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemCategory.php
 */

namespace iveeCrest\Responses;

/**
 * ItemCategory represents responses of queries to the item category CREST endpoint.
 * Inheritance: ItemCategory -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemCategory.php
 */
class ItemCategory extends EndpointItem
{
    /**
     * Sets content to object, re-indexing groups array by ID parsed from provided href.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->groups as $group) {
            $indexedItems[static::parseTrailingIdFromUrl($group->href)] = $group;
        }
        $this->content = $content;
        $this->content->groups = $indexedItems;
    }

    /**
     * Returns the item group objects belonging to this category
     *
     * @return \iveeCrest\Responses\ItemGroup[]
     */
    public function getGroups()
    {
        $client = static::getLastClient();
        $hrefs = [];
        foreach ($this->getContent()->groups as $group) {
            $hrefs[] = $group->href;
        }

        $ret = [];
        $client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (ItemGroup $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
