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
class RebuildGridimageGroupStat extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();

		if (!$db->getOne("SELECT GET_LOCK('RebuildGridimageGroupStat',10)")) {
			//only execute if can get a lock
			$this->_output(2, "Failed to get Lock");
			return false;
		}


                $status = $db->getRow("SHOW TABLE STATUS LIKE 'gridimage_group'");

                if (!empty($status['Update_time']) && strtotime($status['Update_time']) < time()-3600*24*8) {
                        $this->_output(2, "No updates to process");
                        return true;
		}

                $status = $db->getRow("SHOW TABLE STATUS LIKE 'gridimage_group_stat'");

                if (!empty($status['Update_time']) && strtotime($status['Update_time']) > time()-3600*24*5) {
                        $this->_output(2, "Assume that table is being incrementally updated");  //this script runs weekly, so if recent updates something else must be doing it!
                        return true;
		}


		$sql = '
		select null as gridimage_group_stat_id, grid_reference, label
			, count(*) as images, count(distinct user_id) as users
			, count(distinct imagetaken) as days, count(distinct year(imagetaken)) as years, count(distinct substring(imagetaken,1,3)) as decades
			, min(submitted) as created, max(submitted) as updated, gridimage_id
			, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(submitted ORDER BY submitted),\',\',2),\',\',-1) AS `second`
			, avg(wgs84_lat) as wgs84_lat, avg(wgs84_long) as wgs84_long
		from gridimage_group inner join gridimage_search using (gridimage_id)
		where label not in (\'(other)\',\'Other Topics\') and grid_reference like \'{$prefix}%\' and reference_index = {$reference_index}
		group by grid_reference, label having images > 1 order by null';

		//may as well just create the table fresh - incase schema changed!
		if (false && $db->getCol("SHOW TABLES LIKE 'gridimage_group_stat'")) {
			$this->Execute("CREATE TABLE IF NOT EXISTS gridimage_group_stat_tmp LIKE gridimage_group_stat");
			$this->Execute("TRUNCATE TABLE gridimage_group_stat_tmp");
		} else {
			$this->Execute("DROP TABLE IF EXISTS `gridimage_group_stat_tmp`");
			$prefix = array('prefix'=>'XX','reference_index'=>999); //will never match anything!
			$this->Execute('create table gridimage_group_stat_tmp ( gridimage_group_stat_id int unsigned auto_increment primary key, index(grid_reference) ) '.
				preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql)) or die($db->ErrorMsg());
		}

		$prefixes = $db->GetAll("select prefix,reference_index from gridprefix where landcount > 0 ");
		foreach ($prefixes as $prefix) {
			$this->Execute('insert into gridimage_group_stat_tmp '.preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql));
		}

		$this->Execute("DROP TABLE IF EXISTS gridimage_group_stat");
		$this->Execute("RENAME TABLE gridimage_group_stat_tmp TO gridimage_group_stat");

		$db->Execute("DO RELEASE_LOCK('RebuildGridimageGroupStat')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
