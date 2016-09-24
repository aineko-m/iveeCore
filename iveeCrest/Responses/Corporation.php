<?php
/**
 * Corporation class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Corporation.php
 */

namespace iveeCrest\Responses;
use iveeCrest\Client;

/**
 * Corporation represents responses of queries to the corporation CREST endpoint.
 * Inheritance: Corporation -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Corporation.php
 */
class Corporation extends EndpointItem
{
    /**
     * Returns the structures collection.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\CorporationStructuresCollection
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getStructures(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->structures, 'publicData');
    }
}
