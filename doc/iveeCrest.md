# iveeCrest user manual
This document gives an overview for the use of the classes in the iveeCrest namespace.


## High level overview
There aren't many components in the iveeCrest codebase: The iveeCrest\Client provides the methods for getting responses from CREST and deals with authentication. CurlWrapper is a backend class that handles the HTTP interaction with CREST. And finally there are plenty of response classes, which are used to represent CREST responses.

The response classes of iveeCrest represent a direct mapping of CREST response representations (content types). In the same way you can navigate the CREST endpoint data tree following hrefs, it is possible to navigate the iveeCrest responses with methods leading from one to the other.


## Basic Client usage
The simplest use case for Client is fetching a response based on a given href:
```php
<?php
//initialize iveeCore. Adapt path as required.
require_once('/path/to/iveeCore/iveeCoreInit.php');

use iveeCrest\Client;

//Get Client instance with default configuration
$client = new Client;

//Get a response based on given href
$response = $client->getEndpointResponse('https://public-crest.eveonline.com/killmails/30290604/787fb3714062f1700560d4a83ce32c67640b1797/');

//Note how the response instance is of the subtype Killmail, based on the returned data
print_r($response);

//To get just the response payload without all the meta-data
$response->getContent();
```
The following shows an example of how to navigate the CREST data tree:
```php
//Get Client instance with default configuration
$client = new Client;

//Get the public root response
$pubRoot = $client->getPublicRootEndpoint();

//CREST root contains many hrefs to further endpoints. These are accessible through convenience methods.
print_r($pubRoot);

//Get the first page of the alliance collection
$allianceCollection = $pubRoot->getAllianceCollection();

//Note how the list is incomplete, but more pages are referenced
print_r($allianceCollection);

//To simply get the full list use gather(), which is available on any Collection and should always be used
//to get the complete data.
print_r($allianceCollection->gather());

//To get a specific alliance endpoint
$alliance = $allianceCollection->getAlliance(434243723);
print_r($alliance);
```


## Client usage with authentication scopes
To be able to access authenticated CREST endpoints, you need to setup the application credentials in Eve's [developers backend](https://developers.eveonline.com/applications), and get a character refresh token, as described in the [installation document](https://github.com/aineko-m/iveeCore/blob/master/doc/installAndConfig.md).

The necessary configuration parameters can either be set in the iveeCore\Config class or passed to the Client constructor. In a use case where the private data of multiple characters is fetched from CREST, multiple Clients need to be instantiated.

```php
$client = new Client('my_client_id', 'my_client_secret', 'my_character_refresh_token');
```

On all methods that access endpoints requiring authentication scopes, a Client object can be passed as argument, so it has all necessary information to perform the fetch. If you do not pass the object, the last one instantiated will be used, which might not be what is intended.

```php
//Get the character endpoint for the character linked to the refresh token
$characterResponse = $client->getPublicRootEndpoint()->getCharacter($client);
print_r(characterResponse);

//If the used refresh token has the required authentication scopes, you can fetch different private
//character data
$characterResponse->getContactsCollection();
$characterResponse->getFittingCollection();
$characterResponse->getLocation();

//CREST write access is also possible. Set waypoint to Jita
$characterResponse->setNavigationWaypoint(30000142);
```


## Market data
As markets are segmented by region, the market data is accessed from a region response object:
```php
//Get Client instance with default configuration
$client = new Client;

//Get the public root response
$pubRoot = $client->getPublicRootEndpoint();

//Get regions collection
$regionCollection = $pubRoot->getRegionCollection();

//Get "The Forge" region response
$region = $regionCollection->getRegion(10000002);


//Get market history and orders for Tritanium
print_r($region->getMarketHistory(34));
print_r($region->getMarketOrders(34));
```


## Parallel asynchronous request fetching & processing
In order to speed up the fetching of multiple endpoints, while at the same time trying to do useful work while it waits for responses, iveeCrest implements asynchronous parallel requests. This is used during the fetching of multipage Collection endpoints, or in iveeCore's CLI updater tool for bulk market data processing. The necessary method is exposed to developers through the iveeCrest\Client::asyncGetMultiEndpointResponses() method. A few examples of how to use it exist within the code, for instance in iveeCrest\Responses\Constellation::getSystems();

The basic idea is to pass a closure as argument, which is called for and with every received response. Internally iveeCrest uses a moving window for the parallel requests, keeping a certain number of queries in-flight, and, as the responses come in, passing them on to the closure function. Note that due to the asynchronous nature of the parallel requests, there is no guarantee that the responses will return in-order.


## Code path of a CREST call
To aid in understanding how a call to a CREST endpoint is handled in iveeCrest, here's a description of a typical call:

- Entry is the Client::getEndpointResponse() method.
- If an authentication scope is requested, an access token is fetched by Client (this can result in CREST calls in itself).
- The Client calls CurlWrapper::get().
- CurlWrapper tries to fetch a cached response for the call. If available, it returns it, if not, proceeds.
- A ProtoResponse object is instantiated and passed to doRequest() with the other arguments.
- The HTTP call to CREST is performed.
- The returned data is fed to the ProtoResponse object, which decides the response class, instantiates and returns it. During construction the response object may transform the CREST endpoint data.
- The response object is cached and returned to Client and to the caller.


## About some CREST weaknesses
- Performance: Due to the RESTful way the CREST endpoints are structured and how clients are supposed to traverse the data tree, often dozens to thousands of API calls are necessary to fetch just a single bit of wanted data. Even with client side caching the performance characteristics of CREST are not ideal for interactive applications.
- Inflexibility: CREST doesn't offer ways to filter, search or combine data like you can in a relational database. You generally have to pull in all the data in search yourself. This compounds on the performance issue.
- Availability / Reliability: As CREST is tied to the live EVE cluster, it also follows the daily downtime. 3rd party API access is also among the first to be shut down in case of problems. CREST also routinely throws spurious errors.

One of the possible solutions for these issues (independent of this library) is to proactively collect and persist the data from CREST in a database and serve the application from there instead of pulling it live. That adds complexity to the application code, but that's a price to pay for gaining resilience and ergonomics.

this is exactly what iveeCore does with the market price and history, as well as industry data. It is persisted in the DB and fetched from there (or the cache) as long as it hasn't become too old. By scheduling the automatic fetching and storing of the market data in bulk with the CLI update tool, it is possible to avoid iveeCore ever having to pull market data "live".