<?php
/**
 * WarKillmails class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/WarKillmails.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * WarKillmails represents responses of queries to the war killmails collection CREST endpoint.
 * Inheritance: WarKillmails -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/WarKillmails.php
 */
class WarKillmails extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns the gathered items of this collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used, optional
     *
     * @return \stdClass[]
     * @throws \iveeCrest\Exceptions\PaginationException when this object is not the first page of the Collection
     */
    public function gather(Client $client = null)
    {
        //this is a single page collection endpoint theres no need to to a real gather
        if ($this->getPageCount() == 1) {
            return $this->getElements();
        }

        if (is_null($client)) {
            $client = static::getLastClient();
        }

        return $client->gather(
            $this,
            null,
            function (array &$ret, WarKillmails $response) {
                foreach ($response->getElements() as $id => $km) {
                    $ret[$id] = $km;
                }
            },
            null,
            true,
            3600
        );
    }

    /**
     * Returns all killmail responses.
     *
     * @return \iveeCrest\Responses\Killmail[]
     */
    public function getKillmails()
    {
        $hrefs = [];
        foreach ($this->gather() as $kmData) {
            $hrefs[] = $kmData->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (Killmail $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
