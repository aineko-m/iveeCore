<?php
/**
 * TournamentMatch class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentMatch.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentMatch represents responses of queries to the tournament match CREST endpoint.
 * Inheritance: TournamentMatch -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentMatch.php
 */
class TournamentMatch extends EndpointItem
{
    /**
     * Returns the tournament team response for the red team.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getRedTeam()
    {
        return static::getLastClient()->getEndpointResponse($this->content->redTeam->href);
    }

    /**
     * Returns the tournament team response for the blue team.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getBlueTeam()
    {
        return static::getLastClient()->getEndpointResponse($this->content->blueTeam->href);
    }

    /**
     * Returns the bans response for the match.
     *
     * @return \iveeCrest\Responses\TournamentTypeBanCollection
     */
    public function getBans()
    {
        return static::getLastClient()->getEndpointResponse($this->content->bans->self->href);
    }

    /**
     * Returns the winning team response.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getWinner()
    {
        return static::getLastClient()->getEndpointResponse($this->content->winner->href);
    }

    /**
     * Returns the pilot statistics collection.
     *
     * @return \iveeCrest\Responses\TournamentPilotStatsCollection
     */
    public function getPilotStatsCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->stats->pilots->href);
    }

    /**
     * Returns the tournament series this match belongs to.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     */
    public function getTourtnamentSeries()
    {
        return static::getLastClient()->getEndpointResponse($this->content->series->href);
    }

    /**
     * Returns the tournament this match belongs to.
     *
     * @return \iveeCrest\Responses\Tournament
     */
    public function getTournament()
    {
        return static::getLastClient()->getEndpointResponse($this->content->tournament->href);
    }

    /**
     * Returns the matches static scene data.
     *
     * @return \iveeCrest\Responses\TournamentStaticSceneData
     */
    public function getStaticSceneData()
    {
        return static::getLastClient()->getEndpointResponse($this->content->staticSceneData->href);
    }

    /**
     * Returns the first replay frame of the match.
     *
     * @return \iveeCrest\Responses\TournamentRealtimeMatchFrame
     */
    public function getFirstReplayFrame()
    {
        return static::getLastClient()->getEndpointResponse($this->content->firstReplayFrame->href);
    }

    /**
     * Returns the last replay frame of the match.
     *
     * @return \iveeCrest\Responses\TournamentRealtimeMatchFrame
     */
    public function getLastReplayFrame()
    {
        return static::getLastClient()->getEndpointResponse($this->content->lastReplayFrame->href);
    }
}
