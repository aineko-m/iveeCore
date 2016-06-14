<?php
/**
 * PriceEstimator class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/PriceEstimator.php
 */

namespace iveeCore\CREST;

use iveeCore\Config;

/**
 * PriceEstimator provides methods for estimating realistic buy and sell order prices as well as calculating average
 * order age (a form of measuring activity and competition) and supply and demand based on data from MarketProcessor.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/PriceEstimator.php
 */
class PriceEstimator
{
    /**
     * @var \iveeCore\SDE $sde instance for quicker access
     */
    protected static $sde;

    /**
     * @var \iveeCore\CREST\MarketProcessor $marketProcessor for access to additional CREST market data
     */
    protected $marketProcessor;

    /**
     * Constructor.
     *
     * @param \iveeCore\CREST\MarketProcessor $cmp for access to additional CREST market data
     */
    public function __construct(MarketProcessor $cmp)
    {
        if (!isset(static::$sde)) {
            $sdeClass = Config::getIveeClassName('SDE');
            static::$sde = $sdeClass::instance();
        }
        $this->marketProcessor = $cmp;
    }

    /**
     * Calculates the pricing values based on given CREST order items.
     *
     * @param array $orders containing both sell and buy orders
     * @param int $typeId of the market type
     * @param int $regionId of the region
     *
     * @return array
     */
    public function calcValues(array $orders, $typeId, $regionId)
    {
        $averages = $this->getWeeklyAverages($typeId, $regionId);

        //split buy and sell orders
        $sellOrders = [];
        $buyOrders = [];
        foreach ($orders as $id => $order) {
            if ($order->buy == 1) {
                $buyOrders[$id] = $order;
            } else {
                $sellOrders[$id] = $order;
            }
        }

        //merge all data into single array and return
        return array_merge(
            $this->processSellOrderData($sellOrders, $averages['avgVol']),
            $this->processBuyOrderData($buyOrders, $averages['avgVol']),
            $averages
        );
    }

    /**
     * Fetches the weekle averages of vol and tx, triggering a CREST history update if data is too old
     *
     * @param int $typeId of the type
     * @param int $regionId of the region
     *
     * @return array
     */
    protected function getWeeklyAverages($typeId, $regionId)
    {
        $sql = "SELECT UNIX_TIMESTAMP(lastHistUpdate) as lastHistUpdate,
            avgVol, avgTx
            FROM " . Config::getIveeDbName() . ".trackedMarketData
            WHERE typeID = " . $typeId . "
            AND regionID = " . $regionId . ";";

        $row = static::$sde->query($sql)->fetch_assoc();

        //if history update was run today, use that value
        if ($row['lastHistUpdate'] > mktime(0, 0, 0)) {
            return array(
                'avgVol' => (float) $row['avgVol'],
                'avgTx'  => (float) $row['avgTx'],
                'lastHistUpdate'  => (int) $row['lastHistUpdate'],
                'lastPriceUpdate' => time()
            );
        }

        //history data was too old, trigger CREST update
        $this->marketProcessor->getNewestHistoryData($typeId, $regionId, false);

        //fetch from DB again
        $row = static::$sde->query($sql)->fetch_assoc();
        return array(
            'avgVol' => (float) $row['avgVol'],
            'avgTx'  => (float) $row['avgTx'],
            'lastHistUpdate'  => (int) $row['lastHistUpdate'],
            'lastPriceUpdate' => time()
        );
    }

    /**
     * Calculates the sell order values based on given buy order data.
     *
     * @param array $data containig the buy order items
     * @param float $avgVol weekly average volume of item in region
     *
     * @return array
     */
    protected function processSellOrderData(array $data, $avgVol)
    {
        //sort orders by lowest sell price using lambda
        usort(
            $data,
            function (\stdClass $a, \stdClass $b) {
                if ($a->price == $b->price) {
                    return 0;
                }
                return ($a->price < $b->price) ? -1 : 1;
            }
        );

        $priceStats = static::getPriceStats($data, $avgVol);
        $ret = [];
        if (isset($priceStats['realisticPrice'])) {
            $ret['sell'] = $priceStats['realisticPrice'];
            $ret['avgSell5OrderAge'] = $priceStats['avgOrderAge'];
        }

        //calculate supply within 5% of realistic sell price
        if (isset($ret['sell'])) {
            $ret['supplyIn5'] = 0;
            foreach ($data as $order) {
                //skip orders with ludicrous minimum volume
                if ($order->volume > ($avgVol < 1 ? 1 : $avgVol)) {
                    continue;
                }

                //if cut-off sum reached, break
                if ($ret['sell'] * 1.05 < $order->price) {
                    break;
                }

                $ret['supplyIn5'] += $order->volume;
            }
        }
        return $ret;
    }

    /**
     * Calculates the buy order values based on given sell order data.
     *
     * @param array $data containig the sell order items
     * @param float $avgVol weekly average volume of item in region
     *
     * @return array
     */
    protected function processBuyOrderData(array $data, $avgVol)
    {
        //sort orders by highest buy price using lambda
        usort(
            $data,
            function (\stdClass $a, \stdClass $b) {
                if ($a->price == $b->price) {
                    return 0;
                }
                return ($a->price > $b->price) ? -1 : 1;
            }
        );

        $priceStats = static::getPriceStats($data, $avgVol);
        $ret = [];
        if (isset($priceStats['realisticPrice'])) {
            $ret['buy'] = $priceStats['realisticPrice'];
            $ret['avgBuy5OrderAge'] = $priceStats['avgOrderAge'];
        }

        //calculate demand within 5% of realistic buy price
        if (isset($ret['buy'])) {
            $ret['demandIn5'] = 0;
            foreach ($data as $order) {
                //skip orders with ludicrous minimum volume
                if ($order->volume > ($avgVol < 1 ? 1 : $avgVol)) {
                    continue;
                }

                //if cut-off sum reached, break
                if ($ret['buy'] * 0.95 > $order->price) {
                    break;
                }

                $ret['demandIn5'] += $order->volume;
            }
        }
        return $ret;
    }

    /**
     * Estimate realistic prices by calculating weighted average of top most orders equivalent to 5% of daily volume.
     *
     * @param array $odata the orders (buy or sell)
     * @param float $avgVol
     *
     * @return array with estimated price and average order age
     */
    protected static function getPriceStats(array $odata, $avgVol)
    {
        if (count($odata) < 1) {
            return [];
        }

        $volsum   = 0;
        $pricesum = 0;
        $timesum  = 0; //for average order age

        foreach ($odata as $order) {
            //skip orders with ludicrous minimum volume
            if ($order->minVolume > ($avgVol < 1 ? 1 : $avgVol)) {
                continue;
            }

            //accumulate volume for weighted averages
            $volsum   += $order->volume;
            //accumulate prices weighted with volume
            $pricesum += $order->volume * $order->price;
            //accumulate order age in seconds weighted by volume
            $timesum    += $order->volume * (time() - strtotime($order->issued));

            // if 5% cut-off reached, break
            if ($volsum >= $avgVol * 0.05) {
                break;
            }
        }

        //if the volume is zero we set it to 1 for the purpose of calculating a realistic price
        if ($volsum == 0) {
            $volsum = 1;
        }

        return array(
            //get the averages by dividing by the cumulated volume
            'realisticPrice' => $pricesum / $volsum,
            'avgOrderAge'   => (int) ($timesum / $volsum)
        );
    }
}
