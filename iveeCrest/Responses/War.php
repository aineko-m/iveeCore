<?php
/**
 * War class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/War.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * War represents responses of queries to the war CREST endpoint.
 * Inheritance: War -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/War.php
 */
class War extends EndpointItem
{
    /**
     * Returns this wars aggressor. Note that corporations are currently inaccessible in CREST.
     *
     * @return \iveeCrest\Responses\Alliance
     * @throws \iveeCrest\Exceptons\EndpointUnavailableException when the agressor is a corporation
     */
    public function getAggressor()
    {
        if (stripos($this->content->aggressor->href, '/corporations/')) {
            $exceptionClass = Config::getIveeClassName('EndpointUnavailableException');
            throw new $exceptionClass('Corporations are still inaccessible in CREST.');
        }
        return static::getLastClient()->getEndpointResponse($this->content->aggressor->href);
    }

    /**
     * Returns this wars defender allies. Note that corporations are ignored in this list as they are currently
     * inaccessible in CREST.
     *
     * @return \iveeCrest\Responses\Alliance[]
     */
    public function getAllies()
    {
        if (isset($this->content->allies)) {
            $hrefs = [];
            foreach ($this->content->allies as $allyData) {
                if (!stripos($allyData->href, '/corporations/')) {
                    $hrefs[] = $allyData->href;
                }
            }

            $ret = [];
            static::getLastClient()->asyncGetMultiEndpointResponses(
                $hrefs,
                false,
                function (Alliance $response) use (&$ret) {
                    $ret[$response->getId()] = $response;
                }
            );
            return $ret;
        } else {
            return [];
        }
    }

    /**
     * Returns this wars defender. Note that corporations are currently inaccessible in CREST.
     *
     * @return \iveeCrest\Responses\Alliance
     * @throws \iveeCrest\Exceptons\EndpointUnavailableException when the agressor is a corporation
     */
    public function getDefender()
    {
        if (stripos($this->content->defender->href, '/corporations/')) {
            $exceptionClass = Config::getIveeClassName('EndpointUnavailableException');
            throw new $exceptionClass('Corporations are still inaccessible in CREST.');
        }
        return static::getLastClient()->getEndpointResponse($this->content->defender->href);
    }

    /**
     * Returns this wars killmails collection.
     *
     * @return \iveeCrest\Responses\WarKillmails
     */
    public function getKillmailsCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->killmails);
    }
}
