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

use iveeCore\Config;
use iveeCore\ICache;
use iveeCore\Util;
use iveeCore\Exceptions\KeyNotFoundInCacheException;
use iveeCore\Exceptions\InvalidArgumentException;
use iveeCrest\Responses\ICollection;

/**
 * The Client class provides the infrastructure for requesting data from CREST. Apart from handling authentication, it
 * offers methods for gathering multipage endpoints as well as parallel GET with asynchronous response processing.
 *
 * @category IveeCrest
 * @package  IveeCrestClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Client.php
 */
class Client
{
    /**
     * @var \iveeCrest\Client $lastInstance holds the last created instance.
     */
    protected static $lastInstance;

    /**
     * @var \iveeCore\ICache $cache for data objects
     */
    protected $cache;

    /**
     * @var \iveeCore\CacheableArray[] $gatheredItems used as runtime cache for gathered objects
     */
    protected static $gatheredItems = [];

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
     * @var string[] $charAccessTokens the character and authentication scope specific and short lived access token,
     * gotten by using the appropriate refresh token.
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
     * @var \iveeCrest\Responses\Root $publicRootEndpoint holds the public CREST root endpoint (after having been
     * requested at least once)
     */
    protected $publicRootEndpoint;

    /**
     * @var \stdClass $authedRootEndpoint holds the authenticated CREST root endpoint (after having been requested at
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
    public function __construct(
        $publicCrestBaseUrl = null,
        $authedCrestBaseUrl = null,
        $clientId = null,
        $clientSecret = null,
        array $charRefreshTokens = null,
        $clientUserAgent = null,
        ICache $cache = null
    ) {
        //if configuration parameters aren't passed, look up defaults
        $this->publicCrestBaseUrl
            = is_null($publicCrestBaseUrl) ? Config::getPublicCrestBaseUrl() : $publicCrestBaseUrl;
        $this->authedCrestBaseUrl
            = is_null($authedCrestBaseUrl) ? Config::getAuthedCrestBaseUrl() : $authedCrestBaseUrl;
        $this->clientId = is_null($clientId) ? Config::getCrestClientId() : $clientId;
        $this->clientSecret = is_null($clientSecret) ? Config::getCrestClientSecret() : $clientSecret;
        $this->charRefreshTokens
            = is_null($charRefreshTokens) ? Config::getCrestClientRefreshTokens() : $charRefreshTokens;

        if (is_null($cache)) {
            $cacheClass = Config::getIveeClassName('Cache');
            $this->cache = $cacheClass::instance();
        } else {
            $this->cache = $cache;
        }

        $cwClass = Config::getIveeClassName('CurlWrapper');
        $this->cw = new $cwClass(
            $this->cache,
            is_null($clientUserAgent) ? Config::getUserAgent() : $clientUserAgent
        );

        static::$lastInstance = $this;
    }

    /**
     * Returns the last created instance of Client, instantiating a new one if necessary.
     *
     * @return \iveeCrest\Client
     */
    public static function getLastInstance()
    {
        if (isset(static::$lastInstance)) {
            return static::$lastInstance;
        } else {
            return new static;
        }
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
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getBearerAuthHeader($authScope)
    {
        return ['Authorization: Bearer ' . $this->getAccessToken($authScope)];
    }

    /**
     * Checks if the client has a refresh token configured for a certain auth scope.
     *
     * @param string $authScope the CREST authentication scope to be checked
     *
     * @return bool
     */
    public function hasAuthScope($authScope)
    {
        return isset($this->charRefreshTokens[$authScope]);
    }

    /**
     * Returns the character refresh token for a specific authentication scope.
     *
     * @param string $authScope the CREST authentication scope to be used
     *
     * @return string
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getRefreshToken($authScope)
    {
        if (!$this->hasAuthScope($authScope)) {
            $exceptionClass = Config::getIveeClassName('AuthScopeUnavailableException');
            throw new $exceptionClass(
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
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function getAccessToken($authScope)
    {
        $refreshToken = $this->getRefreshToken($authScope);

        //if we don't have the required valid access token, get one
        if (!isset($this->charAccessTokens[$authScope]) or time() >= $this->charAccessTokenExpiries[$authScope]) {
            $accessTokenResponse = $this->cw->post(
                $this->getPublicRootEndpoint()->getContent()->authEndpoint->href,
                $this->getBasicAuthHeader(),
                'grant_type=refresh_token&refresh_token=' . $refreshToken,
                $refreshToken,
                true
            );
            $this->charAccessTokens[$refreshToken] = $accessTokenResponse->getContent()->access_token;
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
     * @return \iveeCrest\Responses\Root
     */
    public function getPublicRootEndpoint()
    {
        if (is_null($this->publicRootEndpoint)) {
            $this->publicRootEndpoint = $this->getEndpointResponse($this->publicCrestBaseUrl);
        }
        return $this->publicRootEndpoint;
    }

    /**
     * Performs a GET request to a CREST endpoint, returning the full response object.
     *
     * @param string $url the URL of the endpoint
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param string $accept the requested representation
     * @param bool $cache whether the response should be cached or not
     *
     * @return \iveeCrest\Responses\BaseResponse
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function getEndpointResponse($url, $authScope = false, $accept = null, $cache = true)
    {
        if (is_string($authScope) and strlen($authScope) > 0) {
            $header = $this->getBearerAuthHeader($authScope);

            //we use the refresh token as cache key prefix to ensure data separation in multi-character use cases
            $cacheNsPrefix = $this->getRefreshToken($authScope);

            //since CREST doesn't reference between authed and public base URLs, we must infer it from the use of auth
            //scopes and adapt the base URL accordingly
            $url = str_replace($this->publicCrestBaseUrl, $this->authedCrestBaseUrl, $url);
        } else {
            $header = [];
            $cacheNsPrefix = '';
            $url = str_replace($this->authedCrestBaseUrl, $this->publicCrestBaseUrl, $url);
        }

        if (isset($accept)) {
            $header[] = 'Accept: ' . $accept;
        }

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
     * @return \stdClass
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function getEndpoint($url, $authScope = false, $accept = null, $cache = true)
    {
        return $this->getEndpointResponse($url, $authScope, $accept, $cache)->getContent();
    }

    /**
     * Performs a POST request to a CREST endpoint, returning data from the response.
     *
     * @param string $uri the URL to post to
     * @param string $fieldsString the POST payload as JSON encoded data
     * @param string $authScope to be used by the request
     *
     * @return \iveeCrest\Responses\BaseResponse
     * @throws \iveeCrest\Exceptions\CrestException on http return codes other than 200, 201 and 302
     */
    public function post($uri, $fieldsString, $authScope = 'publicData')
    {
        return $this->cw->post(
            $uri,
            array_merge(
                $this->getBearerAuthHeader($authScope),
                ['Content-Type: application/json']
            ),
            $fieldsString,
            '',
            false
        );
    }

    /**
     * Performs a DELETE request to a CREST endpoint, returning data from the response.
     *
     * @param string $uri the URL to post to
     * @param string $authScope to be used by the request
     *
     * @return \iveeCrest\Responses\BaseResponse
     * @throws \iveeCrest\Exceptions\CrestException on http return codes other than 200, 201 and 302
     */
    public function delete($uri, $authScope)
    {
        return $this->cw->customRequest(
            $uri,
            'DELETE',
            $this->getBearerAuthHeader($authScope),
            false
        );
    }

    /**
     * Returns the data returned by an OPTIONS call to a CREST endpoint.
     * Currently only works on the authed CREST domain.
     *
     * @param string $url to be used
     *
     * @return \iveeCrest\Responses\Options
     */
    public function getOptions($url)
    {
        return $this->cw->customRequest($url, 'OPTIONS');
    }

    /**
     * Gathers multipage endpoint responses and joins them into one array, using the passed callback functions to
     * extract the desired data from the individual response. The result of this (potentially expensive) operation can
     * be cached.
     *
     * @param \iveeCrest\Responses\ICollection|string $collection the response for the first page of the endpoint or an
     * href to it
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param callable $itemFunc function to be used to extract the desired data from a response object
     * @param string $accept the representation to request from CREST
     * @param bool $cache whether the gathered data should be cached or not
     * @param int $ttl the time to live to be used for caching of the gathered data. If none given, defaults to next
     * downtime
     * @param string $subCommandKey to avoid cache namespace collisions when different gather requests access the same
     * endpoint URL, an additional subcommand key can be specified.
     *
     * @return array
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     * @throws \iveeCrest\Exceptions\PaginationException when trying to gather NOT starting with the first page of the
     * Collection
     */
    public function gather(
        $collection,
        $authScope = false,
        callable $itemFunc = null,
        $accept = null,
        $cache = true,
        $ttl = null,
        $subCommandKey = null
    ) {
        //get response if href was passed
        if (is_string($collection)) {
            $collection = $this->getEndpointResponse($collection, $authScope, $accept, $cache);
        }

        if (!$collection instanceof ICollection) {
            throw new InvalidArgumentException(
                'Passed entry is neither a iveeCrest\Responses\ICollection nor a href to one.'
            );
        }

        //ensure we are gathering from the first collection page
        if ($collection->hasPreviousPage()) {
            $exceptionClass = Config::getIveeClassName('PaginationException');
            throw new $exceptionClass('Assert failed: Not the first page of a Collection');
        }

        //we introduce another caching layer here because gathering and reindexing multipage data is expensive, even
        //when the individual CREST responses are already cached.

        //compute a key under which the data will be stored under in the caches
        $dataKey = 'gathered:' . $collection->getHref() . (isset($subCommandKey) ? ',' . $subCommandKey : '');

        //firt we try the runtime cache
        if (isset(static::$gatheredItems[$dataKey])) {
            //data was found, check expiry
            if (static::$gatheredItems[$dataKey]->getCacheExpiry() > time()) {
                return static::$gatheredItems[$dataKey]->data;
            } else {
                //remove expired data and continue
                unset(static::$gatheredItems[$dataKey]);
            }
        }

        //try the external cache
        try {
            $dataObj = $this->cache->getItem($dataKey);
        } catch (KeyNotFoundInCacheException $e) {
            //if no TTL has been given, set it to next downtime
            if (is_null($ttl)) {
                $ttl = Util::getNextTimeTtl(11, 5);
            }
            //setup a cacheable array object
            $dataClass = Config::getIveeClassName('CacheableArray');
            $dataObj = new $dataClass($dataKey, time() + $ttl);

            //gather all the pages into one compact array
            $dataObj->data = $this->gather2(
                $collection,
                $authScope,
                $itemFunc,
                false //we don't want to cache individual pages, only the gathered result (lines below)
            );

            if ($cache) {
                $this->cache->setItem($dataObj);
            }
        }
        static::$gatheredItems[$dataKey] = $dataObj;
        return $dataObj->data;
    }

    /**
     * Step 2 in the gathering of multipage responses. It joins them into one array, using the passed callback function
     * to extract the desired data from the individual response.
     *
     * @param \iveeCrest\Responses\ICollection $collection the response for the first page of the endpoint
     * @param string|bool $authScope the CREST authentication scope to be used
     * @param callable $itemFunc function to be used to extract the desired items from a response object
     * @param bool $cache whether the individual responses of the gathering should be cached or not
     *
     * @return array
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    protected function gather2(
        ICollection $collection,
        $authScope = false,
        callable $itemFunc = null,
        $cache = true
    ) {
        $ret = [];

        while (true) {
            if (is_null($itemFunc)) {
                //if no items function has been given, just accumulate them into the result array
                foreach ($collection->getElements() as $element) {
                    $ret[] = $element;
                }
            } else {
                $itemFunc($ret, $collection);
            }

            //if there are more pages, do another iteration with next response
            if ($collection->hasNextPage()) {
                $collection = $collection->getNextPage($this, $authScope, $cache);
            } else {
                break;
            }
        }
        ksort($ret);
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
     * @param callable $callback a function expecting one iveeCrest\Responses\BaseResponse object as argument, called
     * for every successful response
     * @param callable $errCallback a function expecting one iveeCrest\Responses\BaseResponse object as argument, called
     * for every non-successful response
     * @param string $accept the requested representation
     * @param bool $cache whether the individual responses should be cached.
     *
     * @return void
     * @throws \iveeCrest\Exceptions\IveeCrestException on general CURL error
     * @throws \iveeCrest\Exceptions\AuthScopeUnavailableException when a authentication scope is requested for which
     * there is no refresh token.
     */
    public function asyncGetMultiEndpointResponses(
        array $hrefs,
        $authScope,
        callable $callback,
        callable $errCallback = null,
        $accept = null,
        $cache = true
    ) {
        $useAuthScope = is_string($authScope) and strlen($authScope) > 0;
        //run the multi GET
        $this->cw->asyncMultiGet(
            array_unique($hrefs),
            ($accept) ? ['Accept: ' . $accept] : [],
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
