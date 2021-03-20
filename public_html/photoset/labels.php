<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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


$smarty->display('_std_begin.tpl');

?>
<h2>Curated Images - current Topic List</h2>

<p>This is the current topic list. It's still in development, and needs fleshing out. Contact us, if would like to help curate the list of topics themselves.
<? if ($USER->registered) { ?>
<i>(can suggest images for known topics, via the <a href=coverage.php>Coverage</a> page)</i>.
<? } ?></p>


<hr><blockquote style="background-color:#eee;padding:20px;margin:0">
<?


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$where = array();
$where[] = "active > 0";
$where[] = "gridimage_id > 0";
if (isset($_GET['group']))
        $where[] = "`group` = ".$db->Quote($_GET['group']);

$where = implode(" AND ",$where);

//

$data = $db->getAll("SELECT `group`,`label`,COUNT(*) as count FROM curated1 INNER JOIN curated_headword USING (label) WHERE $where GROUP BY `group`,`label` HAVING count > 3");

$last = '';
$prev = '';
$sep = '';

foreach ($data as $row) {
	if ($last != $row['group']) {
		if ($last)
			print "<br><br><hr>";
		print "&gt; <b>".htmlentities(to_title_case($row['group']))."</b><br><br>";
		$last = $row['group'];
		$sep = '';
	}

	print $sep;

	$link = "view.php?label=".urlencode($row['label']);
	$text = htmlentities(to_title_case($row['label']));
	$first = substr($text,0,1);
	if (strcasecmp($first,$prev) != 0) {
		$text = preg_replace('/^(\w)/','<b>$1</b>',$text);
		$prev = $first;
		print "<br>";
	}

	if ($row['count']>50) $row['count']=50; //there may be more, but our phtooset dsiplay only displays 50!

	print "<a href=\"$link\" title=\"{$row['count']} image(s)\">$text</a>";

	$sep = ', ';
}

print "</blockquote>";

$smarty->display('_std_end.tpl');



