<?php
/**
 * BaseResponse class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/BaseResponse.php
 */

namespace iveeCrest\Responses;

use iveeCore\ICacheable;

/**
 * BaseResponse represents a generic CREST response, serving as base class for specialized subtypes. It includes methods
 * for dealing with instantiation and caching.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/BaseResponse.php
 */
class BaseResponse implements ICacheable
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
     * @var \stdClass $content response payload content (decoded json)
     */
    protected $content;

    /**
     * @var array $header of http response
     */
    protected $header = [];

    /**
     * @var array $info from curl
     */
    protected $info;

    /**
     * Constructor.
     *
     * @param string $key under which this BaseResponse is cached
     * @param \stdClass $content the decoded json payload of the response
     * @param array $header the HTTP header data
     * @param array $info the CURL info data
     */
    public function __construct($key, \stdClass $content, array $header, array $info)
    {
        $this->key = $key;
        $this->setHeader($header);
        $this->setInfo($info);
        $this->setContent($content);

        if (isset($this->content->access_token)) {
            //we need to expire (and refresh) the access token before it expires on CCPs side, which appears to happen
            //earlier than it should
            $this->expiry = time() + (int) $this->content->expires_in - 20;
        } elseif (isset($this->content->expires_in)) {
            $this->expiry = time() + (int) $this->content->expires_in;
        } elseif (isset($this->content->ExpiresOn)) {
            $this->expiry = strtotime($this->content->ExpiresOn);
        } elseif (isset($this->header['Cache-Control'])) {
            foreach (explode(',', $this->header['Cache-Control']) as $frag) {
                if (substr(trim($frag), 0, 8) == 'max-age=') {
                    $this->expiry = time() + (int) substr(trim($frag), 8);
                }
            }
        } else {
            $this->expiry = time() + 3600;
        }

        $this->init();
    }

    /**
     * Sets curl header to object.
     *
     * @param array $header to be set
     *
     * @return void
     */
    protected function setHeader(array $header)
    {
        $this->header = $header;
    }

    /**
     * Sets curl info to object, keeping only wanted fields
     *
     * @param array $info to be set
     *
     * @return void
     */
    protected function setInfo(array $info)
    {
        $this->info = array_intersect_key($info, ['url' => null, 'content_type' => null, 'http_code' => null]);
        //add the response timestamp
        if (isset($this->header['Date'])) {
            $this->info['dateTs'] = strtotime($this->header['Date']);
        }
    }

    /**
     * Sets content to object. Use as hook for response type specific handling of content.
     *
     * @param \stdClass $content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $this->content = $content;
    }

    /**
     * Hook for more customized initialization.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Returns the content type of the response.
     *
     * @return string
     */
    public function getContentType()
    {
        if (!isset($this->header['Content-Type'])) {
            return;
        }
        return trim(explode(';', $this->header['Content-Type'])[0]);
    }

    /**
     * Check if the response header included a deprecation warning.
     *
     * @return bool
     */
    public function isDeprecated()
    {
        if (isset($this->header['X-Deprecated'])) {
            return true;
        }
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
     * Returns the URL of this response.
     *
     * @return string
     */
    public function getHref()
    {
        return $this->info['url'];
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

    /**
     * Gets the responses payload content.
     *
     * @return \stdClass
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the last created instance of Client, instantiating a new one if necessary.
     *
     * @return \iveeCrest\Client
     */
    protected static function getLastClient()
    {
        $clientClass = \iveeCore\Config::getIveeClassName('Client');
        return $clientClass::getLastInstance();
    }

    /**
     * Parses a trailing ID from a given URL. This is useful to index data returned from CREST which doesn't contain the
     * ID of the object it refers to, but provides a href which contains it.
     *
     * @param string $url to be parsed
     *
     * @return int
     */
    public static function parseTrailingIdFromUrl($url)
    {
        $trimmed = rtrim($url, '/');
        return (int) substr($trimmed, strrpos($trimmed, '/') + 1);
    }
}
