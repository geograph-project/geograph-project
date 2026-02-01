<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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
$param=array('execute'=>false, 'days'=>false, 'name'=>false);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($db->readonly))
	die("ERROR: contected to readonly replica\n");


$sources = array();
##################################
// quick file parser!

foreach(glob('../sources/*.txt') as $filename) {
	if (!is_file($filename)) //can now contain symlinks!
		continue;
	$h = fopen($filename,'r');
	$key = null;
	$values = array();
	while($h && !feof($h)) {
		$line = trim(fgets($h));
		if (preg_match('/^(\w+):(.*)/',$line,$m)) {
			$key = $m[1];
			$values[$key] = $m[2]??'';
		} elseif(preg_match('/^#/',$line)) {
			continue; //comment line!
		} elseif(!empty($line)) {
			$values[$key] .= " ".$line; //space because of the trim!
		}
	}
	fclose($h);
	$name = trim($values['name'] ?? basename($filename,'.txt'));
	$sources[$name] = $values;
}

##################################

if (!empty($param['name'])) {
	foreach ($sources as $name => $values) {
		if ($name != $param['name'])
			unset($sources[$name]);
	}
}


//print_r($sources);

foreach ($sources as $name => $values) {
	print "-- $name\n";

	if (!empty($values['creation'])) perform_event('creation', $values);
	if (!empty($values['user_edit'])) perform_event('user_edit', $values);
	if (!empty($values['other_edit'])) perform_event('other_edit', $values);
	if (!empty($values['flagged'])) perform_event('flagged', $values);
	if (!empty($values['reply'])) perform_event('reply', $values);

	print "\n";
}

##################################

function perform_event($name, $values) {
	global $db, $param;
	if (!empty($values[$name])) {
		$sql = get_sql(trim($values[$name]), $values['columns']??'');
		print "$sql;\n";
		if ($sql && $param['execute']) {
			$db->Execute($sql);
			print " -- affected: ".$db->Affected_Rows()."\n";
		}
	}
}

##################################

///getrs the SQL to do insert, might not be SOURCED from mysql

function get_sql($input, $columns = '') {
	global $db, $param, $CONF;

	/////////////////////////////
	// REMOTE
	if (preg_match('/^http/',$input)) {
		$bits = array();
		if ($param['days'])
			$bits[]="days=".$param['days'];
		$bits[] = "hash=".substr(hash_hmac('md5', date('Y-m-d'), $CONF['r2_endpoint']),0,10);
		$input .= (strpos($input,'?')?'&':'?').implode('&',$bits);
		$raw = file_get_contents($input);
		$decoded = json_decode($raw,'true');
		if (empty($decoded)) //should detect even an empty array!
			return false;
		$columns = array_keys($decoded[0]);

		$sql = "INSERT IGNORE INTO `moderation` (".implode(', ',$columns).")";
		$values = array();
		foreach($decoded as $row)
			$values[] = "(".implode(',',array_map(array($db,'Quote'),array_values($row))).")";
		$sql .= " VALUES ".implode(', ',$values);

	/////////////////////////////
	// SQL
	} else {
		if (!empty($param['days']))
			$input = str_replace('interval 7 day',"interval {$param['days']} day",$input);

		if (!empty($columns)) {
			$columns = explode(',', $columns);
		} else {
			//$columns = array_keys($db->getRow(
			//not sure how to use fetch_fields in adodb
			$result = mysqli_query($db->_connectionID, $input." LIMIT 0") or die("\n\n".$input." LIMIT 0;\n\n".mysqli_error($db->_connectionID)."\n\n");
			$r = mysqli_fetch_fields($result);
			$columns = array();
			foreach($r as $obj)
				$columns[] = $obj->name;
		}

		$sql = "INSERT IGNORE INTO `moderation` (".implode(', ',$columns).")";
		$sql .= " ".$input; //no limit!
	}
	return $sql;
}
