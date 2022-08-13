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
$param=array(
	'tables'=>'',
	'count'=>10,
	'host'=>'',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$host = $CONF['db_connect'];
if ($param['host']) {
    $host = $param['host'];
}
print(date('H:i:s')."\tUsing server: $host\n");
$DSN = str_replace($CONF['db_connect'],$host,$DSN);

//uses $GLOBALS['DSN']
$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$database = $db->getOne("SELECT DATABASE()");
print "database: $database\n";

$x = 586; $y = 201; $d=10;

############################################

if (!empty($param['tables'])) {
	$tables = explode(',',$param['tables']);

	$rows = $db->getAll("SELECT grid_reference,x,y FROM gridsquare WHERE imagecount > 5 ORDER BY RAND() LIMIT {$param['count']}");

	print "testing ".count($rows)." queries:\n";

	$stat = array();
	foreach ($rows as $row) {
		foreach ($tables as $table) {

		        $start = microtime(true);
		        $result = getSpatial($row['x'],$row['y'],$d,$table);
		        $end = microtime(true);

			$stat[$table][] = $end-$start;
		}
		print ".";
	}
	print "\n";

	foreach ($stat as $table => $data) {
		$sum = array_sum($data);
		$count = count($data);

		printf("%40s : %.3f   (%.3f) \n",$table, $sum/$count, $sum);
	}

	exit;
}

############################################

$where = array();
$where[] = "table_schema = DATABASE()";
$where[] = "COLUMN_NAME = 'point_xy'";

$where = implode(" AND ",$where);
$rows = $db->getAll("select TABLE_NAME, COLUMN_NAME, ENGINE
 FROM information_schema.columns
INNER JOIN information_schema.tables USING (table_schema,TABLE_NAME)
 where $where order by table_name");

############################################


foreach ($rows as $row) {

	print str_repeat('#',80)."\n";
	print implode("\t",$row)."\n";

	$table = $row['TABLE_NAME'];

        $start = microtime(true);
        $result = getSpatial($x,$y,$d,$table,'*');
        $end = microtime(true);

        print date('r').sprintf(', took %.3f seconds, %d rows.',$end-$start,$result->_numOfRows)."\n\n";

	if ($db->getOne("show columns from $table LIKE 'x'")) {

	        $start = microtime(true);
        	$result = getSpatialAll($table,'count(*)');
	        $end = microtime(true);

	        print date('r').sprintf(', took %.3f seconds, %d rows.',$end-$start,$result->_numOfRows)."\n\n";
	}
}


#################################################################
#################################################################


	function getSpatial($x,$y,$d,$table,$select = "COUNT(*)") {
		global $db;

		$sql_where = '';

                                        $left=$x-$d;
                                        $right=$x+$d-1;
                                        $top=$y+$d-1;
                                        $bottom=$y-$d;

                                        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

                                        $sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

		$sql = "SELECT SQL_NO_CACHE $select FROM $table WHERE $sql_where";

		return $db->Execute($sql);
	}



	function getSpatialAll($table,$select = "COUNT(*)") {
		global $db;

		$sql_where = '';

			$row = $db->getRow("select min(x),min(y),max(x),max(y) from $table"); //this can be query cached!

                                        $left=$row['min(x)'];
                                        $right=$row['max(x)'];
                                        $top=$row['max(y)'];
                                        $bottom=$row['min(y)'];

                                        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

                                        $sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

		$sql = "SELECT SQL_NO_CACHE $select FROM $table WHERE $sql_where";

		return $db->Execute($sql);
	}

