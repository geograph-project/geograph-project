<?php

/**
* Provides the DemoHandler2 class, showing how multiple classes can handle the same event
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Include event handler base class
*/
require_once("geograph/eventhandler.class.php");

/**
* DemoHandler2 class
*
* Provides a demonstration of event handling for the "demo" event
* @package Geograph
*/
class DemoHandler2 extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		$param=$event['event_param'];
		
		//perform some simply trace logging
		$this->processor->trace("Inside DemoHandler2 param=$param");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return ($param=="fail2")?false:true;
	}
	
}

?>