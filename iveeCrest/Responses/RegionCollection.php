<?php
/**
 * RegionCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/RegionCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * RegionCollection represents CREST responses to queries to the region collection endpoint.
 * Inheritance: RegionCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/RegionCollection.php
 */
class RegionCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific region response.
     *
     * @param int $regionId of the region
     *
     * @return \iveeCrest\Responses\Region
     */
    public function getRegion($regionId)
    {
        $regions = $this->gather();
        if (!isset($regions[$regionId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'RegionID = ' . (int) $regionId . ' not found in regions collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($regions[$regionId]->href);
    }

    /**
     * Returns all region responses.
     *
     * @return \iveeCrest\Responses\Region[]
     */
    public function getRegions()
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
            function (Region $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
