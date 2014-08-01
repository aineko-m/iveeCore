<?php
/**
 * Util class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Util.php
 *
 */

namespace iveeCore;

/**
 * Util offers helper functions like string formatting
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Util.php
 *
 */
class Util
{
    /**
     * Converts long numbers to nice readable representation with appended unit: K, M or G
     * 
     * @param int|float $val the number to be formatted
     * 
     * @return string the formated number
     */
    public static function quantitiesToReadable($val)
    {
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
     * 
     * @param int $fseconds the seconds to be formatted
     * 
     * @return string the formated time
     */
    public static function secondsToReadable($fseconds)
    {
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
}
