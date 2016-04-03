<?php
/**
 * DogmaAttributeCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/DogmaAttributeCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * DogmaAttributeCollection represents responses of queries to the dogma attributes collection CREST endpoint.
 * Inheritance: DogmaAttributeCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/DogmaAttributeCollection.php
 */
class DogmaAttributeCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific dogma attribute response.
     *
     * @param int $attributeId of the dogma attribute
     *
     * @return \iveeCrest\Responses\DogmaAttribute
     */
    public function getDogmaAttribute($attributeId)
    {
        $attributes = $this->gather();
        if (!isset($attributes[$attributeId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'AttributeID = ' . (int) $attributeId . ' not found in dogma attributes collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($attributes[$attributeId]->href);
    }
}
