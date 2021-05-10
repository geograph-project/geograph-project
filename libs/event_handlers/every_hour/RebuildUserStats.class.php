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
class RebuildUserStats extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

		$db->getOne("SELECT GET_LOCK('user_stat',3600)");

		$this->Execute("DROP TABLE IF EXISTS user_stat_tmp");

		$user_gridsquare = $db->getRow("SHOW TABLE STATUS LIKE 'user_gridsquare'");

		##############################################

		$status = $db->getRow("SHOW TABLE STATUS LIKE 'user_stat'");

		if (empty($status)) {
			$this->createTable();
		}

		//create table
		$db->Execute("CREATE TABLE user_stat_tmp LIKE user_stat");
		if ($status['Comment'] == 'rebuild')
			$db->Execute("ALTER TABLE user_stat_tmp COMMENT=''");

		##############################################
		// Incremental Update on Recent Table

		if (!empty($status['Update_time']) && strtotime($status['Update_time']) > (time() - 60*60*12) && $status['Comment'] != 'rebuild') {

			$seconds = time() - strtotime($status['Update_time']);
			$hours = ceil($seconds/60/60);
			$hours++; //just to be safe

			$users = $db->getCol("select distinct user_id from gridimage_search where upd_timestamp > date_sub(now(),interval $hours hour)");

			if (empty($users)) {
				//nothing to do then!
				return true;
			}

			$id_list = implode(',',$users);

			//copy over unchanged data (ranks will be recalculated anyway!)
			$this->Execute("INSERT INTO user_stat_tmp SELECT * FROM user_stat WHERE user_id NOT IN ($id_list,0)");

			$crit = "user_id IN ($id_list)";

			//add the changed users data
			if (!empty($user_gridsquare['Update_time']) && strtotime($user_gridsquare['Update_time']) > (time() - 60*60*6) ) {
				$this->insertFromUserGridsquare($crit);
			} else {
				$this->insertFromGridimageSearch($crit);
			}

		##############################################
		// Or build from user_gridsquare (which is quicker!)

		} elseif (!empty($user_gridsquare['Update_time']) && strtotime($user_gridsquare['Update_time']) > (time() - 60*60*6) ) {

                        $size = 100;
                        $users = $db->getOne("SELECT MAX(user_id) FROM gridimage_search");

                        $end = ceil($users/$size)*$size;

                        $db->Execute("ALTER TABLE user_stat_tmp DISABLE KEYS");

                        for($q=0;$q<$end;$q+=$size) {
                                $size += 1000;

                                $crit = sprintf("user_id BETWEEN %d AND %d",$q,$q+$size-1);

				$this->insertFromUserGridsquare($crit);
                        }

                        $this->Execute("ALTER TABLE user_stat_tmp ENABLE KEYS");

		##############################################
		// Or just create from scratch

		} else {
			$size = 100;
			$users = $db->getOne("SELECT MAX(user_id) FROM gridimage_search");

			$end = ceil($users/$size)*$size;

			$db->Execute("ALTER TABLE user_stat_tmp DISABLE KEYS");

			for($q=0;$q<$end;$q+=$size) {
				$size += 1000;

				$crit = sprintf("user_id BETWEEN %d AND %d",$q,$q+$size-1);

				$this->insertFromGridimageSearch($crit);

				sleep(2);//allow held up threads a chance to run
			}

			$this->Execute("ALTER TABLE user_stat_tmp ENABLE KEYS");
		}

		##############################################

		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

                $this->processor->trace("Computing Ranking data...");

	//get rank data
		$topusers=$db->GetAll("SELECT user_id,points,geosquares
		FROM user_stat_tmp
		ORDER BY points DESC");

	//create point rank
		$last = 0;
		$toriserank = 0;
		$ranks = $rise = $geosquares = array();
		foreach($topusers as $idx=>$entry) {
			if ($last != $entry['points']) {
				$toriserank = $last?($last - $entry['points']):0;

				$last = $entry['points'];
				$lastrank = $last?($idx+1):0;
			}
			$rise[$entry['user_id']] = $toriserank;
			$ranks[$entry['user_id']] = $lastrank;
			$geosquares[$entry['user_id']] = intval($entry['geosquares']);
		}

	//create personal rank
		arsort($geosquares);
		$lastpoints = 0;
		$toriserank = 0;
		$granks = $grise = array();
		$r = 1;
		foreach($geosquares as $user_id=>$squares) {
			if ($last != $squares) {
				$toriserank = $last?($last - $squares):0;

				$last = $squares;
				$lastrank = $last?($r):0;
			}
			$grise[$user_id] = $toriserank;
			$granks[$user_id] = $lastrank;
			$r++;
		}

                $this->processor->trace("Saving Ranks...");

	//insert ranks
		foreach ($ranks as $user_id => $rank) {
			$db->query("UPDATE user_stat_tmp
			SET points_rank = $rank,
			points_rise = {$rise[$user_id]},
			geo_rank = {$granks[$user_id]},
			geo_rise = {$grise[$user_id]}
			WHERE user_id = $user_id");
		}

                $this->processor->trace("Computing overall stats...");

	//work out overall stat (historically this was done with WITH ROLLUP, but now we can just calculate the few we need directly)
		$overall = $db->getRow("select
			sum(imagecount) as images,
			sum(imagecount>0) as squares,
			sum(has_geographs=1) as points,
			0 as user_id
		from gridsquare
		where percent_land > 0");
		$db->Execute('INSERT INTO user_stat_tmp SET `'.implode('` = ?,`',array_keys($overall)).'` = ?',array_values($overall));

	//add content data
		$this->Execute("create temporary table user_content_stat (primary key(user_id))
			SELECT user_id,count(distinct if(source in ('blog','trip'),title,content_id)) as content
			FROM content
			WHERE source IN('article','gallery','help','blog','trip')
			GROUP BY user_id
			ORDER BY NULL");

		$this->Execute("update user_stat_tmp inner join user_content_stat using (user_id)
			 set user_stat_tmp.content = user_content_stat.content");


		$db->Execute("DROP TABLE IF EXISTS user_stat_old");

			//done in one operation so there is always a user_stat table, even if the tmp fails
			//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
			//$db->Execute("RENAME TABLE user_stat TO user_stat_old, user_stat_tmp TO user_stat");

	//swap tables around
		$db->Execute("RENAME TABLE user_stat TO user_stat_old");
		$db->Execute("RENAME TABLE user_stat_tmp TO user_stat");
		$db->Execute("DROP TABLE IF EXISTS user_stat_old");


		$db->getOne("SELECT RELEASE_LOCK('user_stat')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}

	function createTable() {
		$this->Execute("CREATE TABLE user_stat (
						`user_id` int(11) unsigned NOT NULL default '0',
						`images` mediumint(5) unsigned NOT NULL default '0',
						`squares` mediumint(5) unsigned NOT NULL default '0',
						`geosquares` smallint(5) unsigned NOT NULL default '0',
						`geo_rank` smallint(5) unsigned NOT NULL default '0',
						`geo_rise` smallint(5) unsigned NOT NULL default '0',
						`points` mediumint(5) unsigned NOT NULL default '0',
						`points_rank` smallint(5) unsigned NOT NULL default '0',
						`points_rise` smallint(5) unsigned NOT NULL default '0',
						`seconds` mediumint(5) unsigned NOT NULL default '0',
						`thirds` mediumint(5) unsigned NOT NULL default '0',
						`fourths` mediumint(5) unsigned NOT NULL default '0',
						`geographs` mediumint(5) unsigned NOT NULL default '0',
						`days` smallint(5) unsigned NOT NULL default '0',
						`depth` decimal(6,2) NOT NULL default '0',
						`myriads` tinyint(5) unsigned NOT NULL default '0',
						`hectads` smallint(3) unsigned NOT NULL default '0',
						`tpoints` mediumint(5) unsigned NOT NULL default '0',
						`first` int(11) unsigned NOT NULL default '0',
						`last` int(11) unsigned NOT NULL default '0',
						`content` mediumint(5) unsigned NOT NULL default '0',
						`comment_len` decimal(8,1) unsigned default NULL,
						PRIMARY KEY  (`user_id`),
						KEY `points` (`points`)
					) ENGINE=MyISAM");

	}

	function insertFromGridimageSearch($crit) {
		$this->Execute("INSERT INTO user_stat_tmp
				SELECT user_id,
					count(*) as images,
					count(distinct grid_reference) as squares,
					count(distinct if(moderation_status = 'geograph',grid_reference,null)) as geosquares,
					0 as geo_rank,
					0 as geo_rise,
					sum(ftf=1 and moderation_status = 'geograph') as points,
					0 as points_rank,
					0 as points_rise,
					sum(ftf=2 and moderation_status = 'geograph') as seconds,
					sum(ftf=3 and moderation_status = 'geograph') as thirds,
					sum(ftf=4 and moderation_status = 'geograph') as fourths,
					sum(moderation_status = 'geograph') as geographs,
					count(distinct imagetaken) as days,
					count(*)/count(distinct grid_reference) as depth,
					count(distinct substring(grid_reference,1,3 - reference_index)) as myriads,
					count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) as hectads,
					sum(points = 'tpoint') as tpoints,
					min(gridimage_id) as `first`,
					max(gridimage_id) as `last`,
					0 as `content`,
					avg(length(comment)) as comment_len
				FROM gridimage_search
				WHERE $crit
				GROUP BY user_id
				ORDER BY NULL");

	}

	function insertFromUserGridsquare($crit) {
                $this->Execute("INSERT INTO user_stat_tmp
				SELECT user_id,
					sum(imagecount) as images,
					count(*) as squares,
					sum(max_ftf>0) as geosquares,
					0 as geo_rank,
					0 as geo_rise,
					sum(max_ftf=1) as points,
					0 as points_rank,
					0 as points_rise,
					sum(max_ftf=2) as seconds,
					sum(max_ftf=3) as thirds,
					sum(max_ftf=4) as fourths,
					sum(has_geographs) as geographs,
					0 as days,
					sum(imagecount)/count(*) as depth,
					count(distinct substring(grid_reference,1,length(grid_reference)-4) ) as myriads,
					count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) as hectads,
					sum(tpoints) as tpoints,
					min(`first`) as `first`,
					max(`last`) as `last`,
					0 as `content`,
					sum(comment_len)/sum(imagecount) as comment_len
				FROM user_gridsquare
                                WHERE $crit
                                GROUP BY user_id
                                ORDER BY NULL");

/*	//v1 - using gridimage_search directly

		$this->Execute("
			create temporary table user_day_stat (primary key(user_id))
			select user_id,count(distinct imagetaken) as days from gridimage_search
			WHERE $crit group by user_id");

		$this->Execute("update user_stat_tmp inner join user_day_stat using (user_id)
			set user_stat_tmp.days = user_day_stat.days");

		$this->Execute("drop temporary table user_day_stat");
*/

	//v2 - using manticore insteasd
		$sph = GeographSphinxConnection();
		$db = $this->_getDB();

		//rather than using $crit, whic may be MORE than 1000 rows, lets do it piecemeal

		$ids = $db->getCol("SELECT user_id FROM user_stat_tmp WHERE days = 0 and user_id > 0 limit 1000");

		$loop = 0;
		while (!empty($ids)) {
			$data = $sph->getAll("SELECT auser_id,count(distinct takendays) as days FROM gi_stemmed
				WHERE auser_id IN (".implode(',',$ids).") GROUP BY auser_id LIMIT 1000");

			$this->processor->trace("Found ".count($data)." Rows from manticore");

			foreach ($data as $row)
				$db->Execute("UPDATE user_stat_tmp SET days = {$row['days']} WHERE user_id = {$row['auser_id']}");

			$ids = $db->getCol("SELECT user_id FROM user_stat_tmp WHERE days = 0 and user_id > 0 limit 1000");
			$loop++;
			if ($loop > 10)
				return;
		}

	//v3 - TODO we now have user_date_stat, which could be used, rather than calling manticore
		//select user_id,sum(days) as days from user_date_stat where type='imagetaken' and month = '' group by user_id;
		// (the month filter is important as the table contains BOTH month and year groupings, we can just use the yearly one)

	}
}

