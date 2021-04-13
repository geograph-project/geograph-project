<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

?>
<form method=get>
	<input type=text name=filter value="<? echo htmlentities($_GET['filter']); ?>" placeholder="table filter/search" size=50>
	(<input type=checkbox name=all <? echo isset($_GET['all'])?' checked':''; ?>> All)
	<input type=submit>
</form>

<?
print "<script src=\"".smarty_modifier_revision("/js/geograph.js")."\"></script>";
print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

/*
SELECT TABLE_NAME AS table_name,TABLE_ROWS,DATA_LENGTH,UPDATE_TIME,shard,method  
        type,backedup,maxage     
        FROM information_schema.tables       
    LEFT JOIN _tables USING (table_name) 
    WHERE table_schema = DATABASE()   
        AND method IS NOT NULL   
	AND TABLE_ROWS > 0    
        AND backup != 'N' 
	AND UPDATE_TIME >  coalesce(DATE_ADD(backedup, INTERVAL maxage DAY), '0000-00-00 00:00:00')       
        order by table_name;
*/

$cols = array(); 
$cols[] = 'TABLE_NAME AS `table`'; 
$cols[] = 'FORMAT(TABLE_ROWS,0) AS `rows`'; 

$cols[] = 'FORMAT(DATA_LENGTH,0) AS `data`'; 
$cols[] = 'FORMAT(bytes_written,0) AS written'; 
$cols[] = 'CONCAT(FORMAT(bytes_written/DATA_LENGTH*100,0),\'%\') AS percent'; 

$cols[] = 'DATE(UPDATE_TIME) AS updated'; 
$cols[] = 'datediff(NOW(),UPDATE_TIME) AS `days`'; 

$cols[] = 'backedup AS backup'; 
//$cols[] = 'DATE(backedup) AS backup'; 
$cols[] = 'datediff(NOW(),backedup) AS since';

$cols[] = 'CEIL(shard/1000) AS `shK`'; 
$cols[] = 'type'; 

$cols[] = 'maxage'; 
//$cols[] = ' AS ';


$where = array();
$where[] = "table_schema = DATABASE()";
$where[] = "TABLE_ROWS > 0";
$where[] = "backup != 'N'";
if (empty($_GET['all']))
	$where[] = "UPDATE_TIME > date_sub(now(),interval 90 day)";
if (!empty($_GET['filter']))
	$where[] = "tables.table_name LIKE ".$db->Quote("%".$_GET['filter']."%");

$sql = "select ".implode(', ',$cols)." FROM information_schema.tables LEFT JOIN _tables USING (table_name) WHERE ".implode(' AND ',$where);

dump_sql_table($sql,'Tables');



function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;

	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2' class='report sortable' id=ttabl style=white-space:nowrap><thead><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
//	print "<th>bar</th>";
	print "</TR></thead><tbody>";
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;
		print "<TR>";
		$align = "left";
		foreach ($row as $key => $value) {
			if ($key == 'ip' || $key == 'useragent') {
				print "<TD ALIGN=$align><A HREF=\"?$key=".urlencode($value)."\">".htmlentities($value)."</A></TD>";
			} else {
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			}
			$align = "right";
		}
		/*
		if (!empty($row['percent'])) {
			$size = intval($row['percent']*4);
			print "<td><div style=\"width:{$size}px;height:10px;background-color:red;\"> </div></td>";
		}*/
		print "</TR>";
		$recordSet->MoveNext();
	}
	print "</TR></tbody></TABLE>";
}


