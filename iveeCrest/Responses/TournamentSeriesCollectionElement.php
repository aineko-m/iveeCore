<?php
/**
 * TournamentSeriesCollectionElement class file.
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
 * TournamentSeriesCollectionElement is used to represent single series elements from a TournamentSeriesCollection.
 * Inheritance: TournamentSeriesCollectionElement -> CollectionElement
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeriesCollectionElement.php
 */
class TournamentSeriesCollectionElement extends CollectionElement
{
    use TournamentSeriesTrait;

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
        $this->id = BaseResponse::parseTrailingIdFromUrl($content->self->href);
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
     * Returns the tournament series of this element.
     *
     * @return \iveeCrest\Responses\TournamentSeries
     */
    public function getSeries()
    {
        return static::getLastClient()->getEndpointResponse($this->content->self->href);
    }
}
