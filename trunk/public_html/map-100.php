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

$square = $_GET['gridref'];

$smarty->display('_std_begin.tpl');

$sql="select origin_x,origin_y,width,height,title ".
"from gridprefix ".
"where prefix = '$square' limit 1";
	
$recordSet = $db->Execute($sql);

	$origin_x=$recordSet->fields['origin_x'];
	$origin_y=$recordSet->fields['origin_y'];
	$width=$recordSet->fields['width'];
	$height=$recordSet->fields['height'];
	$title=$recordSet->fields['title'];
	
	$left=$origin_x;
	$right=$origin_x + $width;
	$top=$origin_y + $height-11;
	$bottom=$origin_y-1;

print "<h2>$title</h2>";

print "<p>Click a an area with pictures to zoom in, or <a href='map.php'>Zoom Out</a></p>";

print "<table cellspacing=0 cellpadding=0>";
print "<tr><td>&nbsp;</td>";
print "<td align=center>".get_direction($origin_x,$origin_y,0,$height,'North')."</td>";
print "<td>&nbsp;</td></tr>";
print "<tr><td valign=middle>".get_direction($origin_x,$origin_y,-$width,0,'West')."</td>";
	
print "<td><table cellspacing=0 cellpadding=0>";
$n = 9;
for($y= $top; $y >= $bottom; $y-=10) {
	print "<tr>";
	$e = 0;
	for($x= $left; $x < $right; $x+=10) {
		$gridref=sprintf("%s%d%d", $square,$e,$n);
		$imgfile=$mapmaker->build($x, $y, $x+10, $y+10,false,4);
		echo "<td width=40 height=40><a href='map-10.php?gridref=$gridref'><img src=\"$imgfile\" alt='$gridref' border=0 width=40 height=40></a></td>";
		$e++;
	}
	print "</tr>";
	$n--;
}
$recordSet->Close(); 
print "</table></td>";

print "<td valign=middle>".get_direction($origin_x,$origin_y,$width,0,'East')."</td></tr>";
print "<tr><td>&nbsp;</td>";
print "<td align=center>".get_direction($origin_x,$origin_y,0,-$height,'South')."</td>";
print "<td>&nbsp;</td></tr>";
print "</table>";


$smarty->display('_std_end.tpl');

function get_direction($ox,$oy,$dx,$dy,$title) {
	global $db;
	$sql="select prefix ".
	"from gridprefix ".
	"where origin_x = ".($ox+$dx)." and origin_y = ".($oy+$dy).
	" and landcount > 0";
	$prefix = $db->GetOne($sql);

	if ($prefix) {
		return "<a href='?gridref=$prefix'>$title</a>";
	} else {
		return "&nbsp;";
	}
}
	
?>
