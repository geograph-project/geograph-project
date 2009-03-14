<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Ian Rutson (ian@rutson.com)
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



//Experimental MemoryMap export for the geograph project



require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
init_session();



$smarty = new GeographPage;
$square = new GridSquare;


if(isset($_POST['gridsquare'])||isset($_POST['getall'])) {

	if (!isset($_POST['getall']))
		$prefix = $_POST['gridsquare'];
	else
		$prefix = '';

	if (strlen($prefix) == 2 || strlen($prefix) == 1) {
		if(!($error = makefile($prefix))) {
			//Let the browser know what to expect
			$csvfilename="geograph".$prefix.".csv";
			$len  =(int) filesize("memorymap/$csvfilename");
			header("Content-type: text/plain");
			header("Content-Disposition: attachment; filename=\"$csvfilename\"");
			header("Content-length: $len");
			@readfile("memorymap/$csvfilename");
			die();
		}
		else{
			$smarty->assign('errormsg', $error);
		}
	} else {
		$smarty->assign('errormsg', "Please choose a grid square");
	}
}


if(isset($_GET['getbmp'])) {
		header('Content-type: image/bmp');
		header("Content-Disposition: attachment; filename=\"geograph.bmp\"");
		@readfile("memorymap/geograph.bmp");
		die();
}

$smarty->assign('prefixes', $square->getGridPrefixes());

$smarty->assign('filesize', round(@filesize("memorymap/geograph.csv")/1024));

//lets find some recent photos
new RecentImageList($smarty);

$smarty->display('memorymap.tpl');

function makefile($prefix='TQ') {

	//assign local filename
	$csvfilename = 'memorymap/geograph'.$prefix.'.csv';

	//get age of file, if new enough, go back
	if(file_exists($csvfilename)) {
		$fileage= time() - filemtime($csvfilename);
		//If it's new enough already, just serve it as is (TIME HERE IN SECONDS)
		if($fileage < 3600)	return '';
	}

	//OK, file must be too old, or not exist, let's make a shiny new one
	

	if(file_exists($csvfilename))
		if(!@unlink($csvfilename))
			return "Something is wrong, could not delete $csvfile.".
					" Click <a href=\"$csvfilename\">here to try to download a stale copy";

	if(!$csvfile=fopen($csvfilename,'w'))
		return "Something is wrong, could not open $csvfile for writing.";

	$images=new ImageList;

	$recordSet=& $images->getRecordSetbyPrefix($prefix);
	
	//Memory Map header, define geograph.bmp as icon #800
	fwrite($csvfile, "IC01,800,\"geograph.bmp\"\n");

	while (!$recordSet->EOF) 
	{
		$image = $recordSet->fields;

		//limit decimal places
		$lat   = sprintf("%.6f",$image['wgs84_lat']);
		$long  = sprintf("%.6f",$image['wgs84_long']);

		//avoid problems with stray commas
		if (strpos($image['title'],',') !== FALSE || strpos($image['title'],'"') !== FALSE) {
			$image['title'] = '"'.str_replace('"', '""', $image['title']).'"';
		}

		//WP04,Lat,Lon,Symbol,Name,Comment,File,Radius,Display,Unique,Visible,Locked,Category,Circle
		fwrite($csvfile, "WP04,$lat,$long,800,$image[title],,http://{$_SERVER['HTTP_HOST']}/photo/$image[gridimage_id],0,1,$image[grid_reference],1,1,Geographs,0\r\n");
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	fclose($csvfile);
	return '';
}


?>
