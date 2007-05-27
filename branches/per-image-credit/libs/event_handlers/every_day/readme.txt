every_day Event Handlers
------------------------

Insert class definitions in this directory to perform actions every day,
for example:



require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class MyHandler extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		//perform any necessary logging e.g.
		//$this->processor->error("message");
		//$this->processor->warning("message");
		//$this->processor->trace("message");
		//$this->processor->verbose("message");
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}