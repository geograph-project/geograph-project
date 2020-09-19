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


############################################

//these are the arguments we expect
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$a = array();



$sql = "
SELECT user_id,`gridsquare_id`,MIN(seq_no) AS min_seq_no
FROM gridimage
WHERE moderation_status='geograph'
GROUP BY user_id,`gridsquare_id`
ORDER BY gridsquare_id,min_seq_no
";



$last = -1;
$recordSet = $db->Execute($sql);

while (!$recordSet->EOF) 
{
	$row = $recordSet->fields;
	
	if ($last != $row['gridsquare_id']) {
		$rank = 1;
		$last = $row['gridsquare_id'];
	} else {
		$rank++;
	}
	if ($rank > 1) { //ftf=1 should already be set!
		$sql = "
		UPDATE gridimage 
		SET upd_timestamp = upd_timestamp, ftf = $rank
		WHERE gridsquare_id = {$row['gridsquare_id']}
		AND seq_no = {$row['min_seq_no']}
		AND moderation_status='geograph' 
		";
		$db->Execute($sql);
	
		print "."; 
	}
	
	$recordSet->MoveNext();
}

$recordSet->Close();
print "l\n";



