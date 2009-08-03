<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

    
    

//these are the arguments we expect
$param=array(
	'dir'=>'/home/geograph',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

	'cols'=>'', 
	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
	
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
recreate_maps.php 
---------------------------------------------------------------------
php recreate_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --cols=<params>     : comma seperated list of column definitions
    --help              : show this message	
---------------------------------------------------------------------
	
ENDHELP;
exit;
}
	
//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];

$_GET = array();
$cols = explode(',',$param['cols']);
foreach ($cols as $col) {
	$_GET[$col] = 1;
}


$h = fopen("/tmp/output.csv",'w');




require_once('geograph/global.inc.php');

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

include('geograph/export.inc.php');

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


if (isset($_GET['headers']) && $_GET['headers'] == 'lower') {
	fwrite($h, str_replace(array('photographer','easting','northing','figures','view_direction','image_'),array('photo','e','n','figs','dir',''),
		preg_replace('/[^\w,]+/','_',
			strtolower($csvhead)))."\n");
} else {
	fwrite($h, "$csvhead\n");
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
	fwrite($h, "{$image['gridimage_id']},{$image['title']},{$image['grid_reference']},{$image['realname']},{$image['imageclass']}");
	if (!empty($_GET['desc'])) {
		if (empty($image['comment'])) {
			fwrite($h, ',');
		} else {
			fwrite($h, ',"'.str_replace('"', '""', $image['comment']).'"');
		}
	}
	if (!empty($_GET['thumb'])) {
		$gridimage->fastInit($image);
		fwrite($h, ','.$gridimage->getThumbnail(120,120,true));
	}
	if (!empty($_GET['gr'])) {
		if (empty($image['nateastings'])) {
			fwrite($h, ",{$image['grid_reference']}");
		} else {
			$gridimage->grid_square = new GridSquare();
			$gridimage->grid_square->natspecified = 1;
			$gridimage->grid_square->natgrlen=$gridimage->natgrlen;
			$gridimage->grid_square->nateastings=$gridimage->nateastings;
			$gridimage->grid_square->natnorthings=$gridimage->natnorthings;
			$gridimage->grid_square->reference_index=$gridimage->reference_index;
			fwrite($h, ",".$gridimage->getSubjectGridref());
			$gridimage->subject_gridref = ''; // so it not reused!
		}
		if (!empty($_GET['ppos'])) {
			fwrite($h, ",".$gridimage->getPhotographerGridref());
			$gridimage->photographer_gridref = ''; // so it not reused!
		}
	} elseif (!empty($_GET['en'])) {
		if (empty($image['nateastings']) && isset($_GET['coords'])) {
			list($e,$n) = $conv->internal_to_national($image['x'],$image['y'],$image['reference_index']);
			
			fwrite($h, ",$e,$n,{$image['natgrlen']}");
		} else {
			fwrite($h, ",{$image['nateastings']},{$image['natnorthings']},{$image['natgrlen']}");
		}
		if (!empty($_GET['ppos']))
			fwrite($h, ",{$image['viewpoint_eastings']},{$image['viewpoint_northings']},{$image['viewpoint_grlen']}");
	} elseif (!empty($_GET['ll']))
		fwrite($h, ",{$image['wgs84_lat']},{$image['wgs84_long']}");
	if (!empty($_GET['taken']))
		fwrite($h, ",{$image['imagetaken']}");
	if (!empty($_GET['dir']))
		fwrite($h, ",{$image['view_direction']}");
	if (!empty($_GET['hits']))
		fwrite($h, ",{$image['hits']}");

	fwrite($h, "\n");
	$recordSet->MoveNext();
	$counter++;
}
$recordSet->Close();

fclose($h);

print "outputed $counter records\n";

?>
