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
class RebuildRecentSquares extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS recent_only_tmp");
		
		
		$db->Execute("CREATE TABLE recent_only_tmp (
				  `gridsquare_id` int(11) NOT NULL default '0',
				  `images` smallint NOT NULL default '0',
				  PRIMARY KEY  (`gridsquare_id`)
				) 
				SELECT gridsquare_id,COUNT(*) AS images 
				FROM gridimage gi 
				WHERE imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR)) 
				AND moderation_status IN ('geograph','accepted')
				GROUP BY gi.gridsquare_id
				ORDER BY NULL"); 
		
		$db->Execute("DROP TABLE IF EXISTS recent_only");
		$db->Execute("RENAME TABLE recent_only_tmp TO recent_only");
		
		$db->Execute("UPDATE gridsquare SET has_recent = 0");
		$db->Execute("UPDATE gridsquare INNER JOIN recent_only USING (gridsquare_id) SET gridsquare.has_recent = 1");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}