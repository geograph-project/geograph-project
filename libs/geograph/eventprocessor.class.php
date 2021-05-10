<?php
/**
 * $Project: GeoGraph $
 * $Id: eventprocessor.class.php 8672 2017-12-10 14:28:42Z barry $
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
* @version $Revision: 8672 $
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
	var $filter="";

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

		if (!empty($GLOBALS['DSN2'])) {
			$this->logdb=NewADOConnection($GLOBALS['DSN2']);

		} elseif (!empty($CONF['db_tempdb'])) {
			$this->logdb=NewADOConnection(str_replace('/'.$CONF['db_db'],'/'.$CONF['db_tempdb'],$GLOBALS['DSN']));

		} else {
			$this->logdb=NewADOConnection($GLOBALS['DSN']);
		}
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
	* optiaonl filte
	* @public
	*/
	function setFilter($filter)
	{
		$this->filter=$filter;
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
			if (!empty($_SERVER['REMOTE_ADDR'])) {
				echo $this->fmt[$level][0].$text.$this->fmt[$level][1]."\n<br>";
				flush();
			} else {
				echo "$text\n";
			}
		}

		if (!empty($this->current_event_id))
		{
			//add to db
			$this->logdb->Execute("insert into event_log(event_id, logtime, verbosity, log, pid) values ".
			"({$this->current_event_id}, now(), $level, ".$this->logdb->Quote($text).", ".getmypid().")");
		}
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
							@list($classname, $ext1, $ext2)=explode(".", $entry,3);

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
		if (rand(1,100) < 2)
		{
			//clear events and event log entries older than one month
			if (rand(1,100) < 3)
			{
				$this->db->Execute("delete from event where status='completed' and processed < date_sub(now(),interval 30 day)");
				$this->logdb->Execute("delete from event_log where logtime < date_sub(now(),interval 30 day)");
			}

			//clear all verbose entries not associated with a event once they are 8 hours old - they are really just for debugging
			$this->logdb->Execute("delete from event_log where event_id=0 and verbosity in('trace', 'verbose') and logtime < date_sub(now(),interval 24 hour)");
		}
	}

	/**
	* process events until timeout
	* @public
	*/
	function start()
	{
		global $CONF;

		$lockkey = $CONF['db_db'].'.'.get_class($this).md5($this->filter); //note, we DON'T use testmode in the key

                if (!$this->db->getOne("SELECT GET_LOCK('$lockkey',10)")) {
                        //only execute if can get a lock
                        $this->warning("Failed to get Lock");
                        return false;
                }

		$this->_buildHandlerTable();

		if ($this->testmode)
		{
			$this->warning("testmode active");
		}
		else
		{
			$this->_gc();
		}

		$attempted="";
		$sep="";

		$endtime=time()+$this->max_execution;
		while (time() < $endtime)
		{
			$this->current_event_id=0;

			if (!empty($_SERVER['BASE_DIR']) && file_exists($_SERVER['BASE_DIR'].'/shutdown-sential'))
				break;

			//are we over our load average?
			$load=get_loadavg();
			if ($load > $this->max_load)
			{
				$this->trace("load average of $load exceeds {$this->max_load} sleeping for a bit...");
				sleep(15);
				continue;
			}

			$where="";
			if (strlen($attempted))
				$where.=" and event_id not in($attempted)";

			if (!empty($this->filter)) {
				if (preg_match('/-(\w+)/',$this->filter,$m)) {
					$where.=" and event_name NOT LIKE ".$this->db->Quote($m[1]."%");
				} else {
					$where.=" and event_name LIKE ".$this->db->Quote($this->filter."%");
				}
			}

			$this->db->Execute("LOCK TABLES event WRITE");

			$event=$this->db->GetRow("select * from event
				where (
						status = 'pending'
						or (status = 'in_progress' and updated < date_sub(now(),interval 6 hour))
				      ) $where
				order by priority, posted limit 1");

			if ($event)
			{
				$event_id=$event['event_id'];

				//lets mark the event as in progress
				// ... need to set updated explicitly, because if already in progress, the updated column wont be changed
				$this->db->Execute("update event set status='in_progress',updated=NOW() where event_id=$event_id");

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
						if ($event_name == 'every_day')
						{
							$this->trace("Sleeping for 5 minutes...");
							//temporally bodge, to space them out, so can align them with IO graphs!
							sleep(60*5);
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
				$this->trace("");
			}
			else
			{
				$this->db->Execute("UNLOCK TABLES");

				//no events to process! let's sleep for a bit
				$this->verbose("no events in queue, sleeping for a bit...");
				sleep(11);
			}
		}

		//ok, we're going to quit, but lets post some useful diagnostics
		$count=$this->db->GetOne("select count(*) as cnt from event where status<3");
		$this->trace("Processing complete - $count events remaining in queue");
		$this->db->Execute("DO RELEASE_LOCK('$lockkey')");
	}
}

