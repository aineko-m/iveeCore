<?php
/**
 * Client class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCrest/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCrest/blob/master/iveeCrest/Client.php
 */

namespace iveeCrest;

/**
 * The Client class provides the infrastructure for requesting data from CREST. Apart from handling authentication, it
 * offers methods for gathering and reindexing multipage endpoints as well as parallel GET with asynchronous response
 * processing.
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCrest/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCrest/blob/master/iveeCrest/Client.php
 */
class Client
{
    //the root endpoint representation
    const ROOT_REPRESENTATION = 'vnd.ccp.eve.Api-v3+json';

    /**
     * @var \iveeCrest\ICache $cache for data objects
     */
    protected $cache;

    /**
     * @var string $rootEndpointUrl specifies the CREST root URL from which most other endpoints can be navigated to.
     */
    protected $rootEndpointUrl;

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
     * @var \stdClass $rootEndpoint holds the root endpoint (after having been requested at least once)
     */
    protected $rootEndpoint;

    /**
     * Constructs a Client object. Note that these are character-specific.
     *
     * @param string $rootEndpointUrl the Url to the CREST root
     * @param string $clientId the Id of the app you registered
     * @param string $clientSecret the secret for the app you registered
     * @param string $charRefreshToken the chracter-specific refresh token to be used
     * @param string $clientUserAgent the user agent that should be used in the requests
     *
     * @return \iveeCrest\Client
     */
    public function __construct($rootEndpointUrl = null, $clientId = null, $clientSecret = null,
        $charRefreshToken = null, $clientUserAgent = null
    ) {
        $this->rootEndpointUrl  = is_null($rootEndpointUrl)  ? Config::getCrestBaseUrl()       : $rootEndpointUrl;
        $this->clientId         = is_null($clientId)         ? Config::getClientId()           : $clientId;
        $this->clientSecret     = is_null($clientSecret)     ? Config::getClientSecret()       : $clientSecret;
        $this->charRefreshToken = is_null($charRefreshToken) ? Config::getClientRefreshToken() : $charRefreshToken;

        $cacheClass = Config::getIveeClassName('Cache');
        $this->cache = new $cacheClass(get_called_class() . '_' . $this->charRefreshToken);

        $cwClass = Config::getIveeClassName('CurlWrapper');
        $this->cw = new $cwClass(
            is_null($clientUserAgent) ? Config::getUserAgent() : $clientUserAgent,
            $this->charRefreshToken
        );

        //load root endpoint
        $this->rootEndpoint = $this->getEndpoint(
            $this->rootEndpointUrl,
            false,
            static::ROOT_REPRESENTATION
        );
    }

    /**
     * Returns the root endpoint.
     *
     * @return \stdClass
     */
    public function getRootEndpointUrl()
    {
        return $this->rootEndpointUrl;
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
     *
     * @return string
     */
    protected function getAccessToken()
    {
        //if we don't have an access token, get one
        if (!isset($this->charAccessToken) OR time() >= $this->charAccessTokenExpiry) {
            $accessTokenResponse = $this->cw->post(
                $this->getRootEndpoint()->authEndpoint->href, 
                $this->getBasicAuthHeader(), 
                $this->getRefreshTokenPostFields()
            );
            $this->charAccessToken = $accessTokenResponse->content->access_token;
            $this->charAccessTokenExpiry = time() + $accessTokenResponse->content->expires_in - 10;
        }
        return $this->charAccessToken;
    }

    /**
     * Returns the root endpoint.
     *
     * @return \stdClass
     */
    public function getRootEndpoint()
    {
        return $this->rootEndpoint;
    }

    /**
     * Returns the data returned by an OPTIONS call to a CREST endpoint.
     *
     * @param string $url to be used
     *
     * @return \stdClass
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
     *
     * @return \iveeCrest\Response
     */
    public function getEndpointResponse($url, $auth = false, $accept = null)
    {
        if($auth)
            $header = $this->getBearerAuthHeader();
        else
            $header = array();

        if(isset($accept))
            $header[] = 'Accept: application/' . $accept;

        return $this->cw->get($url, $header);
    }

    /**
     * Performs a GET request to a CREST endpoint, returning data from the response.
     *
     * @param string $url the URL of the endpoint
     * @param bool $auth if authentication header should be sent
     * @param string $accept the requested representation
     *
     * @return \stdClass
     */
    public function getEndpoint($url, $auth = false, $accept = null)
    {
        return $this->getEndpointResponse($url, $auth, $accept)->content;
    }

    /**
     * Gathers multipage endpoint responses and joins them into one array, using the passed callback functions to 
     * traverse and index the data. Since this operation is potentially expensive, it is recommended to use
     * gatherCached() instead, which introduces another layer of caching.
     *
     * @param string $endpointHref the URL to the first page of the endpoint 
     * @param callable $indexFunc function to be used to extract the ID from/for and individual response item
     * @param callable $elementFunc function to be used to extract the desired data from and individual response item
     * @param string $accept the representation to request from CREST
     *
     * @return array
     */
    public function gather($endpointHref, callable $indexFunc = null, callable $elementFunc = null, $accept = null)
    {
        $ret = array();
        $href = $endpointHref;

        while (true) {
            //get the response for the current href
            $response = $this->getEndpointResponse($href, true, $accept);

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
     * Gathers multipage endpoint responses and joins them into one array, using the passed callback functions to 
     * traverse and index the data. The result of this (potentially expensive) operation is cached.
     *
     * @param string $endpointHref the URL to the first page of the endpoint 
     * @param callable $indexFunc function to be used to extract the ID from/for and individual response item
     * @param callable $elementFunc function to be used to extract the desired data from and individual response item
     * @param string $accept the representation to request from CREST
     * @param int $ttl the time to live to be used in the cache
     * @param string $subCommandKey to avoid cache namespace collisions when different gather requests access the same
     * endpoint URL, an additional subcommand key can be specified.
     *
     * @return array
     */
    public function gatherCached($endpointHref, callable $indexFunc = null, callable $elementFunc = null, 
        $accept = null, $ttl = 3600, $subCommandKey = null
    ) {
        $dataKey = 'gathered:' . $endpointHref . (isset($subCommandKey) ? ',' . $subCommandKey : '');
        //we introduce another caching layer here because gathering and reindexing multipage data is expensive, even
        //when the individual CREST responses are already cached.
        try {
            $dataObj = $this->cache->getItem($dataKey);
        } catch (Exceptions\KeyNotFoundInCacheException $e) {
            //setup a cacheable array object
            $dataClass = Config::getIveeClassName('CacheableArray');
            $dataObj = new $dataClass($dataKey, $ttl);

            //gather all the pages into one compact array
            $dataObj->data = $this->gather(
                $endpointHref,
                $indexFunc,
                $elementFunc,
                $accept
            );
            $this->cache->setItem($dataObj);
        }
        return $dataObj->data;
    }

    /**
     * Performs parallel asyncronous GET requests to a CREST endpoint. Since the same header is used for all requests,
     * all hrefs passed should be to the same endpoint. This method has void return, instead, responses are
     * passed to the callback functions provided as arguments.
     *
     * This method will most likely be most useful in batch scripting scenarios. If the same data is requested less
     * frequently than the cache TTL, it is advisable to disable caching via argument to avoid overflowing the cache
     * with data that won't be requested again before they expire.
     *
     * @param array $hrefs the hrefs to request
     * @param callable $callback a function expecting one \iveeCrest\Response object as argument, called for every
     * successful response
     * @param callable $errCallback a function expecting one \iveeCrest\Response object as argument, called for every
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
        $header = array();
        if(isset($accept))
            $header[] = 'Accept: application/' . $accept;
        
        //can't pass "this" in callables
        $client = $this;

        //run the multi GET
        return $this->cw->asyncMultiGet(
            array_unique($hrefs),
            $header,
            function () use ($client) {
                return $client->getBearerAuthHeader(); //little trick to avoid having to make the method public
            },
            $callback,
            $errCallback,
            $cache
        );
    }
}