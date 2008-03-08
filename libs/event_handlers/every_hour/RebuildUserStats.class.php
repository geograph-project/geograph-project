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
		
		
		$db->Execute("CREATE TABLE user_stat_tmp 
				ENGINE=MyISAM
				SELECT user_id,
					count(*) as images,
					count(distinct grid_reference) as squares,
					10000 as geosquares,
					50000 as geo_rank,
					500 as geo_rise,
					sum(ftf=1 and moderation_status = 'geograph') as points,
					50000 as points_rank,
					500 as points_rise,
					FLOOR(sum(moderation_status = 'geograph')) as geographs,
					FLOOR(sum(moderation_status = 'accepted')) as accepted,
					count(distinct imagetaken) as days,
					count(*)/count(distinct grid_reference) as depth,
					count(distinct substring(grid_reference,1,3 - reference_index)) as myriads,
					count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ) as hectads
				FROM gridimage_search
				GROUP BY user_id");
		
		$db->Execute("ALTER TABLE `user_stat_tmp` ADD PRIMARY KEY (`user_id`)");
		
		$topusers=$db->GetAll("SELECT user_id,sum(ftf=1) as points,count(distinct grid_reference) as geosquares
		FROM gridimage_search 
		WHERE moderation_status = 'geograph'
		GROUP BY user_id
		ORDER BY points desc"); 
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
		
		
		foreach ($ranks as $user_id => $rank) {
			$db->query("UPDATE user_stat_tmp 
			SET points_rank = $rank,
			points_rise = {$rise[$user_id]},
			geosquares = {$geosquares[$user_id]},
			geo_rank = {$granks[$user_id]},
			geo_rise = {$grise[$user_id]}
			WHERE user_id = $user_id");
		}
		$db->Execute("UPDATE user_stat_tmp SET points_rank=0,points_rise=0,geosquares=0,geo_rank=0,geo_rise=0 WHERE points_rank = 50000");
		
		
		$db->Execute("DROP TABLE IF EXISTS user_stat");
		$db->Execute("RENAME TABLE user_stat_tmp TO user_stat");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}