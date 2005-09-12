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
* Provides the Event class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/



//event names - it's not necessary to use a constant when firing an event
//but it helps to document the known events here
define('EVENT_NEWPHOTO', 'photo_submitted');
define('EVENT_MODERATEDPHOTO', 'photo_moderated');
define('EVENT_MOVEDPHOTO', 'photo_moved');

//event priorities
define('PRIORITY_HIGH', 0);
define('PRIORITY_NORMAL', 50);
define('PRIORITY_LOW', 100);

/**
* Event class
*
* Provides a class for firing events which are picked up by
* the asynchronous event handler
* 
* An example event firing would be like this
* Event::fire(EVENT_NEWPHOTO, $gridimage_id);
* @package Geograph
*/

class Event
{
	/**
	* Static method for firing an event
	*/
	function fire($event_name, $event_param="", $priority=50)
	{
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
		
		//is a similar event pending? if so, increase its counter
		$sql=sprintf("select event_id from event where status in ('pending', 'in_progress') and ".
			"event_name='%s' and event_param='%s'",
			mysql_escape_string($event_name),mysql_escape_string($event_param));
			
		$event_id=$db->GetOne($sql);
		if ($event_id===false)
		{
			//add new event
			$priority=intval($priority);
			$sql=sprintf("insert into event(event_name, event_param,posted,priority) ".
				"values('%s', '%s', now(), '%d')",
				mysql_escape_string($event_name),
				mysql_escape_string($event_param), 
				$priority);
				$db->Execute($sql);
		}
		else
		{
			//increment counter of event
			$db->Execute("update event set instances=instances+1 where event_id=$event_id");
		}
	}
}


?>