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
class RebuildGridimageDuplicate extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;

		$db=&$this->_getDB();


//Note this script, fines 'exact' duplicates using mysql GROUP BY, there is a seperate `scripts/duplicate_titles.php` that finds sequences
//... that script creates the titles with # at end, so this script doesn't delete them!


		$status = $db->getRow("SHOW TABLE STATUS LIKE 'gridimage_duplicate'");

                if (!empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*12) && $status['Comment'] != 'rebuild') {

                        $seconds = time() - strtotime($status['Update_time']);
                        $hours = ceil($seconds/60/60);
                        $hours++; //just to be safe

			if (true) { //experimental version doing it for single squares;

				//insert into a temporally table, so can delete the squares that are updated
				$db->Execute("CREATE TEMPORARY TABLE gridimage_duplicate_tmp LIKE gridimage_duplicate");

				$max = $db->getOne("SELECT MAX(gridsquare_id) FROM gridsquare");
				for($start=1;$start<=$max;$start+=10000) {
					$end = $start + 9999;

					$this->Execute("INSERT INTO gridimage_duplicate_tmp
					SELECT grid_reference,title,COUNT(*) AS images,COUNT(DISTINCT user_id) AS users
					 FROM gridsquare
						INNER JOIN gridimage_search USING (grid_reference)
				                 WHERE last_timestamp > date_sub(now(),interval $hours hour)
					 AND gridsquare_id BETWEEN $start and $end
					 GROUP BY grid_reference,title HAVING images > 1 ORDER BY NULL");
				}

				$this->Execute("DELETE gridimage_duplicate.* FROM gridimage_duplicate INNER JOIN gridimage_duplicate_tmp USING (grid_reference)
					WHERE gridimage_duplicate.title NOT like '% #'");

				$this->Execute("INSERT INTO gridimage_duplicate SELECT * FROM gridimage_duplicate_tmp");

				return true;
			}

			//... otherwise fall back to doing it by myriad

			//but we can at least only do the myriad that have updated!
			//$prefixes = $db->GetCol("select substring(grid_reference,1,3 - reference_index) as prefix,max(last_timestamp) as last
			//	 from gridsquare group by prefix having last > date_sub(now(),interval $hours hour)");

			$prefixes = $db->GetCol("select prefix from gridprefix where landcount > 0 and last_timestamp > date_sub(now(),interval $hours hour)");
		} else {
			$prefixes = $db->GetCol("select prefix from gridprefix where landcount > 0");
		}


		foreach ($prefixes as $prefix) {
			//note, a prefix query on gridimage_search is not very efficent, should change this to use point_xy
			$crit = $db->Quote("{$prefix}____");

			$db->Execute("DELETE FROM gridimage_duplicate WHERE grid_reference LIKE $crit AND title NOT like '% #'");

			$db->Execute("INSERT INTO gridimage_duplicate
				SELECT grid_reference,title,COUNT(*) AS images,COUNT(DISTINCT user_id) AS users FROM gridimage_search
				WHERE grid_reference LIKE $crit	GROUP BY grid_reference,title HAVING images > 1 ORDER BY NULL");

			//give the server a breather...
			//sleep(10);
		}

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}


