<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
class RebuildGridimageDuplicate extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;

		$db=&$this->_getDB();

		$prefixes = $db->GetCol("select prefix from gridprefix where landcount > 0");

		foreach ($prefixes as $prefix) {
			$crit = $db->Quote("{$prefix}____");

//print "$crit ... ";
			$db->Execute("DELETE FROM gridimage_duplicate WHERE grid_reference LIKE $crit");
//print " deleted ...";
			$db->Execute("INSERT INTO gridimage_duplicate
				SELECT grid_reference,title,COUNT(*) AS images,COUNT(DISTINCT user_id) AS users FROM gridimage_search
				WHERE grid_reference LIKE $crit	GROUP BY grid_reference,title HAVING images > 1 ORDER BY NULL");
//print " inserted.\n";
			//give the server a breather...
			//sleep(10);
		}
//print "done\n";
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}


