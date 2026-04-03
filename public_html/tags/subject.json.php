<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7361 2011-08-11 23:11:50Z geograph $
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

$db = GeographDatabaseConnection(true);

if (!empty($_GET['suggestions'])) {
	//used by the test-subject-matching.php

	customExpiresHeader(90);

	$query = "select sum(is_candidate!=0) as m, 'subject' as prefix, subject as tag, min(cosine_min) as min_min
		 from subject_embedding inner join subjects using (subject_id)
		 where model = 'pe' and cosine_cnt > 50 group by subject order by m asc, min_min desc limit 20";

	//cosine_cnt is because currently, only partial data is PE encoded, so some 'iamges tagged with subject' provides few images with the embedding vector!

} else {
	customExpiresHeader(3600*24, true, true);

	$query = "SELECT `prefix`, tag, tag_id, count
		FROM tag LEFT JOIN tag_stat using (tag_id)
		WHERE prefix = 'subject' and status = 1 and canonical = 0
		ORDER BY tag";
	//canonical=0 ensures it an 'offical' subject tag
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getAll($query);

header("Access-Control-Allow-Origin: *");
outputJSON($data);



