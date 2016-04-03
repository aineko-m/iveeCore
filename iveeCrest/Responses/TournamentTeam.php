<?php
/**
 * TournamentTeam class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentTeam.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentTeam represents responses of queries to the tournament team CREST endpoint.
 * Inheritance: TournamentTeam -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentTeam.php
 */
class TournamentTeam extends EndpointItem
{
    /**
     * Sets content to object, re-indexing items by ID parsed from provided href.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedPilots = [];
        foreach ($content->pilots as $pilot) {
            $indexedPilots[static::parseTrailingIdFromUrl($pilot->href)] = $pilot;
        }

        $indexedBanFrequency = [];
        foreach ($content->banFrequency as $ban) {
            $indexedBanFrequency[static::parseTrailingIdFromUrl($ban->shipType->href)] = $ban;
        }

        $indexedBanFrequencyAgainst = [];
        foreach ($content->banFrequencyAgainst as $ban) {
            $indexedBanFrequencyAgainst[static::parseTrailingIdFromUrl($ban->shipType->href)] = $ban;
        }

        $this->content = $content;
        $this->content->pilots              = $indexedPilots;
        $this->content->banFrequency        = $indexedBanFrequency;
        $this->content->banFrequencyAgainst = $indexedBanFrequencyAgainst;
    }

    /**
     * Returns the team member collection
     *
     * @return \iveeCrest\Responses\TournamentTeamMemberCollection
     */
    public function getTeamMemberCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->members->href);
    }

    /**
     * Returns a tournament match response.
     *
     * @param int $matchNum the n-th match played by the team.
     *
     * @return \iveeCrest\Responses\TournamentMatch
     */
    public function getTournamentMatch($matchNum)
    {
        if (!isset($this->content->matches[$matchNum])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'Match number ' . (int) $matchNum . ' not found for team'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->matches[$matchNum]->href);
    }

    /**
     * Returns all tournament match responses.
     *
     * @return \iveeCrest\Responses\TournamentMatch[]
     */
    public function getTournamentMatches()
    {
        $hrefs = [];
        foreach ($this->content->matches as $match) {
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
