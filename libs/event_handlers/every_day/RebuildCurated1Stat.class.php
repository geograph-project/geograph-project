<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2008  Barry Hunter (geo@barryhunter.co.uk)
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
class Curated1Stat extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();

		$db->Execute("DROP TABLE IF EXISTS curated1_stat_tmp");

		$db->Execute("
create table curated1_stat_tmp
select `group`,
`label`,
count(*) as images,
sum(c.score > 7 AND GREATEST(original_width,original_height) > 1023) as larger,
sum(active=2) as featured,
count(distinct c.user_id) as curators,
count(distinct substring(grid_reference,1,3 - reference_index)) as myriads,
count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) as hectads,
count(distinct region) as regions,
count(distinct decade) as decades,
count(distinct gi.user_id) as contributors,
max(c.updated) as last,
gi.gridimage_id
from curated1 c
inner join gridimage_search gi using (gridimage_id)
inner join gridimage_size using (gridimage_id)
where active>0
group by `group`, `label`
order by null
");

		$db->Execute("alter table curated1_stat_tmp add index(`group`), add unique(label,`group`), add index(`last`)");

		$db->Execute("DROP TABLE IF EXISTS curated1_stat");
		$db->Execute("RENAME TABLE curated1_stat_tmp TO curated1_stat");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
