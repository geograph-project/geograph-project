<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2302 2006-07-05 12:15:49Z barryhunter $
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

$smarty = new GeographPage;

$USER->hasPerm("director") || $USER->mustHavePerm("admin");

$db = NewADOConnection($GLOBALS['DSN']);

$smarty->display('_std_begin.tpl');

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";


#################################################

$where = array();

$limit = 2500;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

#################################################

$filters = array();
$filters['src_table'] = 'list';
$filters['src_col'] = 'list';
$filters['deviceused'] = 'list';
$filters['sentiment'] = 'list';
$filters['type'] = 'list';
$filters['feature'] = 'list';

//$filters[''] = array('','');

print "<form method=get style=background-color:#eee;padding:5px>Filter:";
foreach ($filters as $name => $rows) {
	if ($rows == 'text') {
		print "<input type=search name=$name value=\"".htmlentities(@$_GET[$name])."\" onkeyup=\"if (event.key == 'Enter') {this.form.submit(); }\" title=$name size=10 placeholder=$name>";
		if(!empty($_GET[$name])) {
			$where[] = "p.$name LIKE ".$db->Quote($_GET[$name]);
		}
	} else {
		print "<select name=$name onchange=this.form.submit() title=$name placeholder=$name>";
		print "<option value=\"\" style=\"color:grey\">$name</option>";
		if ($name == 'deviceused')
			$rows = $db->getAll("select substring_index(deviceused,'(',1) as deviceused,count(*) as c from survey_combined group by substring_index(deviceused,'(',1)");
		else
			$rows = $db->getAll("SELECT $name, COUNT(*) AS c FROM survey_parsed GROUP BY $name");

		foreach ($rows as $row) {
			$value = $row[$name];
			printf('<option value="%s"%s>%s [%d]</option>',$value, (@$_GET[$name] == $value)?' selected':'', $value, $row['c']);
			if(@$_GET[$name] == $value) {
				if ($name == 'deviceused') {
					$where[] = "deviceused LIKE ".$db->Quote($_GET[$name]."%");
				} elseif (preg_match('/^!(\w+)/',$value,$m)) {
					$where[] = "p.$name != ".$db->Quote($_GET[$name]);
				} else {
					$where[] = "p.$name = ".$db->Quote($_GET[$name]);
				}
			}
		}
		print "</select>";
	}
}
print "<br>";

$checked = empty($_GET['orig'])?'':'checked';
print "<label class=nowrap><input type=checkbox name=orig $checked onclick=this.form.submit()>Full Comments</label>";

$checked = empty($_GET['single'])?'':'checked';
print "<label class=nowrap><input type=checkbox name=single $checked onclick=this.form.submit()>Just Comments</label>";

$checked = empty($_GET['prompt'])?'':'checked';
print "<label class=nowrap><input type=checkbox name=prompt $checked onclick=this.form.submit()>AI Classification Prompt</label>";

print "</form>";

if (count($where) > 1 && $limit == 1000)
	$limit=10000;

#################################################

if (empty($where)) $where[] = 1;


if (!empty($_GET['single']) || !empty($_GET['prompt'])) {
	if (!empty($_GET['orig']))
		$cols = "CONCAT_WS('; ',moreabout,anythingelse) as comments";
	else
		$cols = "phrase";
} else {
	$col = "phrase";
	if (!empty($_GET['orig']))
		$col = "moreabout,anythingelse";
	$cols = "src_table, typeofuser, howoften, src_col, sentiment, type, $col, auto_id as rowid";
}

$sql = "select distinct $cols
from survey_parsed p
	INNER JOIN survey_combined2 USING (auto_id, src_table)
where ".implode(" AND ",$where)."
order by auto_id
LIMIT $limit";

if (!empty($_GET['debug']))
	print htmlentities($sql).";";

#################################################

if (!empty($_GET['prompt'])) {
	print "Sample prompt (for testing, maybe a more refined prompt would work?):<br><br>";
	print "<textarea wrap=off rows=40 cols=120 style='width:100%'>";
	print "You are an expert sentiment analysis and structured data extraction AI.\n";
	print "Please analyse these phrases, and extract the common subjects, clustering where multiple comments refer to the same underlying issue.\n";
	print "Seperately list out the phrases that are too anbigious to understand and group with others.\n\n";
	$data = $db->getCol($sql);
	foreach($data as $value)
		print "* ".htmlentities($value)."\n";
	print "</textarea>";
	exit;
} else {

	$count = dump_sql_table($sql,"Results");

	if ($count == $limit) {
		print "Last $limit Results";
	} else {
		print "All $count Results";
	}
}

$smarty->display('_std_end.tpl');

#################################################


function dump_sql_table($sql,$title) {
	global $db;

	$recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";
	if ($recordSet->EOF)
		return;

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='4' bordercolor=#eee  class='report sortable' id='photolist'><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD>";
	print "<TBODY>";
	$last = null;
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR>";
		$align = "left";
		foreach ($row as $key => $value) {
			$align = is_numeric($value)?"right":"left";
			print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
		}
		print "</TR>";

		$recordSet->MoveNext();
	}

	print "</TBODY></TABLE>";

	return $recordSet->RecordCount();
}


