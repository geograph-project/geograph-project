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
class RebuildUserDateStat extends EventHandler
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

		$db->Execute("DROP TABLE IF EXISTS user_date_stat_tmp");

		$create = "CREATE TABLE user_date_stat_tmp (primary key (user_id,type,year,month),index(type,year)) ";
		$insert = "INSERT INTO user_date_stat_tmp ";

		$c = 0;
		$has_table = $db->getOne("SHOW TABLES LIKE 'user_date_stat'");
		foreach (array('imagetaken','submitted') as $column) {
			$start = ($column == 'submitted')?2005:1900;
			foreach(range($start,date('Y')) as $year) {

$weeks = rand(1,5); if ($year == date('Y')) $weeks=0;
if ($year == date('Y')-1 && date('z')<10) $weeks=0; //and also update last year in first week of new year!
if ($has_table && $db->getOne("select type from user_date_stat where type='$column' and year = '$year' and updated > date_sub(now(),interval $weeks week)")) {
	continue;
}

				$where = array();
				//$where[] = "$column LIKE '$year-%'"; //mariadb doesnt use index for LIKE on dates
				if ($column == 'submitted') {
					$where[] = "$column BETWEEN '$year-01-01 00:00:00' AND '$year-12-31 23:59:59'";
					$days = "SUBSTRING($column,1,10)";
				} else {
					$where[] = "$column BETWEEN '$year-01-00' AND '$year-12-31'";
					$days = $column;
				}

				$this->Execute(($c?$insert:$create)."
				SELECT
				'$column' as type,
				substring($column,1,4) AS year,
			        substring($column,1,7) AS month,

      user_id,
      count(*) AS `images`,
      count(distinct grid_reference) AS `squares`,
      count(distinct if(moderation_status = 'geograph',grid_reference,null)) AS `geosquares`,
      0 AS `geo_rank`,
      0 AS `geo_rise`,
      sum(ftf=1 and moderation_status = 'geograph') AS `points`,
      0 AS `points_rank`,
      0 AS `points_rise`,
      sum(ftf=2 and moderation_status = 'geograph') AS `seconds`,
      sum(ftf=3 and moderation_status = 'geograph') AS `thirds`,
      sum(ftf=4 and moderation_status = 'geograph') AS `fourths`,
        sum( ftf between 1 and 4 and moderation_status = 'geograph') AS `visitors`,
        sum( ftf >0 and moderation_status = 'geograph') AS `personals`,
      sum(moderation_status = 'geograph') AS `geographs`,
      count(distinct $days) AS `days`,
      count(*)/count(distinct grid_reference) AS `depth`,
      count(distinct substring(grid_reference,1,3 - reference_index)) AS `myriads`,
      count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) AS `hectads`,
      sum(points = 'tpoint') AS `tpoints`,
      min(gridimage_id) AS `first`,
      max(gridimage_id) AS `last`,
        avg(length(comment)) as comment_len,

				NOW() as updated
				FROM `gridimage_search`
				WHERE ".implode(' AND ',$where)."
				GROUP BY user_id,substring($column,1,7)
				with rollup");//group by year is implicit, beucase running query per year!

				$c++;
			}
		}

		$this->Execute("CREATE TABLE IF NOT EXISTS user_date_stat LIKE user_date_stat_tmp");
		$this->Execute("REPLACE INTO user_date_stat SELECT * FROM user_date_stat_tmp");

                $db->Execute("DO RELEASE_LOCK('".get_class($this)."')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
