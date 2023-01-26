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
$param=array('d'=>4000, 'limit'=>10, 'ri'=>2);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################


require_once('geograph/gridimage.class.php');


$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		//we need to join on os_gaz to get east/north
	$sql = "select placename_id,full_name,e,n,p.km_ref
	from sphinx_placenames p inner join loc_placenames l on (placename_id = id)
	where p.reference_index = {$param['ri']} and l.country = 'uk' and postcode is null
	limit {$param['limit']}";
	$recordSet = $db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;

		//list ($gridref,) = $conv->national_to_gridref($r['e'],$r['n'],4,$r['reference_index']);

		$where = array();
		$where[] = sprintf("e BETWEEN %d AND %d", $r['e']-$param['d'], $r['e']+$param['d']);
		$where[] = sprintf("n BETWEEN %d AND %d", $r['n']-$param['d'], $r['n']+$param['d']);
		$where[] = "reference_index = {$param['ri']}";

		//just find the closest place with a postcode!
		$col = "pow(e-{$r['e']},2)+pow(n-{$r['n']},2) as dist_sq"; //we only sorting, so dont need to bother with sqrt
		$row = $db->getRow($sql = "SELECT code,$col FROM loc_postcodes WHERE ".implode(" AND ",$where)." ORDER BY dist_sq");

		if (!empty($row)) {
			//print_r($row);
			$updates = array();
			if (!empty($row['seq']))
				$updates['open_id'] = $row['seq'];
			$updates['postcode'] = preg_replace('/ .*/','',$row['code']); //drop the after space
			$where = "placename_id = ".$r['placename_id'];
			$db->Execute($sql = 'UPDATE sphinx_placenames SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where", array_values($updates));

			print "Found {$row['code']} for {$r['full_name']}\n";
			//print_r($updates);
			//print "$sql\n\n";
		} else {
			//SET open_id = 0 as nnone?
			print "Nothing found for ".implode(', ',$r)."\n";
			print " $sql;\n\n";

		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

