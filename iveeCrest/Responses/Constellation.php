<?php
/**
 * Constellation class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Constellation.php
 */

namespace iveeCrest\Responses;

/**
 * Constellation represents responses of queries to the constellation CREST endpoint.
 * Inheritance: Constellation -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Constellation.php
 */
class Constellation extends EndpointItem
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
        foreach ($content->systems as $system) {
            $indexedItems[(int) $system->id] = $system;
        }
        $this->content = $content;
        $this->content->systems = $indexedItems;
    }

    /**
     * Returns this constellations region response
     *
     * @return \iveeCrest\Responses\Region
     */
    public function getRegion()
    {
        return static::getLastClient()->getEndpointResponse($this->content->region->href);
    }

    /**
     * Returns a specific system response.
     *
     * @param int $systemId of the system
     *
     * @return \iveeCrest\Responses\System
     */
    public function getSystem($systemId)
    {
        if (!isset($this->content->systems[$systemId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'SystemID = ' . (int) $systemId . ' not found in constellation'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->systems[$systemId]->href);
    }

    /**
     * Returns all solar system responses.
     *
     * @return \iveeCrest\Responses\System[]
     */
    public function getSystems()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->content->systems as $item) {
            $hrefs[] = $item->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (System $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
