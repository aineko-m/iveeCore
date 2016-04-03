<?php
/**
 * FittingCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/FittingCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;
use iveeCrest\Client;

/**
 * FittingCollection represents CREST responses of queries to a characters ship fittings endpoint.
 * Inheritance: FittingCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/FittingCollection.php
 */
class FittingCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $elementClass = Config::getIveeClassName('FittingCollectionElement');
        $items = [];

        foreach ($content->items as $item) {
            $obj = new $elementClass($item);
            $items[$obj->getId()] = $obj;
        }

        $this->content = $content;
        $this->content->items = $items;
    }

    /**
     * Returns the gathered items of this collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used for authenticated CREST, otpional
     *
     * @return \stdClass[]
     * @throws \iveeCrest\Exceptions\PaginationException when this object is not the first page of the Collection
     */
    public function gather(Client $client = null)
    {
        //this is a single page collection endpoint theres no need to to a real gather
        if ($this->getPageCount() == 1) {
            return $this->getElements();
        }

        if (is_null($client)) {
            $client = static::getLastClient();
        }

        return $client->gather(
            $this,
            'characterFittingsRead',
            function (array &$ret, FittingCollection $response) {
                foreach ($response->getElements() as $id => $fiting) {
                    $ret[$id] = $fiting;
                }
            },
            null,
            true,
            900
        );
    }
}
