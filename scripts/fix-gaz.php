<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

/*

our def_nam column is a latin1 column, and geograph code pretty much treats it AS latin1
but its actully an import of utf8 data, so doesnt work as latin1.
... so this script converts it back to real latin on, encoding as, using a backup of the column.

alter table os_gaz add `def_nam_utf` varchar(60) NOT NULL DEFAULT '' after  `def_nam`;
update os_gaz set def_nam_utf = def_nam;
*/

//runit('os_gaz','def_nam','seq');

/*

select placename_id,Place,def_nam from sphinx_placenames inner join os_gaz on (seq = placename_id - 1000000 and Place = BINARY def_nam_utf )
where reference_index = 1 and Place  not rlike binary '^[ -~\\n\\r]*$' limit 100

update sphinx_placenames inner join os_gaz on (seq = placename_id - 1000000 and Place = BINARY def_nam_utf )
set Place = IF(os_gaz.has_dup,CONCAT(def_nam,'/',os_gaz.km_ref),def_nam)
where reference_index = 1 and Place  not rlike binary '^[ -~\\n\\r]*$';


alter table os_open_names2
 add `name1_utf` varchar(255) NOT NULL after `name1`,
 add `name2_utf` varchar(255) NOT NULL after `name2`,
 add `populated_place_utf` varchar(255) NOT NULL after `populated_place`;

update os_open_names2 set name1_utf=name1, name2_utf=name2, populated_place_utf=populated_place;

runit('os_open_names2','name1','id');
runit('os_open_names2','name2','id');
runit('os_open_names2','populated_place','id');

*/

runit('os_open_names2','name1','seq');
runit('os_open_names2','name2','seq');
runit('os_open_names2','populated_place','seq');


function runit($table,$column,$primary) {
	global $db;

	$sql = "select $primary,$column,{$column}_utf from $table where $column not rlike binary '^[ -~\\n\\r]*$'";
	print "$sql\n";

	$c=0;$m=0;
	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF) {
        	//$recordSet->fields['comment']
		$row = $recordSet->fields;

		//convert data that is really UTF8 encoded, back to ISO-8859-15 as that is what Geograph expects!

		$row[$column] = mb_convert_encoding($row[$column.'_utf'], 'ISO-8859-15', 'UTF-8');

		$q = $db->Quote($row[$column]);
		$sql = "UPDATE $table SET $column = $q WHERE $primary = ".$db->Quote($row[$primary]);

		$db->Execute($sql);
		$m += mysql_affected_rows();
		$c++;

		if (!($c%10))
			print "$c $m\n";

		$recordSet->MoveNext();
	}
	$recordSet->Close();

	print "$c $m.\n";

}
