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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;
$mapmaker=new MapMaker;


$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;


preg_match('/^([A-Z]{1,2})(\d)(\d)$/',$_GET['gridref'], $matches);
list($gridref,$square,$offset_x,$offset_y) = $matches;

$smarty->display('_std_begin.tpl');

$sql="select origin_x,origin_y,width,height ".
"from gridprefix ".
"where prefix = '$square' limit 1";
	
$recordSet = $db->Execute($sql);

	$origin_x=$recordSet->fields['origin_x'];
	$origin_y=$recordSet->fields['origin_y'];
	$width=$recordSet->fields['width'];
	$height=$recordSet->fields['height'];
	
	$left=$origin_x + $offset_x*10;
	$right=$origin_x + $offset_x*10 + 10;
	$top=$origin_y + $offset_y*10 + 10;
	$bottom=$origin_y + $offset_y*10;




$sql="select x,y,gridsquare_id from gridsquare where ".
	"(x between $left and $right) and ".
	"(y between $bottom and $top) ".
	"and imagecount > 0";


$recordSet = $db->Execute($sql);
while (!$recordSet->EOF) 
{
	$x=$recordSet->fields[0];
	$y=$recordSet->fields[1];

	$images[$x][$y] = $recordSet->fields[2];
	
	$recordSet->MoveNext();
}
$recordSet->Close(); 

print "<h2>$gridref</h2>";

print "<p>Click a an area with pictures to zoom in, or <a href='map-100.php?gridref=".substr($gridref,0,2)."'>Zoom Out</a></p>";

print "<table cellspacing=0 cellpadding=0>";
print "<tr><td>&nbsp;</td>";
print "<td align=center>".get_direction($left,$bottom,0,10,'North')."</td>";
print "<td>&nbsp;</td></tr>";
print "<tr><td valign=middle>".get_direction($left,$bottom,-10,0,'West')."</td>";

	$imgfile=$mapmaker->build($left, $bottom, $right, $top,false,40);	
print "<td><table cellspacing=0 cellpadding=0 width=400 height=400 BACKGROUND='$imgfile'>";
$n = 9;
for($y= $top-1; $y >= $bottom; $y--) {
	print "<tr>";
	$e = 0;
	for($x= $left; $x < $right; $x++) {
		$gridref=sprintf("%s%d%d%d%d", $square,$offset_x,$e,$offset_y,$n);
		if ($id = $images[$x][$y]) {
			
			$recordSet = &$db->Execute("select gridimage.*,user.realname,user.email,user.website ".
						"from gridimage ".
						"inner join user using(user_id) ".
						"where gridsquare_id=$id order by seq_no limit 1");
			if (!$recordSet->EOF) {
				$image=new GridImage;
				$image->loadFromRecordset($recordSet);
			}
			$recordSet->Close(); 
		
		
			echo "<td><a href='browse.php?gridref=$gridref'>".$image->getSquareThumbnail(40, 40)."</a></td>";
		} else {
			echo "<td alt='$gridref' width=40 height=40>&nbsp;</td>";		
		}
		$e++;
	}
	print "</tr>";
	$n--;
}
$recordSet->Close(); 
print "</table></td>";

print "<td valign=middle>".get_direction($left,$bottom,10,0,'East')."</td></tr>";
print "<tr><td>&nbsp;</td>";
print "<td align=center>".get_direction($left,$bottom,0,-10,'South')."</td>";
print "<td>&nbsp;</td></tr>";
print "</table>";


$smarty->display('_std_end.tpl');

function get_direction($ox,$oy,$dx,$dy,$title) {
	global $db;
	$sql="select grid_reference ".
	"from gridsquare ".
	"where x = ".($ox+$dx)." and y = ".($oy+$dy);
	$grid_reference1 = $db->GetOne($sql);

	if ($grid_reference1) {
		$grid_reference1 = substr($grid_reference1,0,3).substr($grid_reference1,4,1);
		return "<a href='?gridref=$grid_reference1'>$title</a>";
	} else {
		return "&nbsp;";
	}
}
	
?>
