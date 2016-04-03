<?php
/**
 * DogmaEffectCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/DogmaEffectCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * DogmaEffectCollection represents responses of queries to the dogma effects collection CREST endpoint.
 * Inheritance: DogmaEffectCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/DogmaEffectCollection.php
 */
class DogmaEffectCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific dogma effect response.
     *
     * @param int $effectId of the dogma effect
     *
     * @return \iveeCrest\Responses\DogmaEffect
     */
    public function getDogmaEffect($effectId)
    {
        $effects = $this->gather();
        if (!isset($effects[$effectId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'EffectID = ' . (int) $effectId . ' not found in dogma effects collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($effects[$effectId]->href);
    }
}
