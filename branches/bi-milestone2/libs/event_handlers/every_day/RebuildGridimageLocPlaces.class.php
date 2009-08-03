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
class RebuildGridimageLocPlaces extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS gridimage_loc_placenames_tmp");
		
		$db->Execute("CREATE TABLE gridimage_loc_placenames_tmp
				(INDEX (reference_index,country,adm1))
				ENGINE=MyISAM
				SELECT placename_id,loc_placenames.country,loc_placenames.adm1,loc_placenames.reference_index,
				full_name,count(*) as c,gridimage_id
				FROM gridimage INNER JOIN loc_placenames ON(placename_id = loc_placenames.id)
				WHERE moderation_status <> 'rejected' AND placename_id < 1000000
				GROUP BY placename_id,loc_placenames.reference_index,loc_placenames.country,loc_placenames.adm1;");
		
		$db->Execute("DROP TABLE IF EXISTS gridimage_loc_placenames");
		$db->Execute("RENAME TABLE gridimage_loc_placenames_tmp TO gridimage_loc_placenames");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}