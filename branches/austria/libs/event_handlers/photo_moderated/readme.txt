photo_moderated Event Handlers
------------------------

Insert class definitions in this directory to perform actions whenever a
photo has been moderated. The parameter passed for this event is the
gridimage_id of the image being moderated.

Note that it's possible for an image to be remoderated - this event is
sent whenever the moderation status changes.



require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class MyHandler extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		list($gridimage_id,$updatemaps) = explode(',',$event['event_param']);
		
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