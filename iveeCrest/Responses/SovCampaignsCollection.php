<?php
/**
 * SovCampaignsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/SovCampaignsCollection.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * SovCampaignsCollection represents CREST responses to queries to the sovereignty campaigns collection endpoint.
 * Inheritance: SovCampaignsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/SovCampaignsCollection.php
 */
class SovCampaignsCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by ID given in item.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[(int) $item->campaignID] = $item;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }

    /**
     * Returns the gathered items of this collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used, optional
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
            null,
            function (array &$ret, SovCampaignsCollection $response) {
                foreach ($response->getElements() as $id => $sc) {
                    $ret[$id] = $sc;
                }
            },
            null,
            true,
            3600
        );
    }
}
