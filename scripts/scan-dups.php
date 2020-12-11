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

$param = array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

$key = 'md5sum';

//quick safely check that that the hash is indexed! The query will be horendus without the key!
$keys = $db->getAll("show keys from full_md5");
$found = false;
foreach ($keys as $row)
	if ($row['Column_name'] == $key && $row['Seq_in_index'] == 1)
		$found = true;

if (empty($found))
	die("No key found for $key - refusing to run!\n ALTER TABLE full_md5 ADD key($key)\n");

//the table has a unique key on 'md5sum', so reinsertions of hte same dup should be silently ignored.
/// ... but does mean will miss adding a new image to a previousp duplication!
$sql = "INSERT IGNORE INTO full_dup SELECT $key as md5sum,COUNT(*) cnt,'new',0,NOW(),NOW() FROM full_md5 WHERE $key != '' AND class != 'upload' GROUP BY $key HAVING cnt > 1 ORDER BY NULL";

print "$sql;\n";

$db->Execute($sql);
 print "Affected Rows: ".$db->Affected_Rows()."\n";


