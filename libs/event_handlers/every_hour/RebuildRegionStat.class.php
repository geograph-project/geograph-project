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
class RebuildRegionStat extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;
		
		//perform actions
		
		$db=&$this->_getDB();
		
		$data = $db->getRow("SHOW TABLE STATUS LIKE 'loc_hier_stat_tmp'");
		
		if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*60*3)) {
			//if a recent table give up this time. It might still be running. 
			
			return false;
		} 
		
		
		$db->Execute("CREATE TABLE IF NOT EXISTS loc_hier_stat_tmp LIKE loc_hier_stat");
		
		$db->Execute("TRUNCATE loc_hier_stat_tmp"); //just incase we inheritied a old table.

		#$db->Execute("ALTER TABLE hectad_stat_tmp DISABLE KEYS");
		
		$db->Execute("
insert into loc_hier_stat_tmp (level,community_id,squares_total,images_total,squares_submitted,tenk_total,geographs_submitted,tenk_submitted,images_thisweek) select * from (
	select level,community_id,count(*) as squares_total,sum(imagecount) as images_total,sum(imagecount > 0) as squares_submitted, count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,-2,1))) as tenk_total from gridsquare gs inner join gridsquare_percentage gp on (gs.gridsquare_id=gp.gridsquare_id) where percent > 0 and percent_land > 0 group by level,community_id
) as da left join (
	select level,community_id,count(*) as geographs_submitted,count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,-2,1))) as tenk_submitted from gridsquare gs inner join gridsquare_percentage gp on (gs.gridsquare_id=gp.gridsquare_id) where percent > 0 and percent_land > 0 and has_geographs > 0 group by level,community_id
 ) as db using (level,community_id) left join (
	select level,community_id,count(*) as images_thisweek from gridimage gi inner join gridsquare_percentage gp on (gi.gridsquare_id=gp.gridsquare_id) where percent > 0 and (unix_timestamp(now())-unix_timestamp(submitted))<604800 group by level,community_id
) as dc using (level,community_id)
");
		
		#$db->Execute("UPDATE hectad_stat_tmp INNER JOIN hectad_stat USING (hectad) 
		#	SET hectad_stat_tmp.map_token = hectad_stat.map_token,
		#	hectad_stat_tmp.largemap_token = hectad_stat.largemap_token
		#	WHERE hectad_stat.map_token != '' OR hectad_stat.largemap_token != ''");
		
		#$db->Execute("ALTER TABLE hectad_stat_tmp ENABLE KEYS");
		
		
		$data = $db->getRow("SHOW TABLE STATUS LIKE 'loc_hier_stat_tmp'");
		
		if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*15)) {
			//make sure we have a recent table

			$db->Execute("DROP TABLE IF EXISTS loc_hier_stat_old");

			//done in one operation so there is always a hectad_stat table, even if the tmp fails 
			//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
			//$db->Execute("RENAME TABLE loc_hier_stat TO loc_hier_stat_old, loc_hier_stat_tmp TO loc_hier_stat");
			
			$db->Execute("RENAME TABLE loc_hier_stat TO loc_hier_stat_old");
			$db->Execute("RENAME TABLE loc_hier_stat_tmp TO loc_hier_stat");

			$db->Execute("DROP TABLE IF EXISTS loc_hier_stat_old");
		
		
			//return true to signal completed processing
			//return false to have another attempt later
			return true;
		} else {
			return false;
		}
	}
	
}

