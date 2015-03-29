<?php
/**
 * CrestDataUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/CrestDataUpdater.php
 */

namespace iveeCore\CREST;
use \iveeCore\Config;

/**
 * Abstract base class for the specific CREST endpoint updaters.
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/CrestDataUpdater.php
 */
abstract class CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = '';

    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.Api-v3';

    /**
     * @var \stdClass $data holding the data received from CREST
     */
    protected $data;

    /**
     * @var int[] $updatedIDs holding the updated IDs
     */
    protected $updatedIDs = array();

    /**
     * Constructor.
     *
     * @param \stdClass $data the data received from CREST
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Saves the data to the database.
     *
     * @return void
     */
    public function insertIntoDB()
    {
        //lookup SDE class
        $sdeClass = Config::getIveeClassName('SDE');
        $sdeDb = $sdeClass::instance();
        $sql = '';
        $count = 0;

        foreach ($this->data->items as $item) {
            $sql .= $this->processDataItemToSQL($item);
            $count++;
            if ($count % 100 == 0 OR $count == $this->data->totalCount) {
                $sdeDb->multiQuery($sql . ' COMMIT;');
                $sql = '';
            }
        }

        $this->invalidateCaches();
        $this->updatedIDs = array();
    }

    /**
     * Processes data objects to SQL.
     *
     * @param \stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        return '';
    }

    /**
     * Invalidate any cache entries that were update in the DB.
     *
     * @return void
     */
    protected function invalidateCaches()
    {
    }

    /**
     * Perform the complete update.
     *
     * @return void
     */
    public static function doUpdate()
    {
        //get CrestFetcher class name and instantiate
        $crestFetcherClass = Config::getIveeClassName('CrestFetcher');
        $cf = new $crestFetcherClass;
        echo get_called_class() . ' getting data from CREST... ';

        //fetch the data, check returned representation name
        $data = $cf->getCrestData(static::$path, static::$representationName);
        echo "Done" . PHP_EOL . 'Saving data in DB... ';

        //store in DB
        $citu = new static($data);
        $citu->insertIntoDB();
        echo 'Done' . PHP_EOL;
    }
}
