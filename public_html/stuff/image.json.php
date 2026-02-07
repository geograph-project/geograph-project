<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

	$allowed_origins = [
	    'https://www.geograph.org.uk',
	    'https://schools.geograph.org.uk',
	    'https://www.geograph.ie',
	];
	$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

	if ($origin) {
	    if (in_array($origin, $allowed_origins)) {
	        header("Access-Control-Allow-Origin: $origin");
	        header("Vary: Origin");
	    } else {
	        http_response_code(403);
	        exit('Access Denied: Origin not allowed.');
	    }
	}
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	    header("Access-Control-Allow-Methods: GET, OPTIONS");
	    header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
	    header("Access-Control-Max-Age: 3600");
	    http_response_code(204);
	    exit;
	}

	customExpiresHeader(3600*24, true, true);

if (empty($_GET['id'])) {
	die("no image");
}


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$row = $db->getRow("SELECT
	SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) AS myriad,
	CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad,
	grid_reference,
	year(imagetaken) AS takenyear,REPLACE(SUBSTRING(imagetaken,1,7),'-','') AS takenmonth,REPLACE(imagetaken,'-','') AS takenday,
	REPLACE(tags,'?',' _SEP_ ') AS tags,user_id,imageclass
 FROM gridimage_search gi WHERE gridimage_id = ".intval($_GET['id']));

outputJSON($row);



