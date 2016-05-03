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
        $indexedPlanets = [];
        foreach ($content->planets as $planet) {
            if (isset($planet->moons)) {
                $indexedMoons = [];
                foreach ($planet->moons as $moon) {
                    $indexedMoons[static::parseTrailingIdFromUrl($moon->href)] = $moon;
                }
                $planet->moons = $indexedMoons;
            }
            $indexedPlanets[static::parseTrailingIdFromUrl($planet->href)] = $planet;
        }

        $indexedStargates = [];
        foreach ($content->stargates as $stargate) {
            $indexedStargates[static::parseTrailingIdFromUrl($stargate->href)] = $stargate;
        }
        $this->content = $content;
        $this->content->planets = $indexedPlanets;
        $this->content->stargates = $indexedStargates;
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
     * @throws \iveeCore\Exceptions\InvalidArgumentException when non-existant planet ID is requested
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

    /**
     * Returns a specific stargate response.
     *
     * @param int $stargateId of the stargate
     *
     * @return \iveeCrest\Responses\Stargate
     * @throws \iveeCore\Exceptions\InvalidArgumentException when non-existant stargate ID is requested
     */
    public function getStargate($stargateId)
    {
        if (!isset($this->content->stargates[$stargateId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'Stargate ID = ' . (int) $stargateId . ' not found in system'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->stargates[$stargateId]->href);
    }

    /**
     * Returns all stargate responses.
     *
     * @return \iveeCrest\Responses\Stargate[]
     */
    public function getStargates()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->content->stargates as $item) {
            $hrefs[] = $item->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (Stargate $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }

    /**
     * Returns all moon hrefs, keys by moon ID.
     *
     * @return string[]
     */
    protected function getMoonHrefs()
    {
        $hrefs = [];
        foreach ($this->content->planets as $planet) {
            if (isset($planet->moons)) {
                foreach ($planet->moons as $moonId => $moon) {
                    $hrefs[(int) $moonId] = $moon->href;
                }
            }
        }
        return $hrefs;
    }

    /**
     * Returns a specific Moon response.
     *
     * @param int $moonId of the moon
     *
     * @return \iveeCrest\Responses\Moon
     * @throws \iveeCore\Exceptions\InvalidArgumentException when non-existant moon ID is requested
     */
    public function getMoon($moonId)
    {
        $hrefs = $this->getMoonHrefs();
        if (!isset($hrefs[$moonId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'Moon ID = ' . (int) $moonId . ' not found in system'
            );
        }
        return static::getLastClient()->getEndpointResponse($hrefs[$moonId]);
    }

    /**
     * Returns all moon responses.
     *
     * @return \iveeCrest\Responses\Moon[]
     */
    public function getMoons()
    {
        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $this->getMoonHrefs(),
            false,
            function (Moon $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
