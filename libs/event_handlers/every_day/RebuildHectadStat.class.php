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
class RebuildHectadStat extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;

		//perform actions

		$db=&$this->_getDB();

                if (!$db->getOne("SELECT GET_LOCK('".get_class($this)."',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }


##################################
//check another process

                $tables = $db->getAssoc("SHOW TABLE STATUS LIKE 'hectad_stat%'");

##################################
//may need to create the original table

                if (empty($tables['hectad_stat'])) {
                        $db->Execute("
CREATE TABLE `hectad_stat` (
  `reference_index` tinyint(4) DEFAULT '1',
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `hectad` varchar(7) NOT NULL DEFAULT '',
  `landsquares` smallint(5) unsigned DEFAULT '0',
  `images` int(11) unsigned NOT NULL DEFAULT '0',
  `geographs` mediumint(8) unsigned DEFAULT '0',
  `squares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geosquares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `recentsquares` smallint(5) unsigned NOT NULL DEFAULT '0',
  `users` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `first_submitted` datetime DEFAULT NULL,
  `last_submitted` datetime DEFAULT NULL,
  `map_token` varchar(128) DEFAULT NULL,
  `largemap_token` varchar(128) DEFAULT NULL,
  `ftfusers` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hectad`),
  KEY `reference_index` (`reference_index`),
  KEY `geosquares` (`geosquares`),
  KEY `y` (`y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
			");
		}

##################################
// create the TEMP table

		if (empty($tables['hectad_stat_tmp'])) {
			$db->Execute("CREATE TABLE hectad_stat_tmp LIKE hectad_stat");
		} else {
			$db->Execute("TRUNCATE hectad_stat_tmp");
		}

		$db->Execute("ALTER TABLE hectad_stat_tmp DISABLE KEYS");


                $status = $db->getRow("SHOW TABLE STATUS LIKE 'hectad_stat'");

                if ($status['Rows'] > 100 && !empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*52) && $status['Comment'] != 'rebuild') {
                        $seconds = time() - strtotime($status['Update_time']);
                        $hours = ceil($seconds/60/60);
                        $hours++; //just to be safe

                        $prefixes = $db->GetAssoc("select prefix,origin_x,origin_y,reference_index from gridprefix where landcount > 0 and last_timestamp > date_sub(now(),interval $hours hour)");
			$full = 0;
                } else {
                        $prefixes = $db->GetAssoc("select prefix,origin_x,origin_y,reference_index from gridprefix where landcount > 0");
			$full = 1;
                }

##################################

			foreach ($prefixes as $prefix => $data) {
				$ri = $data['reference_index'];

				//used to use "grid_reference LIKE '$prefix%'" but wasnt using index on gr, was using the reference_index index anyway
				// see scripts/try-hectad-index.php
				// so could add "FORCE INDEX(grid_reference)" which helps a bit, but can do EVEN better using spatial index...
				$indexes = "FORCE INDEX(point_xy)"; //shouldnt be needed as will pick it anyway, but query is quicker with it (saves a bit of calulations to choose index??!)
				$left=$data['origin_x'];
				$right=$data['origin_x']+99; //we could use width, but lets just grab whole square, we ARE filtering by reference_index anyway.
				$top=$data['origin_y']+99;
				$bottom=$data['origin_y'];

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
				$where = "CONTAINS(GeomFromText($rectangle),point_xy)";

				$this->Execute("DROP TABLE IF EXISTS hectat_stat_pre");
				$this->Execute("create TEMPORARY table hectat_stat_pre
				SELECT gs.reference_index,
				        min(gs.x) AS `x`,
				        min(gs.y) AS `y`,
				        CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1)) AS `hectad`,
				        COUNT(DISTINCT gs.gridsquare_id) AS `landsquares`,
				        SUM(gs.imagecount>0) AS `squares`,
				        SUM(gs.has_geographs>0) AS `geosquares`,
				        SUM(gs.has_recent>0) AS `recentsquares`
					FROM gridsquare gs
					WHERE reference_index = $ri AND $where AND percent_land >0
					GROUP BY (x-{$CONF['origins'][$ri][0]}) div 10,(y-{$CONF['origins'][$ri][1]}) div 10
					ORDER BY NULL");
				//we group using CONF['origins'] rather than gridprefix.origin_x because while gridprefix should contain whole square, it not nesseraily aligned to hectad boundaries!
				//todo when the origin is a multiple of 10 (or =0) then can be optimised away - but mysql might do that anyway

				//... because if group by gridsquare and hectat_stat_pre at once, then (upto) 100 rows per square, the sum()s are out by a factor of 100

				$this->Execute("INSERT INTO hectad_stat_tmp
					SELECT pre.reference_index,
				        pre.`x`,
				        pre.`y`,
				        `hectad`,
				        pre.`landsquares`,
				        SUM(uh.images) AS `images`,
				        SUM(uh.geographs) AS `geographs`,
				        pre.`squares`,
				        pre.`geosquares`,
				        pre.`recentsquares`,
				        COUNT(DISTINCT user_id) AS `users`,
				        MIN(first_first_submitted) AS `first_submitted`,
				        MAX(last_first_submitted) AS `last_submitted`,
				        '' AS `map_token`,
				        '' AS `largemap_token`,
				        SUM(first_first_submitted>'1000-01-01') AS `ftfusers`
					FROM hectat_stat_pre pre
				        LEFT JOIN hectad_user_stat uh USING (hectad)
					GROUP BY hectad
					ORDER BY NULL");

				//give the server a breather...
				usleep(500);
			}

##################################

		$db->Execute("UPDATE hectad_stat_tmp INNER JOIN hectad_stat USING (hectad)
			SET hectad_stat_tmp.map_token = hectad_stat.map_token,
			hectad_stat_tmp.largemap_token = hectad_stat.largemap_token
			WHERE hectad_stat.map_token != '' OR hectad_stat.largemap_token != ''");

##################################

		$db->Execute("ALTER TABLE hectad_stat_tmp ENABLE KEYS");

##################################

		$db->Execute("DROP TABLE IF EXISTS hectad_stat_old");

		if ($db->getOne("SELECT GET_LOCK('hectad_stat',10)")) {
			//check the table STILL exists, there is small chance another thread beat us to it, and renamed the tables!
			$table = $db->getAll("SHOW TABLES LIKE 'hectad_stat_tmp'");
			if (!empty($table)) {

				if ($full) {
					//done in one operation so there is always a hectad_stat table, even if the tmp fails
					//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
					//$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old, hectad_stat_tmp TO hectad_stat");

					$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old");
					$db->Execute("RENAME TABLE hectad_stat_tmp TO hectad_stat");

					$db->Execute("DROP TABLE IF EXISTS hectad_stat_old");
				} else {
					$db->Execute("REPLACE INTO hectad_stat SELECT * FROM hectad_stat_tmp");
				}
			}
			$db->getOne("SELECT RELEASE_LOCK('hectad_stat')");

			//return true to signal completed processing
			//return false to have another attempt later
			return true;
		} else {
			return false;
		}

##################################

	}
}

