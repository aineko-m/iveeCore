<?php
/**
 * InsurancePricesCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/InsurancePricesCollection.php
 */

namespace iveeCrest\Responses;

/**
 * InsurancePricesCollection represents responses of queries to the insurance prices collection CREST endpoint.
 * Inheritance: InsurancePricesCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/InsurancePricesCollection.php
 */
class InsurancePricesCollection extends Collection
{
    use ContentItemsTypeIdIndexer;
}
