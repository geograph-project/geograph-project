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
class RebuildTagStat extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

/*
select if(t2.tag_id is not null,if(t2.prefix !='',concat(t2.prefix,':',t2.tag),t2.tag),if(t1.prefix !='',concat(t1.prefix,':',t1.tag),t1.tag)) as tagtext,
t1.tag_id,t1.prefix,t1.tag,t1.canonical,t2.tag_id,t2.prefix,t2.tag
from tag t1 left join tag t2 on (t2.tag_id = t1.canonical)
where t1.tag != t2.tag limit 10;
*/

		$sql = "SELECT t1.tag_id, COUNT(gridimage_id) AS count,RAND() AS rnd,COUNT(DISTINCT t1.user_id) AS users,MAX(t1.created) AS last_used,
if(t2.tag_id is not null,if(t2.prefix !='',concat(t2.prefix,':',t2.tag),t2.tag),if(t1.prefix !='',concat(t1.prefix,':',t1.tag),t1.tag)) as tagtext,
coalesce(t2.tag_id,t1.tag_id) as final_id, now() as stat_updated
FROM tag_public t1 left join tag t2 on (t2.tag_id = t1.canonical and t2.status =1)
WHERE \$where GROUP BY t1.tag_id ORDER BY NULL";

                $status = $db->getRow("SHOW TABLE STATUS LIKE 'tag_stat'");

		//incremental
		if (!empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*6)) {
			if (false) {
				//this works, but can still block for like 40 seconds
				$this->Execute("CREATE TEMPORARY TABLE tag_updated (PRIMARY KEY(tag_id))
					SELECT DISTINCT tag_id FROM gridimage_tag WHERE updated > date_sub(now(),interval 6 hour)");

				$sql = str_replace('tag_public t1','tag_public t1 inner join tag_updated using (tag_id)', $sql);
				$where = '1';

				$this->Execute("REPLACE INTO tag_stat ".str_replace('$where',$where,$sql));
			} else {
				//doing one, by one, is inefficent, but runs better as lots of small queries
				$ids = $db->getCol("SELECT DISTINCT tag_id FROM gridimage_tag WHERE updated > date_sub(now(),interval 3 hour)");
				foreach ($ids as $id) {
					$where = "t1.tag_id = $id";
					$db->Execute("REPLACE INTO tag_stat ".str_replace('$where',$where,$sql));
				}
			}

		//do a (full) inplace update
		} elseif (!empty($status)) {

			$max = $db->getOne("SELECT MAX(tag_id) FROM tag");
			for($start=1;$start<$max;$start+=1000) {
				$where = "t1.tag_id BETWEEN $start AND ".($start+999)." AND gridimage_id < 4294967296";
				$this->Execute("REPLACE INTO tag_stat ".str_replace('$where',$where,$sql));
			}

			//delete any not updated! (must be from wholely deleted tags!)
			$this->Execute("DELETE FROM tag_stat WHERE stat_updated < DATE_SUB(NOW(),INTERVAL 2 HOUR)");

		//create again from scratch
		} else {
			$where = 'gridimage_id < 4294967296';

			$db->Execute("DROP TABLE IF EXISTS tag_stat_tmp");
			$this->Execute("CREATE TABLE tag_stat_tmp (primary key (`tag_id`),index (`rnd`)) ".str_replace('$where',$where,$sql));
			$db->Execute("DROP TABLE IF EXISTS tag_stat");
			$db->Execute("RENAME TABLE tag_stat_tmp TO tag_stat");
		}

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
