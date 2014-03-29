<?php

/**
 * This file holds the custom exceptions for iveeCore.
 * As of now they are all classes with empty body, their purpose being to allow the application calling iveeCore to 
 * catch and handle exception types differently.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license public domain
 * @link https://github.com/aineko-m/iveeCore/blob/master/IveeCoreExceptions.php
 * @package iveeCore
 */

/**
 * Super class for all iveeCore Exceptions
 */
class IveeCoreException extends Exception{}

/**
 * Thrown when a given typeID is not found
 */
class TypeIdNotFoundException extends IveeCoreException{}

/**
 * Thrown when a given typeName is not found
 */
class TypeNameNotFoundException extends IveeCoreException{}

/**
 * Thrown when a memcached function is called but it is not enabled
 */
class MemcachedDisabledException extends IveeCoreException{}

/**
 * Thrown when a given key is not found in memcached
 */
class KeyNotFoundInMemcachedException extends IveeCoreException{}

/**
 * Thrown when trying to call reprocessing functions on non-reprocessable items
 */
class NotReprocessableException extends IveeCoreException{}

/**
 * Thrown when an invalid parameter is passed to a method
 */
class InvalidParameterValueException extends IveeCoreException{}

/**
 * Thrown when market data related methods are called on items not on market
 */
class NotOnMarketException extends IveeCoreException{}

/**
 * Thrown when market data related methods are called but no price data is available
 */
class NoPriceDataAvailableException extends IveeCoreException{}

/**
 * Thrown when market data related methods are called but the maximum market data age has been exceeded
 */
class PriceDataTooOldException extends IveeCoreException{}

/**
 * Thrown when manufacture() is called on blueprints with no manufacture requirements defined
 */
class NoManufacturingRequirementsException extends IveeCoreException{}

/**
 * Thrown when a specified activity ID can't be found
 */
class ActivityIdNotFoundException extends IveeCoreException{}

/**
 * Thrown when unexpected data is received
 */
class UnexpectedDataException extends IveeCoreException{}

/**
 * Thrown when an invalid decryptor group is specified
 */
class InvalidDecryptorGroupException extends IveeCoreException{}

/**
 * Thrown when the id of a non-inventable item is passed
 */
class NotInventableException extends IveeCoreException{}

/**
 * Thrown when the given type is of the wrong type
 */
class WrongTypeException extends IveeCoreException{}

/**
 * Thrown when methods for activity output items are called, but none are produced
 */
class NoOutputItemException extends IveeCoreException{}

/**
 * Thrown when no relevant data was given to be processed
 */
class NoRelevantDataException extends IveeCoreException{}

?>