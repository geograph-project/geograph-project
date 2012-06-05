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
		
		$data = $db->getRow("SHOW TABLE STATUS LIKE 'hectad_stat_tmp'");
		
		if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*60*3)) {
			//if a recent table give up this time. It might still be running. 
			
			return false;
		} 
		
		
		$db->Execute("CREATE TABLE IF NOT EXISTS hectad_stat_tmp LIKE hectad_stat");
		
		$db->Execute("TRUNCATE hectad_stat_tmp"); //just incase we inheritied a old table.
		
		$db->Execute("ALTER TABLE hectad_stat_tmp DISABLE KEYS");
		
		foreach (array(1,2) as $ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			
			$prefixes = $db->GetCol("select prefix from gridprefix where reference_index = $ri and landcount > 0 ");
			
			foreach ($prefixes as $prefix) {
			
				$db->Execute("INSERT INTO hectad_stat_tmp
				SELECT 
					reference_index,min(x) as x,min(y) as y,
					CONCAT(SUBSTRING(grid_reference,1,".($letterlength+1)."),SUBSTRING(grid_reference,".($letterlength+3).",1)) AS hectad,
					COUNT(DISTINCT gs.gridsquare_id) AS landsquares,
					COUNT(gridimage_id) AS images,
					SUM(moderation_status = 'geograph') AS geographs,
					COUNT(DISTINCT gi.gridsquare_id) AS squares,
					COUNT(DISTINCT IF(moderation_status='geograph',gi.gridsquare_id,NULL)) AS geosquares,
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
				sleep(10);
		
			}
		}
		
		$db->Execute("UPDATE hectad_stat_tmp INNER JOIN hectad_stat USING (hectad) 
			SET hectad_stat_tmp.map_token = hectad_stat.map_token,
			hectad_stat_tmp.largemap_token = hectad_stat.largemap_token
			WHERE hectad_stat.map_token != '' OR hectad_stat.largemap_token != ''");
		
		$db->Execute("ALTER TABLE hectad_stat_tmp ENABLE KEYS");
		
		
		$data = $db->getRow("SHOW TABLE STATUS LIKE 'hectad_stat_tmp'");
		
		if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*30)) {
			//make sure we have a recent table

			$db->Execute("DROP TABLE IF EXISTS hectad_stat_old");

			//done in one operation so there is always a hectad_stat table, even if the tmp fails 
			//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
			//$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old, hectad_stat_tmp TO hectad_stat");
			
			$db->Execute("RENAME TABLE hectad_stat TO hectad_stat_old");
			$db->Execute("RENAME TABLE hectad_stat_tmp TO hectad_stat");

			$db->Execute("DROP TABLE IF EXISTS hectad_stat_old");
		
		
			//return true to signal completed processing
			//return false to have another attempt later
			return true;
		} else {
			return false;
		}
	}
	
}

