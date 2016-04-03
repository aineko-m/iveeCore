<?php
/**
 * TournamentCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentCollection.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentCollection represents responses of queries to the tournament collection CREST endpoint.
 * Inheritance: TournamentCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentCollection.php
 */
class TournamentCollection extends Collection
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
        $indexedItems = [];
        foreach ($content->items as $item) {
            $indexedItems[static::parseTrailingIdFromUrl($item->href->href)] = $item->href;
        }
        $this->content = $content;
        $this->content->items = $indexedItems;
    }

    /**
     * Returns a specific tournament response.
     *
     * @param int $tournamentId of the tournament
     *
     * @return \iveeCrest\Responses\Tournament
     */
    public function getTournament($tournamentId)
    {
        $tournaments = $this->gather();
        if (!isset($tournaments[$tournamentId])) {
            $invalidArgumentExceptionClass = Config::getIveeClassName('InvalidArgumentException');
            throw new $invalidArgumentExceptionClass(
                'TournamentID = ' . (int) $tournamentId . ' not found in tournaments collection'
            );
        }
        return static::getLastClient()->getEndpointResponse($tournaments[$tournamentId]->href);
    }
}
