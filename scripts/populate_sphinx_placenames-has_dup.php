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
$param=array('limit'=>10, 'execute'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once('geograph/gridimage.class.php');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


//sphinx_placename set has_dup=1 when douplicate of a irish place in os_gaz, now do the reverse!

$sql = "select seq,def_nam,o.km_ref,name,p.county from os_gaz o inner join ie_open_data p on (name = def_nam) where o.has_dup=0";

	$recordSet = $db->Execute($sql);

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;
		$sql = "UPDATE os_gaz SET has_dup = 1 WHERE seq={$r['seq']}";
		print "$sql; -- for {$r['def_nam']}\n";
		if ($param['execute'])
			$db->Execute($sql);

		$sql = "UPDATE sphinx_placenames SET has_dup = 1,Place = CONCAT(Place,'/',km_ref) WHERE placename_id = {$r['seq']} + 1000000 AND has_dup = 0"; //dup=0 to avoid double CONCAT!
		print "$sql;\n";
		if ($param['execute'])
			$db->Execute($sql);

		$recordSet->MoveNext();
	}

	$recordSet->Close();

