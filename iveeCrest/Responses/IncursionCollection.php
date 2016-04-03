<?php
/**
 * IncursionCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IncursionCollection.php
 */

namespace iveeCrest\Responses;

/**
 * IncursionCollection represents responses of queries to the incursion collection CREST endpoint.
 * Inheritance: IncursionCollection -> Collection -> BasesReponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IncursionCollectionResponse.php
 */
class IncursionCollection extends Collection
{
    /**
     * Sets content to object, re-indexing incursions by the constellation ID it occurs in.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->constellation->id] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
