<?php
/**
 * Character class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Character.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;
use iveeCore\Exceptions\InvalidParameterValueException;
use iveeCrest\Client;

/**
 * Character represents responses of queries to the character CREST endpoint.
 * Inheritance: Character -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Character.php
 */
class Character extends EndpointItem
{
    /**
     * Returns the corporation.
     *
     * @return \iveeCrest\Responses\Corporation
     */
    public function getCorporation()
    {
        return static::getLastClient()->getEndpointResponse($this->content->corporation->href);
    }

    /**
     * Returns the characters contacts.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\ContactCollection
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getContactsCollection(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->contacts->href, 'characterContactsRead');
    }

    /**
     * Returns the characters fittings.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\FittingCollection
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getFittingCollection(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->fittings->href, 'characterFittingsRead');
    }

    /**
     * Posts a new fitting based on the given parameters.
     *
     * @param int $shipId of the ship the fitting is for
     * @param array $slotItemData following the schema 'slotId' => ['quantity' => ..., 'id' => ...]. For slot IDs, see
     * the table invFlags in the SDE.
     * @param string $name to be used
     * @param string $description to be set
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function addFitting($shipId, array $slotItemData, $name, $description = '', Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        $types = $client->getRootEndpoint()->getItemTypeCollection()->gather();

        //build data object to be posted as json
        $data = new \stdClass;
        $data->name = $name;
        $data->description = $description;
        $data->ship = $types[$shipId];
        $data->items = [];

        //put modules and items in their respective slots
        foreach ($slotItemData as $slot => $itemData) {
            if (!isset($types[$itemData['id']])) {
                throw new InvalidParameterValueException(
                    'Specified fit item with ID = ' . (int) $itemData['id'] . " wasn't found."
                );
            }
            $item = new \stdClass;
            $item->flag = $slot;
            $item->quantity = $itemData['quantity'];
            $item->type = $types[$itemData['id']];
            $data->items[] = $item;
        }

        $elementClass = Config::getIveeClassName('FittingCollectionElement');
        $this->addFittingFromElement(new $elementClass($data), $client);
    }

    /**
     * Posts a new fitting from a FittingCollectionElement object.
     *
     * @param \iveeCrest\Responses\FittingCollectionElement $fitting to be added
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function addFittingFromElement(FittingCollectionElement $fitting, Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        //if we are re-using an existing fitting, remove the ID and href from the post data
        $data = clone $fitting->content;
        if (isset($data->fittingID)) {
            unset($data->fittingID);
        }
        if (isset($data->href)) {
            unset($data->href);
        }

        $client->post(
            $this->content->fittings->href,
            json_encode($data, JSON_UNESCAPED_SLASHES),
            'characterFittingsWrite'
        );
    }

    /**
     * Deletes a fitting. Note that due to caching fetching the FittingCollection again after a delete request might
     * still show the deleted fitting up to 5 minutes later.
     *
     * @param int $fittingId of the fitting to be deleted
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\InvalidParameterValueException when a non-existant contact ID is specified
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function deleteFitting($fittingId, Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        $fittings = $this->getFittingCollection($client)->gather($client);
        if (!isset($fittings[(int) $fittingId])) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass(
                'No fitting with ID = ' . (int) $fittingId . ' found in characters FittingCollection.'
            );
        }

        $fittings[(int) $fittingId]->delete($client);
    }

    /**
     * Returns the characters location.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\CharacterLocation
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getLocation(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->location->href, 'characterLocationRead');
    }

    /**
     * Sets navigation waypoints.
     *
     * @param int $systemId ID of the system to be added to the waypoint list
     * @param bool $clearExisting controls whether existing waypoints should be cleared
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used will be fetched.
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a non-existant systemId is passed.
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function setNavigationWaypoint($systemId, $clearExisting = false, Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        //fetch system data
        $systems = $client->getRootEndpoint()->getSystemCollection()->gather();
        if (!isset($systems[$systemId])) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass('No solar system with ID = ' . (int) $systemId . ' found.');
        }

        //build data object to be posted as json
        $data = new \stdClass;
        $data->solarSystem = [
            'href' => $systems[$systemId]->href,
            'id'   => $systems[$systemId]->id
        ];
        $data->first = (bool) $clearExisting;
        $data->clearOtherWaypoints = (bool) $clearExisting;

        $client->post(
            $this->content->waypoints->href,
            json_encode($data, JSON_UNESCAPED_SLASHES),
            'characterNavigationWrite'
        );
    }

    /**
     * Adds or updates a contact.
     *
     * @param int $id of the character, corporation or alliance
     * @param string $type must be 'character', 'corporation' or 'alliance'
     * @param int $standing between -10 und 10
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used will be fetched.
     *
     * @return void
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when invalid contact type is passed
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function addContact($id, $type, $standing = 0, Client $client = null)
    {
        $types = ['character' => 'characters/', 'corporation' => 'corporations/', 'alliance' => 'alliances/'];
        if (!isset($types[$type])) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass('No valid contact type given. Must be "character", "corporation" or "alliance".');
        }

        if (is_null($client)) {
            $client = static::getLastClient();
        }

        //build data object to be posted as json
        $data = new \stdClass;
        $data->contact = [
            'id' => (int) $id,
            //theres no openly accesible character or corporation collection we could get the data from, so we must
            //construct the URL
            'href' => Config::getCrestBaseUrl() . $types[$type] . (int) $id . '/'
        ];
        $data->standing = (int) $standing;

        $client->post(
            $this->content->contacts->href,
            json_encode($data, JSON_UNESCAPED_SLASHES),
            'characterContactsWrite'
        );
    }

    /**
     * Deletes a contact. Note that due to caching fetching the ContactsCollection again after a delete request might
     * still show the deleted contact up to 5 minutes later.
     *
     * @param int $contactId of the contact to be deleted
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\InvalidParameterValueException when a non-existant contact ID is specified
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function deleteContact($contactId, Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        $contacts = $this->getContactsCollection($client)->gather($client);
        if (!isset($contacts[(int) $contactId])) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass(
                'No contact with ID = ' . (int) $contactId . ' found in characters ContactCollection.'
            );
        }

        $contacts[(int) $contactId]->delete($client);
    }

    /**
     * Gets the loyaltypoints endpoint for the character.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\LoyaltyPointsCollection
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getLoyaltyPoints(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->loyaltyPoints->href, 'characterLoyaltyPointsRead');
    }

    /**
     * Gets the opportunities endpoint for the character.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\CharacterOpportunitiesCollection
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getOpportunities(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->opportunities->href, 'characterOpportunitiesRead');
    }

    /**
     * Returns the characters aggregated yearly statistics.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used is fetched.
     *
     * @return \iveeCrest\Responses\BaseResponse
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when the required authentication scope token is not
     * available
     */
    public function getAggregatedStats(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }

        //There's currently no navigable way to reach the stats endpoint, so we have to construct the URL for it.
        //The response is a BaseResponse because the server does not return a unique content type.
        return $client->getEndpointResponse(
            'https://characterstats.tech.ccp.is/v1/' . $this->getId() . '/',
            'characterStatsRead'
        );
    }
}
