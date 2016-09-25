# Installing iveeCore

## Requirements
iveeCore requires:
- PHP >= 5.4 CLI/Web (64 bit) or HHVM >= 3.6. [Newest HHVM](https://github.com/facebook/hhvm/wiki/Prebuilt%20Packages%20for%20HHVM) recommended
- One of the following: Memcached + php5-memcached OR Redis + php5-redis ([phpredis](https://github.com/phpredis/phpredis))
- MySQL >= 5.5 or derivate ([MariaDB 10](https://mariadb.org/) recommended)
- Steve Ronuken's EVE Static Data Export (SDE) in MySQL format (not required if using only iveeCrest functionality)
- If using authenticated CREST: A refresh token (instructions of how to get below)

If using PHP prior to version 5.5 it is also recommended to use the APC opcode cache for quicker application startup. Using HHVM instead of the standard PHP interpreter will provide a nice speed improvement and a significant reduction in memory use.

You'll probably want root access to whatever box you plan on running iveeCore on, but this isn't required if the required packets are installed. As for the resource use, a VPS is likely the minimum required setup for full functionality. [A VM on a desktop is fine too](http://k162space.com/2014/03/14/eve-development-environment/).

The largest chunk of used DB space will come from the (CREST enabled) market history and with time also market prices. The initial history data pull for the 6 most active (default) market regions makes the DB go to about 1.2GB in size. Adding more regions doesn't scale linearly though, since if there is no market activity for items, they produce no data.

The necessary amount of cache memory to avoid hot data being evicted is strongly dependant on the usage pattern. Users should carefully monitor their caches (and review their applications data requirements) before trying to cache objects that won't be read again before expiry, potentially causing cache thrashing.


## Installation of OS packets
These steps apply to an Ubuntu 14.04 as environment. Other can probably work too, but are untested.
Run the following command with root privileges to install the required packages:
```
apt-get install git mysql-server-5.6 php5-cli phpunit php5-mysqlnd php5-curl php5-memcached php5-json memcached
```

If you are using MariaDB or another MySQL derivate, or HHVM instead of PHP, or have a different setup and know what your are doing, adapt the command as required. If you want to use Redis instead of memcached, replace the memcached packages with 
```
redis-server redis-tools php5-redis
```

## Git Clone iveeCore
You'll probably want to git clone iveeCore directly into your project:

```
cd /path/to/my/project
git clone git://github.com/aineko-m/iveeCore.git
```
Once you've done this, you'll find the directory 'iveeCore' with all its files.


## Setting up the Static Data Export DB in MySQL
If you intend to only use the CREST functionality through the iveeCrest classes, you may skip all the database setup steps.

The SDE dump in MySQL format can be found at Steve Ronukens Fuzzwork site: [https://www.fuzzwork.co.uk/dump/](https://www.fuzzwork.co.uk/dump/)

Using your favorite MySQL administration tool, set up a database for the SDE and give a user full privileges to acces and modify it. You could use a naming scheme to reflect the current EvE expansion and version, for instance "eve_sde_yc118_8". Then import the SDE SQL file into this newly created database. FYI, phpmyadmin will probably choke on the size of the file, so I recommend the CLI mysql client or something like [HeidiSQL](http://www.heidisql.com/).

Then create a second database, name it "iveeCore" and give the same user as before full privileges.

Import the file iveeCore/sql/SDE_additions.sql into the same database you set up for the SDE. This will add a few indices for improved performance. Then import the file iveeCore/sql/iveeCore_tables_and_SP into the iveeCore database. This will create the tables and stored procedures iveeCore uses.


## Setup the configuration file
There are two methods for handling the configuration of iveeCore: Either through a manually edited configuration file or setting the parameters programatically at runtime. Both have pros and cons.

Using the configuration file is simpler, but it is more of a hassle to adapt the file when the template changes and it stores some secrets in plaintext (in case of authenticated CREST). Setting parameters at runtime is more flexible and you won't have to adapt the configuration file when the template changes, but you'll have to write minimal wrappers for the CLI updater and Unit Tests to be able to use them. There is an example of such a wrapper at the end of this document.

- Config file: If you choose to use the manually edited config file, make a copy of the file iveeCore/Config_template.php, naming it Config.php and edit the configuration to match your environment.
- Parameters at runtime: If you choose to set the parameters at runtime just leave the Config_template.php as is, and configure iveeCore programatically through the setter methods of the iveeCore\Config class. The class loader will use the template if Config.php doesn't exist.

The following parts of the configuration are of particular interest:
- The DB connection, with its host, name, username and password.
- The cache connection. The default configuration is set up for using a local Memcached instance for caching. If you want to use Redis instead, you'll have to change the static variable Config::$cachePort (Redis default: 6379, Memcached: 11211) and the caching wrapper class with nickname 'Cache' in Config:$classes from \iveeCore\MemcachedWrapper to \iveeCore\RedisWrapper. A DoctrineCacheWrapper class is also available if you are using Doctrine.
- The authenticated CREST parameters like client ID, client secret and refresh token.

iveeCore comes with a lot of variables pre-set to reasonable (but not universal) default values, so eventually adjustments will be needed. Apart from the parameters in Config, two classes that developers will surely want to customize are CharacterModifier and BlueprintModifier, so that skills, standings and BP research levels match their setup or scenario. See info [about IndustryModifier](https://github.com/aineko-m/iveeCore/blob/master/doc/iveeCore.md#industrymodifier) and about [extending iveeCore](https://github.com/aineko-m/iveeCore/blob/master/doc/extending.md#extending-iveecore).


## Setup iveeCore using Composer
You can use Composer to install iveeCore, the package name is 'aineko-m/ivee-core'. See https://getcomposer.org/ for how to get started.
In this case, you'll default to configuring iveeCore programatically, which you can do at runtime like:

```php
use iveeCore\Config;

Config::setSdeDbUser('ivee');
Config::setSdeDbPw('supersecret');
Config::setSdeDbName('eve');
Config::setIveeDbName('ivee_core');
```


## Setup authenticated CREST
The following steps are only necessary if you intend to use authenticated CREST. For unauthenticated CREST, the configuration should work out of the box.

To enable access to authenticated CREST, you'll need to setup the credentials for your application. You'll also need to acquire a refresh token, which is tied to the character that authorized its data to be used.

iveeCore comes with a self-contained web-script that does exactly that. It can be found under [www/getrefreshtoken.php](https://github.com/aineko-m/iveeCore/blob/master/www/getrefreshtoken.php).
Simply copy that file to a webserver and point your web browser to it. The script will take you through the steps. You'll be asked to register your application at [https://developers.eveonline.com/applications](https://developers.eveonline.com/applications), if you haven't already, selecting "CREST Access" and the authentication scopes the application can potentially use.

The application ID and secret nee to be entered into the web script form, with the redirect URL (filled automatically). Also check the authentication scopes you want to request for this character. When you submit the form you'll be redirected to the CREST authentication page, where you'll need to authorize the app to access your characters data. Having done that your browser will be redirected back to the script and it'll be able to pull the refresh token and display it. Copy and save it.

Configure Config with your CREST client ID, secret and refresh token. These are used at runtime for getting an ephemeral access token, which in turn is used to fetch authenticated data from CREST.

To test the CREST setup run the script under [cli/updater.php](https://github.com/aineko-m/iveeCore/blob/master/cli/updater.php) from the console:
```
php updater.php -testauth
```
If everything is fine, you should see the CREST response to an access token verify call, which shows some data about your character.


## CREST updater tool
If you are only using iveeCrest, you can ignore the updater (apart from letting you check the authentication setup).

The updater tool under cli/update.php is used to pull CREST data in bulk and store it in the DB used by iveeCore. 

The tool offers various flags that control its operation:
```
== iveeCore 3.0.0 Updater ==
Available options:
-testauth     : Test the authenticated CREST connectivity (configured refresh token required)
-indices      : Update system industry indices
-globalprices : Update global prices (average, adjusted)
-facilities   : Update facilities (outposts)
-history      : Update market history for items in all configured regions
-prices       : Update market prices for items in all configured regions
-all          : Run all of the above updates
-s            : Silent operation
Multiple options can be specified.
```
The updates are run for data that has become too old, with different criteria for different data. Market history and orders are updated for all regions configured in Config::$trackedMarketRegionIds. The configuration variable Config::$maxPriceDataAge controls the maximum age of market data in seconds after which it will be updated.

Note that iveeCore will automatically pull the data from CREST when it has become stale during regular use, however, this might incur a long delay in the response time of an interactive application. By using the script, you can proactively pull all data in bulk, so the response for the user is always snappy.

You should setup this script to run with the -all flag automatically (cronjob) at least once a day. You can also run it more frequently, every hour for instance. Only data that requires updating will be fetched from CREST. The script prevents being executed twice at the same time, so long running update jobs that overlap into the next execution do not cause problems.

On the first history update run the DB load will be higher, because non-trivial amounts of data is being inserted (~1GB for 6 regions). Subsequent runs should be quicker. By default the CREST updater only tracks "The Forge", "Lonetrek", "Heimatar", "Sinq Laison", "Domain" and "Metropolis" market regions. This can be changed in Config.php.

On fast internet lines and with a SSD backed DB the updater is able to reach over 100 requests a second. Although steps have been taken to try to minimize the I/O load of the update process, having slower storage like a mechanical disk backing the DB reduces the speed somewhat. Tuning the mysql configuration can have a positive impact on the performance.

Note that the performance and reliability of the CREST server on CCPs side can vary a lot. A certain fraction of requests are randomly answered with errors. The updater will not retry those failed requests automatically, but subsequent runs will.


## Upgrading the SDE
Whenever you want to upgrade to another SDE, the following steps are recommended:
- Create a new database and set up permissions for it
- Import the new SDE into this new database
- Import SDE_additions.sql into it
- Adapt iveeCore/Config.php to the new database
- If using cache, flush it
- It is good practice to run the provided unit test to check if everything is working as intended
- Possibly an updated iveeCore version is required to become compatible to changes, check the forum thread.
- Restart any long running scripts you might have


## Upgrading iveeCore
When iveeCore is updated, be sure to read the [release notes](https://github.com/aineko-m/iveeCore/blob/master/RELEASENOTES.md) for changes that might affect your application or setup.
Most of the time upgrading to newer versions of iveeCore is as simple as cd-ing into iveeCore's directory and running "git pull".
When the iveeCore/Config_template.php is extended you'll have to recreate or adapt your own iveeCore/Config.php. This is not needed if you set your Config parameters at runtime.

If necessary, iveeCore will provide migration SQL for adapting the DB to new versions.

After the upgrade remember to restart or flush the cache. For memcached you can do so with a command like: ```echo 'flush_all' | nc localhost 11211```. For Redis enter the interactive Redis client using ```redis-cli``` and issue ```FLUSHALL```. Alternatively you can run the PHPUnit test, which also clears the cache.


## How to use the CLI updater and Unit Tests with configuration set at runtime
If you chose to set configuration parameters at runtime, the CLI updater and the Unit Tests won't work out of the box. You'll have to write a minimal wrapper for them where you set your parameters, as shown in the example below.

```php
<?php
require_once('/path/to/iveeCore/iveeCoreInit.php');
use iveeCore\Config;

//set the configuration parameters
Config::setCrestClientId('my_client_id');
Config::setCrestClientSecret('my_client_secret');
Config::setCrestClientRefreshToken('my_character_refresh_token');
//any other config you need to do...

require_once(/path/to/iveeCore/test/IveeCrestTest.php');

//for Unit Tests the class name must match the file name
class TestCrest extends IveeCrestTest
{}
```