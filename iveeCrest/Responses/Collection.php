<?php
/**
 * Collection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Collection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;
use iveeCrest\Client;
use iveeCrest\Responses\ICollection;

/**
 * Collection is an abstract class to be extended by concrete classes representing collection CREST responses. It
 * provides methods for dealing with pagination and adds the ICollection interface.
 *
 * Collections as returned by CREST generally contain an array of elements not indexed by their respective IDs. To
 * improve usability and performance when acessing these arrays, concrete Collection subclasses will reindex the items
 * using IDs derived from the elements. This happens before the caching layer, thus the content transformation needs
 * only to be done once for each CREST call.
 *
 * Note that collections can in theory always be or become paginated, should CCP decide that change the maximum elements
 * of a specific collection endpoint. Thus it is recommended that access the data using the gather() method, which will
 * fetch and concatenate items from multiple pages as needed.
 *
 * Inheritance: Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Collection.php
 */
abstract class Collection extends BaseResponse implements ICollection
{
    /**
     * Returns the number of total pages available on the request endpoint.
     *
     * @return int
     */
    public function getPageCount()
    {
        if (isset($this->content->pageCount)) {
            return (int) $this->content->pageCount;
        }
        return 1;
    }

    /**
     * Checks if the response has a next page.
     *
     * @return bool
     */
    public function hasNextPage()
    {
        if (isset($this->content->next->href)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the next page href, if there is one.
     *
     * @return string
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no next page
     */
    public function getNextPageHref()
    {
        if ($this->hasNextPage()) {
            return $this->content->next->href;
        }

        $iveeCrestExceptionClass = Config::getIveeClassName('PaginationException');
        throw new $iveeCrestExceptionClass('No next page href present in response body');
    }

    /**
     * Gets the next page.
     *
     * @param \iveeCrest\Client $client to be used, optional
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Responses\ICollection
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no next page
     */
    public function getNextPage(Client $client = null, $authScope = false, $cache = true)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->getNextPageHref(), $authScope, null, $cache);
    }

    /**
     * Checks if the response has a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        if (isset($this->content->previous->href)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the previous page href, if there is one.
     *
     * @return string
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no previous page
     */
    public function getPreviousPageHref()
    {
        if ($this->hasPreviousPage()) {
            return $this->content->previous->href;
        }

        $iveeCrestExceptionClass = Config::getIveeClassName('PaginationException');
        throw new $iveeCrestExceptionClass('No previous page href present in response body');
    }

    /**
     * Gets the previous page.
     *
     * @param \iveeCrest\Client $client to be used, optional
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Responses\ICollection
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no previous page
     */
    public function getPreviousPage(Client $client = null, $authScope = false, $cache = true)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->getPreviousPageHref(), $authScope, null, $cache);
    }

    /**
     * Gets the elements of the collection.
     *
     * @return array
     */
    public function getElements()
    {
        //overwrite in subclasses when elements are not in content->items
        return $this->content->items;
    }

    /**
     * Returns the gathered items of this collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used
     *
     * @return array
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when an authentication scope is requested for which
     * there is no refresh token.
     * @throws \iveeCrest\Exceptions\PaginationException when trying to gather NOT starting with the first page of the
     * Collection
     */
    public function gather(Client $client = null)
    {
        //this is a single page collection endpoint theres no need to to a real gather
        if ($this->getPageCount() <= 1) {
            return $this->getElements();
        }

        if (is_null($client)) {
            $client = static::getLastClient();
        }

        return $client->gather(
            $this,
            null,
            function (array &$ret, ICollection $collection) {
                foreach ($collection->getElements() as $id => $element) {
                    $ret[$id] = $element;
                }
            },
            null,
            true
        );
    }
}
