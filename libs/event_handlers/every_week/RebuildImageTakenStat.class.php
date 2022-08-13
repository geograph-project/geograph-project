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
class RebuildImageTakenStat extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

                if (!$db->getOne("SELECT GET_LOCK('".get_class($this)."',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		$db->Execute("DROP TABLE IF EXISTS imagetaken_stat_tmp");
		$db->Execute("CREATE TABLE imagetaken_stat_tmp (
			imagetaken date not null,
			gridimage_id int unsigned not null,
			images int unsigned not null,
			geographs int unsigned not null)");

		$max_id = $db->getOne("SELECT max(gridimage_id) FROM gridimage_search");

		for($start=1;$start<$max_id;$start+=100000) {
			$crit = sprintf("gridimage_id BETWEEN %d AND %d",$start,$start+99999);
			$this->Execute("INSERT INTO imagetaken_stat_tmp
				SELECT imagetaken,gridimage_id,count(*) AS images,sum(moderation_status='geograph') as geographs
				FROM gridimage_search
				WHERE $crit
				GROUP BY imagetaken
				ORDER BY NULL");
		}
		$this->Execute("ALTER TABLE imagetaken_stat_tmp ADD index(imagetaken)");

		if ($db->getOne("SHOW TABLES LIKE 'imagetaken_stat'")) {
			$db->Execute("TRUNCATE imagetaken_stat");
		} else {
			$db->Execute("CREATE TABLE imagetaken_stat LIKE imagetaken_stat_tmp");
		}

		$this->Execute("INSERT INTO imagetaken_stat
				SELECT imagetaken,gridimage_id,SUM(images) AS images,SUM(geographs) AS geographs
				FROM imagetaken_stat_tmp
				GROUP BY imagetaken
				ORDER BY NULL");


                $db->Execute("DO RELEASE_LOCK('".get_class($this)."')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
