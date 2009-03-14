<?php
/**
 * $Project: GeoGraph $
 * $Id: imagemap.php 1690 2005-12-22 15:05:42Z barryhunter $
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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
require_once('geograph/rastermapOS.class.php');
require_once('geograph/rastermapOS250.class.php');
init_session();

if (true) {
	$m = new RasterMapOS250();
} else {
	$m = new RasterMapOS();
}

set_time_limit(3600*24);

$gr = "SH7042";
if (!empty($_GET['gr'])) 
	$gr = $_GET['gr'];
$tile = "SH64";
if (!empty($_GET['tile']) && preg_match('/^[\w]+$/',$_GET['tile'])) 
	$tile = $_GET['tile'];

if (!empty($_GET['epoch']) && preg_match('/^[\w]+$/',$_GET['epoch'])) 
	$CONF['rastermap'][$m->source]['epoch'] = $_GET['epoch']."/";


$USER->mustHavePerm("admin");



if ($_GET['listTiles']) {
	$m->listTiles();
	
	print "DDONE";
	exit;
}

//probably outdated
##if ($_GET['fakeSetup'])
##	$m->fakeSetup($gr);

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
	$m->processSingleTile($tile,$_GET['processSingleTile']);

if ($_GET['processSingleTile2'])
	$m->processSingleTile($tile,200);

//probably outdated
##if ($_GET['combineTiles'])
##	$m->combineTiles($gr);



	
?>Done
