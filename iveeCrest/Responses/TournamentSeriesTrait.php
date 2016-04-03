<?php
/**
 * TournamentSeriesTrait class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeriesTrait.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentSeriesTrait is used to provide common functionality to tournament series classes.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeriesTrait.php
 */
trait TournamentSeriesTrait
{
    /**
     * Returns the tournament team playing as red.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getRedTeam()
    {
        return static::getLastClient()->getEndpointResponse($this->content->redTeam->team->href);
    }

    /**
     * Returns the tournament team playing as blue.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getBlueTeam()
    {
        return static::getLastClient()->getEndpointResponse($this->content->blueTeam->team->href);
    }

    /**
     * Returns the tournament matches collection for this series.
     *
     * @return \iveeCrest\Responses\TournamentMatchCollection
     */
    public function getMatchesCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->matches->href);
    }

    /**
     * Returns the tournament team winning the match.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getWinner()
    {
        return static::getLastClient()->getEndpointResponse($this->content->winner->team->href);
    }

    /**
     * Returns the tournament team losing the match.
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getLoser()
    {
        return static::getLastClient()->getEndpointResponse($this->content->loser->team->href);
    }

    /**
     * Returns the tournament series of the outgoing winning team.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     * @throws \iveeCore\Exceptions\NoRelevantDataException if there is no outgoing winner for this series
     */
    public function getOutgoingWinnerSeries()
    {
        if (!isset($this->content->structure->outgoingWinner->href)) {
            $noRelevantDataExceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $noRelevantDataExceptionClass('No outgoing winner from this series');
        }
        return static::getLastClient()->getEndpointResponse($this->content->structure->outgoingWinner->href);
    }

    /**
     * Returns the tournament series of the outgoing losing team.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     * @throws \iveeCore\Exceptions\NoRelevantDataException if there is no outgoing loser for this series
     */
    public function getOutgoingLoserSeries()
    {
        if (!isset($this->content->structure->outgoingLoser->href)) {
            $noRelevantDataExceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $noRelevantDataExceptionClass('No outgoing loser for this series');
        }
        return static::getLastClient()->getEndpointResponse($this->content->structure->outgoingLoser->href);
    }

    /**
     * Returns the incoming red tournament series.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     * @throws \iveeCore\Exceptions\NoRelevantDataException if there is no incoming red team for this series
     */
    public function getIncomingRedSeries()
    {
        if (!isset($this->content->structure->incomingRed->href)) {
            $noRelevantDataExceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $noRelevantDataExceptionClass('No incoming red team for this series');
        }
        return static::getLastClient()->getEndpointResponse($this->content->structure->incomingRed->href);
    }

    /**
     * Returns the incoming blue tournament series.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     * @throws \iveeCore\Exceptions\NoRelevantDataException if there is no incoming blue team for this series
     */
    public function getIncomingBlueSeries()
    {
        if (!isset($this->content->structure->incomingBlue->href)) {
            $noRelevantDataExceptionClass = Config::getIveeClassName('NoRelevantDataException');
            throw new $noRelevantDataExceptionClass('No incoming blue team for this series');
        }
        return static::getLastClient()->getEndpointResponse($this->content->structure->incomingBlue->href);
    }
}
