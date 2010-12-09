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

$USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = GeographDatabaseConnection(false); //forum is mainly on master anyway

if (!empty($_GET['user_id'])) {
	$u = intval($_GET['user_id']);
	dump_sql_table("select post_id,poster_name,poster_id,post_time,post_text,topic_id from geobb_posts where poster_id = $u order by post_id desc limit 20",'Latest Posts for user '.$u);
} else {
	dump_sql_table('select post_id,poster_name,poster_id,post_time,post_text,topic_id,count(*) as num_posts from geobb_posts group by poster_id desc limit 10','Latest First Posts');
}

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");
	
	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";
	
	do {
		foreach ($row as $key => $value) {
			printf("<pre style=display:inline><b>%20s</b></pre> ",$key);
			if ($key == 'poster_name') {
				print "<a href=\"/finder/discussions.php?q=".urlencode($value)."\">".htmlentities($value)."</a>";

			} else if ($key == 'poster_id') {
				print "<a href=\"?user_id=$value\">$value</a>";

			} else if ($key == 'topic_id') {
				print "<a href=\"/discuss/index.php?&action=vpost&topic=$value&post={$row['post_id']}\">$value</a>";
			} else {
				print htmlentities($value);
			}
			print "<br>";
			$align = "right";
		}
		print "<HR/>";
	} while ($row = mysql_fetch_array($result,MYSQL_ASSOC));
}

	
