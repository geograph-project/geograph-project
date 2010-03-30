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

dump_sql_table('select rendered,level,url,filename,count(*),min(ts),max(ts) from kmlcache group by  rendered,level order by level,rendered','KML - Superlayer Rendering Progress');

dump_sql_table('select now()','');

dump_sql_table("select substring(ts,1,10),count(*),count(distinct level),sum(rendered=1),sum(rendered=0) from kmlcache group by substring(ts,1,10) order by ts desc limit 20",'Last few days');


function dump_sql_table($sql,$title,$autoorderlimit = false) {
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");
	
	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";
	
	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR>";
	do {
		print "<TR>";
		$align = "left";
		foreach ($row as $key => $value) {
			print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			$align = "right";
		}
		print "</TR>";
	} while ($row = mysql_fetch_array($result,MYSQL_ASSOC));
	print "</TR></TABLE>";
}

	
?>
