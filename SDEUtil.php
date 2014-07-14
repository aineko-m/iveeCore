<?php

/**
 * SDEUtil offers a series of helper functions, mostly for looking up default values, calculate factors, sanity 
 * checking parameters and handling string formatting
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/SDEUtil.php
 * @package iveeCore
 */
class SDEUtil {
    
    /**
     * Calulcates the effective reprocessing (= refining) yield
     * @param float $stationRefineryEffiency of the station you are reprocessing at
     * @param int $refiningSkillLevel. If left null, it is looked up.
     * @param int $refineryEfficiencySkillLevel. If left null it is looked up.
     * @param int $specificRefiningSkillLevel item-specific reprocessing skill level (or refining or scrap metal 
     * processing)
     * @param float $implantMod implant reprocessing yield bonus, as factor (1.0 >= x <= 1.05)
     * @return float reprocessing yield
     * @throws InvalidParameterValueException if invalid parameter values are given
     */
    public static function calcReprocessingYield(
            $stationRefineryEffiency = 0.5, 
            $refiningSkillLevel = null, 
            $refineryEfficiencySkillLevel = null, 
            $specificRefiningSkillLevel = 0, 
            $implantMod = 1.0){
        
        if($stationRefineryEffiency < 0.3 OR $stationRefineryEffiency > 0.5) 
            throw new InvalidParameterValueException("Station refinery efficiency needs to be >=0.3 and <= 0.5");
        if($implantMod < 1.0 OR $implantMod > 1.05) 
            throw new InvalidParameterValueException("Implant modifier needs to be >= 1.0 and <= 1.05");
        
        //set undefined skill levels, sanity check otherwise
        $defaults = SDE::instance()->defaults;
        $skillMapClass = iveeCoreConfig::getIveeClassName('SkillMap');
        if(is_null($refiningSkillLevel))
            $refiningSkillLevel = $defaults->getSkillLevel(3385);
        else
            $skillMapClass::sanityCheckSkillLevel($refiningSkillLevel);

        if(is_null($refineryEfficiencySkillLevel))
            $refineryEfficiencySkillLevel = $defaults->getSkillLevel(3389);
        else
            $skillMapClass::sanityCheckSkillLevel($refineryEfficiencySkillLevel);
        
        $skillMapClass::sanityCheckSkillLevel($specificRefiningSkillLevel);

        //calculate reprocessing yield
        $reprocessingYield = $stationRefineryEffiency 
        + 0.375 
            * (1 + $refiningSkillLevel * 0.02) 
            * (1 + $refineryEfficiencySkillLevel * 0.04) 
            * (1 + $specificRefiningSkillLevel * 0.05)
        + ($implantMod - 1);
        
        //clamp to 1
        if($reprocessingYield > 1.0) $reprocessingYield = 1.0;
        
        return $reprocessingYield;
    }
    
    /**
     * Calulcates the tax factor for refining in stations (5% tax = factor of 0.95)
     * @param float $standings with the corporation of the station you are reprocessing at
     * @return float reprocessing tax factor
     * @throws InvalidParameterValueException if invalid parameter values are given
     */
    public static function calcReprocessingTaxFactor($standings = 6.67){
        //sanity checks
        if($standings < 0 OR $standings > 10) 
            throw new InvalidParameterValueException("Standing needs to be between 0.0 and 10.0");
        
        //calculate tax factor
        $tax = 0.05 - (0.0075 * $standings);
        if($tax < 0) $tax = 0;
        
        return 1 - $tax;
    }
    
    /**
     * Converts long numbers to nice readable representation with appended unit: K, M or G
     * @param int|float $val the number to be formatted
     * @return string the formated number
     */
    public static function quantitiesToReadable($val) {
        if (abs($val) < 1000) {
            if ($val - ((int) $val) !== 0)
                return sprintf("%1.2f", $val);
            else
                return $val;
        } elseif (abs($val) >= 1000000000) {
            $val = $val / 1000000000;
            $unit = 'G';
        } elseif (abs($val) >= 1000000) {
            $val = $val / 1000000;
            $unit = 'M';
        } else {
            $val = $val / 1000;
            $unit = 'K';
        }
        return sprintf("%1.2f", $val) . $unit;
    }
    
    /**
     * Convenience function for converting large second values into a 1d2h33m44s representation
     * @param int $fseconds the seconds to be formatted
     * @return string the formated time
     */
    public static function secondsToReadable($fseconds) {
        $seconds = (int) $fseconds;
        if (($fseconds - $seconds) * 60 > 1)
            $seconds++;

        $readable = "";
        if ($seconds >= (24 * 3600)) {
            $readable .= (int) ($seconds / (24 * 60 * 60)) . "d ";
            $seconds = $seconds % (24 * 60 * 60);
            return $readable . (int) ($seconds / (60 * 60)) . "h";
        }
        if ($seconds >= 3600) {
            $readable .= (int) ($seconds / (60 * 60)) . "h ";
            $seconds = $seconds % (60 * 60);
            return $readable . (int) ($seconds / 60) . "m";
        }
        if ($seconds >= 60) {
            $readable .= (int) ($seconds / 60) . "m ";
            $seconds = $seconds % 60;
        }
        $readable .= $seconds . "s";

        return $readable;
    } 
    
    /**
     * Makes INSERT .. ON DUPLICATE KEY UPDATE query string
     * @param string $table the name of the SQL table to be used
     * @param array $insert the data to be inserted as column => value. Values need to be already escaped, if required 
     * by type.
     * @param array $update the data to be updated as column => value, optional. If not given, a regular insert is 
     * performed.
     * @return string the SQL query
     */
    public static function makeUpsertQuery($table, $insert, $update = NULL){
        //prepare columns and values list
        $icols   = "";
        $ivalues = "";
        foreach($insert as $i => $val){
            if(!isset($val)) continue;
            $icols   .= ", `".$i."`";
            $ivalues .= ", ".$val;
        }

        $icols   = substr($icols, 2);
        $ivalues = substr($ivalues, 2);

        $q = "INSERT INTO ".$table." (".$icols.") VALUES (".$ivalues.")";

        if(is_array($update)){
            $us = "";
            foreach($update as $u => $val){
                if(!isset($val)) continue;
                $us .= ", `".$u."` = ".$val;
            }
            $q .= PHP_EOL . "ON DUPLICATE KEY UPDATE ".substr($us, 2);
        } 
        return $q.";" . PHP_EOL;
    }
    
    /**
     * Makes simple UPDATE query string
     * @param string $table the name of the SQL table to be used
     * @param array $update the data to be updated as column => value. Values need to be already escaped, if required 
     * by type.
     * @param array $where the conditions for the update as column => value. Conditions are linked via 'AND'.
     * @return string the SQL query
     */
    public static function makeUpdateQuery($table, $update, $where){
        $data = array();
        $condition = array();
        
        foreach ($update as $col => $val){
            $data[] = $col . "=" . $val;
        }
        
        foreach ($where as $col => $val) {
            $condition[] = $col . "=" . $val;
        }
        
        return "UPDATE " . $table . " SET " . implode(', ', $data) 
            . " WHERE " . implode(' AND ', $condition) . ';' . PHP_EOL;
    }
}

?>