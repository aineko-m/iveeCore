<?php
/**
 * MaterialParseResult class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MaterialParseResult.php
 *
 */

namespace iveeCore;

/**
 * MaterialParseResult objects are used to return material parsing results. They are implemented as simple extension of
 * MaterialMap with an additonal array attribute for unparseables.
 * Inheritance: MaterialParseResult -> MaterialMap
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/MaterialParseResult.php
 *
 */
class MaterialParseResult extends MaterialMap
{
    /**
     * @var array $unparseables holds whatever strings couldn't be parsed or an error description.
     */
    protected $unparseables;

    /**
     * Add an unparseable string or error description. Strings are sanitized for security.
     *
     * @param string $unparseable the string that couldn't be parsed to an item
     *
     * @return void
     */
    public function addUnparseable($unparseable)
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $this->unparseables[] = $sdeClass::sanitizeString($unparseable);
    }

    /**
     * Returns the array with the unparseables.
     *
     * @return array
     */
    public function getUnparseables()
    {
        return $this->unparseables;
    }
}
