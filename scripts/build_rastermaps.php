<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2578 2006-09-27 20:58:54Z barry $
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


    

//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph_live/',		//base installation dir
	'config'=>'www.geograph.org.uk', //effective config
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
		
		//if we have a value, use it, else just flag as true
		$param[$bits[0]]=isset($bits[1])?$bits[1]:true;

		//fake it into the GET array;
		$_GET[$bits[0]]=isset($bits[1])?$bits[1]:true;
	}
	else die("unexpected argument $arg - try --help\n");
	
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
build_rastermaps.php 
---------------------------------------------------------------------
    --dir=<dir>         : base directory (/var/www/geograph_live/)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --help              : show this message	
---------------------------------------------------------------------
	
ENDHELP;
exit;
}
	
//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];

#-----------------------------------------------

require_once('geograph/global.inc.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
require_once('geograph/rastermapOS.inc.php');

$gr = "SH7042";
$tile = "SH64";

		define('TIFF_W',4000); 
		define('TIFF_KMW',20);
		define('TIFF_KMW_BY10',TIFF_KMW / 10);
		define('TIFF_PX_PER_KM',TIFF_W / TIFF_KMW);


$m = new RasterMapOS();


if ($_GET['listTiles']) {
	$m->listTiles();
	
	print "DDONE";
	exit;
}

if ($_GET['fakeSetup'])
	$m->fakeSetup($gr);

if ($_GET['processTile1'])
	$m->processTile($tile,100,100);
if ($_GET['processTile3'])
	$m->processTile($tile,300,300);
if ($_GET['processTile']) {
	$m->processTile($tile,100,100);
	$m->processTile($tile,300,100);
	$m->processTile($tile,300,300);
	$m->processTile($tile,100,300);
}

if ($_GET['testTable'])
	$m->testTable($tile);

if ($_GET['processSingleTile'])
	$m->processSingleTile($tile);

if ($_GET['processSingleTile2'])
	$m->processSingleTile($tile,200);


if ($_GET['combineTiles'])
	$m->combineTiles($gr);


print "\n\ndone\n";
?>

