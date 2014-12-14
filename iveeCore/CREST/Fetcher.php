<?php
/**
 * Fetcher class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/Fetcher.php
 *
 */

namespace iveeCore\CREST;

/**
 * Fetcher is a CREST specific wrapper around CURL with minimal functionality
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/Fetcher.php
 *
 */
class Fetcher
{
    /**
     * @var string $baseUrl holds the base CREST url
     */
    protected $baseUrl;

    /**
     * @var string $userAgent holds the user agent to be used by CURL when accessing CREST
     */
    protected $userAgent;

    /**
     * Constructor
     *
     * @return \iveeCore\CREST\Fetcher
     */
    public function __construct()
    {
        $this->baseUrl   = \iveeCore\Config::getCrestBaseUrl();
        $this->userAgent = \iveeCore\Config::getUserAgent();
    }

    /**
     * Get data from CREST and check returned representation name
     *
     * @param string $path the path to the specific CREST endpoint
     * @param string $representationName the expected representantion name embedded in resonse content type
     *
     * @return \stdClass
     */

    public function getCrestData($path, $representationName)
    {
        $res = $this->curlGetJson($this->baseUrl . $path);

        if ($this->parseContentTypeToRepresentation($res->curlInfo['content_type']) != $representationName) {
            $exceptionClass = \iveeCore\Config::getIveeClassName('CrestException');
            throw new $exceptionClass('Unexpected Content-Type returned by CREST: ' . $res->info['content_type']);
        }
        return $res;
    }

    /**
     * Executes a HTTP get request and tries to return the response as decoded JSON data
     *
     * @param string $url the complete URL to call
     *
     * @return \stdClass with $obj->content being the decoded JSON data and $obj->info being the CURL info
     * @throws \iveeCore\Exceptions\CurlException if some HTTP error occurs
     */
    protected function curlGetJson($url)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,  //return the http response from curl_exec() as string
            CURLOPT_HEADER         => false, //do not include response header in return string
            CURLOPT_FOLLOWLOCATION => false, //do not follow redirects
            CURLOPT_USERAGENT      => $this->userAgent, // who am i
            CURLOPT_CONNECTTIMEOUT => 10,    // timeout on connect
            CURLOPT_TIMEOUT        => 60,    // timeout on response
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $res = json_decode(curl_exec($ch));
        $res->curlInfo = curl_getinfo($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);

        $exceptionClass = \iveeCore\Config::getIveeClassName('CurlException');

        if ($err != 0)
            throw new $exceptionClass($errmsg, $err);

        if ($res->curlInfo['http_code'] != 200)
            throw new $exceptionClass ("HTTP response not OK", $res->curlInfo['http_code']);

        return $res;
    }

    /**
     * Parse a CREST response content-type to a representation name
     *
     * @param string $contentType to parse
     *
     * @return string
     * @throws \iveeCore\Exceptions\CrestException when parsing is not successful
     */
    protected function parseContentTypeToRepresentation($contentType)
    {
        $matches = array();

        preg_match(\iveeCore\Config::CREST_CONTENT_TYPE_REPRESENTATION_PATTERN, $contentType, $matches);

        if (count($matches) == 2)
            return $matches[1];

        $exceptionClass = \iveeCore\Config::getIveeClassName('CrestException');
        throw new $exceptionClass("Couldn't parse CREST response content type to representation");
    }
}
