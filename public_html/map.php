<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
require_once('geograph/mapmaker.class.php');
init_session();

$smarty = new GeographPage;
$mapmaker=new MapMaker;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;

$smarty->display('_std_begin.tpl');


print "<table><tr><td>";
output_map(2);
print "</td><td>";
output_map(1);
print "</td></tr></table>";


function output_map($reference_index = 1) {
	global $db,$mapmaker;

	$sql="select prefix,origin_x,origin_y,width,height,landcount ".
	"from gridprefix ".
	"where reference_index = $reference_index ".
	"order by origin_y desc, origin_x";

	print "<table cellspacing=0 cellpadding=0 BGCOLOR=#0000C8>";

	$recordSet = $db->Execute($sql);

	while (!$recordSet->EOF) {
		$prefix=$recordSet->fields['prefix'];
		$x=$recordSet->fields['origin_x'];
		$y=$recordSet->fields['origin_y'];
		$w=$recordSet->fields['width'];
		$h=$recordSet->fields['height'];
		$imgfile=$mapmaker->build($x, $y, $x+$w, $y+$h,true,0.5,true,$reference_index);

		if ($last != $y) {
			if ($last) 
				print "</tr>";
			print "<tr>";
		}
		$last = $y;
		if ($recordSet->fields['landcount'] > 0) {
			echo "<td><a href='map-100.php?gridref=$prefix'><img src=\"$imgfile\" alt='$prefix' border=0 width=50 height=50></a></td>";
		} else {
			echo "<td><img src=\"$imgfile\" alt='$prefix' border=0 width=50 height=50></td>";
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	if ($last) 
		print "</tr>";
	print "</table>";
}

$smarty->display('_std_end.tpl');


	
?>
