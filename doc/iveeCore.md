# iveeCore user manual
This document gives an overview for the use of the classes in the iveeCore namespace.


## High level overview
iveeCore maps entities from the Eve Static Data Export (SDE) to its own classes. For all inventory items the base class is iveeCore\Type, which provides basic attributes and methods. Subclasses expand on that, for e.g. as Manufacturable or Blueprint, the latter of which can further be specialized into InventorBlueprint or InventableBlueprint.

Then there are the classes representing Eve universe entities like SolarSystem, Station and AssemblyLines, which have an impact on industrial activities. These can be grouped in the IndustryModifier object, which is provided to industry activity methods as context.

The output of calling an industry activity method is usually a ProcessData object, which groups all the information about the activity, material input and output, time and skill requirements, used assembly lines and even more ProcessData objects representing sub-activities (like building components before the actual ship is built).

Finally there are classes dealing with the DB interaction, caching, as well as processing and persisting the data pulled from CREST via iveeCrest.

The examples below show only a small portion of the methods of the classes. Once you've gotten a basic idea of the use of the classes, it is recommended to look a the [class diagram](https://github.com/aineko-m/iveeCore/raw/master/doc/iveeCore_class_diagram.pdf) and read some source code for method signatures and the explanations in the docblocks.


## Type
The iveeCore\Type class will be one of the most frequently used. It provides the method for instancing inventory item objects.

One of the simplest scripts would look like this:

```php
<?php
//initialize iveeCore. Adapt path as required.
require_once('/path/to/iveeCore/iveeCoreInit.php');

use iveeCore\Type;

//Load and show the object for 'Tritanium'
$type = Type::getById(34);
print_r($type);
```
Types can also be instantiated by their exact name:
```php
$type = Type::getByName('Tritanium');
```
Some types can be reprocessed. Let's refine some Veldspar. For this activity, we'll need an IndustryModifier object for giving context about the refinery efficiency and character skills involved. We'll talk more about this later.
```php
//Get the object for a solar system
$system = SolarSystem::getByName('Osmon');
//Get an IndustryModifier object for all NPC stations in this system
$industryContext = $system->getIndustryModifierForAllNpcStations();
//Get the type to be reprocessed
$type = Type::getByName('Veldspar');

//Reprocess a batch of 100 units passing the context object.
//Since the context object references multiple NPC stations, the one with the best overall efficiency is
//chosen, also considering character standings.
$reprocessedMaterials = $type->getReprocessingMaterialMap($industryContext, 100);
//The output data type is MaterialMap, which has several interesting features we'll look at later
print_r($reprocessedMaterials);
```


## Market data
You can get current market prices for types on the market, as well as their history. Since Eve markets are segmented by region, you'll need to specify a region ID. You can also look it up via a system name:

```php
$type = Type::getByName('Damage Control I');

//Get a regionId
$regionId = SolarSystem::getByName('Jita')->getRegionId();

//This data is fetched from the DB or from CREST, if the last update was too long ago
print_r($type->getMarketPrices($regionId));
print_r($type->getMarketHistory($regionId));
```


## Blueprints
The blueprint classes offer methods for performing, or rather calculating, an industrial activity. Let's see how a simple manufacturing process is done:

```php
//We could get the the Blueprint directly via name or ID, but lets get it via its product
$ship = Type::getByName('Dominix');
//Note how this item is of type Manufacturable
print_r($ship);
//This means we can easily get the originating Blueprint
$blueprint = $ship->getBlueprint();

//For manufacturing, we'll need an IndustryModifier again, this time directly via system ID
$context = IndustryModifier::getBySystemIdForAllNpcStations(30000180);

//Now let's manufacture 1 unit, setting perfect blueprint ME and TE values
$processData = $blueprint->manufacture($context, 1, -10, -20);

//Show the ManufactureProcessData object
print_r($processData);

//You can get the required input materials and skills
print_r($processData->getMaterialMap());
print_r($processData->getSkillMap());

//Get a context object for pricing at the Jita 4-4 CNAP Station.
$marketContext = IndustryModifier::getByStationId(60003760);

//This outputs the final profit, considering buying input materials and selling the final product at the
//same station with orders. If the value is negative, we're producing at a loss.
print_r($processData->getTotalProfit($marketContext, $marketContext));
```
For invention the process is similar, just with other parameters. You can call the methods from either an InventorBlueprint or InventableBlueprint.

```php
//Let's start at the InventableBlueprint
$blueprint = Type::getByName('Oneiros Blueprint');

//Get the IndustryModifier for NPC stations via system ID
$context = IndustryModifier::getBySystemIdForAllNpcStations(30000180);

//You can use a decryptor
$decryptor = Type::getByName('Symmetry Decryptor');

//Now let's invent, using the context and the decryptor
$processData = $blueprint->invent($context, $decryptor->getId());

//Show the ManufactureProcessData object
print_r($processData);

//The output of invention is a T2 BPC, which can't be price checked, so we're not looking at the profits. We
//could build from it, but for the sake of showing functionality, let's repeat the invention with another
//convenience method that makes the required initial T1 BP copy, runs the invention (considering invention
//chances), and manufactures from the resulting T2 BPC, without building the required components recursively.
$copyInventManufacture = $blueprint->copyInventManufacture($context, $decryptor->getId(), 0);

//Note how there are multiple ProcesData objects are chained, manufacturing as the top (last) activity, then
//invention and at the bottom (first) the copy process.
print_r($copyInventManufacture);

//Get a context object for pricing at the Jita 4-4 CNAP Station.
$marketContext = IndustryModifier::getByStationId(60003760);

//This outputs the final profit, considering buying the input materials and selling the final product at the
//same station with orders. If the value is negative, we're producing at a loss.
print_r($copyInventManufacture->getTotalProfit($marketContext, $marketContext));

//iveeCore is able to calculate recursive component building, which you'd do with the following arguments
$copyInventManufacture = $blueprint->copyInventManufacture($context, $decryptor->getId(), 1);

//iveeCore can even recurse into reactions, here seen for two levels (the current maximum)
$copyInventManufacture = $blueprint->copyInventManufacture($context, $decryptor->getId(), 1, 2);

//The complexity of the resulting manufacturing tree is high, as the reactions are performed for every set
//of components
print_r($copyInventManufacture);

//It is more practical to get the total amount of required input materials for this scenario (starting from
//raw moon materials), going up to the finished T2 ship. Note that amounts can be fractions due to invention
//chances or partial reaction batches.
print_r($copyInventManufacture->getTotalMaterialMap());
```
The other industrial activities (research, copying, reverse engineering) behave quite similarly.


### Reactions
Reactions follow the same pattern. A peculiar case is the alchemy reaction, where the output is reprocessed and part of that is fed back into the reaction, which iveeCore also handles.

```php
//A normal reaction
$reaction = Type::getByName('Nanotransistors Reaction');

//Let's get the IndustryModifier for a low-sec POS
$context = SolarSystem::getByName('Taisy')->getIndustryModifierForPos(0);

//Run a month worth of the reaction
$reactionData = $reaction->react($context, 24 * 30);
print_r($reactionData);

//Get a context object for pricing at the Jita 4-4 CNAP Station
$marketContext = IndustryModifier::getByStationId(60003760);

//Get the profit for this reaction process considering buying and selling the materials in Jita
print_r($reactionData->getTotalProfit($marketContext, $marketContext));

//And now to an alchemy reaction. We pass the arguments for reprocessing and feedback.
$alchemy = Type::getByName('Unrefined Hyperflurite Reaction');
$alchemyData = $alchemy->react($context, 24 * 30, true, true);

print_r($alchemyData);
print_r($alchemyData->getTotalProfit($marketContext, $marketContext));
```


## IndustryModifier
IndustryModifier objects are used to group objects and factors that modify the cost, time and material requirements of performing industrial activities (manufacturing, TE research, ME research, copying, reverse engineering, invention and reactions), or market activities. Namely, these are solar systems (industry indices), assembly lines (of stations or POSes), station industry taxes. The contained CharacterModifier allows for lookup of skills, time and implant factors, standings, taxes.

A number of convenience functions are provided that help in instantiating IndustryModifier objects for a specific NPC station, a POS in a system, all NPC stations in a system or a system plus manual assembly line type definition (necessary for wormholes or hypothetical scenarios).

IndustryModifier objects are passed as argument to methods calculating some industrial activity. They can be reused.

For a given industry activityId and Type object, IndustryModifier objects can calculate the cost, material and time factors, considering all of the modifiers. The AssemblyLine objects carried by an IndustryModifier allows it to determine if a certain industrial activity is at all possible. For instance, trying to build a supercapital ship in a hi-sec POS is not possible.

The default CharacterModifier and BlueprintModifier objects held by an IndustryModifier are stub implementations. Out of the box, CharacterModifier represents a character with perfect skills, standings of 7.5 towards all corporations, standings of 2.5 towards all factions and a reprocessing efficiency implant. Likewise, BlueprintModifier will return perfect research levels for all blueprints. These two classes are likely some of the first any developer using iveeCore will want to customize to adapt them to their own industrial setup.


## ProcessData objects
iveeCore uses ProcessData objects to represent calculated industrial activities. They aggregate all the data relevant to them, like input and output materials, required skills, the time and slot cost involved and at which AssemblyLine (the industry "slot" of a station or POS) in which solar system (relevant due to industry indices) the activity is performed. ProcessData objects can have sub-ProcessData objects representing activities that need to happen before, like the building of components. Multiple ProcessData objects can also be added to one top level ProcessData object to provide something like a "shopping cart" functionality.

Note that many of the methods for getting data from a ProcessData object come in a regular and a "total" variant, for instance getMaterialMap() and getTotalMaterialMap(). The first will return only the materials used by the current ProcessData object, not considering any requirements potential sub-ProcessData objects might have. The latter method will return a MaterialMap object represent the summed material requirements of all ProcessData objects.

InventionProcessData has one additional wrinkle: Since invention only succeeds with a certain probability, methods come in a regular and a "attempt" variant, for instance getProcessCost() and getAttemptProcessCost(). The first will return the average slot cost for a successful invention, while the second returns the cost of a single invention attempt.


## Notes
- The price estimates used for profit calculations are calculated by the iveeCore\CREST\PriceEstimator class based on market order and history data fetched from CREST, and assumes you are buying your materials and selling your products using competitively priced orders. But everyone has a different idea what a realistic price estimate is, so the algorithm can be customized by writing an extension to the PriceEstimator class.
- The prices of items that can't be sold on the market also can't be determined. This includes BPCs. However, the _cost_ of copying, inventing or researching a BPC can and is calculated for processes.
- Calculated material amounts might be fractions, which is due invention chance or (hypothetical) production or reaction batches in non-multiples of their portion size. These should be treated as the average required or consumed when doing multiple production batches.
- When automatically picking AssemblyLines for use in industry activities, iveeCore will choose first based on ME bonuses, then TE bonuses and slot cost savings last.
