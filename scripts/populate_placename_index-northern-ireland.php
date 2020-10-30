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



	$sql = "select id,full_name,e,n from loc_placenames where country = 'uk' and reference_index = 2 and dsg like 'PPL%'";
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
			$name = $db->Quote($r['full_name']);
			preg_match('/(\w+)(\d)\d(\d)\d/',$gridref,$m);
			$grs = "{$m[1]} {$m[1]}{$m[2]}{$m[3]}";
			$sql = "INSERT INTO placename_index SET id = {$r['id']}, name = $name, gr = '$gridref', localities = $localities, grs = '$grs', score=6";

			print "$sql\n\n";

			$db->Execute($sql);
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

