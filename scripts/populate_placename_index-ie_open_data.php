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


require_once('geograph/gridimage.class.php');


$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



	$sql = "select * from ie_open_data";
	$localities = $db->Quote("Northern Ireland, uk");

	$recordSet = $db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;
		$r['reference_index'] =2;

		list ($gridref,) = $conv->national_to_gridref($r['e'],$r['n'],4,$r['reference_index']); 

		if (strlen($gridref) != 5) {
			print "FAILED[{$r['placename_id']}] => ($d,$e,$n)($gridref,)\n";
		} else {
			$name = $db->Quote($r['name']);
			preg_match('/(\w+)(\d)\d(\d)\d/',$gridref,$m);
			$grs = "{$m[1]} {$m[1]}{$m[2]}{$m[3]}";

			//just to mtaintain compatiblities!
			if ($r['country'] == 'Ireland')
				$r['country'] = "Republic of Ireland, ie";
			else
				$r['country'] = "Northern Ireland, uk";

			$updates = array();
			$updates['id'] = $r['id']+3000000;
			$updates['name'] = recaps($r['name']);
			$updates['name_2'] = $r['irish'];
			$updates['gr'] = $gridref;
			$updates['localities'] = recaps($r['county']).', '.$r['country'];
			$updates['localities_2'] = '';
			$updates['grs'] = $grs;
			$updates['score'] = preg_replace('/[^\d]+/','',$r['town_class']) || 3;


			$db->Execute('INSERT INTO placename_index SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

			print "{$updates['name']}\n\n";

			$db->Execute($sql);
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

