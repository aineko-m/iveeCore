<?php
/**
 * IndustryFacilityCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IndustryFacilityCollection.php
 */

namespace iveeCrest\Responses;

/**
 * IndustryFacilityCollection represents CREST responses of queries to the industry facilities endpoint.
 * Inheritance: IndustryFacilityCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IndustryFacilityCollection.php
 */
class IndustryFacilityCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by system ID and facility ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->solarSystem->id][(int) $item->facilityID] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
