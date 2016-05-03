<?php
/**
 * Moon class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Moon.php
 */

namespace iveeCrest\Responses;

/**
 * Moon represents responses of queries to the CREST moon endpoint.
 * Inheritance: Moon -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Moon.php
 */
class Moon extends EndpointItem
{
    /**
     * Returns this type response for this moon
     *
     * @return \iveeCrest\Responses\ItemType
     */
    public function getType()
    {
        return static::getLastClient()->getEndpointResponse($this->content->type->href);
    }

    /**
     * Returns this system response for this moon
     *
     * @return \iveeCrest\Responses\System
     */
    public function getSystem()
    {
        return static::getLastClient()->getEndpointResponse($this->content->solarSystem->href);
    }
}
