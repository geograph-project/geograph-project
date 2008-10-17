<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$sql = array();

$sql['submitting'] = "select user_id,substring(submitted,1,13) as hour from gridimage group by user_id,substring(submitted,1,13)";

$sql['moderatoring'] = "select moderator_id,substring(moderated,1,13) as hour from gridimage group by moderator_id,substring(moderated,1,13)";
$sql['modinginprocess'] = "select user_id,substring(lock_obtained,1,13) as hour from gridsquare_moderation_lock group by user_id,substring(lock_obtained,1,13)";

$sql['modinginprocess2'] = "select user_id,substring(lock_obtained,1,13) as hour from gridimage_moderation_lock group by user_id,substring(lock_obtained,1,13)";

$sql['ticketing'] = "select user_id,substring(suggested,1,13) as hour from gridimage_ticket group by user_id,substring(suggested,1,13)";
$sql['ticketclosing'] = "select moderator_id,substring(updated,1,13) as hour from gridimage_ticket where status = 'closed' group by moderator_id,substring(updated,1,13)";

$sql['ticketcooment'] = "select user_id,substring(added,1,13) as hour from gridimage_ticket_comment group by user_id,substring(added,1,13)";

$sql['articles'] = "select modifier,substring(update_time,1,13) as hour from article_revisions group by modifier,substring(update_time,1,13)";

$sql['forumpost'] = "select poster_id,substring(post_time,1,13) as hour from geobb_posts group by poster_id,substring(post_time,1,13)";



$sql['forumread'] = "select user_id,substring(ts,1,13) as hour from geobb_lastviewed group by user_id,substring(ts,1,13)";



	$is = array();
	$hs = array();
	$bs = array();

foreach ($sql as $name => $s) {
	print "<h2>$name</h2>";
	
	$a = $db->getAll($s);

	
	foreach ($a as $row) {
		$is[$row['user_id']]++;
		$hs[$row['hour']]++;
		$bs["{$row['user_id']}:{$row['hour']}"]++;
	}
	
	print "men = ".count($is)."<br/>";
	print "hours = ".count($hs)."<br/>";
	print "man hours = ".count($bs)."<br/>";
	
}

	
?>
