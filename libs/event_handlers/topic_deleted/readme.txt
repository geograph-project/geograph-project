topic_deleted Event Handlers
------------------------

Insert class definitions in this directory to perform actions whenever a
forum topic is deleted. The parameter passed for this event is the
geobb_topics.topic_id of the topic that has been deleted

Note that this event is sent *after* the topic record has been deleted


require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class MyHandler extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		$topic_id = $event['event_param'];
		
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