<?php
/**
 * IBlueprintModifier interface file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IBlueprintModifier.php
 */

namespace iveeCore;

/**
 * IBlueprintModifier is the interface to be implemented by classes that will be used as BlueprintModifier, providing
 * methods for getting research levels for specific blueprints.
 *
 * @category IveeCore
 * @package  IveeCoreInterfaces
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/IBlueprintModifier.php
 */
interface IBlueprintModifier
{
    /**
     * Returns the ME level for a specific blueprint.
     *
     * @param int $bpId the ID of the blueprint being looked up
     *
     * @return int blueprint ME level
     */
    public function getBpMeLevel($bpId);

    /**
     * Returns the TE level for a specific blueprint.
     *
     * @param int $bpId the ID of the blueprint being looked up
     *
     * @return int blueprint TE level
     */
    public function getBpTeLevel($bpId);
}
