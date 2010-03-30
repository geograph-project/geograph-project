<?php
/**
 * $Project: GeoGraph $
 * $Id: moversboard.php 3001 2007-01-22 19:30:41Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$USER->hasPerm("admin") || $USER->hasPerm("ticketmod") || $USER->mustHavePerm("moderator");



$template='games_approve.tpl';



$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed'); 

if (isset($_POST['submit']) && count($_POST['a'])) {
	$y = $n = array(); 
	foreach ($_POST['a'] as $id => $v) {
		if ($v) {
			$y[] = $id;
		} else {
			$n[] = $id;
		}
	}
	if (count($y)) {
		$names = $db->GetCol("select username from game_score where game_score_id in (".implode(',',$y).")");
		$db->Execute("update game_score set approved = 1 where username in ('".implode("','",array_map("mysql_real_escape_string", $names))."')");
	} 

	if (count($n)) {
		$names = $db->GetCol("select username from game_score where game_score_id in (".implode(',',$n).")");
		$db->Execute("update game_score set approved = -1 where username in ('".implode("','",array_map("mysql_real_escape_string", $names))."')");
	}
}

/////////////

$sql="select game_score_id,username from game_score where approved = 0 group by username";

$names=$db->GetAssoc($sql);



$smarty->assign_by_ref('names', $names);
	


$smarty->display($template, $cacheid);

	
?>
