<?php
/**
 * TeamsUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/TeamsUpdater.php
 *
 */

namespace iveeCore\CREST;

/**
 * TeamsUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/TeamsUpdater.php
 *
 */
class TeamsUpdater extends CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = 'industry/teams/';
    
    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.IndustryTeamCollection-v1';
    
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

        if (!isset($item->id))
            throw new $exceptionClass('teamID missing in Teams CREST data');
        $teamID = (int) $item->id;

        //skip expired teams
        if (strtotime($item->expiryTime) < time())
            return '';

        $update = array();
        $update['solarSystemID'] = (int) $item->solarSystem->id;
        $update['expiryTime']    = date('Y-m-d H:i:s', strtotime($item->expiryTime));
        $update['creationTime']  = date('Y-m-d H:i:s', strtotime($item->creationTime));
        $update['costModifier']  = (int) $item->costModifier;
        $update['specID']        = (int) $item->specialization->id;
        $update['activityID']    = (int) $item->activity;
        $update['teamName']      = str_replace('<br>', ' ', $item->name);

        $update['w0BonusID']    = (int) $item->workers[0]->bonus->id;
        $update['w0BonusValue'] = (float) $item->workers[0]->bonus->value;
        $update['w0SpecID']     = (int) $item->workers[0]->specialization->id;

        $update['w1BonusID']    = (int) $item->workers[1]->bonus->id;
        $update['w1BonusValue'] = (float) $item->workers[1]->bonus->value;
        $update['w1SpecID']     = (int) $item->workers[1]->specialization->id;

        $update['w2BonusID']    = (int) $item->workers[2]->bonus->id;
        $update['w2BonusValue'] = (float) $item->workers[2]->bonus->value;
        $update['w2SpecID']     = (int) $item->workers[2]->specialization->id;

        $update['w3BonusID']    = (int) $item->workers[3]->bonus->id;
        $update['w3BonusValue'] = (float) $item->workers[3]->bonus->value;
        $update['w3SpecID']     = (int) $item->workers[3]->specialization->id;

        $insert = $update;
        $insert['teamID'] = $teamID;

        $this->updatedIDs[] = $update['solarSystemID'];

        return $sdeClass::makeUpsertQuery('iveeTeams', $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        //Team caches do not need to be invalidated as teams are immutable
        //Delete Systems from cache where teams are
        $assemblyLineClass  = \iveeCore\Config::getIveeClassName('SolarSystem');
        $assemblyLineClass::getInstancePool()->deleteFromCache($this->updatedIDs);
    }
}
