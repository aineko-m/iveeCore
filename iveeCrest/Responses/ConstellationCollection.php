<?php
/**
 * ConstellationCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ConstellationCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * ConstellationCollection represents responses of queries to the constellation collection CREST endpoint.
 * Inheritance: ConstellationCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ConstellationCollection.php
 */
class ConstellationCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific constellation response.
     *
     * @param int $constellationId of the constellation
     *
     * @return \iveeCrest\Responses\Constellation
     */
    public function getConstellation($constellationId)
    {
        $constellations = $this->gather();
        if (!isset($constellations[$constellationId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'ConstellationID = ' . (int) $constellationId . ' not found in constellations collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($constellations[$constellationId]->href);
    }
}
