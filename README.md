# iveeCore
a PHP library for calculations of EVE Online industrial activities

Copyright (C)2013-2015 by Aineko Macx
All rights reserved.


## License
Unless otherwise noted, all files in this distribution are released under the LGPL v3.
See the file LICENSE included with the distribution.


## Purpose and target audience
The goal of this project is to provide its users with a simple but powerful API to get information about industrial activities in EVE Online such as bill of materials, activity cost and profit or skill requirements. By hiding the complexities of EvE's Static Data Export, iveeCore helps developers to quickly prototype scripts or develop full blown (web) applications.

iveeCore will likely be most useful for developers with at least basic PHP knowledge wanting to create their own industry related tools.

These are a few example questions that can be answered with a few lines of code using iveeCore:
- "What is the profit of buying this item in Jita and selling in Dodixie?"
- "What do these items reprocess to? And whats the volume of that and it's sell value in Jita?"
- "What is the job cost for building this item in this system? And when built in a POS in low sec?"
- "What is the total bill of materials to build these Jump Freighters and the minimum skills?"
- "Is it more profitable to build the components myself or buy off the market?"
- "Whats the total profit for copying this BPC, inventing with it, building from the resulting T2 BPC and selling the result?"
- "How do different Decryptors affects ISK/hour?"
- "How do Blueprint ME levels impact capital ship manufacturing profits?"
- "How much is a month of Unrefined Ferrofluid Reaction worth?"


## Features
- An API that strives to be "good", with high power-to-weight ratio
- Strong object oriented design and class model for inventory types
- Classes for representing manufacturing, copying, T2 & T3 invention, research and reaction activities, with recursive component building
- Market data gathering via EMDR with realistic price estimation and profit calculation
- CREST data fetcher handling system industry indices, market prices and facilities
- Parsers for EFT-style and EvE XML ship fittings descriptions as well as cargo and ship scanning results
- Caching support for Memcached or Redis (via Predis)
- Extensible via configurable subclassing
- A well documented and mostly PSR compliant codebase


## Requirements
For basic usage, iveeCore requires:
- PHP >= 5.3 CLI (64 bit). PHP 5.5 CLI 64 bit recommended.
- MySQL >= 5.5 or derivate. [MariaDB 10](https://mariadb.org/) recommended.
- Steve Ronuken's EVE Static Data Export (SDE) in MySQL format with industry tables

However, with just that you won't have access to the EMDR market data feed and thus lack any pricing and cost/profit calculation capabilities, as it requires the ZMQ PHP bindings. Since ZMQ is a not a standard module you'll need (at least terminal) root access to whatever box you plan on running the EMDR client on. Note that EMDR is being phased out in favor of CREST in an upcoming release.

With long running batch scripts iveeCore can consume more RAM than what is typically configured on shared hosting offers for PHP, so a VPS is likely the minimum required setup for full functionality. [A VM on a desktop is fine too](http://k162space.com/2014/03/14/eve-development-environment/).

For best performance of iveeCore, using caching (Memcached or Redis) is highly recommended. Also using APC is recommended for faster application startup if using PHP prior to version 5.5. Using PHP 5.4 or newer will reduce memory usage of iveeCore by about a third compared to 5.3.

With increasing number of tracked regional markets, the database size and load from the EMDR client also increases.

Preliminary tests indicate iveeCore also works with the [HipHop Virtual Machine](http://en.wikipedia.org/wiki/HHVM). For long running scripts it can bring some speed improvements and significant memory savings. The EMDR client is not compatible out of the box due to the ZMQ binding requirement.
Pre-built HHVM packages can be found [here](https://github.com/facebook/hhvm/wiki/Prebuilt%20Packages%20for%20HHVM).

## Installation
- Setting up the environment

These steps assume an Ubuntu Server 14.04 as environment, which is where the author develops and uses iveeCore. Other can probably work too, but are untested.
Run the following command with root privileges to install the required packages:
```
apt-get install build-essential git mysql-server-5.6 php5-dev php5-cli phpunit php5-mysqlnd php5-curl php5-memcached php5-json libzmq3 libzmq3-dev memcached re2c pkg-config
```

If you are using MariaDB or another MySQL derivate, or have a different setup and know what your are doing, adapt the command as required. If you want to use Redis instead of memcached, replace the memcached packages with 
```
redis-server redis-tools php-pear
```
and then follow the intructions to install Predis via PEAR channel as described [here](https://github.com/nrk/predis).

### Compile PHP ZMQ binding

To install the PHP ZMQ binding, follow the "Building from Github" instructions found here:
[http://zeromq.org/bindings:php](http://zeromq.org/bindings:php)

Enable the freshly built extension in PHP by creating the file
/etc/php5/cli/conf.d/zmq.ini with the following content:
```
extension=zmq.so
```
Test it by running the command:
```
php -i | grep zmq
```
If everything went well, you should see a line with the libzmq version.

### Setting up the Static Data Export DB in MySQL

The SDE dump in MySQL format can usually be found in the Technology Lab section of the EVE Online forum, thanks to helpful 3rd party developer Steve Ronuken. At the time of this writing the latest conversion can be found here:
[https://forums.eveonline.com/default.aspx?g=posts&t=397875](https://forums.eveonline.com/default.aspx?g=posts&t=397875)

Using your favorite MySQL administration tool, set up a database for the SDE and give a user full privileges to it. I use a naming scheme to reflect the current EvE expansion and version, for instance "eve_sde_pro10". Then import the SDE SQL file into this newly created database. FYI, phpmyadmin will probably choke on the size of the file, so I recommend the CLI mysql client or something like [HeidiSQL](http://www.heidisql.com/).
Then create a second database, naming it "iveeCore" and giving the same user as before full privileges to it.

### Setup iveeCore

You'll probably want to git clone iveeCore directly into your project:

```
cd /path/to/my/project
git clone git://github.com/aineko-m/iveeCore.git
```

Once you've done this, you'll find the directory 'iveeCore'. Import the file iveeCore/sql/SDE_additions.sql into the same database you set up for the SDE. This will add a few indices for improved peformance and add some missing data. Then imort the file iveeCore/sql/iveeCore_tables_and_SP into the iveeCore database. This will create the tables iveeCore uses and stored procedures.

Make a copy of the file iveeCore/Config_template.php, naming it Config.php and edit the configuration to match your environment.
iveeCore comes with a lot of default variables describing an industrial setup in eve, defined in iveeCore/Defaults.php. Once you get comfortable using iveeCore you'll want to customize these defaults, to be done in iveeCoreExtensions/MyDefaults.php, which extends the Defaults class and thus allows you to overwrite whichever aspect is required. The variables are commented or should be self-explanatory to an EvE industrialist or developer.
MyDefaults serves as example of the intended way of extending iveeCore.

To test the setup try running the EMDR client:
```
php emdr.php
```
If everything is fine, you should see IDs of items and regions for which price and history market data is being updated as it comes in. Ctrl+C to cancel.
You'll want to setup this script to run in the background to have up-to-date market data available in iveeCore at all times. The EMDR client tends to become zombified if it doesn't receive data for a while so occasionaly you'll have to kill the existing process and restart it. restart_emdr.sh is a bash script that does this. You can set up a cronjob to run it on a hourly basis, for instance. The job needs to run under a user with the necessary rights, but not root!

During the first few days of market data collection the load on the DB will be higher, especially if multiple regions are tracked, due to the large number of inserts for the market history of items. By default, the EMDR client only tracks The Forge market region. This can be changed in iveeCoreExtensions/MyDefaults.php.

Note that EMDR relays can change, so visit https://eve-market-data-relay.readthedocs.org/en/latest/access.html and pick the one nearest to you and change iveeCore/Config.php accordingly.

Now test the CREST client:
```
php update_crest.php
```
This will fetch the newest relevant data from CREST. This script should be run every few hours so especially your system industry indices are up-to-date. They do change over the course of a day.


## Upgrading the SDE
Whenever you want to upgrade to another SDE, the following steps are recommended:
- Create a new database and set up permissions for it
- Import the new SDE into this new database
- Import SDE_additions.sql into it
- Adapt iveeCore/Config.php to the new database
- If using cache, flush it
- It is good practice to run the provided unit test to check if everything is working as intended
- Possibly an updated iveeCore version is required to become compatible to changes, check the forum thread.
- Restart the EMDR client and any long running scripts you might have


## Upgrading iveeCore
Most of the time upgrading to newer versions of iveeCore is as simple as cd-ing into iveeCore's directory and running "git pull".
When the iveeCore/Config_template.php is extended you'll have to recreate or adapt your own iveeCore/Config.php.

If necessary, iveeCore will provide migration SQL for adapting the DB to new versions.

Again, running the provided unit test to check for problems is a good idea.


## Usage
Please take a look at the class diagram in [iveeCore/doc/iveeCore_class_diagram.pdf](https://github.com/aineko-m/iveeCore/raw/master/doc/iveeCore_class_diagram.pdf) and familiarize yourself with the iveeCore object model. iveeCore provides a simple but powerful API. Once configured, one can use it as demonstrated by the following examples. Do note that you have to have run update_crest.php at least once before any of the industry methods will work.
```php
<?php
//initialize iveeCore. Adapt path as required.
require_once('/path/to/iveeCore/iveeCoreInit.php');

//show the object for 'Damage Control I'
print_r(\iveeCore\Type::getById(2046));

//it's also possible to instantiate type objects by name
$type = \iveeCore\Type::getByName('Damage Control I');

//Now lets looks at industry activities.
//First we need to get an IndustryModifier object, which aggregates all the things
//like system indices, available assembly lines, skills & implants.
$iMod = \iveeCore\IndustryModifier::getBySystemIdForAllNpcStations(30000180); //Osmon

//manufacture 5 units of 'Damage Control I' with ME 10 and TE 20
$manuData = $type->getBlueprint()->manufacture($iMod, 5, 10, 20);

//show the ManufactureProcessData object
print_r($manuData);

//print materials, cost and profits for this process
$manuData->printData();

//get the data for making Damage Control I blueprint copy, inventing from it with a
//decryptor and building from the resulting T2 BPC, recursively building the necessary
//components
$processData = \iveeCore\Type::getByName('Damage Control II Blueprint')->copyInventManufacture($iMod, 34203, true);

//get the raw profit for running an Unrefined Hyperflurite Reaction for 30 days,
//taking into account the refining and material feedback steps,
//using defaults for refinery efficiency and skills
$reactionProcessData = \iveeCore\Type::getByName('Unrefined Hyperflurite Reaction')->react(24 * 30, true, true);
echo PHP_EOL . 'Reaction Profit: ' . $reactionProcessData->getProfit() . PHP_EOL;
```
The above are just basic examples of the possibilities you have with iveeCore. Reading the PHPDoc in the classes is suggested. Of particular importance to users of the library are Type and its child classes, ProcessData and its child classes and IndustryModifier.

## Notes
Although I tried to make iveeCore as configurable as possible, there are still a number of underlying assumptions made and caveats:
- For profit calculations, it is assumed you buy items using buy orders; you sell your products with sell orders with competitive pricing.
- The prices of items that can't be sold on the market also can't be determined. This includes BPCs (The _cost_ of copying, inventing or researching a BPC can and is calculated for processes, however).
- Calculated material amounts might be fractions, which is due invention chance or (hypothetical) production batches in non-multiples of portionSize. These should be treated as the average required or consumed when doing multiple production batches.
- The EMDR client does some basic filtering on the incoming market data, but there is no measure against malicious clients uploading fake data. This isn't known to have caused any problems, but should be considered.
- When automatically picking AssemblyLines for use in industry activities, iveeCore will choose first based on ME bonuses, then TE bonuses and cost savings last.
- (My)Defaults.php contains functions for setting and looking up default BPO ME and TE levels. Also see Extending iveeCore below.

Generals notes:
- Remember to restart or flush the cache after making changes to type classes or changing the DB. For memcache, you can do so with a command like: ```echo 'flush_all' | nc localhost 11211```. For Redis, enter the interactive Redis client using ```redis-cli``` and issue ```FLUSHALL```.
Alternatively you can run the PHPUnit test, which also clears the cache.
- iveeCore is under active development so I can't promise the API will be stable, especially if noone gives input.
- When iveeCore is updated, be sure to read RELEASENOTES for changes that might affect your application or setup


## Extending iveeCore
To extend iveeCore to your needs, the suggested way of doing so is to use subclassing, creating new classes inheriting from the iveeCore classes, and changing the configuration (iveeCore/Config::classes). Class names are looked up dynamically, so with the adjustment objects from your classes will get instantiated instead.
You can modify iveeCore directly, however, you'll need to comply with the LGPL and release your modifications under the same license. Also you'll have more work maintaining and applying patches to iveeCore when updates are released.


## Future Plans
With the release of the new market price API endpoint via authenticated CREST, EMDR and cache scraping will become obsolete; a CREST client will be introduced (also as a standalone library). Something for calculating ore compression would be nice. While T3 invention is now supported by iveeCore, T3 production chains are not, so this is an area where there is possibly going to be improvements. PI is not of interest to me, but would welcome someone working on it.
I'll try to keep improving iveeCores structuring, API and test coverage. I also want to write a more comprehensive manual. I'm open to suggestions and will also consider patches for inclusion. If you find bugs, have any other feedback or are "just" a user, please post in this thread: [https://forums.eveonline.com/default.aspx?g=posts&t=292458](https://forums.eveonline.com/default.aspx?g=posts&t=292458)


## FAQ
Q: What were the beginnings of iveeCore?
A: In early 2012 I began writing my own indy application in PHP. I had been using the [Invention Calculator Plugin](http://oldforums.eveonline.com/?a=topic&threadID=1223530) for EvEHQ, but with the author going AFG and the new EvEHQ v2 having a good but not nearly flexible enough calculator for my expanding industrial needs, I decided to build my own. The application called "ivee" grew over time and well beyond the scope of it's predecessor. In the end it was rewritten from scratch two and a half times, until I was happy with the overall structure.
Eventually I decided I wanted to release the part of the code that provided general useful functionality, without revealing too much of ivee's secret sauce. So I put in some effort into separating and generalizing the code dealing with SDE DB interaction and Type classes into the library which now is iveeCore.

Q: What's the motivation for releasing iveeCore?
A: I wanted to share something back to the eve developer community. I also see it as an opportunity to dip my toes into working on a Github hosted project, even if it is a small one, and it is a motivation to strive for better code quality.

Q: Are you going to release ivee proper?
A: No.

Q: Why not use a library like [Perry](https://github.com/3rdpartyeve/perry) for CREST access?
A: I wanted to avoid more dependencies and the CREST functionality required by iveeCore is very simple, so I made a minimal implementation for it. This is going to be superseded by the upcoming iveeCrest.


## Acknowledgements
EVE Online is a registered trademark of CCP hf.