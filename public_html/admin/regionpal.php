<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

echo <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Geograph: Region colour calculator</title>
</head>
<body>
<h1>Geograph: Region colour calculator</h1>
EOF;

if (isset($_GET['level']) && is_numeric($_GET['level']) && isset($_GET['calcpal'])) {
	set_time_limit(3600*24);
	$level = intval($_GET['level']);
	$newpal = isset($_GET['newpal']) && $_GET['newpal']=='1';
	if ($newpal) {
		echo "<p>reassign colours for level $level</p>";
		flush();
		#$cids = $db->GetArray("SELECT DISTINCT(community_id) FROM gridsquare_percentage WHERE level='$level'");
		$cids = $db->GetCol("SELECT DISTINCT(community_id) FROM gridsquare_percentage WHERE level='$level'");
		if ($cids === false) die('GetCol failed');
		echo "<p>calculating neighbours</p>";
		flush();
		$neighbours = array();
		$cidsleft = array();
		foreach ($cids as $cid) {
			#$sql = "SELECT gp2.community_id FROM gridsquare_percentage gp LEFT JOIN gridsquare_percentage gp2 USING(gridsquare_id) WHERE gp.percent>0 AND gp.level='$level' AND gp.community_id='$cid' AND gp2.percent>0 AND gp2.level='$level' AND gp2.community_id!='$cid' GROUP BY gp2.community_id";
			$sql = "SELECT DISTINCT(gp2.community_id) FROM gridsquare_percentage gp LEFT JOIN gridsquare_percentage gp2 USING(gridsquare_id) WHERE gp.percent>0 AND gp.level='$level' AND gp.community_id='$cid' AND gp2.percent>0 AND gp2.level='$level' AND gp2.community_id!='$cid'";
			$neighbours[$cid] = $db->GetCol($sql);
			if ($neighbours[$cid] === false) die ('GetCol failed');
			echo "<p>$cid: ".implode(", ", $neighbours[$cid])."</p>";
			flush();
			$cidsleft[$cid] = count($neighbours[$cid]);
		}
		arsort($cidsleft);
		$colours = array();
		$nextcids = array();
		echo "<p>calculating colours</p>";
		while (count($cidsleft) || count($nextcids)) {
			if (count($nextcids)) {
				reset($nextcids);
				$cid = key($nextcids);
				unset($nextcids[$cid]);
			} else {
				reset($cidsleft);
				$cid = key($cidsleft);
				unset($cidsleft[$cid]);
			}
			#calc pal for $cid
			$coloursleft = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15); #used as set. values not needed
			foreach ($neighbours[$cid] as $ncid) {
				if (array_key_exists($ncid, $colours) && array_key_exists($colours[$ncid], $coloursleft)) {
					unset($coloursleft[$colours[$ncid]]);
				}
			}
			if (count($coloursleft)) {
				reset($coloursleft);
				$colour = key($coloursleft);
				echo "<p>colour for cid $cid: $colour</p>";
				flush();
			} else {
				echo "<p>could not find colour for cid $cid</p>";
				flush();
				$colour = -1; #FIXME?
			}
			$colours[$cid] = $colour;
			#move neighbours to end of list (preferring the ones with many neighbours)
			$neighbourcids = array();
			foreach ($neighbours[$cid] as $ncid) {
				if (array_key_exists($ncid, $cidsleft)) {
					$neighbourcids[$ncid] = $cidsleft[$ncid];
					unset($cidsleft[$ncid]);
				}
			}
			arsort($neighbourcids);
			#foreach($neighbours[$cid] as $ncid) {
			#	$cidsleft[$ncid] = $neighbourcids[$ncid];
			#}
			#$cidsleft += $neighbourcids;
			$nextcids += $neighbourcids;
		}
		echo "<p>assigning colours</p>";
		foreach($colours as $cid=>$colour) {
			$mp = $colour + 1;
			$sql = "UPDATE loc_hier SET mappal='$mp' WHERE level='$level' AND community_id='$cid'";
			echo "<p>$sql</p>";
			$db->execute($sql);
		}
	}

	echo "<p>calculating colours for level $level</p>";
	flush();
	$db->execute("DELETE FROM gridsquare_mappal WHERE level='$level'");
	$db->execute("INSERT INTO gridsquare_mappal (gridsquare_id, level, mappal, norm)
		SELECT gs.gridsquare_id, gp.level,COALESCE(SUM(gp.percent*lh.mappal)/100.,0),COALESCE(SUM(gp.percent)/100.,0)
		FROM gridsquare gs LEFT JOIN gridsquare_percentage gp USING(gridsquare_id) LEFT JOIN loc_hier lh USING(level,community_id)
		WHERE level='$level' GROUP BY gridsquare_id;");
	echo "<p>done.</p>";

} else {
	echo <<<EOF
		<form action="/admin/regionpal.php" method="get">
		<p><label for="level">Level:</label> <input name="level" id="level" type="text" size="4" maxlength="4"></p>
		<p><input type="checkbox" name="newpal" id="newpal"  value="1"> <label for="newpal">reassign colours</label></p>
		<p><input type="submit" name="calcpal" value="ok"></p>
		</form>
EOF;
}
echo <<<EOF
</body>
</html>
EOF;

?>
