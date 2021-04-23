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
init_session();

$USER->mustHavePerm("basic");

if (!empty($_GET['since'])) {
	$db = GeographDatabaseConnection(false); //this is a small refresh, so go direct to master

	$crit = "g.gridimage_id > ".intval($_GET['since']);
	$crit .= " limit 1000";
} else {
	$db = GeographDatabaseConnection(3600);

	$crit = "g.submitted > date_sub(now(),interval 3 day)";
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//needs to use gridimage/gridsquare because may be pending images. but join in gridimage_search, as may already be moderated, which case have the lat/long ready to use!
$sql = "select gridimage_id,g.submitted,gs.grid_reference,g.title,nateastings,natnorthings,natgrlen,gs.reference_index,wgs84_lat,wgs84_long
	from gridimage g
		inner join gridsquare gs using (gridsquare_id)
		left join gridimage_search gi using (gridimage_id)
	where g.user_id = {$USER->user_id} and g.moderation_status != 'rejected'
	and $crit";


require_once('geograph/conversions.class.php');
$conv = new Conversions;



//the dataset can be big, so streaming!

header("Content-Type:application/json");
print "[";

$sep = '';
$recordSet = $db->Execute($sql);

if ($count = $recordSet->RecordCount()) {
        while (!$recordSet->EOF)
        {
                $r =& $recordSet->fields;

		$r['title'] = latin1_to_utf8($r['title']);

		if (empty($r['wgs84_lat']) || $r['wgs84_lat'] < 1) {
		        list($r['wgs84_lat'],$r['wgs84_long']) = $conv->national_to_wgs84($r['nateastings'],$r['natnorthings'],$r['reference_index']);
		}

                print $sep.json_encode($recordSet->fields,JSON_PARTIAL_OUTPUT_ON_ERROR);
                $sep = ',';
                $recordSet->MoveNext();
        }
        $recordSet->Close();
}

print "]";

