<?php
/**
 * $Project: GeoGraph $
 * $Id$
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


$db = GeographDatabaseConnection(true);

if (isset($_GET['tags'])) {
	$wordcount = $db->getAssoc("SELECT REPLACE(REPLACE(tag,',',''),' ','_'),COUNT(*) AS images FROM tag t INNER JOIN gridimage_tag gt USING (tag_id) WHERE t.status = 1 AND gt.status = 2 AND tag NOT LIKE '%/%' GROUP BY tag_id ORDER BY images DESC LIMIT 1000");
} else {
	$where = array();
	if (isset($_GET['mine']) && $USER->user_id) {
		$where[] = "user_id = {$USER->user_id}";
	} 

	if (!empty($_GET['u'])) {
		$where[] = "user_id = ".intval($_GET['u']);
	}

	if (!empty($_GET['myriad']) && preg_match('/^\w{1,3}$/',$_GET['myriad'])) {
		$where[] = "grid_reference like '{$_GET['myriad']}____'";
	}

	if (!empty($_GET['hectad']) && preg_match('/^(\w{1,3}\d)(\d)$/',$_GET['hectad'],$m)) {
		$where[] = "grid_reference like '{$m[1]}_{$m[2]}_'";	
	}

	if (!empty($_GET['gridref']) && preg_match('/^(\w{1,3})(\d{4})$/',$_GET['gridref'],$m)) {
		$where[] = "grid_reference = '{$_GET['gridref']}'";
	}

	if (!empty($_GET['category'])) {
		$where[] = "imageclass = ".$db->Quote($_GET['category']);	
	}

	if (!empty($_GET['when']) && preg_match('/^\d{4}-\d{2}(-\d{2}|)$/',$_GET['when'])) {
		if (strlen($_GET['myriad']) == 10) {
			$where[] = "imagetaken = '{$_GET['when']}'";
		} else {
			$where[] = "imagetaken like '{$_GET['when']}%'";
		}
	}

	if (count($where)) {	
		$sql = "select title from gridimage_search where ".implode(' and ',$where);
		if (empty($_GET['u']) && empty($_GET['mine'])) {
			$sql .= " limit 10000";
		}
	} else {
		$max = $db->getOne("select max(gridimage_id) from gridimage_search"); //Select tables optimized away

		$sql = "select title from gridimage_search where gridimage_id > ".($max-1200)." order by gridimage_id limit 1000";
	}

	$wordcount = array();

	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF) {
		$words = preg_split('/[^a-zA-Z0-9]+/',trim(str_replace("'",'',$recordSet->fields['title'])));

		foreach ($words as $word) {
			@$wordcount[$word]++;	
		}

		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	unset($wordcount['']);
	arsort($wordcount,SORT_NUMERIC);
}

?>
<html>
<head>
<title>loading wordle...</title>
</head>
<body onload="document.theForm.submit()">
<form action="http://www.wordle.net/compose" method="post" name="theForm"> 
<input type=hidden name=wordcounts value="<? 
$c = 0;
foreach ($wordcount as $word => $count) {
	print "$word:$count";
	if ($c > 1000) {
		break;
	}
	print ",";
	$c++;
}
?>"> 
<input type=submit value="if nothing happens within 10 seconds click here"> 
</form>
</body>
</html>