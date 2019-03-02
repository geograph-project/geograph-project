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



chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$perpage = 1000;

extract($db->GetRow("select min(gridimage_id) as `min`,max(gridimage_id) as `max` from gridimage_search where gridimage_id > 0"),
	EXTR_PREFIX_INVALID, 'numeric'); //need to cope with row being either Assoc or Both. Can't assume with be Both. But can assume not Num only.

$start=floor($min / $perpage) * $perpage;
if (!$start) $start =1;

print "list($min,$max)=>$start)\n\n";

for ($from=$start; $start<=$max; $start+=$perpage)
{
	$sql = "select gridimage_id,comment from gridimage_search where gridimage_id between $start and ".($start+$perpage-1)." and (comment like '%[[%' or comment like '%/photo/%')";

	$bits = array();

	$recordSet = &$db->Execute($sql);

	while (!$recordSet->EOF)
	{
		$gridimage_id =  $recordSet->fields['gridimage_id'];

		if (preg_match_all('/\[\[(\d+)\]\]/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[1] as $g_i => $g_id) {
				$bits[] = "($g_id,$gridimage_id,NOW())";
			}
		}
		if (preg_match_all('/geograph\.(org\.uk|ie)\/photo\/(\d+)\b/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[2] as $g_i => $g_id) {
				$bits[] = "($g_id,$gridimage_id,NOW())";
			}
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

	if (count($bits)) {
		$sql = "INSERT INTO gridimage_backlink VALUES ".implode(',',$bits);
		print "Inserting ".count($bits)." Rows...";
		$db->Execute($sql);
	}

	exit;
}
