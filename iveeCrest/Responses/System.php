<?php
/**
 * System class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/System.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * System represents responses of queries to the CREST system endpoint.
 * Inheritance: System -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/System.php
 */
class System extends EndpointItem
{
    /**
     * Sets content to object, re-indexing constellations by ID given in "href" stdClass object.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->planets as $planet) {
            $indexedItems[static::parseTrailingIdFromUrl($planet->href)] = $planet;
        }
        $this->content = $content;
        $this->content->planets = $indexedItems;
    }

    /**
     * Returns this systems constellation response
     *
     * @return \iveeCrest\Responses\Constellation
     */
    public function getConstellation()
    {
        return static::getLastClient()->getEndpointResponse($this->content->constellation->href);
    }

    /**
     * Returns a specific planet response.
     *
     * @param int $planetId of the planet
     *
     * @return \iveeCrest\Responses\Planet
     */
    public function getPlanet($planetId)
    {
        if (!isset($this->content->planets[$planetId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'PlanetId = ' . (int) $planetId . ' not found in system'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->planets[$planetId]->href);
    }

    /**
     * Returns all planet responses.
     *
     * @return \iveeCrest\Responses\Planet[]
     */
    public function getPlanets()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->content->planets as $item) {
            $hrefs[] = $item->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (Planet $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
