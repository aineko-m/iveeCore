<?php
/**
 * MarketPricesUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/MarketPricesUpdater.php
 *
 */

namespace iveeCore\CREST;

/**
 * MarketPricesUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/MarketPricesUpdater.php
 *
 */
class MarketPricesUpdater extends CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = 'market/prices/';
    
    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.MarketTypePriceCollection-v1';

    /**
     * Processes data objects to SQL
     * 
     * @param \stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = \iveeCore\Config::getIveeClassName('CrestException');

        if (!isset($item->type->id))
            throw new $exceptionClass('typeID missing in Market Prices CREST data');
        $typeID = (int) $item->type->id;
        $this->updatedIDs[] = $typeID;
        $update = array();

        if (!isset($item->adjustedPrice))
            throw new $exceptionClass('Missing adjustedPrice in CREST MarketPrices data for typeID ' . $typeID);
        $update['adjustedPrice'] = (float) $item->adjustedPrice;

        if (isset($item->averagePrice))
            $update['averagePrice'] = (float) $item->averagePrice;

        $insert = $update;
        $insert['typeID'] = $typeID;
        $insert['date'] = date('Y-m-d');

        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery('iveeCrestPrices', $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        $cachePrefix = \iveeCore\Config::getCachePrefix();
        $cacheClass  = \iveeCore\Config::getIveeClassName('Cache');
        $cache = $cacheClass::instance();
        foreach ($this->updatedIDs as $typeID)
            $cache->deleteItem($cachePrefix . 'type_' . $typeID);
    }
}
