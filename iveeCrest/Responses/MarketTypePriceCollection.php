<?php
/**
 * MarketTypePriceCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypePriceCollection.php
 */

namespace iveeCrest\Responses;

/**
 * MarketTypePriceCollection represents CREST responses to queries to the market type price collection endpoint,
 * containing the global adjusted and average prices.
 * Inheritance: MarketTypePriceCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketTypePriceCollection.php
 */
class MarketTypePriceCollection extends Collection
{
    use ContentItemsTypeIdIndexer;
}
