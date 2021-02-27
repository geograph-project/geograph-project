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
class RebuildDateStat extends EventHandler
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

		$db->Execute("DROP TABLE IF EXISTS date_stat_tmp");

		$create = "CREATE TABLE date_stat_tmp (primary key (type,reference_index,year,month)) ";
		$insert = "INSERT INTO date_stat_tmp ";

		$c = 0;
		$has_table = $db->getOne("SHOW TABLES LIKE 'date_stat'");
		foreach (array('imagetaken','submitted') as $column) {
			$start = ($column == 'submitted')?2005:1900;
			foreach(range($start,date('Y')) as $year) {
				foreach (range(0,2) as $ri) {

$weeks = rand(1,5); if ($year == date('Y')) $weeks=0;
if ($has_table && $db->getOne("select type from date_stat where type='$column' and reference_index = $ri and year = '$year' and updated > date_sub(now(),interval $weeks week)")) {
	continue;
}

					$where = array();
					//$where[] = "$column LIKE '$year-%'"; //mariadb doesnt use index for LIKE on dates
					if ($column == 'submitted')
						$where[] = "$column BETWEEN '$year-01-01 00:00:00' AND '$year-12-31 23:59:59'";
					else
						$where[] = "$column BETWEEN '$year-01-01' AND '$year-12-31'";

					if ($ri)
						$where[] = "reference_index = $ri";

					$this->Execute(($c?$insert:$create)."
				SELECT
				'$column' as type,
				$ri as reference_index,
				substring($column,1,4) AS year,
			        substring($column,1,7) AS month,
			        count( * ) AS images,
			        sum( moderation_status = 'geograph' ) AS geographs,
			        sum( points = 'tpoint' ) AS tpoints,
			        sum( ftf =1 ) AS points,
			        sum( ftf between 1 and 4 ) AS visitors,
			        sum( ftf >0 ) AS personals,
			        count( DISTINCT grid_reference ) AS squares,
			        count( DISTINCT SUBSTRING(grid_reference,1,3 - reference_index)) as myriads,
			        count( DISTINCT concat(substring(grid_reference,1,3 - reference_index),substring(grid_reference,6 - reference_index,1)) ) as hectads,
			        count( DISTINCT user_id ) AS users,
				NOW() as updated
				FROM `gridimage_search`
				WHERE ".implode(' AND ',$where)."
				GROUP BY substring($column,1,4),substring($column,1,7)
				with rollup having year is not null"); //because we are running a seperate query for each year, the rows with NULL year dont work. (they yearly anyway!)

					$rows = $db->Affected_Rows();

					if (!$ri && !$rows)
						break; //if no rows on ri=0, no point doing 1,2!

					$c++;
				}
			}
		}

		$this->Execute("CREATE TABLE IF NOT EXISTS date_stat LIKE date_stat_tmp");
		$this->Execute("REPLACE INTO date_stat SELECT * FROM date_stat_tmp");		

                $db->Execute("DO RELEASE_LOCK('".get_class($this)."')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
