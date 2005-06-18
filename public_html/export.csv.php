<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph.csv\"");

$db=NewADOConnection($GLOBALS['DSN']);

echo "Id,Name,Grid Ref,Submitter,Image Class";
if ($_GET['ll']) {
	echo ",Lat,Long";
	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	
	$sql_from = ",x,y,reference_index";	
} elseif ($_GET['en']) {
	echo ",Easting,Northing";
}
if ($_GET['thumb']) {
	require_once('geograph/gridimage.class.php');
	$gridimage = new GridImage;
	echo ",Thumb URL";
}
echo "\n";

$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,realname,imageclass,nateastings,natnorthings $sql_from ".
	"from user ".
	"inner join gridimage using(user_id) ".
	"inner join gridsquare using(gridsquare_id) ".
	"where moderation_status in ('accepted','geograph')");
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	if (strpos($image['title'],',') !== FALSE || strpos($image['title'],'"') !== FALSE) 
	{
		$image['title'] = '"'.str_replace('"', '""', $image['title']).'"';
	}
	if (strpos($image['imageclass'],',') !== FALSE || strpos($image['imageclass'],'"') !== FALSE) 
	{
		$image['imageclass'] = '"'.str_replace('"', '""', $image['imageclass']).'"';
	}
	echo "{$image['gridimage_id']},{$image['title']},{$image['grid_reference']},{$image['realname']},{$image['imageclass']}";
	if ($image['nateastings']) {
		if ($_GET['ll']) {
			list($lat,$long) = $conv->national_to_wgs84($image['nateastings'],$image['natnorthings'],$image['reference_index']);
			echo ",$lat,$long";
		} elseif ($_GET['en']) {
			echo ",{$image['nateastings']},{$image['natnorthings']}";
		}
	} elseif ($_GET['ll']) {
		list($lat,$long) = $conv->internal_to_wgs84($image['x'],$image['y'],$image['reference_index']);
		echo ",$lat,$long";
	}
	if ($_GET['thumb']) {
		$gridimage->fastInit($image);
		echo ','.$gridimage->getThumbnail(120,120,true);
	}
	echo "\n";
	$recordSet->MoveNext();
	$i++;
}
$recordSet->Close(); 
	
?>
