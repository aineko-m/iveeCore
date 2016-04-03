<?php
/**
 * Killmail class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Killmail.php
 */

namespace iveeCrest\Responses;

/**
 * Killmail represents responses of queries to the CREST killmail endpoint.
 * Inheritance: Killmail -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Killmail.php
 */
class Killmail extends EndpointItem
{
    /**
     * Returns the killmails ID.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->content->killID;
    }

    /**
     * Returns the solar system where the kill occurred.
     *
     * @return \iveeCrest\Responses\System
     */
    public function getSolarSystem()
    {
        return static::getLastClient()->getEndpointResponse($this->content->solarSystem->href);
    }
}
