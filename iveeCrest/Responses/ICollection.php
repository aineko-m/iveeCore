<?php
/**
 * ICollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ICollection.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * ICollection is the interface to be implemented by all Collection classes.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ICollection.php
 */
interface ICollection
{
    /**
     * Returns the URL of the response.
     *
     * @return string
     */
    public function getHref();

    /**
     * Checks if the response has a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage();

    /**
     * Gets the previous page of the collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used, optional
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Responses\Collection
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no previous page
     */
    public function getPreviousPage(Client $client = null, $authScope = false, $cache = true);

    /**
     * Checks if the response has a next page.
     *
     * @return bool
     */
    public function hasNextPage();

    /**
     * Gets the next page of the collection endpoint.
     *
     * @param \iveeCrest\Client $client to be used, optional
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Responses\Collection
     * @throws \iveeCrest\Exceptions\PaginationException when the response has no next page
     */
    public function getNextPage(Client $client = null, $authScope = false, $cache = true);

    /**
     * Gets the elements of this (single) page.
     *
     * @return array
     */
    public function getElements();

    /**
     * Gathers the elements of a multipage collection response. Should only be called from an object representing the
     * first response page.
     *
     * @param \iveeCrest\Client $client to be used for authenticated collection endpoints
     *
     * @return array
     */
    public function gather(Client $client = null);
}
