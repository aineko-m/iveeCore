<?php
/**
 * ItemGroup class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemGroup.php
 */

namespace iveeCrest\Responses;

/**
 * ItemGroup represents responses of queries to the item group CREST endpoint.
 * Inheritance: ItemGroup -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ItemGroup.php
 */
class ItemGroup extends EndpointItem
{
    /**
     * Sets content to object, re-indexing types array by ID parsed from provided href.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->types as $type) {
            $indexedItems[static::parseTrailingIdFromUrl($type->href)] = $type;
        }
        $this->content = $content;
        $this->content->types = $indexedItems;
    }

    /**
     * Returns this groups category response object
     *
     * @return \iveeCrest\Responses\ItemCategory
     */
    public function getCategory()
    {
        return static::getLastClient()->getEndpointResponse($this->content->category->href);
    }

    /**
     * Returns this groups item type response objects
     *
     * @return \iveeCrest\Responses\ItemType[]
     */
    public function getTypes()
    {
        $client = static::getLastClient();
        $hrefs = [];
        foreach ($this->content->types as $type) {
            $hrefs[] = $type->href;
        }

        $ret = [];
        $client->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (ItemType $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            },
            //CREST references unreachable types, so lets silently ignore the resulting errors for those
            function (BaseResponse $error) {
            }
        );
        return $ret;
    }
}
