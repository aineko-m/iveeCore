<?php
/**
 * ContactCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContactCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;
use iveeCrest\Client;

/**
 * ContactCollection represents responses of queries to the character contact collection CREST endpoint.
 * Inheritance: ContactCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContactCollection.php
 */
class ContactCollection extends Collection
{
    /**
     * Processes and sets the contect during object instantiation.
     *
     * @param \stdClass $content the content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $elementClass = Config::getIveeClassName('ContactCollectionElement');
        $items = [];
        foreach ($content->items as $contactData) {
            $obj = new $elementClass($contactData);
            $items[$obj->getId()] = $obj;
        }

        $this->content = $content;
        $this->content->items = $items;
    }

    /**
     * Returns the gathered items of this collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used for authenticated CREST, optional
     *
     * @return \iveeCrest\Responses\ContactCollectionElement[]
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
            'characterContactsRead',
            function (array &$ret, ContactCollection $response) {
                foreach ($response->getElements() as $id => $contact) {
                    $ret[$id] = $contact;
                }
            },
            null,
            true,
            300
        );
    }
}
