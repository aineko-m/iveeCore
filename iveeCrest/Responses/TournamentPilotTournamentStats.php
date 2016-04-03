<?php
/**
 * TournamentPilotTournamentStats class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotTournamentStats.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentPilotTournamentStats represents responses of queries to the pilot tournaments statistics CREST endpoint.
 * Inheritance: TournamentPilotTournamentStats -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotTournamentStats.php
 */
class TournamentPilotTournamentStats extends EndpointItem
{
    /**
     * Returns a specific match.
     *
     * @param int $matchNum specifying the nth match
     *
     * @return \iveeCrest\Responses\TournamentMatch
     */
    public function getMatch($matchNum)
    {
        if (!isset($this->content->matchesParticipatedIn[$matchNum])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $invalidArgumentExceptionClass(
                'Match number ' . (int) $matchNum . ' not found for pilot'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->matchesParticipatedIn[$matchNum]->href);
    }

    /**
     * Returns all matches.
     *
     * @return \iveeCrest\Responses\TournamentMatch[]
     */
    public function getMatches()
    {
        $hrefs = [];
        foreach ($this->content->matchesParticipatedIn as $match) {
            $hrefs[] = $match->href;
        }
        $hrefToPos = array_flip($hrefs);

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (TournamentMatch $response) use (&$ret, $hrefToPos) {
                //use hrefsToPos to preserve position
                $ret[$hrefToPos[$response->getInfo()['url']]] = $response;
            }
        );
        ksort($ret);
        return $ret;
    }
}
