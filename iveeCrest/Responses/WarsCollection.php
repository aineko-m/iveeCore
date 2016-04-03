<?php
/**
 * WarsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/WarsCollection.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * WarsCollection represents responses of queries to the wars collection CREST endpoint.
 * Inheritance: WarsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/WarsCollection.php
 */
class WarsCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns all wars. Using this method is not recommended. The number of wars is in the hundreds of thousands, and
     * the result exceeds the default maximum cacheable data size of memcached, which is 1MB. If you must use it,
     * consider increasing memcached max item size to 4MB by setting the option "-I 4m" in its configuration.
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
            function (array &$ret, WarsCollection $response) {
                foreach ($response->getElements() as $id => $war) {
                    $ret[$id] = $war;
                }
            },
            null,
            true,
            3600
        );
    }

    /**
     * Returns a specific war response.
     *
     * @param int $warId of the war
     *
     * @return \iveeCrest\Responses\War
     */
    public function getWar($warId)
    {
        //we don't use the wars collection here due to it's huge size
        $client = static::getLastClient();
        return $client->getEndpointResponse(
            $client->getPublicRootEndpoint()->getContent()->wars->href . (int) $warId . '/'
        );
    }
}
