<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

$db = GeographDatabaseConnection(3600);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$data = $db->getAll("SELECT answer_id,question_id,title,content,created
	FROM answer_answer
	WHERE target = 'Photo Contributors :: Contributing' AND level = 0 AND status=1
	ORDER BY answer_id DESC");

foreach($data as &$row) {
	$row['title'] = latin1_to_utf8($row['title']);
	$row['content'] = latin1_to_utf8($row['content']);
}

outputJSON($data);
