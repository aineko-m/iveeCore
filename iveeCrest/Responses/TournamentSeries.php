<?php
/**
 * TournamentSeries class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeries.php
 */

namespace iveeCrest\Responses;

/**
 * TournamentSeries represents responses of queries to the tournament series CREST endpoint.
 * Inheritance: TournamentSeries -> EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/TournamentSeries.php
 */
class TournamentSeries extends EndpointItem
{
    use TournamentSeriesTrait;
}
