<?php
/**
 * CorporationStructuresCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CorporationStructuresCollection.php
 */

namespace iveeCrest\Responses;

/**
 * CorporationStructuresCollection represents responses of queries to the corporation structures collection CREST
 * endpoint.
 * Inheritance: CorporationStructuresCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CorporationStructuresCollection.php
 */
class CorporationStructuresCollection extends Collection
{
    use ContentItemsIdIndexer;
}
