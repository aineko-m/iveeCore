<?php
/**
 * Tournament class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Tournament.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentResponse represents responses of queries to the tournament CREST endpoint.
 * Inheritance: Tournament -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Tournament.php
 */
class Tournament extends EndpointItem
{
    /**
     * Sets content to object, re-indexing entries by ID parsed from provided href.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $indexedEntries = [];
        foreach ($content->entries as $entry) {
            $indexedEntries[static::parseTrailingIdFromUrl($entry->href)] = $entry;
        }
        $this->content = $content;
        $this->content->entries = $indexedEntries;
    }

    /**
     * Returns this tournaments series collection.
     *
     * @return \iveeCrest\Responses\TournamentSeriesCollection
     */
    public function getTournamentSeriesCollection()
    {
        return static::getLastClient()->getEndpointResponse($this->content->series->href);
    }

    /**
     * Returns a tournament team response.
     *
     * @param int $teamId the team ID
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getTournamentTeam($teamId)
    {
        if (!isset($this->content->entries[$teamId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'TeamID = ' . (int) $teamId . ' not found in tournament'
            );
        }
        return static::getLastClient()->getEndpointResponse($this->content->entries[$teamId]->teamStats->href);
    }

    /**
     * Returns all tournament team responses.
     *
     * @return \iveeCrest\Responses\TournamentTeam[]
     */
    public function getTournamentTeams()
    {
        $hrefs = [];
        //prepare all hrefs to get
        foreach ($this->content->entries as $team) {
            $hrefs[] = $team->teamStats->href;
        }

        $ret = [];
        static::getLastClient()->asyncGetMultiEndpointResponses(
            $hrefs,
            false,
            function (TournamentTeam $response) use (&$ret) {
                $ret[$response->getId()] = $response;
            }
        );
        return $ret;
    }
}
