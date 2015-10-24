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
     * @var string[] $charRefreshTokens the character and authentication scope specific and durable refresh tokens.
     */
    protected $charRefreshTokens;

    /**
     * @var string[] $charAccessTokens the character and authentication scope specific and short lived access token, gotten
     * by using the appropriate refresh token.
     */
    protected $charAccessTokens;

    /**
     * @var int[] $charAccessTokenExpiries timestamps of when the access tokens expire (and will need to be refreshed).
     */
    protected $charAccessTokenExpiries;

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
     * Constructs a Client object. Note that because of the refresh tokens these are character-specific.
     *
     * @param string $publicCrestBaseUrl the URL to the public CREST root
     * @param string $authedCrestBaseUrl the URL to the authenticated CREST root
     * @param string $clientId the Id of the app you registered
     * @param string $clientSecret the secret for the app you registered
     * @param string[] $charRefreshTokens the chracter-specific refresh token to be used
     * @param string $clientUserAgent the user agent that should be used in the requests
     * @param \iveeCore\ICache $cache optional cache object. If none is given, a new one is instantiated
     */
    public function __construct($publicCrestBaseUrl = null, $authedCrestBaseUrl = null, $clientId = null,
        $clientSecret = null, array $charRefreshTokens = null, $clientUserAgent = null, \iveeCore\ICache $cache = null
    ) {
        $this->publicCrestBaseUrl =
            is_null($publicCrestBaseUrl) ? Config::getPublicCrestBaseUrl() : $publicCrestBaseUrl;
        $this->authedCrestBaseUrl =
            is_null($authedCrestBaseUrl) ? Config::getAuthedCrestBaseUrl() : $authedCrestBaseUrl;
        $this->clientId = is_null($clientId) ? Config::getCrestClientId() : $clientId;
        $this->clientSecret = is_null($clientSecret) ? Config::getCrestClientSecret() : $clientSecret;
        $this->charRefreshTokens =
            is_null($charRefreshTokens) ? Config::getCrestClientRefreshTokens() : $charRefreshTokens;

        if (is_null($cache)) {
            $cacheClass = Config::getIveeClassName('Cache');
            $this->cache = $cacheClass::instance();
        } else
            $this->cache = $cache;

        $cwClass = Config::getIveeClassName('CurlWrapper');
        $this->cw = new $cwClass(
            $this->cache,
            is_null($clientUserAgent) ? Config::getUserAgent() : $clientUserAgent
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
     * @return \iveeCore\ICache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Returns a basic authorization header.
     *
     * @return string[]
     */
    protected function getBasicAuthHeader()
    {
        return ['Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)];
    }

    /**
     * Returns a bearer authorization header, pulling a new access token if necessary.
     *
     * @param string $authScope the CREST scope to be used
     *
     * @return string[]
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getBearerAuthHeader($authScope)
    {
        return ['Authorization: Bearer ' . $this->getAccessToken($authScope)];
    }

    /**
     * Returns the character refresh token for a specific authentication scope.
     *
     * @param string $authScope the CREST authentication scope to be used
     *
     * @return string
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getRefreshToken($authScope)
    {
        if (!isset($this->charRefreshTokens[$authScope])) {
            $invalidParameterValueExceptionClass = Config::getIveeClassName('InvalidParameterValueException');
            throw new $invalidParameterValueExceptionClass(
                'No refresh token found for authentication scope ' . $authScope
            );
        }

        return $this->charRefreshTokens[$authScope];
    }

    /**
     * Returns an access token, requesting a new one if none available or expired.
     * This method is called for every request made.
     *
     * @param string $authScope the CREST authentication scope to be used
     *
     * @return string
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getAccessToken($authScope)
    {
        $refreshToken = $this->getRefreshToken($authScope);

        //if we don't have the required valid access token, get one
        if (!isset($this->charAccessTokens[$authScope]) OR time() >= $this->charAccessTokenExpiries[$authScope]) {
            $accessTokenResponse = $this->cw->post(
                $this->getAuthedRootEndpoint()->authEndpoint->href, 
                $this->getBasicAuthHeader(), 
                [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken
                ],
                $refreshToken,
                true
            );
            $this->charAccessTokens[$refreshToken] = $accessTokenResponse->content->access_token;
            //The access token response is cached for a slightly lower time then it's actual validity. Here we hold the
            //token for local expiry + 1, so the local cache is garanteed to have expired when we fetch a new one from
            //CREST before it expires on CCPs side.
            $this->charAccessTokenExpiries[$refreshToken] = $accessTokenResponse->getCacheExpiry() + 1;
        }
        return $this->charAccessTokens[$refreshToken];
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
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param string $accept the requested representation
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Response
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function getEndpointResponse($url, $authScope = false, $accept = null, $cache = true)
    {
        if (is_string($authScope) AND strlen($authScope) > 0) {
            $header = $this->getBearerAuthHeader($authScope);
            $cacheNsPrefix = $this->getRefreshToken($authScope);
        } else {
            $header = [];
            $cacheNsPrefix = '';
        }

        if(isset($accept))
            $header[] = 'Accept: application/' . $accept;

        return $this->cw->get($url, $header, $cacheNsPrefix, $cache);
    }

    /**
     * Performs a GET request to a CREST endpoint, returning data from the response.
     *
     * @param string $url the URL of the endpoint
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param string $accept the requested representation
     * @param bool $cache whether the response should be cached or not
     *
     * @return stdClass
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function getEndpoint($url, $authScope = false, $accept = null, $cache = true)
    {
        return $this->getEndpointResponse($url, $authScope, $accept, $cache)->content;
    }

    /**
     * Gathers multipage endpoint responses and joins them into one array, using the passed callback functions to 
     * traverse and index the data. The result of this (potentially expensive) operation can be cached.
     *
     * @param string $endpointHref the URL to the first page of the endpoint
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param callable $indexFunc function to be used to extract the ID from/for an individual Response object
     * @param callable $elementFunc function to be used to extract the desired data from an individual Response object
     * @param string $accept the representation to request from CREST
     * @param bool $cache whether the gathered data should be cached or not
     * @param int $ttl the time to live to be used for caching of the gathered data
     * @param string $subCommandKey to avoid cache namespace collisions when different gather requests access the same
     * endpoint URL, an additional subcommand key can be specified.
     *
     * @return array
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function gather($endpointHref, $authScope = false, callable $indexFunc = null, callable $elementFunc = null,
        $accept = null, $cache = true, $ttl = 3600, $subCommandKey = null
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
                $authScope,
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
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param callable $indexFunc function to be used to extract the ID from/for an individual Response object
     * @param callable $elementFunc function to be used to extract the desired data from an individual Response object
     * @param string $accept the representation to request from CREST
     * @param bool $cache whether the individual responses of the gathering should be cached or not
     *
     * @return array
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function gather2($endpointHref, $authScope = false, callable $indexFunc = null,
        callable $elementFunc = null, $accept = null, $cache = true
    ) {
        $ret = [];
        $href = $endpointHref;

        while (true) {
            //get the response for the current href
            $response = $this->getEndpointResponse($href, $authScope, $accept, $cache);

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
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param callable $callback a function expecting one iveeCrest\Response object as argument, called for every
     * successful response
     * @param callable $errCallback a function expecting one iveeCrest\Response object as argument, called for every
     * non-successful response
     * @param string $accept the requested representation
     * @param bool $cache whether the individual Responses should be cached.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\IveeCrestException on general CURL error
     * @throws \iveeCore\Exceptions\InvalidParameterValueException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function asyncGetMultiEndpointResponses(array $hrefs, $authScope, callable $callback,
        callable $errCallback = null, $accept = null, $cache = true
    ) {
        $useAuthScope = is_string($authScope) AND strlen($authScope) > 0;
        //run the multi GET
        $this->cw->asyncMultiGet(
            array_unique($hrefs),
            ($accept) ? ['Accept: application/' . $accept] : [],
            ($useAuthScope)
                ? function () use ($authScope) {
                    return $this->getBearerAuthHeader($authScope); //avoids having to make the method public
                }
                : function () {
                    return [];
                },
            $callback,
            $errCallback,
            $cache,
            ($useAuthScope) ? $this->getRefreshToken($authScope) : ''
        );
    }
}