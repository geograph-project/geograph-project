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
class RebuildHectadUserStat extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;
		
		//perform actions
		
		$db=&$this->_getDB();
		
		$tables = $db->getCol("SHOW TABLES LIKE 'hectad_user_stat%'");
		
		if (!in_array('hectad_user_stat',$tables)) {
		
			$db->Execute("CREATE TABLE IF NOT EXISTS `hectad_user_stat` (
				  `reference_index` tinyint(4) default '1',
				  `hectad` varchar(4) NOT NULL default '',
				  `user_id` int(11) NOT NULL default '0',
				  `images` bigint(21) NOT NULL default '0',
				  `geographs` decimal(23,0) default NULL,
				  `squares` bigint(21) NOT NULL default '0',
				  `geosquares` bigint(21) NOT NULL default '0',
				  `first_submitted` datetime default NULL,
				  `last_submitted` varbinary(19) default NULL,
				  PRIMARY KEY  (`hectad`,`user_id`)
				) ENGINE=MyISAM");
		}
		
		if (!in_array('hectad_user_stat_tmp',$tables)) {
			$db->Execute("CREATE TABLE IF NOT EXISTS hectad_user_stat_tmp LIKE hectad_user_stat");
		} else {
			$db->Execute("TRUNCATE hectad_user_stat_tmp"); //just incase we inheritied a old table.
		}
		
		$db->Execute("ALTER TABLE hectad_user_stat_tmp DISABLE KEYS"); 
		
		foreach (array(1,2) as $ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			
			//give the server a breather...
			sleep(10);
		
			$db->Execute("INSERT INTO hectad_user_stat_tmp
			SELECT 
				reference_index,
				concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) AS hectad,
				user_id,
				COUNT(gridimage_id) AS images,
				SUM(moderation_status = 'geograph') AS geographs,
				COUNT(DISTINCT gi.gridsquare_id) AS squares,
				COUNT(DISTINCT IF(moderation_status='geograph',gi.gridsquare_id,NULL)) AS geosquares,
				MIN(submitted) AS first_submitted,
				MAX(submitted) AS last_submitted
				FROM gridsquare gs
				INNER JOIN gridimage gi ON (gs.gridsquare_id=gi.gridsquare_id) 
				WHERE reference_index = $ri AND percent_land >0 AND moderation_status IN ('geograph','accepted')
				GROUP BY (x-{$CONF['origins'][$ri][0]}) div 10,(y-{$CONF['origins'][$ri][1]}) div 10,user_id");
			//todo when the origin is a multiple of 10 (or =0) then can be optimised away - but mysql might do that anyway
		}
		
		sleep(5);
		$db->Execute("ALTER TABLE hectad_user_stat_tmp ENABLE KEYS"); 
		
		
		$db->Execute("DROP TABLE IF EXISTS hectad_user_stat");
		
		$db->Execute("RENAME TABLE hectad_user_stat_tmp TO hectad_user_stat");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}