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

$param=array(
	'source'=>'tag_named_stat',

        //'mode'=>'unknown',
	'format' => 'jsonl',

	'limit'=>10,
	'file'=>'taxonomy.jsonl',
	'execute'=>false,
);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$_REQUEST = $param; //so code copied from web pae, can be used as is!

        $where = array();
        $cols = array();
        $join = '';
        $group = '';

	$columns = $db->getAssoc("DESCRIBE {$param['source']}");

	foreach($columns as $column => $data) {
		if (isset($columns[$column.'s'])) {
			$cols[] = "IF({$column}s = 1, $column, CONCAT('Example: ',$column)) as $column";
		} else {
			$cols[] = $column;
		}
	}

	if (!empty($param['cols']))
		$cols[] = $param['cols'];

	$cols = implode(", ",$cols);

        $limit = 10;
        if (!empty($_REQUEST['limit']))
                $limit = min(2000000, intval($_REQUEST['limit']));

	$where[] = "COALESCE(classification2, '') NOT IN ('other', 'geographical-generic')";
	$where[] = "images > 5";
        if (empty($where)) $where[] = 1;

#########################################

	$h = fopen($param['file'],'w');
	$c=0;

	        $sql = "SELECT $cols
	                FROM {$param['source']} t
	                $join
	                WHERE ".implode(" AND ",$where)."
			LIMIT $limit";

		if (empty($param['execute'])) {
			print "\n$sql;\n";
			exit;
		}

                $recordSet = $db->Execute($sql);

		while (!$recordSet->EOF) {
                        if (!empty($recordSet->fields['tag']))
                                $recordSet->fields['tag'] = latin1_to_utf8($recordSet->fields['tag']);
                        if (!empty($recordSet->fields['place']))
                                $recordSet->fields['place'] = latin1_to_utf8($recordSet->fields['place']);

//convert to numeric - small size reduction on the file, but makes helps will make easier working (eg as a dataframe in python) 

foreach ($recordSet->fields as $key => $value) {
    // Check if the string is actually a valid number (int or float)
    if ($key != 'tag' && is_numeric($value)) {
        // Adding 0 is a PHP trick to cast to the appropriate numeric type (int or float)
        $recordSet->fields[$key] = $value + 0;
    }
}
try {
    $json = json_encode($recordSet->fields, JSON_THROW_ON_ERROR);
    fwrite($h, $json . "\n");
} catch (JsonException $e) {
    // This will kill the script and tell you EXACTLY what went wrong
print_r($recordSet->fields);
    die("JSON Encoding failed at tag_id " . $recordSet->fields['tag_id'] . ": " . $e->getMessage());
}
                   //     fwrite($h, json_encode($recordSet->fields)."\n");
                        $recordSet->MoveNext();
                        if (!($c%1000))
				print "$c. ";
                        $c++;
                }

	print "; $c to {$param['file']}\n";
