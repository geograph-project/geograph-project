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

if (empty($_GET['callback'])) {
        header('Access-Control-Allow-Origin: *');
}

customExpiresHeader(3600*24);

if (empty($_GET['id'])) {
	die("no image");
}


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$row = $db->getRow("SELECT gridimage_id,user_id,gridsquare_id,imagetaken FROM gridimage WHERE gridimage_id = ".intval($_GET['id']));

$data = array();
if (!empty($row)) {
        $five_years_in_days = 365*5;

	$info = $db->getRow($sql= "SELECT
			COUNT(*) AS images,MAX(ftf) AS ftf,SUM(user_id={$row['user_id']}) AS personal,SUM(moderation_status='geograph') AS geos,
			MIN(IF(imagetaken>1,ABS(DATEDIFF('{$row['imagetaken']}',imagetaken)),99999)) AS days
			FROM gridimage WHERE gridimage_id < {$row['gridimage_id']} AND gridsquare_id = {$row['gridsquare_id']}
			AND moderation_status != 'rejected'
			GROUP BY gridsquare_id");

	if (!$info['personal'] && $info['ftf'] < 4) {
		switch($info['ftf']) {
			case 0: $data[] = "First Geograph Point"; break;
			case 1: $data[] = "Second Geograph Point"; break;
			case 2: $data[] = "Third Geograph Point"; break;
			case 3: $data[] = "Fourth Geograph Point"; break;
		}
	}
	if ($info['days'] > $five_years_in_days || empty($info))
		$data[] = "TPoint";
	if (!$info['personal'])
		$data[] = "Personal Point";

} else {
	$data['error'] = "unable to load image";
}


outputJSON($data);

