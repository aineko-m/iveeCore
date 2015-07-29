# iveeCore
a PHP engine for calculations of EVE Online industrial activities and CREST library

Copyright (C)2013-2015 by Aineko Macx
All rights reserved.


## License
Unless otherwise noted, all files in this distribution are released under the LGPL v3.
See the file LICENSE included with the distribution.


## Purpose and target audience
The goal of this project is to provide its users with a simple but powerful API to get information about industrial activities in EVE Online such as bill of materials, activity cost and profit or skill requirements. By providing solutions for dealing with EvE's Static Data Export and CREST API, abstracting from their complexities and quirks, iveeCore helps developers to quickly prototype scripts or develop full blown (web) applications.

iveeCore will likely be most useful for developers with at least basic PHP knowledge wanting to create their own industry related or CREST powered tools.

These are a few example questions that can be answered with a few lines of code using iveeCore:
- "What is the profit of buying this item in Jita and selling in Dodixie?"
- "What do these items reprocess to? And whats the volume of that and it's sell value in Jita?"
- "How much does this EFT fit cost? How much is this scanned cargo worth?"
- "What is the job cost for building this item in this system? And when built in a POS in low sec?"
- "What is the total bill of materials to build these Jump Freighters and the minimum skills?"
- "Is it more profitable to build the components myself or buy off the market?"
- "Whats the total profit for copying this BPC, inventing with it, building from the resulting T2 BPC and selling the result?"
- "How do different Decryptors affect ISK/hour?"
- "How do Blueprint ME levels impact capital ship manufacturing profits?"
- "How much is a month of Unrefined Ferrofluid Reaction worth?"


## Features
- An API that strives to be "good", with high power-to-weight ratio
- Strong object oriented design and class model for inventory types
- Classes for representing manufacturing, copying, T2 & T3 invention, research and reaction activities, with recursive component building
- Market data gathering via CREST with realistic price estimation and profit calculation
- CREST data fetcher handling system industry indices, market prices and facilities
- Parsers for EFT-style and EvE XML ship fittings descriptions as well as cargo and ship scanning results
- Caching support for Memcached or Redis (via PhpRedis)
- Extensible via configurable subclassing
- A well documented and mostly PSR compliant codebase

The CREST portion of the engine (formerly the iveeCrest library) can be used in conjunction with iveeCore or independently. Some of its features include:
- Authenticated CREST based
- Methods for most endpoints reachable from CREST root are available, plus a few deeper endpoints. Easily expanded to support more (pull requests welcome!)
- Gathering of multipage responses
- Supports parallel GET requests with high performance asynchronous processing
- Multilayer cache design
- The index-less collections returned by CREST are properly re-indexed by IDs
- Includes a self-contained web-script to retrieve a refresh token


## Requirements
For basic usage, iveeCore requires:
- PHP >= 5.4 CLI/Web (64 bit) or HHVM >= 3.6. [Newest HHVM](https://github.com/facebook/hhvm/wiki/Prebuilt%20Packages%20for%20HHVM) recommended
- One of the following: Memcached + php5-memcached OR Redis + php5-redis ([phpredis](https://github.com/phpredis/phpredis))
- MySQL >= 5.5 or derivate. [MariaDB 10](https://mariadb.org/) recommended
- Steve Ronuken's EVE Static Data Export (SDE) in MySQL format

If using PHP prior to version 5.5 also using the APC opcode cache is recommended for quicker application startup. Using HHVM instead of the standard PHP interpreter will provide a nice speed improvement and a significant reduction in memory use.

You'll probably want root access to whatever box you plan on running iveeCore on, but this isn't strictly required. As for the resource use, a VPS is likely the minimum required setup for full functionality. [A VM on a desktop is fine too](http://k162space.com/2014/03/14/eve-development-environment/).

The largest chunk of used DB space will come from the (CREST enabled) market history and with time also market prices. Expect each of those tables to consume roughly 1MB per day per region (if fetching all relevant market items).

The necessary amount of cache memory to avoid hot data being evicted is strongly dependant on the usage pattern. Users should carefully monitor their caches (and review their applications data requirements) before trying to cache objects that won't be read again before expiry, potentially causing cache thrashing.


## Installation
- Setting up the environment

These steps assume an Ubuntu Server 14.04 as environment, which is where the author develops and uses iveeCore. Other can probably work too, but are untested.
Run the following command with root privileges to install the required packages:
```
apt-get install git mysql-server-5.6 php5-cli phpunit php5-mysqlnd php5-curl php5-memcached php5-json memcached
```

If you are using MariaDB or another MySQL derivate, or HHVM instead of PHP, or have a different setup and know what your are doing, adapt the command as required. If you want to use Redis instead of memcached, replace the memcached packages with 
```
redis-server redis-tools php5-redis
```

### Setting up the Static Data Export DB in MySQL

The SDE dump in MySQL format can usually be found in the Technology Lab section of the EVE Online forum, thanks to helpful 3rd party developer Steve Ronuken. At the time of this writing the latest conversion can be found here:
[https://forums.eveonline.com/default.aspx?g=posts&t=433747](https://forums.eveonline.com/default.aspx?g=posts&t=433747)

Using your favorite MySQL administration tool, set up a database for the SDE and give a user full privileges to it. I use a naming scheme to reflect the current EvE expansion and version, for instance "eve_sde_aeg11". Then import the SDE SQL file into this newly created database. FYI, phpmyadmin will probably choke on the size of the file, so I recommend the CLI mysql client or something like [HeidiSQL](http://www.heidisql.com/).
Then create a second database, naming it "iveeCore" and giving the same user as before full privileges to it.


### Setup iveeCore

You'll probably want to git clone iveeCore directly into your project:

```
cd /path/to/my/project
git clone git://github.com/aineko-m/iveeCore.git
```

Once you've done this, you'll find the directory 'iveeCore'. Import the file iveeCore/sql/SDE_additions.sql into the same database you set up for the SDE. This will add a few indices for improved performance. Then import the file iveeCore/sql/iveeCore_tables_and_SP into the iveeCore database. This will create the tables iveeCore uses and stored procedures.

Make a copy of the file iveeCore/Config_template.php, naming it Config.php and edit the configuration to match your environment.

iveeCore comes with a lot of variables pre-set to reasonable (but not universal) default values, so with time adjustments will be needed. Apart from the parameters in Config.php there are many things that can be set on IndustryModifier objects, which provide context data for industry and pricing processes. Developers will also surely want to customize the CharacterModifier and BlueprintModifier classes via subclassing, so skills, standings and BP research levels match their setup or scenario.


### Setup iveeCore using Composer

As an alternative, you can use Composer to install iveeCore, the package name is 'aineko-m/ivee-core'. See https://getcomposer.org/ for how to get started with composer.
To configure iveeCore, you can do this during runtime using

```php
use iveeCore\Config;

Config::setSdeDbUser('ivee');
Config::setSdeDbPw('supersecret');
Config::setSdeDbName('eve');
Config::setIveeDbName('ivee_core');
```
If using this method you don't have to create Config.php based on a copy of Config_template.php. If the file Config.php doesn't exist, it will use the template file.


### Setup CREST

To enable access to CREST, you'll need to setup the credentials for your application. To pull data from authenticated CREST, we'll need to acquire a refresh token, which is tied to your application and the character that authorized it's data to be used.

iveeCore comes with a self-contained web-script that does just this. It can be found under www/getrefreshtoken.php.
Simply copy that file to a webserver and point your web browser to it. The script will take you through the steps. You'll be asked to register your application at [https://developers.eveonline.com/applications](https://developers.eveonline.com/applications) if you haven't already, selecting "CREST Access" and the "publicData" scope.

In the script form you'll then need to enter application ID, secret and redirect URL (filled automatically). When you submit the form you'll be redirected to the CREST authentication page where you'll need to authorize your app to access your characters data. With that done your browser will be redirected back to the script and it'll be able to pull the refresh token and display it. Take note.

Edit Config.php to enter your CREST client ID, secret and refresh token. These are used at runtime to fetch an ephemeral access token, which is then used to fetch authenticated data from CREST.

To test the CREST setup run the script under cli/updater.php from the console:
```
php updater.php -test
```
If everything is fine, it should see the CREST response to an access token verify call, which shows some data about your character.
The script offers various flags that control its operation (multiple can be specified):
"-industry" updates the system industry indices and the global (adjusted, average) prices.
"-facilities" updates the information on conquerable stations.
"-history" runs a market history update of all relevant market items for all regions configured in Config::$trackedMarketRegionIds. Note that even on a fast internet connection this will take at least 20 minutes per region.
"-prices" runs a market prices update of all relevant market items for all regions configured in Config::$trackedMarketRegionIds. Note that even on a fast internet connection this will take at least 20 minutes per region.
"-all" runs all of the above updates.
"-s" sets silent operation (except errors)

You'll probably want to setup this script to run with the -all flag automatically (cronjob) at least once a day. Note that history will only be update once per day. The price update respects the Config::$maxPriceDataAge setting and will ignore items whose price data hasn't reached that age. Therefor $maxPriceData age should be set to a matching value, i.e. if you run the update every 6 hours, set the variable to 21600 (6x60x60) or slightly less. Cache expiry of price objects is also controlled via this variable.

The history update should be run before the price update because some history statistics are used when estimating the realistic buy/sell prices. It would fetch the history on demand, but it would not be asynchronous, thus slower overall than if fetching history explicitly first (which runs multiple requests in parallel asynchronously).

For the applications that make use of iveeCore it might be undesirable to have it pull market data on-demand when it encounters old data, potentially incurring substantial delays thanks to CREST. To solve this, the recommended way is setting a higher $maxPriceDataAge globally in Config at runtime, thus it will only trigger a live fetch if the data is really old, while batched updates will run normally. Alternatively setting the variable to null on the IndustryModifier used to provide context for specific pricing operations will disable the data age checking completely.

On the first history update run the DB load will be higher because non-trivial amounts of data is being inserted (~1GB for 6 regions). Subsequent runs should be quicker. By default, the CREST updater only tracks "The Forge", "Lonetrek", "Heimatar", "Sinq Laison", "Domain" and "Metropolis" market regions. This can be changed in Config.php, also at runtime.

During tests on a fast line with a SSD backed DB on runs over 6 regions I've seen averages of 50 item history updates per second. Price data is smaller, but two calls are required per item (buy and sell), in the end averaging at 45 item price updates per second (90 reqs/s). Although steps have been taken to try to minimize the I/O load of the update process, having slower storage like a mechanical disk backing the DB reduces the speed somewhat. Tuning the mysql configuration can have a positive impact on the performance.


## Upgrading the SDE
Whenever you want to upgrade to another SDE, the following steps are recommended:
- Create a new database and set up permissions for it
- Import the new SDE into this new database
- Import SDE_additions.sql into it
- Adapt iveeCore/Config.php to the new database
- If using cache, flush it
- It is good practice to run the provided unit test to check if everything is working as intended
- Possibly an updated iveeCore version is required to become compatible to changes, check the forum thread.
- Restart the any long running scripts you might have


## Upgrading iveeCore
Most of the time upgrading to newer versions of iveeCore is as simple as cd-ing into iveeCore's directory and running "git pull".
When the iveeCore/Config_template.php is extended you'll have to recreate or adapt your own iveeCore/Config.php.

If necessary, iveeCore will provide migration SQL for adapting the DB to new versions.

Again, running the provided unit test to check for problems is a good idea.


## Usage
Please take a look at the class diagram in [iveeCore/doc/iveeCore_class_diagram.pdf](https://github.com/aineko-m/iveeCore/raw/master/doc/iveeCore_class_diagram.pdf) and familiarize yourself with the iveeCore object model. iveeCore provides a simple but powerful API. Once configured, one can use it as demonstrated by the following examples. Do note that you have to have run "cli/updater.php -all" at least once before any of the industry methods will work.
```php
<?php
//initialize iveeCore. Adapt path as required.
require_once('/path/to/iveeCore/iveeCoreInit.php');

use iveeCore\Type, iveeCore\IndustryModifier;

//show the object for 'Damage Control I'
print_r(Type::getById(2046));

//it's also possible to instantiate type objects by name
$type = Type::getByName('Damage Control I');

//Now lets looks at industry activities.
//First we need to get an IndustryModifier object, which aggregates all the things
//like system indices, available assembly lines, character skills & implants.
$iMod = IndustryModifier::getBySystemIdForAllNpcStations(30000180); //Osmon

//manufacture 5 units of 'Damage Control I' with ME 10 and TE 20
$manuData = $type->getBlueprint()->manufacture($iMod, 5, 10, 20);

//show the ManufactureProcessData object
print_r($manuData);

//Lets create another IndustryModifier object for Jita, so we can calculate costs and 
//profits there
$sMod = IndustryModifier::getBySystemIdForAllNpcStations(30000142);
//set default market station to Jita 4-4 CNAP
$sMod->setPreferredMarketStation(60003760);
        
//print materials, cost and profits for this process
$manuData->printData($sMod);

//get the data for making Damage Control I blueprint copy, inventing from it with a
//decryptor and building from the resulting T2 BPC, recursively building the necessary
//components
$processData = Type::getByName('Damage Control II Blueprint')->copyInventManufacture($iMod, 34203, true);

//get the raw profit for running an Unrefined Hyperflurite Reaction for 30 days,
//taking into account the refining and material feedback steps, and refinery
//efficiency and tax dependent on character skill and standing, then selling in Jita
$reaction = Type::getByName('Unrefined Hyperflurite Reaction');
$reactionProcessData = $reaction->react(24 * 30, true, true, $iMod);
echo PHP_EOL . 'Reaction Profit: ' . $reactionProcessData->getProfit($sMod) . PHP_EOL;
```
The above are just basic examples of the possibilities you have with iveeCore. Reading the PHPDoc in the classes is suggested. Of particular importance to users of the engine are type, process and industry context classes.

## Notes
Although I tried to make iveeCore as configurable as possible, there are still a number of underlying assumptions made and caveats:
- For profit calculations, it is assumed you buy items using buy orders; you sell your products with sell orders with competitive pricing.
- The prices of items that can't be sold on the market also can't be determined. This includes BPCs (The _cost_ of copying, inventing or researching a BPC can and is calculated for processes, however).
- Calculated material amounts might be fractions, which is due invention chance or (hypothetical) production batches in non-multiples of portionSize. These should be treated as the average required or consumed when doing multiple production batches.
- When automatically picking AssemblyLines for use in industry activities, iveeCore will choose first based on ME bonuses, then TE bonuses and cost savings last.

Generals notes:
- Remember to restart or flush the cache after making changes to type classes or changing the DB. For memcache, you can do so with a command like: ```echo 'flush_all' | nc localhost 11211```. For Redis, enter the interactive Redis client using ```redis-cli``` and issue ```FLUSHALL```.
Alternatively you can run the PHPUnit test, which also clears the cache.
- iveeCore is under active development so I can't promise the API will be stable.
- When iveeCore is updated, be sure to read RELEASENOTES for changes that might affect your application or setup


## Extending iveeCore
If you extend the engine with features that are generally useful and compatible with the goals and structuring of the project, Github pull requests are welcome. In any case, if you modify iveeCrest source code, you'll need to comply with the LGPL and release your modifications under the same license.

To extend iveeCore to your needs without changing the code, the suggested way of doing so is to use subclassing, creating new classes inheriting from the iveeCore classes or implementing the appropriate interfaces, and changing the configuration (iveeCore/Config::classes). Class names are looked up dynamically, so with the adjustment objects from your classes will get instantiated instead.



## Future Plans
A simplex algorithm based ore compression calculator is in the works, which will allow you to define the target amounts of minerals and the calculator will give you the quantities of (compressed) ores to buy to get these numbers while minimizing price.

I'll try to keep improving iveeCores structuring, CREST endpoint support, API and test coverage. I also want to write a more comprehensive manual. I'm open to suggestions and will also consider patches for inclusion. If you find bugs, have any other feedback or are "just" a user, please post in this thread: [https://forums.eveonline.com/default.aspx?g=posts&t=292458](https://forums.eveonline.com/default.aspx?g=posts&t=292458).


## FAQ
Q: What were the beginnings of iveeCore?
A: In early 2012 I began writing my own indy application in PHP. I had been using the [Invention Calculator Plugin](http://oldforums.eveonline.com/?a=topic&threadID=1223530) for EvEHQ, but with the author going AFG and the new EvEHQ v2 having a good but not nearly flexible enough calculator for my expanding industrial needs, I decided to build my own. The application called "ivee" grew over time and well beyond the scope of it's predecessor. In the end it was rewritten from scratch two and a half times, until I was happy with the overall structure.
Eventually I decided I wanted to release the part of the code that provided general useful functionality, without revealing too much of ivee's secret sauce. So I put in some effort into separating and generalizing the code dealing with SDE DB interaction and Type classes into the engine which now is iveeCore.

Q: What's the motivation for releasing iveeCore?
A: I wanted to share something back to the eve developer community. I also see it as an opportunity to dip my toes into working on a Github hosted project, even if it is a small one, and it is a motivation to strive for better code quality.

Q: Are you going to release ivee proper?
A: No.


## Acknowledgements
EVE Online is a registered trademark of CCP hf.
