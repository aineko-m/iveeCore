<?php
/**
 * MarketTypeCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypeCollection.php
 */

namespace iveeCrest\Responses;

/**
 * MarketTypeCollection represents CREST responses to queries to the market types collection endpoint.
 * Inheritance: MarketTypeCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypeCollection.php
 */
class MarketTypeCollection extends Collection
{
    use ContentItemsTypeIdIndexer;

    /**
     * Returns the gathered hrefs of the market types
     *
     * @return string[]
     * @throws \iveeCrest\Exceptions\PaginationException when this object is not the first page of the Collection
     */
    public function gatherHrefs()
    {
        return static::getLastClient()->gather(
            $this,
            null,
            function (array &$ret, MarketTypeCollection $response) {
                foreach ($response->getElements() as $id => $mtype) {
                    $ret[$id] = $mtype->type->href;
                }
            },
            null,
            true,
            null,
            'hrefsOnly'
        );
    }
}
