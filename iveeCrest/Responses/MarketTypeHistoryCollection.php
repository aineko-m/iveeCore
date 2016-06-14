<?php
/**
 * MarketTypeHistoryCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypeHistoryCollection.php
 */

namespace iveeCrest\Responses;

/**
 * MarketTypeHistoryCollection represents CREST responses to queries to the market history collection endpoint,
 * containing the region wide history for a specific market item.
 * Inheritance: MarketTypeHistoryCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypeHistoryCollection.php
 */
class MarketTypeHistoryCollection extends Collection
{
    /**
     * @var int $typeId the type ID for which this history collection is for
     */
    protected $typeId;

    /**
     * @var int $regionId region type ID for which this history collection is for
     */
    protected $regionId;

    /**
     * Sets content to object, re-indexing entries by date converted to unix timestamp.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[strtotime($item->date)] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }

    /**
     * Do some more initialization.
     *
     * @return void
     */
    protected function init()
    {
        //we have to extract the region and type ID from the URL
        $parsed = parse_url($this->info['url']);
        $this->typeId = (int) explode('/', $parsed['query'])[5];
        $this->regionId = (int) explode('/', $parsed['path'])[2];
    }

    /**
     * Returns the type ID for which this history collection is for.
     *
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Returns the region ID for which this history collection is for.
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->regionId;
    }
}
