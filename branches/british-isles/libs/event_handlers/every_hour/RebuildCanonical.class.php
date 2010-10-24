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
class RebuildCanonical extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS category_canonical_tmp");
		
		$db->Execute("CREATE TABLE category_canonical_tmp 
			SELECT category_map_id,imageclass,canonical,COUNT(DISTINCT user_id) AS users 
			FROM category_map 
			GROUP BY imageclass,canonical
			HAVING users > 2 
			ORDER BY NULL");
		
		$db->Execute("ALTER TABLE category_canonical_tmp ADD INDEX(imageclass), ADD INDEX(canonical)");
		
		$db->Execute("INSERT INTO category_canonical_tmp SELECT 0 AS category_map_id,canonical AS imageclass,canonical,0 AS users FROM category_canonical_tmp cc INNER JOIN category_stat cs ON (canonical=cs.imageclass) WHERE cc.imageclass!=canonical GROUP BY canonical");
		
		$db->Execute("DROP TABLE IF EXISTS category_canonical");
		$db->Execute("RENAME TABLE category_canonical_tmp TO category_canonical");

		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
