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
		
		$db->Execute("DROP TABLE IF EXISTS user_stat_tmp");

		$data = $db->getRow("SHOW TABLE STATUS LIKE 'user_stat'");
		
		if (!empty($data['Update_time']) && strtotime($data['Update_time']) > (time() - 60*60*6)) {
			//a recent table - so lets update that!
			
			$users = $db->getCol("select distinct user_id from gridimage_search where upd_timestamp > date_sub(now(),interval 3 hour)"); //use 3 hour just to be safe!
			
			if (empty($users)) {
				//nothing to do then!
				return true;
			}
$users[] = 9857; //TEST!
			
			$id_list = implode(',',$users);
			
		//create table
			$db->Execute("CREATE TABLE user_stat_tmp LIKE user_stat");
			
		//copy over unchanged data (ranks will be recalculated anyway!)
			$db->Execute("INSERT INTO user_stat_tmp SELECT * FROM user_stat WHERE user_id NOT IN ($id_list,0)");
			
			$crit = "user_id IN ($id_list)";
			
		//add the changed users data
			$db->Execute("INSERT INTO user_stat_tmp
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
					max(gridimage_id) as last,
					0 as `content`
				FROM gridimage_search
				WHERE $crit
				GROUP BY user_id
				ORDER BY NULL");
			
			
		} else {
			
		//create table
			$db->Execute("CREATE TABLE user_stat_tmp (
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
						`last` int(11) unsigned NOT NULL default '0',
						`content` mediumint(5) unsigned NOT NULL default '0',
						PRIMARY KEY  (`user_id`),
						KEY `points` (`points`)
					) ENGINE=MyISAM");

			$size = 5000;
			$users = $db->getOne("SELECT MAX(user_id) FROM gridimage_search");

			$end = ceil($users/$size)*$size;

			$db->Execute("ALTER TABLE user_stat_tmp DISABLE KEYS");

			for($q=0;$q<$end;$q+=$size) {
				$crit = sprintf("user_id BETWEEN %d AND %d",$q,$q+$size-1);

				$db->Execute("INSERT INTO user_stat_tmp
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
						max(gridimage_id) as last,
						0 as `content`
					FROM gridimage_search
					WHERE $crit
					GROUP BY user_id
					ORDER BY NULL");

				sleep(2);//allow held up threads a chance to run
			}

			$db->Execute("ALTER TABLE user_stat_tmp ENABLE KEYS");
		}
		
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
		
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
		
	//insert ranks	
		foreach ($ranks as $user_id => $rank) {
			$db->query("UPDATE user_stat_tmp 
			SET points_rank = $rank,
			points_rise = {$rise[$user_id]},
			geo_rank = {$granks[$user_id]},
			geo_rise = {$grise[$user_id]}
			WHERE user_id = $user_id");
		}
		
	//work out overall stat
		$overall = $db->getRow("select 
			sum(imagecount) as images,
			sum(imagecount>0) as squares,
			sum(has_geographs=1) as points,
			0 as user_id
		from gridsquare 
		where percent_land > 0");
		$db->Execute('INSERT INTO user_stat_tmp SET `'.implode('` = ?,`',array_keys($overall)).'` = ?',array_values($overall));

	//add content data
		$topusers=$db->GetAssoc("SELECT user_id,count(*) as content
			FROM content 
			WHERE source IN('article','gallery','help','blog','trip')
			GROUP BY user_id 
			ORDER BY NULL"); 
		foreach ($topusers as $user_id => $count) {
			$db->query("UPDATE user_stat_tmp
			SET content = $count
			WHERE user_id = $user_id");
		}

		$data = $db->getRow("SHOW TABLE STATUS LIKE 'user_stat_tmp'");
		if (!empty($data['Create_time']) && strtotime($data['Create_time']) > (time() - 60*15)) {
			//make sure we have a recent table

			$db->Execute("DROP TABLE IF EXISTS user_stat_old");

			//done in one operation so there is always a user_stat table, even if the tmp fails 
			//... well we did until it stopped working... http://bugs.mysql.com/bug.php?id=31786
			//$db->Execute("RENAME TABLE user_stat TO user_stat_old, user_stat_tmp TO user_stat");
			
	//swap tables around
			$db->Execute("RENAME TABLE user_stat TO user_stat_old");
			$db->Execute("RENAME TABLE user_stat_tmp TO user_stat");

			$db->Execute("DROP TABLE IF EXISTS user_stat_old");

		
			//return true to signal completed processing
			//return false to have another attempt later
			return true;
		} else {
			return false;
		}
	}
	
}

