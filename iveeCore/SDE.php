<?php
/**
 * SDE Class file.
 *
 * PHP version 5.4
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SDE.php
 */

namespace iveeCore;

/**
 * SDE Class.
 * This class is used for handling interaction with the EVE's static data export (SDE) database.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/SDE.php
 */
class SDE
{
    const IVEECORE_VERSION = '3.0.3';

    /**
     * @var \iveeCore\SDE $instance holds the singleton SDE object.
     */
    protected static $instance;

    /**
     * @var \mysqli $db holds the DB connections.
     */
    protected $db;

    /**
     * @var int $numQueries stores the number of queries run against the DB.
     */
    protected $numQueries = 0;

    /**
     * @var float $timeQueries stores the sum of time spent waiting for DB queries to return.
     */
    protected $timeQueries = 0.0;

    /**
     * Returns (singleton) SDE instance.
     *
     * @param \mysqli $db is an optional reference to an existing DB connection object
     *
     * @return \iveeCore\SDE
     */
    public static function instance(\mysqli $db = null)
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($db);
        }
        return static::$instance;
    }

    /**
     * Constructor. Get singleton instance via iveeCore\SDE::instance() instead.
     *
     * @param \mysqli $db is an optional reference to an existing DB connection object
     */
    protected function __construct(\mysqli $db = null)
    {
        if (!isset($db)) {
            $db = $this->connectDb();
            if ($db->connect_error) {
                exit('Fatal Error: ' . $db->connect_error . PHP_EOL);
            }
        } elseif (!($db instanceof \mysqli)) {
            exit('Fatal Error: parameter given is not a mysqli object' . PHP_EOL);
        }
        $this->db = $db;

        //ivee uses transactions so turn off autocommit
        $this->db->autocommit(false);

        //eve runs on UTC time
        $this->db->query("SET time_zone='+0:00';");
    }

    /**
     * Returns a new mysqli connection object.
     *
     * @return \mysqli
     */
    protected function connectDb()
    {
        return new \mysqli(
            Config::getSdeDbHost(),
            Config::getSdeDbUser(),
            Config::getSdeDbPw(),
            Config::getSdeDbName(),
            Config::getSdeDbPort()
        );
    }

    /**
     * Performs a SQL query.
     *
     * @param string $sql the query to be sent to the DB
     *
     * @return \mysql_result
     * @throws \iveeCore\Exceptions\SQLErrorException when the query execution errors out
     */
    public function query($sql)
    {
        $startTime = microtime(true);
        $res = $this->db->query($sql);
        if ($this->db->error) {
            $exceptionClass = Config::getIveeClassName('SQLErrorException');
            throw new $exceptionClass($this->db->error . "\nQuery: " . $sql, $this->db->errno);
        }
        //gather stats about queries
        $this->addQueryTime(microtime(true) - $startTime);

        return $res;
    }

    /**
     * Performs multiple SQL queries. Results are flushed and not returned. Useful for multiple updates and inserts.
     *
     * @param string $multiSql semicolon separated queries to be sent to the DB
     *
     * @return void
     * @throws \iveeCore\Exceptions\SQLErrorException when the query execution errors out
     */
    public function multiQuery($multiSql)
    {
        $startTime = microtime(true);
        if (!$this->db->multi_query($multiSql)) {
            $exceptionClass = Config::getIveeClassName('SQLErrorException');
            throw new $exceptionClass($this->db->error . "\nQuery: " . $multiSql, $this->db->errno);
        }
        //gather stats about queries
        $this->addQueryTime(microtime(true) - $startTime);
        $this->flushDbResults();
    }

    /**
     * Flushes any remaining result sets from previous (multi) queries.
     * Necessary so subsequent queries don't fail.
     *
     * @return void
     */
    public function flushDbResults()
    {
        while ($this->db->more_results() && $this->db->next_result()) {

        }
    }

    /**
     * Commits pending DB transactions.
     *
     * @return bool true on success
     */
    public function commit()
    {
        $startTime = microtime(true);
        $ret = $this->db->commit();
        //gather stats about queries
        $this->addQueryTime(microtime(true) - $startTime);

        return $ret;
    }

    /**
     * Rollback pending DB transactions.
     *
     * @return boolean true on success
     */
    public function rollback()
    {
        $startTime = microtime(true);
        $ret = $this->db->rollback();
        //gather stats about queries
        $this->addQueryTime(microtime(true) - $startTime);

        return $ret;
    }

    /**
     * Add SQL query time for performance tracking.
     *
     * @param float $time to add
     *
     * @return void
     */
    protected function addQueryTime($time)
    {
        $this->timeQueries += $time;
        $this->numQueries++;
    }

    /**
     * Returns various statistics for database and queries.
     *
     * @return array
     */
    public function getStats()
    {
        return array(
            'numQueries'      => $this->numQueries,
            'timeQueries'     => $this->timeQueries,
            'peakMemoryUsage' => memory_get_peak_usage(true),
            'mysqlStats'      => $this->db->get_connection_stats()
        );
    }

    /**
     * Makes INSERT or "INSERT .. ON DUPLICATE KEY UPDATE" SQL query string.
     *
     * @param string $table the name of the SQL table to be used
     * @param array $insert the data to be inserted in the form column => value, where value is an int, float or
     * string. Strings are automatically sanitized and enquoted.
     * @param array $update the data to be updated as column => value, optional. If not given, a regular insert is
     * created.
     *
     * @return string
     * @throws \iveeCore\Exceptions\InvalidArgumentException if unsuported value types are passed
     */
    public static function makeUpsertQuery($table, array $insert, array $update = null)
    {
        $exceptionClass = Config::getIveeClassName('InvalidArgumentException');
   
        //prepare columns and values list
        $icols   = "";
        $ivalues = "";
        foreach ($insert as $i => $val) {
            if (!(is_int($val) or is_float($val) or is_string($val))) {
                throw new $exceptionClass("Supported data types: int, float, string");
            }
            if (is_string($val)) {
                $val = static::sanitizeAndEnquoteString($val);
            }
            $icols   .= ", `" . $i . "`";
            $ivalues .= ", " . $val;
        }

        $icols   = substr($icols, 2);
        $ivalues = substr($ivalues, 2);

        $q = "INSERT INTO " . $table . " (" . $icols . ") VALUES (" . $ivalues . ")";

        if (is_array($update)) {
            $us = "";
            foreach ($update as $u => $val) {
                if (!(is_int($val) or is_float($val) or is_string($val))) {
                    throw new $exceptionClass("Supported data types: int, float, string");
                }
                if (is_string($val)) {
                    $val = static::sanitizeAndEnquoteString($val);
                }
                $us .= ", `" . $u . "` = " . $val;
            }
            $q .= PHP_EOL . "ON DUPLICATE KEY UPDATE " . substr($us, 2);
        }
        return $q . ";" . PHP_EOL;
    }

    /**
     * Makes simple "UPDATE" SQL query string.
     *
     * @param string $table the name of the SQL table to be used
     * @param array $update the data to be updated as column => value. Values need to be already escaped, if required
     * by type.
     * @param array $where the conditions for the update as column => value. Conditions are linked via 'AND'.
     *
     * @return string
     * @throws \iveeCore\Exceptions\InvalidArgumentException if unsuported value types are passed
     */
    public static function makeUpdateQuery($table, array $update, array $where)
    {
        $data = [];
        $condition = [];
        $exceptionClass = Config::getIveeClassName('InvalidArgumentException');

        foreach ($update as $col => $val) {
            if (!(is_int($val) or is_float($val) or is_string($val))) {
                throw new $exceptionClass("Supported data types: int, float, string");
            }
            if (is_string($val)) {
                $val = static::sanitizeAndEnquoteString($val);
            }
            $data[] = $col . "=" . $val;
        }

        foreach ($where as $col => $val) {
            if (!(is_int($val) or is_float($val) or is_string($val))) {
                throw new $exceptionClass("Supported data types: int, float, string");
            }
            if (is_string($val)) {
                $val = static::sanitizeAndEnquoteString($val);
            }
            $condition[] = $col . "=" . $val;
        }

        return "UPDATE " . $table . " SET " . implode(', ', $data)
            . " WHERE " . implode(' AND ', $condition) . ';' . PHP_EOL;
    }

    /**
     * Sanitizes string. Unallowed characters are replaced by whitespaces.
     *
     * @param string $string to be sanitized
     *
     * @return string
     */
    public static function sanitizeString($string)
    {
        return preg_replace(Config::SANITIZE_STRING_PATTERN, ' ', $string);
    }

    /**
     * Sanitizes and double-enquotes string for use in SQL queries. Unallowed characters are replaced by whitespaces.
     *
     * @param string $string to be sanitized
     *
     * @return string
     */
    public static function sanitizeAndEnquoteString($string)
    {
        return '"' . static::sanitizeString($string) . '"';
    }
}
