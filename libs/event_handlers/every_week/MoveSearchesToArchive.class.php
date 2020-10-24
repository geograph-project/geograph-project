<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2008  Barry Hunter (geo@barryhunter.co.uk)
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
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 3288 $
*/

require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class MoveSearchesToArchive extends EventHandler
{
	function processEvent(&$event)
	{
		$db = $this->_getDB();

		//we should abort early, if the table doesn't exist. Soon might be migrating to partitioned table instead of using _archive tables
		if (!$db->getOne("SHOW TABLES LIKE 'queries_archive'"))
			return true;

		if (!empty($this->processor) && !empty($this->processor->current_event_id)) {
			$this->Execute("lock table queries write, queries_archive write, event_log write"); //this->Execute logs results in event_log!
		} else {
			$this->Execute("lock table queries write, queries_archive write");
		}

		$this->Execute("INSERT INTO queries_archive SELECT * FROM queries WHERE user_id = 0 and use_timestamp < date_sub(now(),interval 1 month)");

		if ($db->Affected_Rows() > 0)
			$this->Execute("DELETE FROM queries WHERE user_id = 0 and use_timestamp < date_sub(now(),interval 1 month)");

		$this->Execute("unlock tables");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
