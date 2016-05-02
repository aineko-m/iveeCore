<?php
/**
 * OpportunityGroup class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityGroup.php
 */

namespace iveeCrest\Responses;

/**
 * OpportunityGroup represents responses of queries to the opportunity groups CREST endpoint.
 * Inheritance: OpportunityGroup -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityGroup.php
 */
class OpportunityGroup extends EndpointItem
{
    /**
     * Initialization called at the beginning of the constructor.
     *
     * @return void
     */
    protected function init()
    {
        $this->id = (int) $this->content->id;
    }
}
