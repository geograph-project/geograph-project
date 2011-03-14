<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7069 2011-02-04 00:06:46Z barry $
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

require_once('geograph/global.inc.php');

if (function_exists('GeographDatabaseConnection')) {
	$db = GeographDatabaseConnection(true);
} else {
	$db = NewADOConnection($GLOBALS['DSN']);
}

$sql = "
SELECT gi.gridimage_id, UNIX_TIMESTAMP(gi.submitted) AS submitted, TO_DAYS(REPLACE(gi.imagetaken,'-00','-01')) AS takendays, gi.user_id, 
gi.title, gi.comment, gi.imageclass, gi.realname, gi.grid_reference, 
SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) AS myriad, 
CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad, 
REPLACE(gi.imagetaken,'-','') AS takenday, REPLACE(substring(gi.imagetaken,1,7),'-','') AS takenmonth, substring(gi.imagetaken,1,4) AS takenyear, 
RADIANS(wgs84_lat) AS wgs84_lat,RADIANS(wgs84_long) AS wgs84_long, 
IF(gi.moderation_status='accepted','supplemental',gi.moderation_status) AS status, 
CRC32(gi.imageclass) AS classcrc, 
(gi.reference_index * 10000000 + (viewpoint_northings DIV 1000) * 1000 + viewpoint_eastings DIV 1000) AS viewsquare 
FROM gridimage_search gi
	INNER JOIN gridimage g2 
	ON (gi.gridimage_id = g2.gridimage_id) 
";

header ("Content-Type: application/xml");

if (!empty($_GET['header'])) {
	$result = mysql_query("$sql LIMIT 1");
	
	print '<?xml version="1.0" encoding="utf-8"?>'."\n";
	print '<sphinx:docset>'."\n";
	print '<sphinx:schema>';
	
	$fields = mysql_num_fields($result);
	for ($i=1; $i < $fields; $i++) {
		$name  = mysql_field_name($result, $i);
	    	$type  = mysql_field_type($result, $i);
		switch ($type) {
			case 'string':
			case 'blob':
				print '<sphinx:field name="'.$name.'" />';
				break;
			case 'int':
				if ($name == 'submitted') {
					print '<sphinx:attr bits="32" name="'.$name.'" type="timestamp" />';
				} else {
					//todo - set bits based on $len = mysql_field_len($result, $i);
					print '<sphinx:attr bits="32" name="'.$name.'" type="int" />';
				}
				break;
			case 'real':
				print '<sphinx:attr name="'.$name.'" type="float" />';
				break;
		}
	}
	print '</sphinx:schema>'."\n";
	
	exit;
} elseif (!empty($_GET['footer'])) {
	print '</sphinx:docset>';
	exit;
}


if (!empty($_GET['last'])) {
	$sql .= " WHERE gi.gridimage_id > ".intval($_GET['last']);
}
if (!empty($_GET['chunk'])) {
	$limit = intval($_GET['chunk']);
} else {
	$limit = 100;
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$recordSet = &$db->Execute("$sql ORDER BY gridimage_id LIMIT $limit");
	
if ($recordSet->RecordCount() == $limit) {
	$recordSet->MoveLast();
	$id = $recordSet->fields['gridimage_id'];
	
	header("X-HS-Key: $id");
	
	$recordSet->MoveFirst();
}

header("X-HS-Length: ".$recordSet->RecordCount());
	
while (!$recordSet->EOF) 
{
	$row = $recordSet->fields;
	
	$id = array_shift($row);
	print '<sphinx:document id="'.intval($id).'">';
	
	$row['wgs84_lat'] = deg2rad($row['wgs84_lat']);
	$row['wgs84_long'] = deg2rad($row['wgs84_long']);
	
	foreach ($row as $key => $value) {
		if (empty($value) || preg_match('/^[\w \.-]+$/',$value)) {
			print "<$key>$value</$key>\n";
		} else {
			$value = xmlentities($value);
			print "<$key>\n<![CDATA[$value]]>\n</$key>";
		}
	}
	
	print '</sphinx:document>';	
	
	$recordSet->MoveNext();
}

$recordSet->Close();


