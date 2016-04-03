<?php
/**
 * TournamentPilotStatsCollectionElement class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotStatsCollectionElement.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentPilotStatsCollectionElement is used to represent single pilot statistic elements from a
 * TournamentPilotStatsCollection.
 * Inheritance: TournamentPilotStatsCollectionElement -> CollectionElement
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentPilotStatsCollectionElement.php
 */
class TournamentPilotStatsCollectionElement extends CollectionElement
{
    /**
     * @var int $id of the element.
     */
    protected $id;

    /**
     * Constructor.
     *
     * @param \stdClass $content data to be set to the object
     */
    public function __construct(\stdClass $content)
    {
        $this->content = $content;
        $this->id = BaseResponse::parseTrailingIdFromUrl($content->pilot->href);
    }

    /**
     * Returns the ID of the element.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the tournament statistic for the pilot
     *
     * @return \iveeCrest\Responses\TournamentPilotTournamentStats
     */
    public function getPilotTournamentStats()
    {
        return static::getLastClient()->getEndpointResponse($this->content->pilotTournamentStats->href);
    }

    /**
     * Returns the team the pilot belongs to
     *
     * @return \iveeCrest\Responses\TournamentTeam
     */
    public function getTeam()
    {
        return static::getLastClient()->getEndpointResponse($this->content->team->href);
    }
}
