<?php
/**
 * SpecialitiesUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/SpecialitiesUpdater.php
 *
 */

namespace iveeCore\CREST;

/**
 * SpecialitiesUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/SpecialitiesUpdater.php
 *
 */
class SpecialitiesUpdater extends CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = 'industry/specialities/';
    
    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.IndustrySpecialityCollection-v1';

    /**
     * Saves the data to the database
     *
     * @return void
     */
    public function insertIntoDB()
    {
        //lookup SDE class
        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //clear existing data
        $sql = 'DELETE FROM iveeSpecialityGroups; DELETE FROM iveeSpecialities;';

        foreach ($this->data->items as $item)
            $sql .= $this->processDataItemToSQL($item);

        $sde->multiQuery($sql . ' COMMIT;');

        $this->invalidateCaches();
    }

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
        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

        $update = array();

        if (!isset($item->id))
            throw new $exceptionClass("specialityID missing from specialities CREST data");
        $specialityID = (int) $item->id;
        
        if (!isset($item->name))
            throw new $exceptionClass("name missing from specialities CREST data");
        $update['specialityName'] = $item->name;

        $insert = $update;
        $insert['specialityID'] = $specialityID;

        $sql = $sdeClass::makeUpsertQuery('iveeSpecialities', $insert, $update);

        if (!isset($item->groups))
            throw new $exceptionClass("groups missing from specialities CREST data");

        foreach ($item->groups as $group) {
            if (!isset($group->id))
                throw new $exceptionClass("groupID missing from specialities CREST data");
            $update = array('groupID' => (int) $group->id);
            $insert = $update;
            $insert['specialityID'] = $specialityID;
            $sql .= $sdeClass::makeUpsertQuery('iveeSpecialityGroups', $insert, $update);
        }
        return $sql;
    }
}
