<?php
/**
 * FitParser class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/FitParser.php
 *
 */

namespace iveeCore;

/**
 * FitParser provides methods to parse different formats of fits or scan results to a MaterialParseResult.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/FitParser.php
 *
 */
class FitParser
{
    /**
     * Parses EFT-style fits to MaterialParseResult.
     * 
     * @param string $eftFit the string to be parsed
     * 
     * @return \iveeCore\MaterialParseResult
     */
    public static function parseEftFit($eftFit)
    {
        $materialParseResultClass = Config::getIveeClassName('MaterialParseResult');
        $pr = new $materialParseResultClass;

        //get fit as line array
        $lines = explode("\n", str_replace("\r\n", "\n", trim($eftFit)));

        //extract the ship name string from the first line
        $headerLineFrags = explode(',', $lines[0]);
        $shipName = substr($headerLineFrags[0], 1);

        //add the ship to the MaterialParseResult MaterialMap
        try {
            $pr->addMaterial(Type::getIdByName($shipName), 1);
        } catch (Exceptions\TypeNameNotFoundException $e) {
            $pr->addUnparseable($shipName);
        }

        //remove first line from array
        unset($lines[0]);

        //iterate over lines
        foreach ($lines as $line) {
            //split line at ',', if present
            foreach (explode(',', $line) as $lineFrag) {
                //clean string
                $lineFrag = trim($lineFrag);

                //skip empty lines
                if (strlen($lineFrag) <1) continue;

                //detect quantities appended at end of string like 'itemName x123'
                $lineFrag = preg_split('/( x[0-9]+)$/', $lineFrag, null, PREG_SPLIT_DELIM_CAPTURE);
                try {
                    //if regex split happened
                    if (count($lineFrag) > 1)
                        $pr->addMaterial(Type::getIdByName($lineFrag[0]), (int) substr($lineFrag[1], 2));
                    //if no quantity specified
                    else
                        $pr->addMaterial(Type::getIdByName($lineFrag[0]), 1);
                } catch (Exceptions\TypeNameNotFoundException $e) {
                    $pr->addUnparseable(implode('', $lineFrag));
                }
            }
        }
        return $pr;
    }

    /**
     * Parses fits in EvE or EFT XML format to MaterialParseResult
     * 
     * @param \DOMDocument $fitDom the XML DOM to be parsed
     * 
     * @return \iveeCore\MaterialParseResult
     */
    public static function parseXmlFit(\DOMDocument $fitDom)
    {
        $materialParseResultClass = Config::getIveeClassName('MaterialParseResult');
        $pr = new $materialParseResultClass;

        //get ships
        foreach ($fitDom->getElementsByTagName('shipType') as $shipNode) {
            if ($shipNode->hasAttribute('value')) {
                try {
                    $pr->addMaterial(Type::getIdByName($shipNode->getAttribute('value')), 1);
                } catch (Exceptions\TypeNameNotFoundException $e) {
                    $pr->addUnparseable($shipNode->getAttribute('value'));
                }
            } else
                $pr->addUnparseable("Can't parse line " . $shipNode->getLineNo());
        }

        //get fittings
        foreach ($fitDom->getElementsByTagName('hardware') as $hardwareNode) {
            //if attribute 'qty' is present, use it as quantity, otherwise default to 1
            if ($hardwareNode->hasAttribute('qty'))
                $qty = (int) $hardwareNode->getAttribute('qty');
            else
                $qty = 1;
            try {
                $pr->addMaterial(Type::getIdByName($hardwareNode->getAttribute('type')), $qty);
            } catch (Exceptions\TypeNameNotFoundException $e) {
                $pr->addUnparseable("Can't parse line " . $hardwareNode->getLineNo());
            }
        }
        return $pr;
    }

    /**
     * Parses cargo or ship scan results to MaterialParseResult
     * 
     * @param string $scanResult to be parsed
     * 
     * @return \iveeCore\MaterialParseResult
     */
    public static function parseScanResult($scanResult)
    {
        $materialParseResultClass = Config::getIveeClassName('MaterialParseResult');
        $pr = new $materialParseResultClass;

        //iterate over lines
        foreach (explode("\n", str_replace("\r\n", "\n", trim($scanResult))) as $line) {
            $line = trim($line);
            if (strlen($line) < 1) continue;

            //split line if item is preceded with a quantifier in the form "123 An Item"
            $lineFrag = preg_split('/^([0-9]+ )/', $line, null, PREG_SPLIT_DELIM_CAPTURE);

            try {
                if (count($lineFrag) == 1) {
                    $pr->addMaterial(Type::getIdByName($lineFrag[0]), 1);
                } elseif (count($lineFrag) == 3) {
                    $pr->addMaterial(Type::getIdByName($lineFrag[2]), (int) $lineFrag[1]);
                } else {
                    $pr->addUnparseable($line);
                }
            } catch (Exceptions\TypeNameNotFoundException $e) {
                $pr->addUnparseable(implode('', $lineFrag));
            }
        }
        return $pr;
    }
}
