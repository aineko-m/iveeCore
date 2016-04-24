# iveeCore
a PHP engine for calculations of EVE Online industrial activities and CREST library

Copyright (C)2013-2016 by Aineko Macx.
All rights reserved.


## License
Unless otherwise noted, all files in this distribution are released under the LGPL v3.
See the file LICENSE included with the distribution.


## Purpose and target audience
The goal of this project is to provide its users with a simple but powerful API to calculate industrial activities in EVE Online and get data such as bill of materials, activity costs and profits or skill requirements. iveeCore helps developers to quickly prototype their own scripts or develop full blown (web) applications without having to directly deal with the complexities and quirks of EvE's Static Data Export and CREST API.

iveeCore will likely be most useful for developers with at least basic PHP knowledge creating their own industry related or CREST powered tools.

These are a few example questions that can be answered with a few lines of code using iveeCore:
- "What is the profit of buying this item in Jita and selling in Dodixie?"
- "What do these items reprocess to? And what is it's volume sell value in Jita?"
- "How much does this EFT fit cost? How much is this scanned cargo worth?"
- "What is the job cost for building this item in this system? And if built in a POS in low sec?"
- "What is the total bill of materials and the minimum required skills to build these Jump Freighters?"
- "Is it more profitable to build the components myself or buy off the market?"
- "What is the total profit for copying this Blueprint, inventing with it, building from the resulting T2 BPC and selling the result?"
- "How do different Decryptors affect ISK/hour?"
- "How do Blueprint ME levels impact capital ship manufacturing profits?"
- "How much is a month of Unrefined Ferrofluid Reaction worth?"

The iveeCrest portion of the engine allows you to do things like:
- Access CREST endpoints for universe data (regions, constellations, systems, planets), inventory (types, groups, categories), live market data (orders and history), alliances, sovereignty, wars & killmails, alliance tournaments and industry related data.
- Read and write to the private character endpoints for fittings, contacts and navigation.


## Technical features
- An API that strives to be "good", with high power-to-weight ratio
- Strong object oriented design and class model for inventory types
- Classes for representing manufacturing, copying, T2 & T3 invention, research and reaction activities, with recursive component building
- Realistic price estimation (based on CREST market order data), profit calculation & DB persistence
- Parsers for EFT-style and EvE XML ship fittings descriptions as well as cargo and ship scanning results
- Caching support for Memcached or Redis (via PhpRedis)
- Extensible via configurable subclassing
- A well documented and mostly PSR compliant codebase
- CLI tool for automated bulk market data updates

The CREST portion of the engine (formerly the iveeCrest library) can be used in conjunction with iveeCore or independently. Some of its features include:
- Aims to be feature-complete and do things "the CREST way" as much as possible
- Support for public and authenticated CREST including write access
- Object oriented model for navigating the CREST endpoint tree, mapping CREST responses to specific classes
- Gathering and re-indexing of multipage responses
- Supports parallel GET requests with asynchronous response processing
- Multilayer cache design
- Includes a self-contained web-script to retrieve a refresh token


## Documentation
- [Release notes](https://github.com/aineko-m/iveeCore/blob/master/RELEASENOTES.md)
- [Installation and configuration](https://github.com/aineko-m/iveeCore/blob/master/doc/installAndConfig.md)
- [iveeCore user guide](https://github.com/aineko-m/iveeCore/blob/master/doc/iveeCore.md)
- [iveeCrest user guide](https://github.com/aineko-m/iveeCore/blob/master/doc/iveeCrest.md)
- [Extending iveeCore and future plans](https://github.com/aineko-m/iveeCore/blob/master/doc/extending.md)
- [About caching in iveeCore](https://github.com/aineko-m/iveeCore/blob/master/doc/caching.md)
- [Class diagram](https://github.com/aineko-m/iveeCore/raw/master/doc/iveeCore_class_diagram.pdf)


If you find bugs, have feedback or are "just" a user, please post in this thread: [EVE Online Forum: iveeCore](https://forums.eveonline.com/default.aspx?g=posts&t=292458).


## FAQ
Q: What were the beginnings of iveeCore?
A: In early 2012 I began writing my own indy application in PHP. I had been using the [Invention Calculator Plugin](http://oldforums.eveonline.com/?a=topic&threadID=1223530) for EvEHQ, but with the author going AFG and the new EvEHQ v2 having a good but not nearly flexible enough calculator for my expanding industrial needs, I decided to build my own. The application called "ivee" grew over time and well beyond the scope of it's predecessor. In the end it was rewritten from scratch two and a half times, until I was happy with the overall structure.
Eventually I decided to release the part of the code that provided general useful functionality, without revealing too much of ivee's secret sauce. So I put in some effort into separating and generalizing the code dealing with SDE DB interaction and Type classes into the engine which now is iveeCore.
Later I started iveeCrest as a separate library but ended up merging it with iveeCore in July 2015.

Q: What's the motivation for releasing iveeCore?
A: I wanted to give something back to the eve developer community. I also see it as an opportunity to dip my toes into working on a Github hosted project, even if it is a small one, and it is a motivation to strive for better code quality.

Q: Are you going to release ivee, the application that spawned iveeCore?
A: No.


## Acknowledgements
EVE Online is a registered trademark of CCP hf.
