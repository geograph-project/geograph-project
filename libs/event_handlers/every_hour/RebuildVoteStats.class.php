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
class RebuildVoteStats extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

		$wm = 1; #minimum votes required to be listed (//todo if change need to add a having to clause below!)

		//move poty upcoming to canonical search
		$this->Execute("UPDATE vote_log SET type = 'i2136521' WHERE type = 'i5761957'");

		//move ratings to the canonical search. (see topic 13112)
		$this->Execute("UPDATE vote_log SET type = 'i19618112' WHERE type = 'i19625903'");

		##########################################
		// mark the votes as final

		$max_id = $db->getOne("select vote_id from `vote_log` where final = 1 order by vote_id desc"); //limit 1 is automatic
		$max_id -= 10000;

		$db->Execute("CREATE TEMPORARY TABLE vote_final AS SELECT MAX(vote_id) AS vote_id,type,id FROM vote_log WHERE vote_id > $max_id GROUP BY type,id,if (user_id>0,user_id,ipaddr)");
		$db->Execute("UPDATE `vote_log` SET `final` = 0 WHERE vote_id > $max_id");
		$db->Execute("UPDATE `vote_log`,vote_final SET vote_log.final = 1 WHERE `vote_log`.vote_id = vote_final.vote_id");

		##########################################
		// prepare the tmp table

		if ($db->getOne("SHOW TABLES LIKE 'vote_stat_tmp'")) {
			$db->Execute("TRUNCATE vote_stat_tmp");
		} else {
			$db->Execute("CREATE TABLE vote_stat_tmp LIKE vote_stat");
		}

		##########################################
		// see if can do a incremenatl update

		$status = $db->getRow("SHOW TABLE STATUS LIKE 'vote_stat'");

		if (!empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*24)) {
			//do an incremental build

			$seconds = time() - strtotime($status['Update_time']);
			$hours = ceil($seconds/60/60);
			$hours+=3; //just to be safe

			//having doesnt help much on the select itself (all the agrigation still happens), but cuts down the size of the data in the tmp table
			$having = " HAVING last_vote > date_sub(now(),interval $hours hour) ";

			//we just want to use vote_final to find the IDs to update, not to get the specific votes (ie wnat all votes for the id!)
			$table1 = "INNER JOIN vote_final USING (type,id)";

			//but need to limit it one row per type/id, so the INNER JOIN doesnt balloon (need an index anyway!)
			$db->Execute("ALTER IGNORE TABLE vote_final ADD UNIQUE(type,id)");

		} else {
			//do a full build
			$having = $table1 = '';
		}

		##########################################
		// first group by type to updated types, (and a per type average for the baysian calculation)

		$types = $db->getAssoc("SELECT type,AVG(vote) AS `avg`,MAX(ts) AS last_vote FROM vote_log
			WHERE vote > 0 AND `final` = 1 GROUP BY type $having ORDER BY NULL");

		if (empty($types))
			return true;

		##########################################
		// do that actual aggreations

		$db->Execute("ALTER TABLE vote_stat_tmp DISABLE KEYS");

		foreach ($types as $type => $row) {
			//can exclude votes on own images!
			$table2 = ($type == 'img' || $type == 'desc')?' INNER JOIN gridimage_search ON (id = gridimage_id AND vote_log.user_id != gridimage_search.user_id)':'';
			$this->Execute("INSERT INTO vote_stat_tmp
				SELECT
					type,
					id,
					COUNT(*) AS num,
					COUNT(DISTINCT vote_log.user_id,ipaddr) AS users,
					AVG(vote) AS `avg`,
					STD(vote) AS `std`,
					(COUNT(*) / (COUNT(*)+$wm)) * AVG(vote) + ($wm / (COUNT(*)+$wm)) * {$row['avg']} AS `baysian`,
					SUM(vote=1) AS v1,
					SUM(vote=2) AS v2,
					SUM(vote=3) AS v3,
					SUM(vote=4) AS v4,
					SUM(vote=5) AS v5,
					MAX(ts) AS last_vote
				FROM vote_log $table1 $table2
				WHERE type = '$type' AND vote > 0 AND `final` = 1
				GROUP BY id
				$having
				ORDER BY NULL");
		}

		##########################################
		// swap in the new rows.

		if (!empty($status)) {
			//there an existing table to insert into
			$this->Execute("REPLACE INTO vote_stat SELECT * FROM vote_stat_tmp");
		} else {
			//otherwise make the tmp table live
			$this->Execute("ALTER TABLE vote_stat_tmp ENABLE KEYS");

			$this->Execute("RENAME TABLE vote_stat_tmp TO vote_stat");
		}

		##########################################
		//final bit of clearup

		$db->Execute("UPDATE gridimage_daily,vote_stat SET vote_baysian = baysian,gridimage_daily.updated = gridimage_daily.updated WHERE vote_stat.id = gridimage_id AND type = 'i2136521' AND users > 3");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
