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

$db = GeographDatabaseConnection(true);

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

$sql = "select id,if(title='', concat(location,' from ',start),title) as title,views,date(first_view) as first,date(last_view) as last from geotrips where uid = {$USER->user_id} order by last_view desc";

print "<p>We only started counting views for geotrips in March 2020. Views prior to that are NOT counted. This in theory is only people, but some counts may be inflated by bots viewing the page. Note these figures including you (and moderators) viewing the trip as well.</p>";

dump_sql_table($sql,'Views Since 23rd March 2020');


$smarty->display('_std_end.tpl');

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;
	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";

        if ($recordSet->EOF) {
                print "0 rows - you probably havent submitted any geotrips";
                return;
        }

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\"><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD><TBODY>";
	$keys = array_keys($row);
	$first = $keys[0];
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR>";
 		foreach ($row as $key => $value) {
			if ($key == 'id') {
				print "<TD SORTVALUE=$value><a href=\"/geotrips/{$value}\">".htmlentities($value)."</a></TD>";
			} elseif (is_numeric($value)) {
				print "<TD ALIGN=right>".htmlentities($value)."</TD>";
			} else {
				print "<TD>".htmlentities($value)."</TD>";
			}
		}
		print "</TR>";
                $recordSet->MoveNext();
	}

	print "</TR></TBODY></TABLE>";
}

