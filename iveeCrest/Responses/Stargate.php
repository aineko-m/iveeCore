<?php
/**
 * Stargate class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Stargate.php
 */

namespace iveeCrest\Responses;

/**
 * Stargate represents responses of queries to the CREST stargate endpoint.
 * Inheritance: Stargate -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Stargate.php
 */
class Stargate extends EndpointItem
{
    /**
     * Returns the response for the system this stargate is in.
     *
     * @return \iveeCrest\Responses\System
     */
    public function getSystem()
    {
        return static::getLastClient()->getEndpointResponse($this->content->system->href);
    }
}
