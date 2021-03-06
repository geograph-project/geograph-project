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
class RebuildGridSquareCoverage extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS gridsquare_coverage_tmp");
		
		$db->Execute("
			create table gridsquare_coverage_tmp 
			select 
				grid_reference,
				concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) as hectad,
				count(*) as images,
				(count(distinct nateastings DIV 100, natnorthings DIV 100) - (sum(nateastings = 0) > 0) ) as centis,
				(count(distinct nateastings DIV 500, natnorthings DIV 500) - (sum(nateastings = 0) > 0) ) as quads,
				IF(SUM(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR)) AND moderation_status='geograph')>0,1,0) AS has_recent,
				has_geographs,
				sum(ftf between 1 and 4) as points,
				sum(nateastings = 0) as nocenti,
				count(distinct user_id) as users,
				count(distinct imageclass) as categories 
			from gridimage 
			inner join gridsquare using (gridsquare_id) 
			where moderation_status in ('accepted','geograph') 
			group by gridsquare_id"); 
		
		$db->Execute("ALTER TABLE gridsquare_coverage_tmp ADD KEY (`hectad`),ADD KEY (`grid_reference`)");

		//havent now computed a complete 'has_recent' we can update the gridsquare table
		//as the main table is updated when new images added to square etc, it wont 'expire' has_recent, if there are no new submissions!		
		$db->Execute("
		update gridsquare inner join gridsquare_coverage_tmp using (grid_reference)
		set gridsquare.has_recent = 0
		 where gridsquare_coverage_tmp.has_recent = 0 and gridsquare.has_recent =1");


		$db->Execute("DROP TABLE IF EXISTS gridsquare_coverage");
		$db->Execute("RENAME TABLE gridsquare_coverage_tmp TO gridsquare_coverage");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}

