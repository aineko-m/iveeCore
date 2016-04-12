<?php
/**
 * GlobalPricesUpdater class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/GlobalPricesUpdater.php
 */

namespace iveeCore\CREST;

use iveeCore\Config;
use iveeCore\SDE;
use iveeCrest\Responses\Root;

/**
 * GlobalPricesUpdater updates the data for the global average and adjusted prices from CREST (not orders or history).
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/GlobalPricesUpdater.php
 */
class GlobalPricesUpdater extends CrestDataUpdater
{
    /**
     * Processes data objects to SQL.
     *
     * @param stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = Config::getIveeClassName('CrestException');

        if (!isset($item->type->id)) {
            throw new $exceptionClass('typeID missing in Market Prices CREST data');
        }
        $typeId = (int) $item->type->id;
        $this->updatedIds[] = $typeId;
        $update = array();

        if (!isset($item->adjustedPrice)) {
            throw new $exceptionClass('Missing adjustedPrice in CREST MarketPrices data for typeID ' . $typeId);
        }
        $update['adjustedPrice'] = (float) $item->adjustedPrice;

        if (isset($item->averagePrice)) {
            $update['averagePrice'] = (float) $item->averagePrice;
        }

        $insert = $update;
        $insert['typeID'] = $typeId;
        $insert['date'] = date('Y-m-d');

        $sdeClass = Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery(Config::getIveeDbName() . '.globalPrices', $insert, $update);
    }

    /**
     * Finalizes the update.
     *
     * @return void
     */
    protected function completeUpdate()
    {
        $sql = SDE::makeUpsertQuery(
            Config::getIveeDbName() . '.trackedCrestUpdates',
            [
                'name' => 'globalPrices',
                'lastUpdate' => date('Y-m-d H:i:s', time())
            ],
            ['lastUpdate' => date('Y-m-d H:i:s', time())]
        );
        SDE::instance()->multiQuery($sql . ' COMMIT;');

        //invalidate global price data cache
        $globalPriceDataClass = Config::getIveeClassName('GlobalPriceData');
        $globalPriceDataClass::deleteFromCache($this->updatedIds);
    }

    /**
     * Fetches the data from CREST.
     *
     * @param \iveeCrest\Responses\Root $pubRoot to be used
     *
     * @return array
     */
    protected static function getData(Root $pubRoot)
    {
        //we dont set the cache flag because the data normally won't be read again
        return $pubRoot->getMarketTypePriceCollection()->gather();
    }
}
