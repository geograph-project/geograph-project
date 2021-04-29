<?php
/**
 * $Project: GeoGraph $
 * $Id: snippet.php 8668 2017-12-08 16:26:23Z barry $
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


require_once('geograph/global.inc.php');


if (empty($_GET['callback'])) {
        header('Access-Control-Allow-Origin: *');
}

customExpiresHeader(3600*24);


$snippet_id = intval($_REQUEST['id']);


	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = $db->getRow("SELECT s.*,realname FROM snippet s LEFT JOIN user USING (user_id) WHERE snippet_id = $snippet_id AND enabled = 1");

	if (!empty($data['snippet_id'])) {

		$data['images'] = $db->getOne("SELECT COUNT(*) FROM gridimage_snippet gs WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296");

		if ($data['images']) {
			$imagelist = new ImageList();

			$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_snippet gs INNER JOIN gridimage_search gi USING (gridimage_id) WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296 ORDER BY crc32(concat(gridimage_id,yearweek(now()))) LIMIT 25";

			$thumbw = $thumbh = 120;
			$imagelist->_getImagesBySql($sql);
			foreach ( $imagelist->images as $idx => $image) {
				 $imagelist->images[$idx]->thumbnail = $image->getThumbnail($thumbw,$thumbh,true);
			}
			$data['imagelist'] = $imagelist->images;
		}
	}



outputJSON($data);



