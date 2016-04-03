<?php
/**
 * ContactCollectionElement class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContactCollectionElement.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * ContactCollectionElement is used to represent single contacts from a ContactsCollection.
 * Inheritance: ContactCollectionElement -> CollectionElement
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ContactCollectionElement.php
 */
class ContactCollectionElement extends CollectionElement
{
    /**
     * Constructor.
     *
     * @param \stdClass $content data to be set to the object
     */
    public function __construct(\stdClass $content)
    {
        $this->content = $content;
    }

    /**
     * Returns the ID of the element.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->content->contact->id;
    }

    /**
     * Deletes the contact.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used will be fetched.
     *
     * @return void
     */
    public function delete(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        $client->delete($this->content->href, 'characterContactsWrite');
    }
}
