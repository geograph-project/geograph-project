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
class RebuildCategoryStats extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS category_stat_tmp");
		
		
		$db->Execute("CREATE TABLE category_stat_tmp (
					`category_id` int(11) NOT NULL AUTO_INCREMENT,
					`imageclass` varchar(32) NOT NULL DEFAULT '',
					`c` int(11) NOT NULL DEFAULT '0',
					`gridimage_id` int(11) NOT NULL ,
					PRIMARY KEY (`category_id`),
					INDEX (c))
				ENGINE=MyISAM
				SELECT imageclass,count(*) as c,gridimage_id
				FROM gridimage_search
				GROUP BY imageclass"); //the autoincrement column doesnt need 'null' for some reason. 
		
		$db->Execute("DROP TABLE IF EXISTS category_stat");
		$db->Execute("RENAME TABLE category_stat_tmp TO category_stat");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}