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

$_GET['live'] =1;

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = GeographDatabaseConnection(true);
if (!$db->readonly) {
	die("No database replica"); //at the moment only run against the replica
}

$is_admin = $USER->hasPerm('admin');

############################################

ini_set('display_errors',1);

if ($is_admin && isset($_GET['edit'])) { //zero used for creation!
	$desc = $db->getAssoc("DESCRIBE alarm");

	if (!empty($_POST['alarm_name'])) {
		$updates = array();
		foreach ($_POST as $key=>$value) {
			if (isset($desc[$key])) {
				if ($desc[$key]['Null'] == 'YES')
					$updates[$key] = strlen($value)?trim($value):NULL; //treat empty string as NULL
				else
					$updates[$key] = $value;
			}
		}
		$db = GeographDatabaseConnection(false);
		if (!empty($_GET['edit'])) {
			$db->Execute('UPDATE alarm SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE alarm_id='.intval($_GET['edit']), array_values($updates));
		} else {
			$db->Execute('INSERT INTO alarm SET `'.implode('` = ?,`',array_keys($updates)).'` = ? ', array_values($updates));
		}

		header("Location: ?");
		exit;

	}


	print "<form method=post>";

	if (!empty($_GET['edit'])) {
		$row = $db->getRow("SELECT * FROM alarm WHERE alarm_id = ".intval($_GET['edit']));
	}

	print "<table>";
	foreach ($desc as $key => $data) {
		print "<tr><th>$key</th>";
		if (preg_match('/varchar\((\d+)\)/',$data['Type'],$m)) {
			print "<td><input type=text size=40 name=$key value=\"".htmlentities(@$row[$key])."\" maxlength={$m[1]} />";
		} elseif (preg_match('/text/',$data['Type'])) {
			print "<td><textarea name=$key rows=3 cols=80 wrap=soft>".htmlentities(@$row[$key])."</textarea>";
		} elseif (preg_match('/int\(10\) unsigned/',$data['Type'])) {
			print "<td><input type=number name=$key value=\"".htmlentities(@$row[$key])."\" />";
		}
		if (strpos($key,'max_') === 0)
			print "Alarm if MORE than this (optional)";
		if (strpos($key,'min_') === 0)
			print "Alarm if LESS than this (optional)";
	}
	print "</table>";
	print "<button type=submit>Submit</button>";
	print "</form>";
	exit;
}

############################################

$rows = $db->getAll("SELECT * FROM alarm WHERE active = 1");

print "<hr>";
foreach ($rows as $row) {
	if (!empty($row['url_head'])) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $row['url_head']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		$response = curl_exec($ch);
		if (curl_errno($ch)) {
		    echo 'Error:' . curl_error($ch);
		    exit();
		}
		curl_close($ch);
		$results = array(array());
		foreach (explode("\n",str_replace("\r",'',$response)) as $line)
			if (preg_match('/^(\w+[\w-]*\w): (.*)/',$line,$m))
				$results[0][$m[1]] = $m[2];

	} elseif (!empty($row['sql_query'])) {
		if (strpos($row['sql_query'],'$recent_ticket_item_id'))
			$row['sql_query'] = str_replace('$recent_ticket_item_id', $db->getOne("SELECT  max(gridimage_ticket_item_id)-1000 from gridimage_ticket_item"), $row['sql_query']);

		$results = $db->getAll($row['sql_query']);
	} else {
		continue;
	}

	if ($is_admin)
		print "<div style=float:right><a href=?edit={$row['alarm_id']}>Edit</a></div>";

	print "<h3>".htmlentities($row['alarm_name'])."</h3>";
	print "<small>".htmlentities($row['description'])."</small>";

	$good = $bad = 0;
	print "<table>";

	if (is_numeric($row['min_rows'])) //could be zero!
		result(count($results), count($results) >= $row['min_rows'], $row,'min_rows');
	if (is_numeric($row['max_rows']))
		result(count($results), count($results) <= $row['max_rows'], $row,'max_rows');

	foreach ($results as $result) {

		//checks "at least" this number
		if (!is_null($row['min_value'])) { //could be number or string!
			if (strpos($row['min_value'],'now') === 0) { //pretty fragile test
				$row['min_value'] = strtotime($row['min_value']);
				$result[$row['metric']] = strtotime($result[$row['metric']]);
			}

			$max_value = is_numeric($row['min_value'])?$row['min_value']:$result[$row['min_value']]; //find the max for this one line!

			result($result[$row['metric']], $result[$row['metric']] >= $max_value, $row, $result[$row['label']]);
		}

		//checks "at most"
		if (!is_null($row['max_value'])) { //could be number or string!
			$max_value = is_numeric($row['max_value'])?$row['max_value']:$result[$row['max_value']]; //find the max for this one line!

			result($result[$row['metric']], $result[$row['metric']] <= $max_value, $row, $result[$row['label']]);
		}
	}
	print "</table>";
	if (!$bad)
		print "<p>$good test".(($good==1)?'':'s')." ok</p>";
	print "<hr>";
}

############################################

function result($value,$result,$row,$label) {
	global $good,$bad;
	$result?$good++:$bad++;

	if ($result && empty($_GET['verbose']))
		return;

	if (is_numeric($value))
		$value = number_format($value,preg_match('/\.\d{4,}/',$value)?3:0); //dont want to round ints!

	$style = $result?'':'background-color:yellow;font-weight:bold';
	printf("<tr style=\"%s\"><td>%30s</td><td>%-40s</td><td>%s</td><td>%s</td>\n", $style, $row['alarm_name'], $label, $value, $result?'ok':'ALARM');
}

