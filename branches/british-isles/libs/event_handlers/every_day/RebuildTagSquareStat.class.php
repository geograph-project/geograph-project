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
class RebuildTagSquareStat extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();


		$sql = "SELECT tag_id, gi.user_id, grid_reference, COUNT(*) AS images
			FROM gridimage_tag
			INNER JOIN gridimage_search gi USING (gridimage_id)
			WHERE \$where AND status = 2
			GROUP BY tag_id, gi.user_id, grid_reference
			ORDER BY NULL";


		if (!$db->getOne("show tables like 'tag_square_stat'")) {
			$prefix = $db->GetOne("select prefix from gridprefix where landcount > 0 order by landcount");
                        $where = "grid_reference LIKE ".$db->Quote("{$prefix}____");

//print "000$prefix ";

			$db->Execute("CREATE TABLE tag_square_stat (index (`tag_id`)) ".str_replace('$where',$where,$sql)) or die(mysql_error());
		}


		$db->Execute("DROP TABLE IF EXISTS tag_square_stat_tmp");

		$db->Execute("CREATE TABLE tag_square_stat_tmp LIKE tag_square_stat");
		$db->Execute("ALTER TABLE tag_square_stat_tmp DISABLE KEYS");

               	$prefixes = $db->GetCol("select prefix from gridprefix where landcount > 0");
       	        foreach ($prefixes as $prefix) {
//print "$prefix ";
                        $where = "grid_reference LIKE ".$db->Quote("{$prefix}____");
			$db->Execute("INSERT INTO tag_square_stat_tmp ".str_replace('$where',$where,$sql)) or die(mysql_error());
		}
//print "done\n";
		$db->Execute("ALTER TABLE tag_square_stat_tmp ENABLE KEYS");
		$db->Execute("DROP TABLE IF EXISTS tag_square_stat");
		$db->Execute("RENAME TABLE tag_square_stat_tmp TO tag_square_stat");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
