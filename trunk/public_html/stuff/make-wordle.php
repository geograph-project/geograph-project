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


$db = NewADOConnection($GLOBALS['DSN']);


if (isset($_GET['mine']) && $USER->user_id) {
	$sql = "select title from gridimage_search where user_id = {$USER->user_id}";
} else {
	$max = $db->getOne("select max(gridimage_id) from gridimage_search"); //Select tables optimized away

	$sql = "select title from gridimage_search where gridimage_id > ".($max-1200)." order by gridimage_id limit 1000";
}

$wordcount = array();

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF) {
	$words = preg_split('/[^a-zA-Z0-9]+/',str_replace("'",'',$recordSet->fields['title']));

	foreach ($words as $word) {
		@$wordcount[$word]++;	
	}

	$recordSet->MoveNext();
}
$recordSet->Close(); 

arsort($wordcount,SORT_NUMERIC);

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