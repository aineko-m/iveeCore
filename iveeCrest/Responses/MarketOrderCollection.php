<?php
/**
 * MarketOrderCollection class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketOrderCollection.php
 */

namespace iveeCrest\Responses;

/**
 * MarketOrderCollection represents CREST responses to queries to the market orders collection endpoint, containing data
 * about all the market orders for a specific market item in a specific region.
 * Inheritance: MarketOrderCollection -> Collection -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/MarketOrderCollection.php
 */
class MarketOrderCollection extends Collection
{
    use ContentItemsHrefIndexer;

    /**
     * @var int $typeId the type ID for which this order collection is for
     */
    protected $typeId;

    /**
     * @var int $regionId region type ID for which this order collection is for
     */
    protected $regionId;

    /**
     * @var bool $isSell if this collection is for sell orders (buy orders otherwise)
     */
    protected $isSell = false;

    /**
     * Do some more initialization.
     *
     * @return void
     */
    protected function init()
    {
        $urlCmpts = parse_url($this->info['url']);
        $this->typeId   = (int) explode('/', $urlCmpts['query'])[4];
        $pathCmpts = explode('/', $urlCmpts['path']);
        $this->regionId = (int) $pathCmpts[2];
        if ($pathCmpts[4] == 'sell') {
            $this->isSell = true;
        }
    }

    /**
     * Returns the type ID for which this order collection is for.
     *
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Returns the region ID for which this order collection is for.
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Returns if this collection is for sell orders (buy orders otherwise).
     *
     * @return bool
     */
    public function isSell()
    {
        return $this->isSell;
    }
}
