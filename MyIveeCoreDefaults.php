<?php

/**
 * This file is intended for customizing the defaults provided by IveeCoreDefaults.
 * Overwrite attributes and methods as required by your application or eve industrial setup.
 * This could even be extended to make the values completely dynamic, if the application demands it.
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license public domain
 * @link https://github.com/aineko-m/iveeCore/blob/master/MyIveeCoreDefaults.php
 * @package iveeCore
 */
class MyIveeCoreDefaults extends IveeCoreDefaults {
    
    /**
     * @var MyIveeCoreDefaults $instance might seem redundant, however it is needed as a workaround to make inherited
     * singleton instantiation work properly.
     */
    protected static $instance = null;
    
    //this defines the regions for which market data should by gathered by the EMDR client
    protected $TRACKED_MARKET_REGION_IDS = array(
        10000002 //The Forge
    );
}

?>
