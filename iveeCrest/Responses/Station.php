<?php
/**
 * Station class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Station.php
 */

namespace iveeCrest\Responses;

/**
 * Station represents responses of queries to the CREST station endpoint.
 * Inheritance: Station -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Station.php
 */
class Station extends EndpointItem
{
    /**
     * Returns this type response for this planet
     *
     * @return \iveeCrest\Responses\ItemType
     */
    public function getType()
    {
        return static::getLastClient()->getEndpointResponse($this->content->type->href);
    }

    /**
     * Returns this system response for this planet
     *
     * @return \iveeCrest\Responses\System
     */
    public function getSystem()
    {
        return static::getLastClient()->getEndpointResponse($this->content->system->href);
    }
}
