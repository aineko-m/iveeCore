<?php
/**
 * TournamentPilotStatsCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotStatsCollection.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * TournamentPilotStatsCollection represents responses of queries to the pilot tournament statistics collection CREST
 * endpoint.
 * Inheritance: TournamentPilotStatsCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotStatsCollection.php
 */
class TournamentPilotStatsCollection extends Collection
{
    /**
     * Sets content to object.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $elementClass = Config::getIveeClassName('TournamentPilotStatsCollectionElement');
        $items = [];
        foreach ($content->items as $pilotData) {
            $dataObj = new $elementClass($pilotData);
            $items[$dataObj->getId()] = $dataObj;
        }

        $this->content = $content;
        $this->content->items = $items;
    }
}
