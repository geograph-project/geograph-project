y<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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


$sqls = array();

$sqls[] = "insert into gridimage_sequence (gridimage_id,sequence)
		SELECT gridimage_id,1000000+(ABS(CRC32(gridimage_id)) MOD 11000000) FROM gridimage_search LEFT JOIN gridimage_sequence USING (gridimage_id)
		WHERE gridimage_sequence.gridimage_id IS NULL";

$sqls[] = "update gridimage_sequence s inner join gridimage_log using (gridimage_id) left join gallery_ids i on (id = gridimage_id)
		set s.score = (hits+hits_archive)*COALESCE(i.baysian,3.2),
			s.baysian = i.baysian";

foreach ($sqls as $sql) {
	print "---\n$sql\n---\n".date('r')."\n";
	$db->Execute($sql);
	print date('r')."\n";
	print "Rows Affected: ".mysql_affected_rows()."\n";
}
print ".\n";
