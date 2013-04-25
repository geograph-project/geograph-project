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
class RebuildImageRating extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();

		#######
		# settings

		$geosquares_shift = 10; #15;
		$geosquares_scale = .25; #.2;
		$geosquares_exp   = 4;

		$votecount_scale = 1.0;#.8;
		$votecount_exp   = 4;

		$dist_factor     = 1.0;
		$dist_threshold  = 1.5;
		$dist_scale      = 1.0;

		$uservotes_scale = 0.4; #.2

		#$good_distributions = array( 1 => .01, 2 => .04, 3 => .45, 4 => .45, 5 => .05 );
		$good_distributions = array( 1 => .05, 2 => .22, 3 => .46, 4 => .22, 5 => .05);
		$gdweight = .3;

		#######
		# calculate weights

		$db->Execute('DROP TABLE IF EXISTS user_vote_stat_tmp');
		$db->Execute('CREATE TABLE user_vote_stat_tmp LIKE user_vote_stat;');

		$db->Execute('INSERT INTO user_vote_stat_tmp (user_id, type, votes, votes1, votes2, votes3, votes4, votes5)
		SELECT gv.user_id, gv.type, COUNT(*), SUM(gv.vote=1), SUM(gv.vote=2), SUM(gv.vote=3), SUM(gv.vote=4), SUM(gv.vote=5)
		FROM gridimage gi INNER JOIN gridimage_vote gv ON (gi.gridimage_id=gv.gridimage_id)
		WHERE gi.user_id != gv.user_id GROUP BY gv.user_id, gv.type;');

		$db->Execute('DROP TABLE IF EXISTS user_vote_stat_old;');
		$db->Execute('RENAME TABLE user_vote_stat TO user_vote_stat_old, user_vote_stat_tmp TO user_vote_stat;');
		$db->Execute('DROP TABLE IF EXISTS user_vote_stat_old;');

		#######
		# calculate gridimage_vote.weight: less weight for
		# * contributors having few geosquares (i.e. only votes of real contributors are taken into account)
		# * contributors who don't vote a lot (i.e. favour contributors who make good statistics)
		# * contributors who only give maximal/minimal votes (i.e. contributors who allow for differentiated statistics)

		$votecount=$db->GetAssoc("SELECT type,SUM(votes),SUM(votes1),SUM(votes2),SUM(votes3),SUM(votes4),SUM(votes5) FROM user_vote_stat GROUP BY type;");
		$types = array_keys($votecount);
		$dist = array();
		foreach($types as $type) {
			$dist[$type] = array();
			if ($votecount[$type][0]) {
				for ($vote = 1; $vote <= 5; ++$vote) {
					$dist[$type][$vote] = $good_distributions[$vote]*$gdweight + (1-$gdweight)*$votecount[$type][$vote]/$votecount[$type][0];
				}
			}
		}

		foreach($types as $type) {
			$recordSet = &$db->Execute("SELECT user_id,votes,votes1,votes2,votes3,votes4,votes5,geosquares FROM user_vote_stat LEFT JOIN user_stat USING(user_id) WHERE type='$type'");
			while (!$recordSet->EOF) {
				$geosquares = $recordSet->fields[7];
				if (is_null($geosquares)) {
					$geosquares = 0;
				}
				$userweight = pow(0.5*(1.0+tanh($geosquares_scale*($geosquares-$geosquares_shift))), $geosquares_exp);
				for ($vote = 1; $vote <= 5; ++$vote) {
					if (!$recordSet->fields[1+$vote]) {
						continue;
					}
					$weight = $userweight;
					$perc_good = $dist[$type][$vote];
					$perc = $recordSet->fields[1+$vote]/$recordSet->fields[1];
					$ratio = $perc_good/$perc;
					$weight *= $dist_factor * exp(log($dist_threshold) * tanh($dist_scale*log($ratio)));

					$weight *= tanh($recordSet->fields[1]*$uservotes_scale);

					$db->Execute("UPDATE gridimage_vote SET weight=$weight WHERE user_id={$recordSet->fields[0]} AND type='$type' AND vote=$vote");
				}
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}

		#######
		# calculate image ratings

		$db->Execute('DROP TABLE IF EXISTS gridimage_rating_tmp;');
		$db->Execute('CREATE TABLE gridimage_rating_tmp LIKE gridimage_rating;');

		$db->Execute('INSERT INTO gridimage_rating_tmp (gridimage_id, type, rating, votes, weighted_votes)
		SELECT gv.gridimage_id, gv.type, SUM((gv.vote-3)*gv.weight)/SUM(gv.weight),COUNT(*),SUM(gv.weight)
		FROM gridimage gi INNER JOIN gridimage_vote gv ON (gi.gridimage_id=gv.gridimage_id)
		WHERE gi.user_id != gv.user_id AND gv.weight > 0 GROUP BY gv.gridimage_id, gv.type;');

		# scale rating with pow(tanh(weighted_votes*$votecount_scale), $votecount_exp)
		$db->Execute("UPDATE gridimage_rating_tmp SET rating=rating*POWER(1-2.0/(1+EXP(2*$votecount_scale*weighted_votes)),$votecount_exp)");

		$db->Execute('DROP TABLE IF EXISTS gridimage_rating_old;');
		$db->Execute('RENAME TABLE gridimage_rating TO gridimage_rating_old, gridimage_rating_tmp TO gridimage_rating;');
		$db->Execute('DROP TABLE IF EXISTS gridimage_rating_old;');

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
