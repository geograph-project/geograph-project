<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";
    
//--------------------------------------------

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/image.inc.php');

$db = NewADOConnection($GLOBALS['DSN']);

	echo "Checking Folders...\n\n";
	flush();

	# /maps/detail/741/120/detail_741_120_200_200_40_0.png
	
function recurse_maps($folder) {	
	global $db;
	$root=&$_SERVER['DOCUMENT_ROOT'];
	$dh = opendir($root.$folder);
	
	while (($file = readdir($dh)) !== false) {
		if (is_dir($root.$folder.$file) && strpos($file,'.') !== 0) {
			recurse_maps($folder.$file.'/');
			print "done $folder $file\n";
		} elseif (preg_match("/detail_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)\./",$file,$m)) {
			array_shift($m);
			$sql = "INSERT DELAYED IGNORE INTO mapcache2 VALUES(".join(',',$m).",0)";
			$db->Execute($sql);
		}		
	}
	closedir($dh);
}
	
	recurse_maps("/maps/detail/");
	
	print "-----------\nDone\n\n";

	
