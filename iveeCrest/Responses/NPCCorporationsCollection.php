<?php
/**
 * NPCCorporationsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/NPCCorporationsCollection/Alliance.php
 */

namespace iveeCrest\Responses;

/**
 * NPCCorporationsCollection represents responses of queries to the NPC corporations collection CREST endpoint.
 * Inheritance: NPCCorporationsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/NPCCorporationsCollection.php
 */
class NPCCorporationsCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns the loyalty store offers collection for a specific NPC corp.
     *
     * @param int $corpId of the NPC corp
     *
     * @return \iveeCrest\Responses\LoyaltyStoreOffersCollection
     * @throws \iveeCore\Exceptions\InvalidArgumentException if a non-existant corporation ID is passed
     */
    public function getCorporationLoyaltyStore($corpId)
    {
        $corps = $this->gather();
        if (!isset($corps[$corpId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'Corporation ID = ' . (int) $corpId . ' not found in the NPC corporations collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($corps[$corpId]->loyaltyStore->href);
    }
}
