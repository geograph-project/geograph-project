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

$row = $db->getRow("SELECT gridimage_id,comment FROM gridimage_search WHERE gridimage_id = ".intval($_GET['id']));

if (!empty($row)) {
	$image = new Gridimage();
	$image->fastInit($row);
	$image->db = $db;
	$image->loadSnippets();

        $row['comment'] = preg_replace('/\s*NOTE.? This image has a detailed.+?To read it click on the image.?/is','',$row['comment']);

	$data = array('comment'=>GeographLinks(htmlentities($row['comment'])));
	if (!empty($image->snippets)) {
		foreach ($image->snippets as $idx => $snippet) {
			unset($image->snippets[$idx]['point_en']);
			$image->snippets[$idx]['comment'] = GeographLinks(htmlentities($snippet['comment']));
		}
		$data['snippets'] = $image->snippets;
	}
	if (!empty($image->snippets_as_ref)) {
		 $data['snippets_as_ref'] = $image->snippets_as_ref;
	}
} else {
	class EmptyClass {}

	$data = new EmptyClass();
	$data->error = "unable to load image";
}

outputJSON($data);



