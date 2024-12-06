<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

//alow the user to login!
if (isset($_REQUEST['login']) && !$USER->hasPerm('basic')) {
        $USER->login(false);

	//the above has either displayed or login form - or they has actully logged them in!
	if (!$USER->registered)
		exit;
}

$smarty->display('_std_begin.tpl');


$db = GeographDatabaseConnection(true);

?>

<h2>Can you locate this place on the map?</h2>

<p>This is a simple game, where you try to see how many places from around the British Isles you can locate on a map</p>

<div class=interestBox>
<p style=font-size:x-large>&middot; <a href="locate-places-play.php">Play Now</a> - play as long as you like, come back to see your ongoing score</p>

<p><i>If playing will all places is too hard, can try <a href="locate-places-play.php?city=1">just with Cities</a></i>
</div>


<? if ($USER->registered) {
	print "<p>As a registered user, you guesses will be stored on your profile and will provide a score below. The data will be remembered long term, and play over multiple sessions.";

	$where = "user_id = ".$USER->user_id;

} else {
	print "<p>The score will only be tracked for this session. <a href=?login=1>Login</a> to save your score on your profile";

	$where = "session = ".$db->Quote(session_id());
}

print "<hr>";

if (!empty($_GET['all'])) {
	$where = "1";
	print "<a href=\"?\">Your Scores</a> / <b>Everybody</b>";

	print "<p>Note that while showing everyones score in aggregate here, we wont share individual scores. If interest might produce a scoreboard to see score of others, but it will be opt in";

	$label = "Total Points";
} else {
	print "<b>Your Scores</b> / <a href=\"?all=1\">Everybody</a>";

	$label = "Your Points";
}

$rows = $db->getAll("SELECT id,guess,distance,lat,def_nam,full_county,f_code FROM locate_log INNER JOIN gaz_locate USING (id) WHERE $where");

if (empty($rows)) {
	print "<p>No scores yet";
} else {
	$stat = array('points'=>0,'total'=>0);
	foreach ($rows as $row) {
		$points = 0;
		if ($row['f_code'] == 'F') {
			if ($row['guess'] == 'bogus') {
				$points = 4;
			} elseif ($row['guess'] == 'heard') {
				$points = -1; //eeek!
			}
		//} if guess = 'heard' and guess='never' doesnt get any score!
		} elseif (!empty($row['distance'])) {
			if ($row['distance'] < 10000)
				$points = 4;
			elseif ($row['distance'] < 50000)
				$points = 3;
			elseif ($row['distance'] < 100000)
				$points = 2;
			elseif ($row['distance'] < 200000)
				$points = 1;
		}
		$stat['points']+=$points;
		$stat['total']+=4; //its out of 4?

		$key = $row['f_code'];
			if ($key == 'C')
				$name = 'GB Cities';
			elseif ($key == 'T')
				$name = 'GB Towns';
			elseif ($key == 'O')
				$name = 'GB Villages';
			elseif ($key == 'R')
				$name = 'Fake Places';
			elseif ($key == 'City')
				$name = 'Ireland Cities';
			elseif ($key == '' || $key == 'District')
				$name = 'Ireland Places';
			elseif (preg_match('/Town[_-](\d+)/',$key,$m)) {
				if (intval($m[1]) < 4)
					$name = 'Ireland Towns';
				else
					$name = 'Ireland Places';
			} else {
				$name = 'Other Places';
			}


		@$stat[$name]['points']+=$points;
		@$stat[$name]['total']+=4;
	}

	printf('<h3>%s: %d points from %d guesses. About <b>%.1f%%</b> overall</h3>',$label, $stat['points'],  $stat['total']/4, $stat['points']/$stat['total']*100);

	if (!empty($stat)) {
		ksort($stat);
		print "<ul>";
 		foreach($stat as $key => $value) {
			if (is_array($value)) {
				printf('<li><b>%s</b>: %d points from %d guesses. About <b>%.1f%%</b> overall',$key, $value['points'],  $value['total']/4, $value['points']/$value['total']*100);
				print "<br><br>";
			}
		}
		print "</ul>";
	}
}

?>
<hr>
<h3>Points</h3>
<ul>
	<li>Guess within 10km: 4 points!
	<li>Guess within 50km: 3 points
	<li>Guess within 100km: 2 points
	<li>Guess within 200km: 1 points
	<li>Correctly identifing a fake place as bogus: 4 points!
	<li>Offering a Guess for a fake place: -1 points!
	<li>Unable to offer a guess: 0 points
</ul>

Note: We are are only including places that seem to be unabigious, excludes where there are multiple places with the same name.
We only using a random sample of small places, otherwise would be swamped with small places. The total playable dataset is currently 3013 places!
<?


$smarty->display('_std_end.tpl');


