<?php
/**
 * EndpointItem class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/EndpointItem.php
 */

namespace iveeCrest\Responses;

/**
 * EndpointItem is a base class for all CREST responses representing items which have an ID, i.e. not collections nor
 * one of the special endpoints.
 * Inheritance: EndpointItem -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/EndpointItem.php
 */
abstract class EndpointItem extends BaseResponse
{
    /**
     * @var int $id the id of the item
     */
    protected $id;

    /**
     * Initialization called at the beginning of the constructor.
     *
     * @return void
     */
    protected function init()
    {
        $this->id = static::parseTrailingIdFromUrl($this->getHref());
    }

    /**
     * Returns the items ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
