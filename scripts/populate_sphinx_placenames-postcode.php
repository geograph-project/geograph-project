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
$param=array('d'=>4000, 'limit'=>10, 'any'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################


require_once('geograph/gridimage.class.php');


$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		//we need to join on os_gaz to get east/north
	$sql = "select placename_id,seq,Place,def_nam,north,east,postcode,f_code,p.km_ref
	from sphinx_placenames p inner join os_gaz on seq = placename_id - 1000000
	where reference_index = 1 and postcode is null
	limit {$param['limit']}";
	//and Place like '%/%' and p.has_dup=0 and f_code in ('C','T','O')
	$recordSet = $db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;
		$r['reference_index'] =1;

		//list ($gridref,) = $conv->national_to_gridref($r['e'],$r['n'],4,$r['reference_index']);

		$where = array();
		$where[] = sprintf("e BETWEEN %d AND %d", $r['east']-$param['d'] , $r['east']+$param['d'] );
		$where[] = sprintf("n BETWEEN %d AND %d", $r['north']-$param['d'], $r['north']+$param['d']);
		$where[] = "LENGTH(postcode_district)>1";

		if ($param['any']) {
			//just find the closest place with a postcode!
			$col = "pow(e-{$r['east']},2)+pow(n-{$r['north']},2) as dist_sq"; //we only sorting, so dont need to bother with sqrt
			$row = $db->getRow($sql = "SELECT postcode_district,$col FROM os_open_names2 WHERE ".implode(" AND ",$where)." ORDER BY dist_sq");
		} else {
			//look for specific placename - use this first!

			$bits = explode('/',$r['def_nam'],2);
			$bits = array_map(array($db, 'Quote'),$bits);
			if (count($bits) == 2) {
				$where[] = "(name1 IN (".implode(',',$bits).") OR name2 IN (".implode(',',$bits)."))";
	//			$where[] = $db->Quote(str_replace('/',' (',$r['def_nam']).")")." IN (name1,name2)";
	//			$where[] = "(name1 = CONCAT_WS(' or ',".implode(',',$bits).") OR name1 = CONCAT_WS(' or ',".implode(',',array_reverse($bits))."))";
			} else {
				$where[] = "{$bits[0]} IN (name1,name2)";
			}

			$row = $db->getRow($sql = "SELECT seq,name1,name2,postcode_district FROM os_open_names2 WHERE ".implode(" AND ",$where)." ORDER BY type = 'populatedPlace' DESC");
		}

		if (!empty($row)) {
			//print_r($row);
			$updates = array();
			if (!empty($row['seq']))
				$updates['open_id'] = $row['seq'];
			$updates['postcode'] = $row['postcode_district'];
			$where = "placename_id = ".$r['placename_id'];
			$db->Execute($sql = 'UPDATE sphinx_placenames SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where", array_values($updates));

			print "Found {$row['postcode_district']} for {$r['def_nam']}\n";
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

