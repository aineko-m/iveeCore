## Extending iveeCore
iveeCore is designed with extensibility in mind. Throughout the code, class names are looked up dynamically on object instantiation in iveeCore\Config. This allows for easy customization and extending of classes by users, without changing the iveeCore source code itself. New subclasses of existing ivee classes can be written, and with a change in the configuration these subclasses get used instead.

If you are using the edited iveeCore\Config.php file, adapt the static array $classes, which maps from class nicknames to the fully qualified names. If you are setting your Config parameters programmatically at runtime, simply use the iveeCore\Config::setIveeClassName($classNickname, $fullClassName) method to register your class under the same nickname.

If you extend the engine with features that are generally useful and compatible with the goals and structuring of the project, I'll be happy to review Github pull requests for inclusion in the engine. If you modify iveeCrest source code itself, you'll need to comply with the LGPL and release your modifications under the same license.


## A note about caching
Remember to restart or flush the cache after making changes to data classes or changing the DB. For memcache, you can do so with this command: ```echo 'flush_all' | nc localhost 11211```. For Redis, enter the interactive Redis client using ```redis-cli``` and issue ```FLUSHALL```. Alternatively you can run one of the PHPUnit tests, which also clears the cache.


## Code style
The codebase is checked with [PHP Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer) for compliance with the [PSR-2](http://www.php-fig.org/psr/psr-2/) standard. With PHPCS installed, this is done via the following command:
```
phpcs --standard=PSR2 /path/to/project
```
In addition to PSR-2, the codebase uses mandatory docblocs for files, classes, attributes and methods, always specifying the fully qualified class name for types.

Finally, static codeanalysis is performed via [Scrutinizer](https://scrutinizer-ci.com/g/aineko-m/iveeCore/). It should be noted that it does flag issues and suggest fixes that don't necessarily apply, but it does help in catching bugs or mistakes.


## Future Plans
- iveeCrest now supports most of the endpoints of CREST, but a lot more features and convenience functions can be implemented for the responses classes. At the same time, CREST is an evolving API, which will continue to produce changes and additions to the engine.
- A class for simple CREST based "SSO" login
- When manufacturing in citadels becomes available, some adaptations will be required for iveeCore to support them. At the moment it is unclear how the AssemblyLine data will be provided by CCP.
- Implementing global rate limiting for iveeCrest, so parallel application runs don't hit CCPs rate limit.
- Improving error handling and resilience of iveeCrest.
- A simplex algorithm based ore compression calculator is in the works, which will allow you to define the target amounts of minerals and the calculator will give you the quantities of (compressed) ores to buy to get these numbers while minimizing price.
- Possibly removing the stored procedures, if the same functionality can be achieved without adding more DB call round-trips.
- Possibly moving to PDO based DB connectivity, although currently there's no pressing need for that.

I'll try to keep improving iveeCores features, structuring, API and test coverage. iveeCore is under active development so I can't promise the API will be stable. I'm open to suggestions and will also consider patches for inclusion. If you find bugs, feedback or are "just" a user, please post in this thread: [EVE Online Forum: iveeCore](https://forums.eveonline.com/default.aspx?g=posts&t=478538).