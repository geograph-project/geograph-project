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
  KEY `geosquares` (`geosquares`)
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

##################################

		foreach (array(1,2) as $ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?

			$prefixes = $db->GetCol("select prefix from gridprefix where reference_index = $ri and landcount > 0 ");

			foreach ($prefixes as $prefix) {

				$this->Execute("INSERT INTO hectad_stat_tmp
				SELECT
					reference_index,min(x) as x,min(y) as y,
					CONCAT(SUBSTRING(grid_reference,1,".($letterlength+1)."),SUBSTRING(grid_reference,".($letterlength+3).",1)) AS hectad,
					COUNT(DISTINCT gs.gridsquare_id) AS landsquares,
					COUNT(gridimage_id) AS images,
					SUM(moderation_status = 'geograph') AS geographs,
					COUNT(DISTINCT gi.gridsquare_id) AS squares,
					COUNT(DISTINCT IF(moderation_status='geograph',gi.gridsquare_id,NULL)) AS geosquares,
					COUNT(DISTINCT IF(has_recent=1,gs.gridsquare_id,NULL)) AS recentsquares,
					COUNT(DISTINCT user_id) AS users,
					MIN(IF(ftf=1,submitted,NULL)) AS first_submitted,
					MAX(IF(ftf=1,submitted,NULL)) AS last_submitted,
					'' AS map_token,
					'' AS largemap_token,
					COUNT(DISTINCT IF(ftf=1,user_id,NULL)) AS ftfusers
					FROM gridsquare gs
					LEFT JOIN gridimage gi ON (gs.gridsquare_id=gi.gridsquare_id AND moderation_status IN ('geograph','accepted'))
					WHERE reference_index = $ri AND grid_reference LIKE '$prefix%' AND percent_land >0
					GROUP BY (x-{$CONF['origins'][$ri][0]}) div 10,(y-{$CONF['origins'][$ri][1]}) div 10
					ORDER BY NULL");
				//todo when the origin is a multiple of 10 (or =0) then can be optimised away - but mysql might do that anyway

				//give the server a breather...
				usleep(500);
			}
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

				//done in one operation so there is always a hectad_stat table, even if the tmp fails
				//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
				//$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old, hectad_stat_tmp TO hectad_stat");

				$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old");
				$db->Execute("RENAME TABLE hectad_stat_tmp TO hectad_stat");

				$db->Execute("DROP TABLE IF EXISTS hectad_stat_old");
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

