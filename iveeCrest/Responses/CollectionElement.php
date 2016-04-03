<?php
/**
 * CollectionElement class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CollectionElement.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * CollectionElement is a base class for elements of a collection.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/CollectionElement.php
 */
abstract class CollectionElement
{
    /**
     * @var \stdClass $content the JSON decoded element data.
     */
    public $content;

    /**
     * Returns the ID of the element.
     *
     * @return int
     */
    public function getId()
    {
        return 0;
    }

    /**
     * Returns the last created instance of Client, instantiating a new one if necessary.
     *
     * @return \iveeCrest\Client
     */
    protected static function getLastClient()
    {
        $clientClass = Config::getIveeClassName('Client');
        return $clientClass::getLastInstance();
    }
}
