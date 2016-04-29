<?php
/**
 * TournamentRealtimeMatchFrame class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentRealtimeMatchFrame.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentRealtimeMatchFrame represents responses of queries to the tournament realtime match frame CREST endpoint.
 * Inheritance: TournamentRealtimeMatchFrame -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentRealtimeMatchFrame.php
 */
class TournamentRealtimeMatchFrame extends EndpointItem
{
    /**
     * Sets the content during construction.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $this->content = $content;
        $this->id = $this->content->frameNum;
    }

    /**
     * More object initialization.
     *
     * @return void
     */
    protected function init()
    {
        //overwriting method with empty body
    }

    //TODO: ship data element objects

    /**
     * Returns whether there is a next match replay frame.
     *
     * @return bool
     */
    public function hasNextFrame()
    {
        return isset($this->content->nextFrame->href);
    }
    
    /**
     * Returns the next replay frame of the match.
     *
     * @return \iveeCrest\Responses\TournamentRealtimeMatchFrame
     */
    public function getNextFrame()
    {
        if (!$this->hasNextFrame()) {
            $paginationExceptionClass = Config::getIveeClassName('PaginationException');
            throw new $paginationExceptionClass('No next frame href present in response body');
        }
        return static::getLastClient()->getEndpointResponse($this->content->nextFrame->href);
    }

    /**
     * Returns whether there is a previous match replay frame.
     *
     * @return bool
     */
    public function hasPreviousFrame()
    {
        return isset($this->content->prevFrame->href);
    }

    /**
     * Returns the previous replay frame of the match.
     *
     * @return \iveeCrest\Responses\TournamentRealtimeMatchFrame
     */
    public function getPreviousFrame()
    {
        if (!$this->hasPreviousFrame()) {
            $paginationExceptionClass = Config::getIveeClassName('PaginationException');
            throw new $paginationExceptionClass('No previous frame href present in response body');
        }
        return static::getLastClient()->getEndpointResponse($this->content->prevFrame->href);
    }
}
