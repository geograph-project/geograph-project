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
class RebuildPerSquare extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;
		
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS gridimage_persquare_tmp");
		
		$db->Execute("CREATE TABLE gridimage_persquare_tmp 
			SELECT gridimage_id,grid_reference,moderation_status,seq_no,user_id,x,y,point_xy FROM gridimage_search
			WHERE ftf <= 1
			ORDER BY moderation_status+0 DESC,seq_no");


		##ftf <= 1 includes ftf and supplemental :) 

		sleep(5);
		
		$db->Execute("ALTER IGNORE TABLE gridimage_persquare_tmp ADD PRIMARY KEY (x,y),ADD UNIQUE (gridimage_id),ADD SPATIAL KEY(point_xy)");
		
		sleep(5);
		
		
		$db->Execute("DROP TABLE IF EXISTS gridimage_persquare");
		
		$db->Execute("RENAME TABLE gridimage_persquare_tmp TO gridimage_persquare");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}