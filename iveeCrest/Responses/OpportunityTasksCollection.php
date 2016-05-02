<?php
/**
 * OpportunityTasksCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityTasksCollection.php
 */

namespace iveeCrest\Responses;

/**
 * OpportunityTasksCollection represents responses of queries to the opportunity tasks collection CREST endpoint.
 * Inheritance: OpportunityTasksCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityTasksCollection.php
 */
class OpportunityTasksCollection extends Collection
{
    use ContentItemsIdIndexer;
}
