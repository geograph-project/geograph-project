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


//these are the arguments we expect
$param=array(
        'table'=>false,
        'reference_index'=>0,
        'execute'=>false,
);


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($param['table']))
	die ("specify table with --table=...\n");

if ($db->getOne("SHOW TABLES LIKE ".$db->Quote($param['table']))) {

	$columns = $db->getAssoc("DESCRIBE `{$param['table']}`");

	if (empty($columns['east']) || empty($columns['north']))
		die("no east/north column found on table\n");

	if (!empty($columns['km_ref']) || !empty($columns['gridref']) || !empty($columns['gr']))
		print("warnign: appears to have gr column already!\n");

	if (empty($columns['reference_index']) && empty($param['reference_index']))
		die("unknown reference_index\n");

	$primary_key = null;
	$extra_set = '';
	foreach ($columns as $key => $row) {
		if ($row['Key'] == 'PRI') {
			if (!empty($primary_key))
				die("appears to have multiple primary key columns, which can't cope with!\n");
			$primary_key = $key;
		}
		if (stripos($row['Extra'],'CURRENT_TIMESTAMP') !== FALSE)
			$extra_set = ", `$key` = `$key`"; //to avoid updating auto timestamp
	}

	if (empty($columns['km_ref'])) {
		$sql = "ALTER TABLE `{$param['table']}` ADD km_ref VARCHAR(6) NOT NULL DEFAULT ''";

		print "$sql\n";

		if (!empty($param['execute']))
			$db->Execute($sql);
	}

	#############################

	$sql = "SELECT $primary_key,east,north FROM `{$param['table']}` WHERE km_ref LIKE ''";

	if (isset($columns['reference_index']))
		$sql = str_replace(' FROM ',',reference_index FROM ',$sql);

	print "$sql\n";


		$fake = "UPDATE `{$param['table']}` SET km_ref = '??' $extra_set WHERE $primary_key = ".$db->Quote('??');
		print "$fake\n";


	if (empty($param['execute']))
		die("add --execute=true to run for real\n");

	$recordSet = $db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	$c = $a = 0;
	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;
		if (!empty($r['reference_index']))
			$param['reference_index'] = $r['reference_index'];

		list ($gridref,) = $conv->national_to_gridref($r['east'],$r['north'],4,$param['reference_index']);

		if (strlen($gridref) < 5) {
			print "FAILED[{$r[$primary_key]}] => ($d,$e,$n)($gridref,)\n";
		} else {
			$sql = "UPDATE `{$param['table']}` SET km_ref = '$gridref' $extra_set WHERE $primary_key = ".$db->Quote($r[$primary_key]);
			$db->Execute($sql);
			$c++;
			$a += mysql_affected_rows();
		}

		if (!($c%1000))
			print "c=$c, a=$a\n";
		$recordSet->MoveNext();
	}

	$recordSet->Close();

	print "Finished with c=$c, a=$a\n";

} else
	die("unknown table\n");
