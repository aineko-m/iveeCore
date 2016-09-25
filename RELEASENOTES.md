This file lists major changes in iveeCore releases.

Version 3.0.4 - September 25th, 2016
------------------------------------
- Added Corporation and CorporationStructuresCollection response classes.
- Minor changes that make iveeCore compatible with PHP 7.
- Reenable tournament scene data tests as CCP fixed the endpoints.
- Added new authentication scopes to getrefreshtoken script.
- Improved the logic behind the "huge" batch price update.


Version 3.0.3 - June 15th, 2016
-------------------------------
This release adds support for new CREST endpoints and fixes issues that cropped up.

- Fixes for the June 14th CREST update.
- Added MarketOrderCollectionSlim for the new combined region-wide market order collection endpoint.
- The batch price update can use MarketOrderCollectionSlim, but since it is a memory intensive feature, it must manually be enabled in Config.
- Added classes for Stargate, Moon, Station, InsurancePrices, LoyaltyPoints, LoyaltyStoreOffers, NPCCorporations as well as Opportunity endpoints.
- Remove redundant CREST attributes from JSON ("_str") via regex before decode, which is much faster.
- Adapt getrefreshtoken to new login domain, warn about requesting too many auth scopes.
- A number of small fixes, improvements and removal of obsolete stuff.


Version 3.0.2 - April 30th, 2016
--------------------------------
Release with bugfixes and minor improvements. The initial CREST issues post-Citadel release have been fixed by CCP. The current SDE is still missing industry data for some of the new items.

- Use new broker fee and transaction tax formulas.
- When no global price data is available for calculating product base cost (for instance new item) in Blueprint activities, use Jita buy price as approximation.
- Auto retry logic in CurlWrapper::asyncMultiget(), with abort on too many errors; also stop using the error callback. Some exception handling improvements.
- Fix multipage gathering for Collections where pageCount=0.
- Don't forget to mention DoctrineCacheWrapper in documentation.


Version 3.0.1 - April 28th, 2016
--------------------------------
This is a point release for adapting to the Citadel release:

- Removed the distinction between public and authenticated CREST domains. Minor iveeCrest\Client API change.
- Implemented and started using the combined buy & sell market order CREST endpoint.
- Added all new authentication scopes to getrefreshtoken.php
- Minor fixes.


Version 3.0.0 - April 24th, 2016
--------------------------------
iveeCrest has been thoroughly refactored:

- Typed responses have been implemented, so depending on the response content types, dedicated classes get instantiated for them.
- EndpointHandler has been removed. All of its functionality is now offered by the response classes.
- Multiple authentication scopes are now supported.
- Write access to CREST is supported.
- Non-authenticated CREST endpoints can now be accessed without the need to set up the application in the Eve developer backend.
- Collections are now gathered in parallel for higher performance.
- The getrefreshtoken PHP web script now supports the selection of multiple authentication scopes.

Other improvements include:

- The iveeCore CREST updater has been improved, all CREST data can now be updated on-the-fly if it is too old.
- Solar system industry indices have been moved to their own class, SytemIndustryIndices.
- In InventionProcessData the methods available in a regular and a "success" variant have been renamed to "attempt" and regular, respectively. This avoids specialcasing for handling ProcessData object trees and is less error prone.
- Rewritten documentation.
- The codebase is now more PSR-2 compliant.
- Improved PHPUnit tests.
- Various bug fixes and removed redundancies.


Version 2.6.2 - August 27th, 2015
---------------------------------
- Recursive reaction calculation. Now industry processes that take a ReactionProduct as input can recurse into the reaction process.
- Changed industry activity recursion control from boolean parameter to a recursion depth int; also added an int reaction recursion depth parameter. Minor API breakage.
- Some refactoring of ProcessData and ReactionProcessData by introduction of a shared abstract parent class ProcessDataCommon implementing IProcessData interface. Minor API breakage.
- MaterialMap->getMultipliedMaterialMap() renamed to multiply() and acts on the object itself instead of returning a new one.
- Adapted to Galatea release.


Version 2.6.1 - August 9th, 2015
--------------------------------
- Normalize and clarify the use of the maxPriceDataAge configuration variable.
- More logic in the CREST updaters to decide what data to update. This allows the user to call the update script with -all flag frequently without having to worry about unneeded updates. A small new DB table was added for update tracking. Migration provided.
- Renamed 3 of the iveeCore\CREST updater classes to better reflect their purpose. Users will have to adapt their Config.php's.
- Split updates for industry indices and global prices into two separate flags in the updater.
- Fixed updaters not fetching data for items on market but with published = 0 in the SDE.
- Prevent updater script from being started more than once.
- Allow a second IndustryModifier object as market context in MaterialMap and ProcessData during profit calculation. Useful when buying and selling is done in different stations.


Version 2.6 - July 27th, 2015
-----------------------------
- Full-fledged CREST client by merging of iveeCrest.
- Bumped minimum PHP version to 5.4 as we are now using some of it's features.
- Removed all EMDR and previous (minimalistic) CREST related code.
- With removal of EMDR and the required ZeroMQ PHP extension iveeCore is now completely HHVM compatible.
- Added CREST based price and industry data updaters with CLI tool.
- DB schema updates (migration provided):
  - Split iveePrices table into marketHistory and marketPrices, this also allowed to solve a performance issue with the stored procedures.
  - Removal of reverse engineering system indices column (unused since 2.1, Phoebe release).
  - Dropped iveeFacilities table (conquerable stations data is still stored in outposts).
  - Renaming of all iveeCore tables.
  - Updated stored procedures to reflect all the changes.
- Refactored RegionMarketData class into separate MarketPrices and MarketHistory.
- Caching now always uses absolute timestamps for TTL.
- Use compression in RedisWrapper.
- Adapted to Aegis release.
- Added sovereignty endpoints to EndpointHandler.
- Consider outposts when loading station IDs.
- Optimized some code hotspots found through profiler.
- Renamed "someID" to "someId" in method and variable names for consistency.
- Code style improvements.

Version 2.5 - May 15th, 2015
----------------------------
- Refactoring to remove Defaults by extending IndustryModifier with new functionality and adding CharacterModifier and BlueprintModifier. CharacterModifier allows for lookup of skills, time and implant factors, standings, taxes and efficiencies based on skills and standings. BlueprintModifier is used for blueprint research level lookup. These are now used throughout the code, making for a cleaner and more flexible API. Developers will likely want to customize those by extending them or implementing the appropriate interface.
- Basic support for player built outposts and conquerable NPC stations.
- DoctrineCacheWrapper, courtesy of Tarioch.
- Replaced the AssemblyLine SQL additions for blueprint compatibility with logic in code.
- Fixed cache invalidation.
- Fixed multi-key cache invalidation under HHVM or memcached extension below version 2.0.0.

Version 2.4.1 - May 3rd, 2015
-----------------------------
- Adapted to Mosaic release.
- Added Composer/Packagist support, courtesy of Tarioch.
- Fixed a floating point precision issue that could make industrial activities use too much materials.

Version 2.4 - March 29th, 2015
------------------------------
- Adapted to Scylla release.
- Removed Team and Speciality classes and related functionality.
- Replaced Redis support via Predis with PhpRedis.
- Refactored cache design in preparation for iveeCrest merge: Some API changes (method names and interfaces), using cache is now mandatory, renamed CacheableCommon to CoreDataCommon, added CacheableArray, removed obsoleted exceptions.
- Changed description from "library" to "engine" as it better describes the scope of the project.

Version 2.3 - January 20th, 2015
--------------------------------
- Adapted to Proteus release.
- Added Redis caching support (via Predis).
- Added Starbase class, with calculation of fuel consumption.
- I decided not to remove teams yet, as the functionality can still be used to calculate "what-if" scenarios.
- Existing users will have to remake the configuration file based on the new template.

Version 2.2 - December 14th, 2014
---------------------------------
- Adapted to Rhea release.
- Extracted market data from the Type class hierarchy to separate classes, RegionMarketData and GlobalPriceData
(averagePrice and adjustedPrice as returned by the CREST API and needed for industry calculations). This provides better
separation of concerns and removes some inconsistencies for types that inherited from Sellable, but were not actually on
the market. Trying to get the market objects from these types will throw an exception instead.
- Removed Sellable class.
- Split iveeCore tables to their own DB schema, separate from SDE DB. This improves handling of the DB during SDE
upgrades, as the iveeCore tables don't have to be copied, which can potentially become multiple GB in size.
- Refactored base Type classes and caching, the new hierarchy being CacheableCommon <- SdeType <- Type.
- Removed assembly line compatibility hack for blueprints, provided missing database rows instead (data missing in the
SDE since Phoebe).
- Fixed bugs in class loader and EMDR cache invalidation.
- Existing users will have to remake the configuration file based on the new template.

Upcoming changes:
- With the introduction of market data API endpoints to authenticated CREST, it is time to replace EMDR.
- The last industry teams in game will expire on January 22th, so the functionality will then be removed from iveeCore.

Version 2.1 - November 8th, 2014
--------------------------------
- Adapt to Phoebe release
-> Rename REBlueprint to T3Blueprint.
-> Refactor reverse engineering as it has been merged into invention, also changing the class hierarchy for Relic and
T3Blueprint.
-> Adapt invention to changes.
-> The SDE is missing assembly line compatibility data for blueprints, which made a hack necessary to make it still
work. However, the calculated cost of research, copying and invention will not take into account cost bonuses for
assembly lines that have them in-game.
- Existing users will have to remake the configuration file based on the new template.

Version 2.0.1 - October 1st, 2014
---------------------------------
- Adapt to Oceanus release.
- Small bugfixes to CREST updater.

Version 2.0 - September 20th, 2014
----------------------------------

- Major overhaul to adapt to industry changes brought with EvE's Crius expansion
-> CREST data fetchers for system industry indices, teams & specialities, facilities and market prices
-> Refactored all industry activity methods to make use of the new modifiers, removed obsoleted methods
- Namespaces, class autoloading and style changes to make iveeCore a bit more PSR compliant
- Restructured SDE, Type and Cache classes for better separation of concerns
- Added Reverse Engineering classes and calculation
- Made instantiation of iveeCore objects use dynamic class name lookup more consistently
- Removed POS slot fuel cost estimation as it was deemed out of scope for this library and confusing
- Database changes, migration provided
- Existing users will have to remake the configuration file based on the new template

Version 1.4.1 - July 14, 2014
-----------------------------
- Fixed faulty SQL queries in the EMDR client
- Throw exceptions on SQL errors
- Print type and region names in the EMDR client for incoming data

Version 1.4 - July 11, 2014
---------------------------
- Refactored EMDR client: Multi region support, reduce memory usage, avoid id autoincrement inflation
- Replaced SDE->printDbStats() with getStats()
- Replaced DB hack for vol and tx weekly averages, DB schema changed as a result; DB migration provided
- Note that the file iveeCoreConfig_template.php changed, thus adaptation of the configuration file is required

Version 1.3.1 - June 8, 2014
----------------------------
- Adapted README, the configuration template and the unit test to Kronos 1.0.
- Added bash script to restart the EMDR client

Version 1.3 - March 29, 2014
----------------------------
- Added parsers for EFT-style and EvE's XML fitting descriptions as well as cargo and ship scanning results.
- Reworked how configuration & defaults and utility methods are organized, for cleaner separation and better
consistency. Defaults now reside in IveeCoreDefaults.php and customization is done in the subclass/file
MyIveeCoreDefaults.php. As a consequence, iveeCoreConfig_template.php also changed a lot, therefore configuration files
will have to be redone.
- Adapted README to Rubicon 1.3 release
- More bugfixes and unit tests

Version 1.2.1 - February 16, 2014
---------------------------------
- Minor changes to include path handling
- Adapted README to Rubicon 1.2 release
- Added iveeCoreConfig.php to .gitignore

Version 1.2 - February 8, 2014
------------------------------
- Added reaction calculations
- Rewrote type materials and requirements handling. This fixed reprocessing calculations and most off-by-1 errors. The
memory use of the type array was reduced by up to 30%. The stored procedure iveeGetRequirements() is obsoleted by this
change, which makes for more maintainable code.
- Bug fixes and more convenience functions.
- Renamed MaterialSet to MaterialMap and SkillSet to SkillMap as it better describes the classes purpose.
- Renamed CopyData to CopyProcessData, InventionData to InventionProcessData and ManufactureData to
ManufactureProcessData for consistency.
- Added some PHPUnit test cases covering different parts of iveeCore.
- Note that the file iveeCoreConfig_template.php changed, thus adaptation of the configuration file is required

Version 1.1 - November 10, 2013
-------------------------------
- Added reprocessing calculations for Type objects
- Added volume attribute to Type classes and total volume calculation to process classes
- Moved material requirements from process class to its own MaterialSet class
- Moved skill requirements from process class to its own SkillSet class
- Added custom Exceptions
- More parameter sanity checks
- Note that the file iveeCoreConfig_template.php and the store procedure iveeGetRequirements() were changed

Version 1.0 - November 2, 2013
------------------------------
- Initial release.
