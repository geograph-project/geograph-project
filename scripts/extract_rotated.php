<?php
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

$param = array('limit' => 1000,'execute'=>false, 'delta'=>true);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

######################################################################################################################################################

$query = "SELECT gridimage_id,exif from gridimage_exif where \$where AND exif LIKE '%Orientation%'";

$max = $db->getRow("SELECT MIN(gridimage_id) as min,MAX(gridimage_id) as max FROM gridimage_exif");

if ($param['delta'])
	$max['min'] = $db->getOne("SELECT MAX(gridimage_id) FROM exif_rotated");


for($start = $max['min'];$start<$max['max'];$start+=$param['limit']) {
	print "$start. ";
        $end = $start+$param['limit']-1;
        $sql = str_replace("\$where","gridimage_id BETWEEN $start AND $end",$query);
	$data = $db->getAll($sql);
	foreach ($data as $row) {
                $exif = unserialize($row['exif']);
		if (!empty($exif['IFD0']['Orientation']) && $exif['IFD0']['Orientation']!==1) {
			$sql = "INSERT IGNORE INTO exif_rotated SET gridimage_id = {$row['gridimage_id']}, extracted = ".$db->Quote($exif['IFD0']['Orientation']);
			if ($param['execute'])
				$db->Execute($sql);
			else
				print_r("$sql;\n");
		}

	}
}

print ".\n";
exit;


######################################################################################################################################################

///this kinda works, but can be an Orientation tag on the thumbnail too, and the . doesnt match when newlines etc. 


$build = "REPLACE INTO exif_rotated 
	SELECT gridimage_id,regexp_replace(exif,'(.*)Orientation\";(\\\\w:\\\\d+)(.*)','\\2') as extracted
	 from gridimage_exif
	 where \$where AND exif LIKE '%Orientation%'";


$max = $db->getRow("SELECT MIN(gridimage_id) as min,MAX(gridimage_id) as max FROM gridimage_exif");

for($start = $max['min'];$start<$max['max'];$start+=$param['limit']) {
        $end = $start+$param['limit']-1;
        $sqls[] = str_replace("\$where","gridimage_id BETWEEN $start AND $end",$build);
}

######################################################################################################################################################

foreach ($sqls as $sql) {
	print "---\n$sql\n---\n".date('r')." (started)\n";
	//$db->Execute($sql);
	print date('r')." (done)\n";
	//print "Rows Affected: ".mysql_affected_rows()."\n";
}
print ".\n";
