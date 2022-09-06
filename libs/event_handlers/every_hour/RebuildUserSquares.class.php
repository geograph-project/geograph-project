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
                                (UNIQUE INDEX (user_id,`grid_reference`),INDEX(`grid_reference`),SPATIAL KEY (`point_xy`))
                                ENGINE=MyISAM";
		$insert = "INSERT INTO user_gridsquare_tmp";
		$select = " SELECT user_id,`grid_reference`,x,y,reference_index,
                                sum(moderation_status='geograph') as has_geographs,count(*) as imagecount,
                                max(ftf) as max_ftf, sum(points = 'tpoint') as tpoints, SUM(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))) as has_recent,
				GROUP_CONCAT(gridimage_id ORDER BY ftf>0 desc,seq_no LIMIT 1) AS first, max(gridimage_id) as `last`, `point_xy`,
				SUM(LENGTH(comment)) AS comment_len
                                FROM gridimage_search
				WHERE \$where
                                GROUP BY user_id,`grid_reference` ORDER BY NULL";

		if (empty($event['event_param'])) //easy way of forcing a full build.
			$user_gridsquare = $db->getRow("SHOW TABLE STATUS LIKE 'user_gridsquare'");

		//FULL (as a single query!)
		if (false) {
			$where = 1;
			$sql = $create.$select;
			$this->Execute(str_replace('$where',$where,$sql));

		//INCREMENTAL (just squares updated recently)
		} elseif ( !empty($user_gridsquare['Update_time']) && strtotime($user_gridsquare['Update_time']) > (time() - 60*60*12) && $user_gridsquare['Comment'] != 'rebuild') {

			$seconds = time() - strtotime($user_gridsquare['Update_time']);
			$hours = ceil($seconds/60/60);
			$hours++; //just to be safe

		        //$grs = $db->getCol("select grid_reference from gridimage_search where upd_timestamp >
	                //date_sub(now(),interval $hours hour) group by grid_reference order by null");

			//now we have last_timestamp on gridsquare column, lets use that. gridimage_search.upd_timestamp is updated if just title etc tweaked. THe gridsquare only updated when something affects the square counts
			$grs = $db->getCol("select grid_reference from gridsquare where last_timestamp >
                        date_sub(now(),interval $hours hour)");

			if (empty($grs))
				return true;

			$this->Execute("CREATE TABLE user_gridsquare_tmp LIKE user_gridsquare");

			$sql = $insert.$select;

			if (count($grs) > 50) {
				while($list = array_splice($grs,0,50)) {
					//insert current rows for this list of squares
					$where = "grid_reference in ('".implode("','",$list)."')";
	                                $this->Execute(str_replace('$where',$where,$sql));

					//we need to delete rows in user_gridsquare, that NO LONGER exist in the square (the $where is critical to delete only processed squares)
					$this->Execute("DELETE user_gridsquare.* FROM user_gridsquare LEFT JOIN user_gridsquare_tmp USING (user_id,grid_reference)
					 WHERE $where AND user_gridsquare_tmp.user_id IS NULL");
				}
			} else {
				//insert current rows for updated squares
				$where = "grid_reference in ('".implode("','",$grs)."')";
				$this->Execute(str_replace('$where',$where,$sql));

				//we need to delete rows in user_gridsquare, that NO LONGER exist in the square (the $where is critical to delete only processed squares)
				$this->Execute("DELETE user_gridsquare.* FROM user_gridsquare LEFT JOIN user_gridsquare_tmp USING (user_id,grid_reference)
				 WHERE $where AND user_gridsquare_tmp.user_id IS NULL");
			}

			$this->Execute("REPLACE INTO user_gridsquare SELECT * FROM user_gridsquare_tmp");
			return true;

		//PIECEMEAL (loop though all users)
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

			if (@$status['Comment'] == 'rebuild')
				$db->Execute("ALTER TABLE user_gridsquare_tmp COMMENT=''");
		}

		$this->Execute("DROP TABLE IF EXISTS user_gridsquare");
		$this->Execute("RENAME TABLE user_gridsquare_tmp TO user_gridsquare");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
