<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
* Provides the EventProcessor class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

require_once("geograph/eventhandler.class.php");

/**
* Event processor class
*
* Provides a class for asynchronous processing of system events
*
* @package Geograph
*/
class EventProcessor
{
	var $testmode=true;
	var $verbosity=4;
	var $max_execution=60;
	var $max_load=0.8;
	var $db=null;
	var $logdb=null;
	var $handlers=array();
	var $event_handler_dir="";
	
	//formatting for each level of trace output
	var $fmt=array(
		1=>array("<font color='#ff0000'><b>", "</b></font>"),
		2=>array("<b>", "</b>"),
		3=>array("", ""),
		4=>array("<font color='#888888'>", "</font>")
		);

	//currently processed event id

	var $current_event_id=0;
	
	/**
	* Constructor
	* @public
	*/
	function EventProcessor()
	{
		$this->event_handler_dir=realpath($_SERVER["DOCUMENT_ROOT"]."/../libs/event_handlers");
		
		$this->db=NewADOConnection($GLOBALS['DSN']);
		$this->logdb=NewADOConnection($GLOBALS['DSN']);
	
	}
	
	/**
	* set up test mode
	* @public
	*/
	function setTestMode($testmode)
	{
		$this->testmode=$testmode?1:0;
	}

	/**
	* set up verbosity
	* 1=errors 2=warnings 3=trace 4=verbose
	* @public
	*/
	function setVerbosity($verbosity)
	{
		$this->verbosity=intval($verbosity);
	}
	

	/**
	* set up maximum execution time in sections
	* @public
	*/
	function setMaxTime($seconds)
	{
		$this->max_execution=intval($seconds);
	}
	

	/**
	* set up maximum load average
	* @public
	*/
	function setMaxLoad($loadavg)
	{
		$this->max_load=$loadavg;
	}
	
	/**
	* output a status message
	* @private
	*/
	function _output($level, $text)
	{
		if ($level<=$this->verbosity)
		{
			echo $this->fmt[$level][0].$text.$this->fmt[$level][1]."\n<br>";
			flush();
		}
		
		//add to db
		$this->logdb->Execute("insert into event_log(event_id, logtime,verbosity, log) values ".
			"({$this->current_event_id}, now(), $level, '".mysql_escape_string($text)."')");
		
		
	}
	
	/**
	* output an error message
	* @public
	*/
	function error($text) { $this->_output(1, $text); }
	
	/**
	* output a warning message
	* @public
	*/
	function warning($text) { $this->_output(2, $text); }
	
	/**
	* output a trace message
	* @public
	*/
	function trace($text) { $this->_output(3, $text); }
	
	/**
	* output a verbose message
	* @public
	*/
	function verbose($text) { $this->_output(4, $text); }
	
	/**
	* see if we're running on Windows
	* @public
	*/
	function isWindowsServer()
	{
		$server=$_SERVER["SERVER_SOFTWARE"];
		return (strstr($server, "Win32")===false)?false:true;
	}
	
	/**
	* get load average
	* @public
	*/
	function getLoadAverage()
	{
		//we don't do this on windows
		if ($this->isWindowsServer())
			return 0;
			
		//get the uptime
		$uptime = `uptime`; 
		
		//get the one minute load average
		$bits=explode(",", $uptime);
		list($title, $onemin)=explode(":", $bits[3]);
		return $onemin;
	}

	/**
	* build lookup array of available handlers
	* @private
	*/
	function _buildHandlerTable()
	{
		//build an array of available event handlers
		$this->handlers=array();
		$d1=dir($this->event_handler_dir);
		
		while (false !== ($entry = $d1->read())) 
		{
			if ($entry[0]!=".")
			{
				$event_name=$entry;
				$handlers[$event_name]=array();
				//now find event handlers in this dir
				if (is_dir($this->event_handler_dir."/$entry"))
				{
					$d2=dir($this->event_handler_dir."/$entry");
					while (false !== ($entry = $d2->read())) 
					{
						if ($entry[0]!=".")
						{
	
							list($classname, $ext1, $ext2)=explode(".", $entry,3);
	
							if ($ext1=="class" && $ext2=="php")
							{
								$this->handlers[$event_name][]=$entry;
							}
						}
					}
					$d2->close();
				}
			}
		}
		$d1->close(); 
	}
	/**
	* garbage collection
	* @private
	*/
	function _gc()
	{
		if (rand(1,100) < 3) 
		{
			//clear events and event log entries older than one month
			if (rand(1,100) < 3)
			{
				$this->db->Execute("delete from event where status='completed' and processed < date_sub(now(),interval 30 day)");
				$this->db->Execute("delete from event_log where logtime < date_sub(now(),interval 30 day)");
			}

			//clear all verbose entries not associated with a event once they are 8 hours old - they are really just for debugging
			$this->db->Execute("delete from event_log where event_id=0 and verbosity in('trace', 'verbose') and logtime < date_sub(now(),interval 8 hour)");
		}
	}
	

	/**
	* process events until timeout 
	* @public
	*/
	function start()
	{
		$check = $this->db->GetRow("select unix_timestamp(now())-unix_timestamp(logtime) as seconds,log like 'Processing complete%' as success from event_log where event_log_id = (select max(event_log_id) from event_log)");
	
		if ($check['seconds'] && $check['seconds'] < ($this->max_execution/2) && !$check['success']) 
		{
			$this->warning("A processor seems still active - dying (last log entry {$check['seconds']} ago)");
			return false;
		}
	
		$this->_buildHandlerTable();
		$this->_gc();
		
		if ($this->testmode)
		{
			$this->warning("testmode active");
		}

		$attempted="";
		$sep="";
		
		$endtime=time()+$this->max_execution;
		while (time() < $endtime)
		{
			$this->current_event_id=0;
		
			//are we over our load average?
			$load=$this->getLoadAverage();
			if ($load > $this->max_load)
			{
				$this->trace("load average of $load exceeds {$this->max_load} sleeping for a bit...");
				sleep(15);
				continue;
			}
			
			$attemptfilter="";
			if (strlen($attempted))
				$attemptfilter=" and event_id not in($attempted)";
			
			$this->db->Execute("LOCK TABLES event WRITE");
			
			$event=$this->db->GetRow("select * from event 
				where (
						status = 'pending'
						or (status = 'in_progress' and updated < date_sub(now(),interval 1 hour))
				      ) $attemptfilter 
				order by priority, posted limit 1");
			
			if ($event)
			{
				$event_id=$event['event_id'];
				
				//lets mark the event as in progress
				$this->db->Execute("update event set status='in_progress' where event_id=$event_id");
				
				$this->db->Execute("UNLOCK TABLES");
				
				$event_name=$event['event_name'];
				$event_param=$event['event_param'];
				$priority=$event['priority'];
				$posted=$event['posted'];

				$attempted.=$sep.$event_id;
				$sep=",";

				$this->current_event_id=$event_id;
				$this->verbose("Processing event $event_id : $event_name($event_param) : posted $posted : priority $priority load:$load");
				
				//ok, we have an unprocessed event, lets build an array of handlers that
				//have had a pop at it...
				$processed=$this->db->GetAssoc("select class_name, 1 as flag from event_handled_by where event_id=$event_id");
				
				//locate interesting classes

				//assume success and prove otherwise - note that an event with
				//no handlers will be successfully processed
				$success=true; 

				if (is_array($this->handlers[$event_name]))
				{
					foreach($this->handlers[$event_name] as $idx=>$filename)
					{
						//filename should be of the form classname.class.php
						list($classname, $ext1, $ext2)=explode(".", $filename);

						//ensure we haven't already processed the event with this class
						if (isset($processed[$classname]))
						{
							$this->verbose("Skipping repeat execution of $classname for event $event_id");
							continue;
						}

						//we need this class, so include the file...
						$include=$this->event_handler_dir."/".$event_name."/".$filename;
						$this->verbose("Including $include");
						require_once($include);

						if (!class_exists($classname))
						{
							$this->error("Included $filename, but no class $classname found");
							$success=false; //ensure this event is not marked as complete
							continue;
						}

						//we're ready to rock! instantiate the class and handle the event
						$handler=new $classname;
						$handler->_setDB($this->db);
						$handler->_setProcessor($this);
						if ($handler->processEvent($event))
						{
							//update the handler log to ensure we don't get a repeat
							if (!$this->testmode)
							{		
								$this->trace("Event handler $classname succeeded for event $event_id");
								$this->db->Execute("insert into event_handled_by (event_id, class_name) values($event_id, '$classname')");
							}
							else
							{
								$this->warning("Event $event_id processed by $classname but not marked as we are in test mode");
							}
						}
						else
						{
							$this->error("Event handler $classname failed for event $event_id");
							$success=false;
						}
					}
				}

				//all relevant handlers called, can we mark the event as complete?
				if ($success)
				{
					if ($this->testmode)
					{
						$this->warning("Test mode active - processed events will remain in queue");		
					}
					else
					{
						$this->trace("Event $event_id successfully processed");
						$this->db->Execute("update event set status='completed',processed=now() where event_id=$event_id");
						$this->db->Execute("delete from event_handled_by where event_id=$event_id");
					}	
					
				}
				else
				{
					$this->warning("Due to processing errors, event $event_id remains in queue");
				}
			} 
			else
			{
				$this->db->Execute("UNLOCK TABLES");
				
				//no events to process! let's sleep for a bit
				$this->verbose("no events in queue, sleeping for a bit...");
				sleep(30);
			}
			
		}
		
		//ok, we're going to quit, but lets post some useful diagnostics
		$count=$this->db->GetOne("select count(*) as cnt from event where status>=0");
		$this->trace("Processing complete - $count events remaining in queue");
		
	}
}

?>
