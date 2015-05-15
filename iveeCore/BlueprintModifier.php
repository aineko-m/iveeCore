<?php
/**
 * BlueprintModifier class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/BlueprintModifier.php
 */

namespace iveeCore;

/**
 * BlueprintModifier provides methods for getting research levels of specific blueprints. Note that this is a stub
 * implementation, where every blueprint is researched to the limit.
 * Developers should implement their own class extending from this one or implementing the IBlueprintModifier interface.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/BlueprintModifier.php
 */
class BlueprintModifier implements IBlueprintModifier
{
    /**
     * Returns the ME level for a specific blueprint.
     *
     * @param int $bpId the ID of the blueprint being looked up
     *
     * @return int blueprint ME level
     */
    public function getBpMeLevel($bpId)
    {
        return -10;
    }

    /**
     * Returns the TE level for a specific blueprint.
     *
     * @param int $bpId the ID of the blueprint being looked up
     *
     * @return int blueprint TE level
     */
    public function getBpTeLevel($bpId)
    {
        return -20;
    }
}
