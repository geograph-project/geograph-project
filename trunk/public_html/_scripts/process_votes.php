<?php
/**
 * $Project: GeoGraph $
 * $Id: process_events.php 3442 2007-06-18 23:05:22Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/eventprocessor.class.php');

set_time_limit(5000); 


//need perms if not requested locally
if (isLocalIPAddress())
{
	$smarty=null;
}
else
{
	init_session();
	$smarty = new GeographPage;
	$USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

$wm = 1; #minimum votes required to be listed (//todo if change need to add a having to clause below!) 


$db->Execute("CREATE TEMPORARY TABLE vote_final AS SELECT MAX(vote_id) AS vote_id FROM vote_log GROUP BY type,id,user_id,ipaddr");
$db->Execute("UPDATE `vote_log` SET `final` = 0");
$db->Execute("UPDATE `vote_log`,vote_final SET vote_log.final = 1 WHERE `vote_log`.vote_id = vote_final.vote_id");


$db->Execute("TRUNCATE vote_stat");
$db->Execute("LOCK TABLE vote_stat WRITE, vote_log READ");

$types = $db->getAssoc("SELECT type,avg(vote) FROM vote_log WHERE vote > 0 AND `final` = 1 GROUP BY type ORDER BY NULL");

$db->Execute("ALTER TABLE vote_stat DISABLE KEYS");
	
foreach ($types as $type => $avg) {
	$db->Execute("INSERT INTO vote_stat
		SELECT 
			type,
			id,
			COUNT(*) AS num,
			COUNT(DISTINCT user_id,ipaddr) AS users,
			AVG(vote) AS `avg`,
			STD(vote) AS `std`,
			(COUNT(*) / (COUNT(*)+$wm)) * AVG(vote) + ($wm / (COUNT(*)+$wm)) * $avg AS `baysian`, 
			SUM(vote=1) AS v1,
			SUM(vote=2) AS v2,
			SUM(vote=3) AS v3,
			SUM(vote=4) AS v4,
			SUM(vote=5) AS v5
		FROM vote_log
		WHERE type = '$type' AND vote > 0 AND `final` = 1
		GROUP BY id
		ORDER BY NULL");
	print "$type ";
}


$db->Execute("ALTER TABLE vote_stat ENABLE KEYS");

$db->Execute("UNLOCK TABLES");

print "<hr/>done";
?>
