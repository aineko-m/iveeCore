<?php
/**
 * OpportunityGroupsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityGroupsCollection.php
 */

namespace iveeCrest\Responses;

/**
 * OpportunityGroupsCollection represents responses of queries to the opportunity groups collection CREST endpoint.
 * Inheritance: OpportunityGroupsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/OpportunityGroupsCollection.php
 */
class OpportunityGroupsCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific opportunity group response.
     *
     * @param int $groupId of the opportunity group
     *
     * @return \iveeCrest\Responses\OpportunityGroup
     * @throws \iveeCore\Exceptions\InvalidArgumentException if a non-existant alliance ID is passed
     */
    public function getOpportunityGroup($groupId)
    {
        $groups = $this->gather();
        if (!isset($groups[$groupId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'Group ID = ' . (int) $groupId . ' not found in opportunity groups collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($groups[$groupId]->href);
    }
}
