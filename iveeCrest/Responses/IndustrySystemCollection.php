<?php
/**
 * IndustrySystemCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IndustrySystemCollection.php
 */

namespace iveeCrest\Responses;

/**
 * IndustrySystemCollection represents CREST responses of queries to the industry system collection endpoint, containing
 * the industry indices.
 * Inheritance: IndustrySystemCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/IndustrySystemCollection.php
 */
class IndustrySystemCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by system ID and the indices by activity ID.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $sysCostIndices = [];
            foreach ($item->systemCostIndices as $index) {
                $sysCostIndices[(int) $index->activityID] = $index;
            }
            $item->systemCostIndices = $sysCostIndices;
            $indexedItems[(int) $item->solarSystem->id] = $item;
        }
            
        $this->content = $content;
        $this->content->items = $indexedItems;
    }
}
