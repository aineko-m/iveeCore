<?php
/**
 * TokenDecode class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TokenDecode.php
 */

namespace iveeCrest\Responses;

use iveeCrest\Client;

/**
 * TokenDecode represents the CREST response of "decoding" an access token, containing information about the character
 * the token is associated with.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TokenDecode.php
 */
class TokenDecode extends BaseResponse
{
    /**
     * Returns the CharacterResponse for the character associated with the used access token.
     *
     * @param \iveeCrest\Client $client to be used. If none is passed, the last one used will be fetched.
     *
     * @return \iveeCrest\Responses\Character
     */
    public function getCharacter(Client $client = null)
    {
        if (is_null($client)) {
            $client = static::getLastClient();
        }
        return $client->getEndpointResponse($this->content->character->href, 'publicData');
    }
}
