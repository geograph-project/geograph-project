<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7424 2011-09-22 21:37:52Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['id'])) {
	//this should be in sync with content_ids MVA in sample8 sphinx index!

	if ($_GET['id'] == 1) {
		$data = array('title'=>'Showcase Gallery');
	} elseif ($_GET['id'] == 2) {
		$data = array('title'=>'Photo of the Day');
	} else {
		$db = GeographDatabaseConnection(true);

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$data = $db->getRow("SELECT title FROM content WHERE content_id = ".intval($_GET['id']));
	}
} else {
	$data = array('error'=>'Missing Param');
}

header('Access-Control-Allow-Origin: *');
outputJSON($data);

