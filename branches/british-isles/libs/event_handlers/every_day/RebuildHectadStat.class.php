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
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS hectad_stat_tmp");
		
		//give the server a breather...
		sleep(10);
				
		$db->Execute("CREATE TABLE hectad_stat_tmp
		(UNIQUE (hectad,user_id))
		ENGINE=MyISAM
		SELECT 
			reference_index,x,y,
			CONCAT(SUBSTRING(grid_reference,1,LENGTH(grid_reference)-3),SUBSTRING(grid_reference,LENGTH(grid_reference)-1,1)) AS hectad,
			user_id,
			COUNT(DISTINCT gs.gridsquare_id) AS landsquares,
			COUNT(gridimage_id) AS images,
			SUM(moderation_status = 'geograph') AS geographs,
			COUNT(DISTINCT gi.gridsquare_id) AS squares,
			MIN(submitted) AS first_submitted,
			MAX(submitted) AS last_submitted, 
			
			FROM gridsquare gs
			LEFT JOIN gridimage gi ON (gs.gridsquare_id=gi.gridsquare_id AND moderation_status IN ('geograph','accepted')) 
			WHERE percent_land >0
			GROUP BY CONCAT(SUBSTRING(grid_reference,1,LENGTH(grid_reference)-3),SUBSTRING(grid_reference,LENGTH(grid_reference)-1,1)),user_id WITH ROLLUP ");
		
		$db->Execute("DROP TABLE IF EXISTS hectad_stat");
		$db->Execute("RENAME TABLE hectad_stat_tmp TO hectad_stat");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}