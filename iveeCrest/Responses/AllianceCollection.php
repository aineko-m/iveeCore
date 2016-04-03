<?php
/**
 * AllianceCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/AllianceCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * AllianceCollection represents responses of queries to the alliance collection CREST endpoint.
 * Inheritance: AllianceCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/AllianceCollection.php
 */
class AllianceCollection extends Collection
{
    use ContentItemsIdIndexer;

    /**
     * Returns a specific alliance response.
     *
     * @param int $allianceId of the alliance
     *
     * @return \iveeCrest\Responses\Alliance
     */
    public function getAlliance($allianceId)
    {
        $alliances = $this->gather();
        if (!isset($alliances[$allianceId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'AllianceID = ' . (int) $allianceId . ' not found in alliances collection'
            );
        }

        return static::getLastClient()->getEndpointResponse($alliances[$allianceId]->href);
    }
}
