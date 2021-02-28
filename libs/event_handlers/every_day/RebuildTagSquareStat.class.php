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

                if (!$db->getOne("SELECT GET_LOCK('RebuildTagSquareStat',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		##################################################
		# define the creation query

		$sql = "SELECT tag_id, gi.user_id, grid_reference, COUNT(*) AS images
			FROM gridimage_tag
			INNER JOIN gridimage_search gi USING (gridimage_id)
			WHERE \$where AND status = 2
			GROUP BY tag_id, gi.user_id, grid_reference
			ORDER BY NULL";

		##################################################
		# create the table if doesnt exist

		if (!$db->getOne("show tables like 'tag_square_stat'")) {
			$prefix = $db->GetOne("select prefix from gridprefix where landcount > 0 order by landcount");
                        $where = "grid_reference LIKE ".$db->Quote("{$prefix}____");

			//for now use MyISAM, as it was optimized that way, and uses disable/enable keys.
			$this->Execute("CREATE TABLE tag_square_stat (primary key(`tag_id`,`user_id`,`grid_reference`)) ENGINE=myisam ".str_replace('$where',$where,$sql)) or die(mysql_error());
		}

		##################################################
		# create the temporaly table

		$db->Execute("DROP TABLE IF EXISTS tag_square_stat_tmp");

		$db->Execute("CREATE TABLE tag_square_stat_tmp LIKE tag_square_stat");

		##################################################
		# fill the tempory table

		$status = $db->getRow("SHOW TABLE STATUS LIKE 'tag_square_stat'");

		if (!empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*12) && $status['Comment'] != 'rebuild') {
			$seconds = time() - strtotime($status['Update_time']);
			$hours = ceil($seconds/60/60);
			$hours++; //just to be safe

			if (true) { //experimental version doing it for single squares; (rather than whole myriads!)

				$sql = str_replace('WHERE ',"INNER JOIN gridsquare USING (grid_reference) WHERE last_timestamp > date_sub(now(),interval $hours hour) AND ", $sql);

				//even though using last_timestamp, which means few rows from gridsquare, can still fan out to be lots of rows in gridimage and tags. 
				$max = $db->getOne("SELECT MAX(gridsquare_id) FROM gridsquare");
				for($start=1;$start<=$max;$start+=50000) {
					$end = $start + 49999;
					$where = "gridsquare_id BETWEEN $start and $end";
					$this->Execute("INSERT INTO tag_square_stat_tmp ".str_replace('$where',$where,$sql)) or die(mysql_error());
				}

				//todo, technically, this could leave some zombie records, if a tag is no longer used in square. But for now lets not worry about that!
				$this->Execute("REPLACE INTO tag_square_stat SELECT * FROM tag_square_stat_tmp");

				return true;
			}

			$prefixes = $db->GetAssoc("select prefix,origin_x,origin_y,reference_index from gridprefix where landcount > 0 and last_timestamp > date_sub(now(),interval $hours hour)");
		} else {
			$prefixes = $db->GetAssoc("select prefix,origin_x,origin_y,reference_index from gridprefix where landcount > 0");
		}

       	        foreach ($prefixes as $prefix => $data) {
                        //$where = "grid_reference LIKE ".$db->Quote("{$prefix}____");
                        //... wasnt using index on gr, was not using ANY index!
                        // see scripts/try-hectad-index2.php
                        // so could add "FORCE INDEX(grid_reference)" which helps a bit, but can do EVEN better using spatial index...
                        $indexes = "FORCE INDEX(point_xy)"; //shouldnt be needed as will pick it anyway, but query is quicker with it (saves a bit of calulations to choose index??!)
                        $left=$data['origin_x'];
                        $right=$data['origin_x']+99; //we could use width, but lets just grab whole square, we ARE filtering by reference_index anyway.
                        $top=$data['origin_y']+99;
                        $bottom=$data['origin_y'];

                        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
                        $where = "CONTAINS(GeomFromText($rectangle),point_xy)";
			$where.= " AND reference_index = ".$data['reference_index'];

			$sql = str_replace('gi USING',"gi $indexes USING",$sql); //hardcoded, as an index on gi

			$this->Execute("INSERT INTO tag_square_stat_tmp ".str_replace('$where',$where,$sql)) or die(mysql_error());
		}

		##################################################
		# swap the tables into place

		$db->Execute("REPLACE INTO tag_square_stat SELECT * FROM tag_square_stat_tmp");

		//todo, this leaves orphan lines, when tags are deleted (or images moved from square)

		##################################################

		$db->Execute("DO RELEASE_LOCK('RebuildTagSquareStat')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
