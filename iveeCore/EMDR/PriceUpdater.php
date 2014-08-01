<?php
/**
 * EmdrPriceUpdate class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/EmdrPriceUpdate.php
 *
 */

namespace iveeCore\EMDR;

/**
 * EmdrPriceUpdate handles price/order data updates from EMDR
 * 
 * @category IveeCore
 * @package  IveeCoreEmdr
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/EMDR/EmdrPriceUpdate.php
 *
 */
class PriceUpdater
{
    /**
     * @var int $typeID of the item being updated
     */
    protected $typeID;

    /**
     * @var int $regionID of the region the data is from
     */
    protected $regionID;

    /**
     * @var int $generatedAt the unix timestamp of the datas generation
     */
    protected $generatedAt;

    /**
     * @var array $averages contains the averages for volume and transactions for the last 7 days
     */
    protected $averages;

    /**
     * @var float $sell the calculated sell price
     */
    protected $sell;

    /**
     * @var int $avgSell5OrderAge the average age in seconds for sell orders withing 5% of calculated sell price
     */
    protected $avgSell5OrderAge;

    /**
     * @var float $buy the calculated buy price
     */
    protected $buy;

    /**
     * @var int $avgBuy5OrderAge the average age in seconds for buy orders withing 5% of calculated buy price
     */
    protected $avgBuy5OrderAge;

    /**
     * @var int $demandIn5 the volume demanded by buy orders withing 5% of calculated buy price
     */
    protected $demandIn5;

    /**
     * @var int $supplyIn5 the volume available in sell orders withing 5% of calculated sell price
     */
    protected $supplyIn5;

    /**
     * Constructor
     * 
     * @param int $typeID ID of item this price data is from
     * @param int $regionID ID of the region this price data is from
     * @param int $generatedAt unix timestamp of the data generation
     * @param array $orders the order data rows
     * 
     * @return void
     */
    public function __construct($typeID, $regionID, $generatedAt, array $orders)
    {
        $this->typeID      = $typeID;
        $this->regionID    = $regionID;
        $this->generatedAt = $generatedAt;

        //separate buy and sell orders
        $sdata = array();
        $bdata = array();
        foreach ($orders as $order) {
            if ($order[6] == 1)
                $bdata[] = $order;
            else
                $sdata[] = $order;
        }

        //sort orders: sell ASC, buy DESC
        usort($bdata, array(get_called_class(), 'cmp'));
        $bdata = array_reverse($bdata);
        usort($sdata, array(get_called_class(), 'cmp'));

        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

        //get weekly averages for vol and tx from DB
        //Look at stored procedure iveeCompleteHistoryUpdate() for details
        $res = $sdeClass::instance()->query(
            "SELECT
            atp.avgVol,
            atp.avgTx
            FROM iveeTrackedPrices as atp
            WHERE atp.regionID = " . (int) $this->regionID . "
            AND atp.typeID = " . (int) $this->typeID . ";"
        );

        while ($tmp = $res->fetch_row()) {
            $this->averages['avgVol'] = (float) $tmp[0];
            $this->averages['avgTx']  = (float) $tmp[1];
        }
        //if vol or tx are below 1 unit, set to 1 unit for the purpose of calculations
        if (!isset($this->averages['avgVol']) OR $this->averages['avgVol'] < 1)
            $this->averages['avgVol'] = 1;

        if (!isset($this->averages['avgTx']) OR $this->averages['avgTx'] < 1)
            $this->averages['avgTx'] = 1;

        //estimate realistic prices
        $sellStats = static::getPriceStats($sdata, $this->averages, $this->generatedAt);
        $this->sell = $sellStats['avgOrderPrice'];
        $this->avgSell5OrderAge = $sellStats['avgOrderAge'];

        $buyStats = static::getPriceStats($bdata, $this->averages, $this->generatedAt);
        $this->buy = $buyStats['avgOrderPrice'];
        $this->avgBuy5OrderAge = $buyStats['avgOrderAge'];

        //get supply within 5% of calculated price
        if (isset($this->sell)) {
            $this->supplyIn5 = 0;
            foreach ($sdata as $sellorder) {
                //if cut-off reached, break
                if ($this->sell * 1.05 < $sellorder[0] )
                    break;
                $this->supplyIn5 += $sellorder[1];
            }
        }

        //get demand within 5% of calculated price
        if (isset($this->buy)) {
            $this->demandIn5 = 0;
            foreach ($bdata as $buyorder) {
                //skip orders with ludicrous minimum volume
                if ($buyorder[5] > $this->averages['avgVol'])
                    continue;
                //if cut-off sum reached, break
                if ($this->buy * 0.95 > $buyorder[0] )
                    break;
                $this->demandIn5 += $buyorder[1];
            }
        }
    }

    /**
     * Method for sorting arrays via usort() based on the 0th element in the arrays.
     * Used as helper function for sorting orders based on price.
     * 
     * @param array $a first array for comparison
     * @param array $b second array for comparison
     * 
     * @return int +1 if $a[0] is bigger, -1 if $b[0] is bigger, 0 if they are equal
     */
    protected static function cmp(array $a, array $b)
    {
        if ($a[0] == $b[0])
            return 0;
        return($a[0] > $b[0])? +1 : -1;
    }

    /**
     * Inserts price data into the DB
     * 
     * @return void
     */
    public function insertIntoDB()
    {
        if (isset($this->sell) OR isset($this->buy)) {
            $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

            //check if row already exists
            $res = $sdeClass::instance()->query(
                "SELECT regionID FROM iveePrices
                WHERE regionID = " . $this->regionID . "
                AND typeID = " . $this->typeID . "
                AND date = '" . date('Y-m-d', $this->generatedAt) . "';"
            );

            //if row already exists
            if ($res->num_rows == 1) {
                //update data
                $updatetData = array();
                if (isset($this->sell)) 
                    $updatetData['sell'] = $this->sell;
                if (isset($this->buy))  
                    $updatetData['buy']  = $this->buy;
                if (isset($this->avgSell5OrderAge)) 
                    $updatetData['avgSell5OrderAge'] = $this->avgSell5OrderAge;
                if (isset($this->avgBuy5OrderAge))  
                    $updatetData['avgBuy5OrderAge']  = $this->avgBuy5OrderAge;
                if (isset($this->demandIn5)) 
                    $updatetData['demandIn5'] = $this->demandIn5;
                if (isset($this->supplyIn5)) 
                    $updatetData['supplyIn5'] = $this->supplyIn5;

                $where = array(
                    'typeID'   => $this->typeID,
                    'regionID' => $this->regionID,
                    'date'     => date('Y-m-d', $this->generatedAt)
                );

                //build update query
                $sql = $sdeClass::makeUpdateQuery('iveePrices', $updatetData, $where);
            } else { //insert data
                $insertData = array(
                    'typeID'   => $this->typeID,
                    'regionID' => $this->regionID,
                    'date'     => date('Y-m-d', $this->generatedAt)
                );

                if (isset($this->sell)) 
                    $insertData['sell'] = $this->sell;
                if (isset($this->buy))  
                    $insertData['buy']  = $this->buy;
                if (isset($this->avgSell5OrderAge)) 
                    $insertData['avgSell5OrderAge'] = $this->avgSell5OrderAge;
                if (isset($this->avgBuy5OrderAge))  
                    $insertData['avgBuy5OrderAge']  = $this->avgBuy5OrderAge;
                if (isset($this->demandIn5)) 
                    $insertData['demandIn5'] = $this->demandIn5;
                if (isset($this->supplyIn5)) 
                    $insertData['supplyIn5'] = $this->supplyIn5;

                //build insert query
                $sql = $sdeClass::makeUpsertQuery('iveePrices', $insertData);
            }

            //add stored procedure call to complete the update
            $sql .= "CALL iveeCompletePriceUpdate(" . $this->typeID . ", " . $this->regionID . ", '"
                . date('Y-m-d H:i:s', $this->generatedAt) . "'); COMMIT;" . PHP_EOL;

            //execute the combined queries
            $sdeClass::instance()->multiQuery($sql);

            if (VERBOSE) {
                $ecClass = \iveeCore\Config::getIveeClassName('EmdrConsumer');
                $ec = $ecClass::instance();
                echo "P: " . $ec->getTypeNameById($this->typeID) . ' (' . $this->typeID . '), '
                    . $ec->getRegionNameById($this->regionID) . ' ('. $this->regionID . ')' . PHP_EOL;
            }
        }
    }

    /**
     * Estimate realistic prices by calculating weighted average of orders equivalent to 5% of daily volume
     * 
     * @param array $odata the orders (buy or sell)
     * @param array $averages with averages for volume and transactions
     * @param int $generatedAt the timestamp of data generation
     * 
     * @return array with estimated price and average order age
     */
    protected static function getPriceStats(array $odata, array $averages, $generatedAt)
    {
        if (count($odata) < 1) 
            return null;

        $volcumula   = 0;
        $pricecumula = 0;
        $tscumula    = 0; //for average order age

        foreach ($odata as $order) {
            //skip orders with ludicrous minimum volume
            if ($order[5] > $averages['avgVol']) 
                continue;

            //accumulate volume for weighted averages
            $volcumula   += $order[1];
            //accumulate prices weighted with volume
            $pricecumula += $order[1] * $order[0];
            //accumulate order age in seconds weighted by volume
            $tscumula    += $order[1] * ($generatedAt - strtotime($order[7]));

            // if 5% cut-off reached, break
            if ($volcumula >= $averages['avgVol']*0.05) 
                break;
        }

        if ($volcumula==0)
            $volcumula = 1;

        return array(
            //get the averages by dividing by the cumulated volume
            'avgOrderPrice' => $pricecumula/$volcumula,
            'avgOrderAge'   => (int) ($tscumula/$volcumula)
        );
    }
}
