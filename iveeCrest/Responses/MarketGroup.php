<?php
/**
 * MarketGroup class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketGroup.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * MarketGroup represents responses of queries to the CREST market group endpoint.
 * Inheritance: MarketGroup -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketGroup.php
 */
class MarketGroup extends EndpointItem
{
    /**
     * Returns whether this market group has a parent group.
     *
     * @return bool
     */
    public function hasParentGroup()
    {
        return isset($this->content->parentGroup);
    }

    /**
     * Returns the parent market group of this group.
     *
     * @return \iveeCrest\Responses\MarketGroup
     * @throws \iveeCrest\Exceptions\IveeCrestException when the parent group is requested but there is none.
     */
    public function getParentGroup()
    {
        if ($this->hasParentGroup()) {
            return static::getLastClient()->getEndpointResponse($this->content->parentGroup->href);
        }
        $exceptionClass = Config::getIveeClassName('IveeCrestException');
        throw new $exceptionClass('MarketGroup has no parent');
    }

    /**
     * Returns the collection of market types in this group.
     *
     * @return \iveeCrest\Responses\MarketTypeCollection
     */
    public function getMarketTypeCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->types->href);
    }
}
