<?php
/**
 * SystemCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/SystemCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * SystemCollection represents CREST responses to queries to the system collection endpoint.
 * Inheritance: SystemCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/SystemCollection.php
 */
class SystemCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * @var array $nameToId holds a map of solar system names to IDs
     */
    protected static $nameToId = [];

    /**
     * Returns a specific system response.
     *
     * @param int $systemId of the solar system
     *
     * @return \iveeCrest\Responses\System
     * @throws \iveeCrest\Exceptions\InvalidArgumentException when the specified system ID is not found.
     */
    public function getSystem($systemId)
    {
        $systems = $this->gather();
        if (!isset($systems[$systemId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'SystemID = ' . (int) $systemId . ' not found in systems collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($systems[$systemId]->href);
    }

    /**
     * Returns a specific system response.
     *
     * @param string $systemName full name of the solar system (case insensitive)
     *
     * @return \iveeCrest\Responses\System
     * @throws \iveeCrest\Exceptions\InvalidArgumentException when the specified system name is not found.
     */
    public function getSystemByName($systemName)
    {
        //fill name to ID array if necessary
        if (count(static::$nameToId) < 1) {
            foreach ($this->gather() as $id => $data) {
                static::$nameToId[strtolower($data->name)] = $id;
            }
        }

        //check if we recognize the system name
        if (!isset(static::$nameToId[strtolower($systemName)])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass('Specified system not found in systems collection');
        }
        return $this->getSystem(static::$nameToId[strtolower($systemName)]);
    }
}
