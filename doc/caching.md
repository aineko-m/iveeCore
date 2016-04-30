# Caching
This document explains some of the inner workings of iveeCores cache system.


## Caching in iveeCore
iveeCore maps entities from the Static Data Export to it's own classes. Since these entities are static, as the name indicates (they can only change with patches, i.e. during Eve downtimes), and the process of instantiating these objects is relatively expensive, caching is a straight forward way of speeding up their use.

iveeCore uses two cache levels, a runtime instance cache, where already instanced objects are referenced for quick access, and an external cache that stores the serialized objects that survive outside of iveeCore application runs/requests. The former is done with the class iveeCore\InstancePool, the latter are implementations of the iveeCore\ICache interface specific to the used caching backend, currently iveeCore\MemcachedWrapper, iveeCore\RedisWrapper and iveeCore\DoctrineCacheWrapper. The interface to be implemented by cacheable objects is iveeCore\ICacheable. The abstract base class used by all classes representing SDE entities is iveeCore\CoreDataCommon.

By default SDE entities have their expiry time set to the next Eve downtime.


## Caching in iveeCrest
While having a cache for the SDE entities is a nice-to-have feature, for a CREST client it is mandatory. CCP asks client developers to use caching and to respect the cache expiry timers, so that their API isn't hammered with redundant requests. Secondly, using a cache is indispensable for performance and any sort of interactive application dependant on CREST.

iveeCrest has a layered cache design, and uses a different caching strategy depending on the CREST endpoint. By default, all calls to CREST using the iveeCrest\Client::getEndpointResponse() method are cached in the external cache. Collection endpoints should always be fetched with the iveeCrest\Responses\Collection::gather() methods, which not only pull and aggregate the data from paginated collections, but also cache the resulting data array in both the runtime object cache as well as the external cache. Note that in this case only the final aggregate is cached, not the individual page responses (except the first), to avoid redundant cache memory use.

iveeCrest respects the expiry time of direct CREST endpoint responses. For gathered collections the cache time-to-live is set to reasonable values, similar to the TTL specified by CREST on a single page response of that collection. For collections holding static universe data the expiry time is set to the next downtime like in iveeCore.