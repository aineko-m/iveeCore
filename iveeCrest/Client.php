<?php
/**
 * Client class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Client.php
 */

namespace iveeCrest;
use iveeCore\Config, iveeCore\Exceptions\KeyNotFoundInCacheException;

/**
 * The Client class provides the infrastructure for requesting data from CREST. Apart from handling authentication, it
 * offers methods for gathering and reindexing multipage endpoints as well as parallel GET with asynchronous response
 * processing.
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Client.php
 */
class Client
{
    //the root endpoint representation
    const ROOT_REPRESENTATION = 'vnd.ccp.eve.Api-v3+json';

    /**
     * @var \iveeCore\ICache $cache for data objects
     */
    protected $cache;

    /**
     * @var string $publicCrestBaseUrl specifies the public CREST root URL from which most other endpoints can be
     * navigated to.
     */
    protected $publicCrestBaseUrl;

    /**
     * @var string $authedCrestBaseUrl specifies the authenticated CREST root URL from which most other endpoints can be
     * navigated to.
     */
    protected $authedCrestBaseUrl;

    /**
     * @var string $clientId holds the client application ID as configured in CCPs developer application backend:
     * https://developers.eveonline.com/applications
     */
    protected $clientId;

    /**
     * @var string $clientSecret the secret key to go along with the client application ID.
     */
    protected $clientSecret;

    /**
     * @var string $charRefreshToken this is the character specific and durable refresh token.
     */
    protected $charRefreshToken;

    /**
     * @var string $charAccessToken this is the character specific and short lived access token, gotten by using the
     * refresh token.
     */
    protected $charAccessToken;

    /**
     * @var int $charAccessTokenExpiry timestamp when the access token expires (and will need to be refreshed).
     */
    protected $charAccessTokenExpiry;

    /**
     * @var \iveeCrest\CurlWrapper $cw holds the object handling CURL.
     */
    protected $cw;

    /**
     * @var stdClass $publicRootEndpoint holds the public CREST root endpoint (after having been requested at least
     * once)
     */
    protected $publicRootEndpoint;

    /**
     * @var stdClass $authedRootEndpoint holds the authenticated CREST root endpoint (after having been requested at
     * least once)
     */
    protected $authedRootEndpoint;

    /**
     * Constructs a Client object. Note that because of the refresh token these are character-specific.
     *
     * @param string $publicCrestBaseUrl the URL to the public CREST root
     * @param string $authedCrestBaseUrl the URL to the authenticated CREST root
     * @param string $clientId the Id of the app you registered
     * @param string $clientSecret the secret for the app you registered
     * @param string $charRefreshToken the chracter-specific refresh token to be used
     * @param string $clientUserAgent the user agent that should be used in the requests
     */
    public function __construct($publicCrestBaseUrl = null, $authedCrestBaseUrl = null, $clientId = null,
        $clientSecret = null, $charRefreshToken = null, $clientUserAgent = null
    ) {
        $this->publicCrestBaseUrl =
            is_null($publicCrestBaseUrl) ? Config::getPublicCrestBaseUrl() : $publicCrestBaseUrl;
        $this->authedCrestBaseUrl =
            is_null($authedCrestBaseUrl) ? Config::getAuthedCrestBaseUrl() : $authedCrestBaseUrl;
        $this->clientId         = is_null($clientId)         ? Config::getCrestClientId()           : $clientId;
        $this->clientSecret     = is_null($clientSecret)     ? Config::getCrestClientSecret()       : $clientSecret;
        $this->charRefreshToken = is_null($charRefreshToken) ? Config::getCrestClientRefreshToken() : $charRefreshToken;

        $cacheClass = Config::getIveeClassName('Cache');
        $this->cache = $cacheClass::instance();

        $cwClass = Config::getIveeClassName('CurlWrapper');
        $this->cw = new $cwClass(
            $this->cache,
            is_null($clientUserAgent) ? Config::getUserAgent() : $clientUserAgent,
            $this->charRefreshToken
        );
    }

    /**
     * Returns the public CREST root URL.
     *
     * @return string
     */
    public function getPublicCrestBaseUrl()
    {
        return $this->publicCrestBaseUrl;
    }

    /**
     * Returns the authenticated CREST root URL.
     *
     * @return string
     */
    public function getAuthedCrestBaseUrl()
    {
        return $this->authedCrestBaseUrl;
    }

    /**
     * Returns the used cache object.
     *
     * @return \iveeCrest\ICache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Returns a basic authorization header.
     *
     * @return array
     */
    protected function getBasicAuthHeader()
    {
        return array('Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret));
    }

    /**
     * Returns a bearer authorization header, pulling a new access token if necessary.
     *
     * @return array
     */
    protected function getBearerAuthHeader()
    {
        return array('Authorization: Bearer ' . $this->getAccessToken());
    }

    /**
     * Returns the necessary POST fields to request a new access token.
     *
     * @return array
     */
    protected function getRefreshTokenPostFields()
    {
        return array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->charRefreshToken
        );
    }

    /**
     * Returns an access token, requesting a new one if none available or expired.
     * This method is called for every request made.
     *
     * @return string
     */
    protected function getAccessToken()
    {
        //TODO: support multiple scopes

        //if we don't have an access token, get one
        if (!isset($this->charAccessToken) OR time() >= $this->charAccessTokenExpiry) {
            $accessTokenResponse = $this->cw->post(
                $this->getAuthedRootEndpoint()->authEndpoint->href, 
                $this->getBasicAuthHeader(), 
                $this->getRefreshTokenPostFields()
            );
            $this->charAccessToken = $accessTokenResponse->content->access_token;
            //The access token response is cached for a slightly lower time then it's actual validity. Here we hold the
            //token for local expiry + 1, so the local cache is garanteed to have expired when we fetch a new one from
            //CREST before it expires on CCPs side.
            $this->charAccessTokenExpiry = $accessTokenResponse->getCacheExpiry() + 1;
        }
        return $this->charAccessToken;
    }

    /**
     * Returns the public CREST root endpoint.
     *
     * @return stdClass
     */
    public function getPublicRootEndpoint()
    {
        if (is_null($this->publicRootEndpoint))
            $this->publicRootEndpoint = $this->getEndpoint(
                $this->publicCrestBaseUrl,
                false,
                static::ROOT_REPRESENTATION,
                true
            );
        return $this->publicRootEndpoint;
    }

    /**
     * Returns the authenticated CREST root endpoint.
     *
     * @return stdClass
     */
    public function getAuthedRootEndpoint()
    {
        if (is_null($this->authedRootEndpoint))
            $this->authedRootEndpoint = $this->getEndpoint(
                $this->authedCrestBaseUrl,
                false,
                static::ROOT_REPRESENTATION,
                true
            );
        return $this->authedRootEndpoint;
    }

    /**
     * Returns the data returned by an OPTIONS call to a CREST endpoint.
     *
     * @param string $url to be used
     *
     * @return stdClass
     */
    public function getOptions($url)
    {
        return $this->cw->options($url)->content;
    }

    /**
     * Performs a GET request to a CREST endpoint, returning the full response object.
     *
     * @param string $url the URL of the endpoint
     * @param bool $auth if authentication header should be sent
     * @param string $accept the requested representation
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Response
     */
    public function getEndpointResponse($url, $auth = false, $accept = null, $cache = true)
    {
        if($auth)
            $header = $this->getBearerAuthHeader();
        else
            $header = [];

        if(isset($accept))
            $header[] = 'Accept: application/' . $accept;

        return $this->cw->get($url, $header, $cache);
    }

    /**
     * Performs a GET request to a CREST endpoint, returning data from the response.
     *
     * @param string $url the URL of the endpoint
     * @param bool $auth if authentication header should be sent
     * @param string $accept the requested representation
     * @param bool $cache whether the response should be cached or not
     *
     * @return stdClass
     */
    public function getEndpoint($url, $auth = false, $accept = null, $cache = true)
    {
        return $this->getEndpointResponse($url, $auth, $accept, $cache)->content;
    }

    /**
     * Gathers multipage endpoint responses and joins them into one array, using the passed callback functions to 
     * traverse and index the data. The result of this (potentially expensive) operation can be cached.
     *
     * @param string $endpointHref the URL to the first page of the endpoint 
     * @param callable $indexFunc function to be used to extract the ID from/for an individual Response object
     * @param callable $elementFunc function to be used to extract the desired data from an individual Response object
     * @param string $accept the representation to request from CREST
     * @param bool $cache whether the gathered data should be cached or not
     * @param int $ttl the time to live to be used for caching of the gathered data
     * @param string $subCommandKey to avoid cache namespace collisions when different gather requests access the same
     * endpoint URL, an additional subcommand key can be specified.
     *
     * @return array
     */
    public function gather($endpointHref, callable $indexFunc = null, callable $elementFunc = null, $accept = null,
        $cache = true, $ttl = 3600, $subCommandKey = null
    ) {
        $dataKey = 'gathered:' . $endpointHref . (isset($subCommandKey) ? ',' . $subCommandKey : '');
        //we introduce another caching layer here because gathering and reindexing multipage data is expensive, even
        //when the individual CREST responses are already cached.
        try {
            $dataObj = $this->cache->getItem($dataKey);
        } catch (KeyNotFoundInCacheException $e) {
            //setup a cacheable array object
            $dataClass = Config::getIveeClassName('CacheableArray');
            $dataObj = new $dataClass($dataKey, time() + $ttl);

            //gather all the pages into one compact array
            $dataObj->data = $this->gather2(
                $endpointHref,
                $indexFunc,
                $elementFunc,
                $accept,
                false //if we want to cache gathered pages, we don't do it on the lower levels, but here (lines below)
            );

            if ($cache)
                $this->cache->setItem($dataObj);
        }
        return $dataObj->data;
    }

    /**
     * Step 2 in the gathering of multipage responses. It joins them into one array, using the passed callback functions
     * to traverse and index the data.
     *
     * @param string $endpointHref the URL to the first page of the endpoint 
     * @param callable $indexFunc function to be used to extract the ID from/for an individual Response object
     * @param callable $elementFunc function to be used to extract the desired data from an individual Response object
     * @param string $accept the representation to request from CREST
     * @param bool $cache whether the individual responses of the gathering should be cached or not
     *
     * @return array
     */
    protected function gather2($endpointHref, callable $indexFunc = null, callable $elementFunc = null, $accept = null,
        $cache = true)
    {
        $ret = [];
        $href = $endpointHref;

        while (true) {
            //get the response for the current href
            $response = $this->getEndpointResponse($href, true, $accept, $cache);

            foreach ($response->content->items as $item) {
                //if an element function has been given, call it, otherwise use the full item in the result array
                if(is_null($elementFunc))
                    $element = $item;
                else
                    $element = $elementFunc($item);

                //if an index function has been given, call it to get a result key, otherwise just push the element
                //onto result array
                if(is_null($indexFunc))
                    $ret[] = $element;
                else
                    $ret[$indexFunc($item)] = $element;
            }

            //if there are more pages, do another iteration with updated href
            if ($response->hasNextPage())
                $href = $response->getNextPageHref();
            else
                break;
        }
        return $ret;
    }

    /**
     * Performs parallel asyncronous GET requests to a CREST endpoint. This method has void return, instead, responses
     * are passed to the callback functions provided as arguments as they are received.
     *
     * This method will most likely be most useful in batch scripting scenarios. If the same data is requested less
     * frequently than the cache TTL, it is advisable to disable caching via argument to avoid overflowing the cache
     * with data that won't be requested again before they expire.
     *
     * @param array $hrefs the hrefs to request
     * @param callable $callback a function expecting one iveeCrest\Response object as argument, called for every
     * successful response
     * @param callable $errCallback a function expecting one iveeCrest\Response object as argument, called for every
     * non-successful response
     * @param string $accept the requested representation
     * @param bool $cache whether the individual Responses should be cached.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\IveeCrestException on general CURL error
     */
    public function asyncGetMultiEndpointResponses(array $hrefs, callable $callback, callable $errCallback = null,
        $accept = null, $cache = true
    ) {
        $header = [];
        if(isset($accept))
            $header[] = 'Accept: application/' . $accept;

        //run the multi GET
        return $this->cw->asyncMultiGet(
            array_unique($hrefs),
            $header,
            function () {
                return $this->getBearerAuthHeader(); //little trick to avoid having to make the method public
            },
            $callback,
            $errCallback,
            $cache
        );
    }
}