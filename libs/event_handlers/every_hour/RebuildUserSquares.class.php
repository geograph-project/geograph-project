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
class RebuildUserSquares extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

		$this->Execute("DROP TABLE IF EXISTS user_gridsquare_tmp");

		$create = "CREATE TABLE user_gridsquare_tmp
                                (UNIQUE INDEX (user_id,`grid_reference`),INDEX(`grid_reference`))
                                ENGINE=MyISAM";
		$insert = "INSERT INTO user_gridsquare_tmp";
		$select = " SELECT user_id,`grid_reference`,
                                sum(moderation_status='geograph') as has_geographs,count(*) as imagecount,
                                max(ftf) as max_ftf, sum(points = 'tpoint') as tpoints, min(gridimage_id) as `first`, max(gridimage_id) as `last`
                                FROM gridimage_search
				WHERE \$where
                                GROUP BY user_id,`grid_reference` ORDER BY NULL";

		if (empty($event['event_param'])) //easy way of forcing a full build.
			$user_gridsquare = $db->getRow("SHOW TABLE STATUS LIKE 'user_gridsquare'");

		//FULL
		if (false) {
			$where = 1;
			$sql = $create.$select;
			$this->Execute(str_replace('$where',$where,$sql));

		//INCREMENTAL
		} elseif ( !empty($user_gridsquare['Update_time']) && strtotime($user_gridsquare['Update_time']) > (time() - 60*60*6) ) {

		        $grs = $db->getCol("select grid_reference from gridimage_search where upd_timestamp >
	                date_sub(now(),interval 6 hour) group by grid_reference order by null");

			$where = "grid_reference in ('".implode("','",$grs)."')";

			$this->Execute("CREATE TABLE user_gridsquare_tmp LIKE user_gridsquare");
			$this->Execute("INSERT INTO user_gridsquare_tmp SELECT * FROM user_gridsquare WHERE NOT ($where)");

			$sql = $insert.$select;
			$this->Execute(str_replace('$where',$where,$sql));

		//PIECEMEAL
		} else {
			$size = 100;
                        $users = $db->getOne("SELECT MAX(user_id) FROM gridimage_search");

                        $end = ceil($users/$size)*$size;
			for($q=0;$q<$end;$q+=$size) {
				$size += 1000; //as go, thogh in general newer contributors submit less, and there are less of them in each range.

                                $where = sprintf("user_id BETWEEN %d AND %d",$q,$q+$size-1);

				$sql = ($q?$insert:$create).$select;

				$this->Execute(str_replace('$where',$where,$sql));
			}
		}

		$this->Execute("DROP TABLE IF EXISTS user_gridsquare");
		$this->Execute("RENAME TABLE user_gridsquare_tmp TO user_gridsquare");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
