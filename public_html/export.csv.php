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

  $smarty = new GeographPage;
  dieUnderHighLoad();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

include('geograph/export.inc.php');

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph.csv\"");

if (isset($_GET['headers']) && $_GET['headers'] == 'lower') {
	echo str_replace(array('photographer','easting','northing','figures','view_direction','image_'),array('photo','e','n','figs','dir',''),
		preg_replace('/[^\w,]+/','_',
			strtolower($csvhead)))."\n";
} else {
	echo "$csvhead\n";
}

if ( isset($_GET['coords'])) {
	require_once('geograph/conversions.class.php');
	$conv = new ConversionsLatLong;
}
$counter = -1;
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	if (strpos($image['title'],',') !== FALSE || strpos($image['title'],'"') !== FALSE)
		$image['title'] = '"'.str_replace('"', '""', $image['title']).'"';
	if (strpos($image['imageclass'],',') !== FALSE || strpos($image['imageclass'],'"') !== FALSE)
		$image['imageclass'] = '"'.str_replace('"', '""', $image['imageclass']).'"';
	echo "{$image['gridimage_id']},{$image['title']},{$image['grid_reference']},{$image['realname']},{$image['imageclass']}";
	if (!empty($_GET['desc'])) {
		if (empty($image['comment'])) {
			echo ',';
		} else {
			echo ',"'.str_replace('"', '""', $image['comment']).'"';
		}
	}
	if (!empty($_GET['thumb'])) {
		$gridimage->fastInit($image);
		echo ','.$gridimage->getThumbnail(120,120,true);
	}
	if (!empty($_GET['gr'])) {
		if (empty($image['nateastings'])) {
			echo ",{$image['grid_reference']}";
		} else {
			$gridimage->grid_square = new GridSquare();
			$gridimage->grid_square->natspecified = 1;
			$gridimage->grid_square->natgrlen=$gridimage->natgrlen;
			$gridimage->grid_square->nateastings=$gridimage->nateastings;
			$gridimage->grid_square->natnorthings=$gridimage->natnorthings;
			$gridimage->grid_square->reference_index=$gridimage->reference_index;
			echo ",".$gridimage->getSubjectGridref();
			$gridimage->subject_gridref = ''; // so it not reused!
		}
		if (!empty($_GET['ppos'])) {
			echo ",".$gridimage->getPhotographerGridref();
			$gridimage->photographer_gridref = ''; // so it not reused!
		}
	} elseif (!empty($_GET['en'])) {
		if (empty($image['nateastings']) && isset($_GET['coords'])) {
			list($e,$n) = $conv->internal_to_national($image['x'],$image['y'],$image['reference_index']);
			
			echo ",$e,$n,{$image['natgrlen']}";
		} else {
			echo ",{$image['nateastings']},{$image['natnorthings']},{$image['natgrlen']}";
		}
		if (!empty($_GET['ppos']))
			echo ",{$image['viewpoint_eastings']},{$image['viewpoint_northings']},{$image['viewpoint_grlen']}";
	} elseif (!empty($_GET['ll']))
		echo ",{$image['wgs84_lat']},{$image['wgs84_long']}";
	if (!empty($_GET['taken']))
		echo ",{$image['imagetaken']}";
	if (!empty($_GET['dir']))
		echo ",{$image['view_direction']}";
	if (!empty($_GET['hits']))
		echo ",{$image['hits']}";

	echo "\n";
	$recordSet->MoveNext();
	$counter++;
}
$recordSet->Close();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#
	
$db = GeographDatabaseConnection(false);	
	
//todo
//if (isset($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
// or if (isset($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
// ... find all rejected (at first glance think only need ones submitted BEFORE but moderated AFTER, as ones submitted after wont be included!) - either way shouldnt harm to include them anyway!
	
$sql = "UPDATE apikeys SET accesses=accesses+1, records=records+$counter,last_use = NOW() WHERE `apikey` = '{$_GET['key']}'";

$db->Execute($sql);	

?>
