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

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$db->Execute("SET SESSION max_statement_time = 3");


print "<h2>Your Recent Submissions - live/no caching <sub><a href=?>refresh</a></sub></h2>";


print "<table cellspacing=0 border=1 cellpadding=3>";

if (!empty($CONF['use_insertionqueue']))
	dump_rows("select gridimage_id,title,submitted,moderation_status from gridimage_queue where user_id = {$USER->user_id}", false);

dump_rows("select gridimage_id,title,submitted,moderation_status from gridimage where user_id = {$USER->user_id} ORDER BY gridimage_id DESC LIMIT 20", true);

print "</table>";

function dump_rows($sql, $full = false) {
	global $db;
	static $head=null;

	$data = $db->getAll($sql);

	if (!empty($data)) {
		if (empty($head)) {
			$head = reset($data);
			print "<THEAD><TR>";
			foreach ($head as $key => $value) {
				print "<TH>$key</TH>";
			}
			print "</TR></THEAD><TBODY>";
		}

		foreach ($data as $row) {
			$row['title'] = htmlentities2($row['title']);
			print "<tr><td>".implode('</td><td>',$row)."</td>";
			if ($full) {
				print "<td><a href=\"/photo/{$row['gridimage_id']}\">Photo Page</a>, <a href=\"/editimage.php?id={$row['gridimage_id']}\">Edit Page</a>";
			}
		}

	} elseif($full) {
		$w = $db->getAll("SHOW WARNINGS");
		if (!empty($w)) { //almost certainly terminated)
			print "<tr><td colspan=4>Unable to load images at this time. Wait two minutes, then <a href=?>Click here</a> to refresh</td>";
		}
	}

}


if (!empty($GLOBALS['DSN_READ']) && $GLOBALS['DSN'] != $GLOBALS['DSN_READ']) {

        $db=NewADOConnection($GLOBALS['DSN_READ']);

	if (!empty($db)) {
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
                $row = $db->getRow("SHOW SLAVE STATUS");
                if (!empty($row)) { //its empty if we actully connected to master!
			if (is_null($row['Seconds_Behind_Master'])) {
				print "<h3>Replication Status: Offline.</h3>";
				print "<p>Because replication is offline, some parts of the the site may not be showing recent updates.</p>";

			} else {
				print "<h3>Current Replication Lag: {$row['Seconds_Behind_Master']} Seconds. </h3>";
				if ($row['Seconds_Behind_Master']>1)
					print "<p>This is roughly long a delay being a change been made in the primary database, and the data replicated and displayed around the site.";
			}
		}
	}
}


$smarty->display('_std_end.tpl');


