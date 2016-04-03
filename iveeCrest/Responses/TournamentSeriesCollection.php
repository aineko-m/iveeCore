<?php
/**
 * TournamentSeriesCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeriesCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * TournamentSeriesCollection represents responses of queries to the tournament series collection CREST endpoint.
 * Inheritance: TournamentSeriesCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeriesCollection.php
 */
class TournamentSeriesCollection extends Collection
{
    /**
     * Sets content to object, re-indexing items by ID given in item.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $elementClass = Config::getIveeClassName('TournamentSeriesCollectionElement');
        $items = [];
        foreach ($content->items as $i => $series) {
            $items[$i] = new $elementClass($series);
        }

        $this->content = $content;
        $this->content->items = $items;
    }

    /**
     * Returns a specific tournament series.
     *
     * @param int $seriesId specifying the nth series
     *
     * @return \iveeCrest\Responses\TournamentSeries
     */
    public function getSeries($seriesId)
    {
        if (!isset($this->content->items[$seriesId])) {
            $exceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $exceptionClass('Tournament series with ID = ' . (int) $seriesId . ' not found.');
        }
        return $this->content->items[$seriesId]->getSeries();
    }
}
