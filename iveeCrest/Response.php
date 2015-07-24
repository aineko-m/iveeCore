<?php
/**
 * Response class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Response.php
 */

namespace iveeCrest;
use iveeCore\ICacheable;

/**
 * Response encapsulates CREST-specific http responses.
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Response.php
 */
class Response implements ICacheable
{
    /**
     * @var string $key under which this response is cached
     */
    protected $key;

    /**
     * @var int $expiry the objects absolute cache expiry as unix timestamp
     */
    protected $expiry;

    /**
     * @var stdClass $content decoded response json body
     */
    public $content;

    /**
     * @var array $header of http response
     */
    protected $header = array();

    /**
     * @var array $info from curl
     */
    protected $info;

    /**
     * Constructor.
     * 
     * @param string $key under which this Response is cached
     */
    public function __construct($key)
    {
        $this->key = $key;
        //set a default expiry. This is overriden once content and header are set to the object.
        $this->expiry = time() + 3600;
    }

    /**
     * Adds the content to the response, which will get JSON decoded and the curl info array.
     * 
     * @param string $content JSON encoded response content
     * @param array $info the curl info array
     *
     * @return void
     */
    public function setContentAndInfo($content, array $info)
    {
        $this->content = json_decode($content);
        $this->info = $info;

        //for some reason curl sometimes fails to decompress gzipped responses, so we must do it manually
        if(empty($this->content)
            AND isset($this->header['Content-Encoding'])
            AND $this->header['Content-Encoding'] == 'gzip'
        )
            $this->content = json_decode(gzdecode($content));

        //add the response timestamp to the content
        if (isset($this->header['Date']))
            $this->content->dateTs = strtotime($this->header['Date']);

        if (isset($this->content->access_token))
            //we need to expire (and refresh) the access token before it expires on CCPs side, which appears to happen
            //earlier than it should
            $this->expiry = time() + (int) $this->content->expires_in - 20;
        elseif (isset($this->content->expires_in))
            $this->expiry = time() + (int) $this->content->expires_in;
        elseif (isset($this->header['Cache-Control'])) {
            foreach (explode(',', $this->header['Cache-Control']) as $frag) {
                if (substr(trim($frag), 0, 8) == 'max-age=')
                    $this->expiry = time() + (int) substr(trim($frag), 8);
            }
        }
    }

    /**
     * Callback function specified with CURLOPT_HEADERFUNCTION, used to process header lines.
     * 
     * @param curl_handle $curl passed to callback
     * @param string $headerLine a single line from the http response header
     * 
     * @return int the length of the input line (this is required)
     */
    public function handleCurlHeaderLine($curl, $headerLine)
    {
        $frags = explode(": ", $headerLine);
        if(count($frags) == 2)
            $this->header[$frags[0]] = trim($frags[1]);
        return strlen($headerLine);
    }

    /**
     * Returns the content type of the response.
     *
     * @return string
     */
    public function getContentType()
    {
        if (!isset($this->header['Content-Type']))
            return;
        $ctypes = explode(';', $this->header['Content-Type']);
        return trim($ctypes[0]);
    }

    /**
     * Returns the number of total pages available on the request endpoint.
     *
     * @return int
     */
    public function getPageCount()
    {
        if (isset($this->content->pageCount))
            return (int) $this->content->pageCount;
        return 1;
    }

    /**
     * Checks if the response has a next page.
     *
     * @return bool
     */
    public function hasNextPage()
    {
        if(isset($this->content->next->href))
            return true;
        return false;
    }

    /**
     * Returns the next page href, if there is one.
     *
     * @return string
     * @throws iveeCres\Exceptions\IveeCrestException when the response has no next page
     */
    public function getNextPageHref()
    {
        if($this->hasNextPage())
            return $this->content->next->href;

        $iveeCrestExceptionClass = Config::getIveeClassName('IveeCrestException');
        throw new $iveeCrestExceptionClass('No next page href present in response body');
    }

    /**
     * Checks if the response has a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        if(isset($this->content->previous->href))
            return true;
        return false;
    }

    /**
     * Returns the previous page href, if there is one.
     *
     * @return string
     * @throws iveeCres\Exceptions\IveeCrestException when the response has no previous page
     */
    public function getPreviousPageHref()
    {
        if($this->hasPreviousPage())
            return $this->content->previous->href;

        $iveeCrestExceptionClass = Config::getIveeClassName('IveeCrestException');
        throw new $iveeCrestExceptionClass('No previous page href present in response body');
    }

    /**
     * Check if the response header included a deprecation warning.
     *
     * @return bool
     */
    public function isDeprecated()
    {
        if (isset($this->header['X-Deprecated']))
            return true;
        return false;
    }

    /**
     * Returns the header of the http response
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Returns the CURL info for the http response
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Returns the key of the ICacheable object under which it is stored and retrieved from the cache.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the objects absolute cache expiry time as unix timestamp.
     *
     * @return int
     */
    public function getCacheExpiry()
    {
        return $this->expiry;
    }
}
