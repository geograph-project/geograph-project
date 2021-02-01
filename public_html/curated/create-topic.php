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
$USER->mustHavePerm("basic");


$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_POST['label'])) {
	$updates = array();
        $updates['user_id'] = intval($USER->user_id);
        $updates['label'] = $_POST['label'];
        $updates['group'] = $_POST['group'];
	if (!empty($_POST['group_other']))
		 $updates['group'] = $_POST['group_other'];
	$updates['active'] = -1; //just a plcehoder

	$db->Execute('INSERT INTO curated1 SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	print "Created, thank you!";

}

if (!empty($_POST['label']) && (!empty($_POST['description']) || !empty($_POST['welsh'])) ) {

	$updates = array();
        $updates['user_id'] = intval($USER->user_id);
        $updates['label'] = $_POST['label'];
        $updates['description'] = $_POST['description'];
        $updates['source'] = $_POST['source'];
        $updates['welsh'] = $_POST['welsh'];

	$db->Execute('INSERT INTO curated_headword SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

	if (!empty($_GET['name'])) {
		$db->Execute('UPDATE curated_label SET label = '.$db->Quote($_POST['label']).' WHERE name = '.$db->Quote($_GET['name']));
	}
	print "Added ".htmlentities($_POST['label']).". Thank you";
}


$data = $db->getAll("SELECT `group` FROM curated1 GROUP BY  `group`");

?>

<h3>Add Topic to system</h3>
<p>Before adding may want to review the current <a href="/curated/all-topics.php">list of all topics</a>,
or a <a href="/curated/topics.php?group=Geography+and+Geology">more succinct list of Geography and Geology terms</a></p>

<form method=post style=background-color:#eee;padding:10px;>
Group: <select name="group" onchange="document.getElementById('more').style.display = (this.value.indexOf('Geography') == 0)?'':'none';"><option>
<?
foreach ($data as $row) {
	printf('<option>%s</option>',htmlentities($row['group']));
}

?></select> or create new: <input type=search name=group_other size=40> <br><br>


<b>Label</b>: <input type=search name=label size=20 maxlength=64 <? if (!empty($_GET['label'])) { echo "value=\"".htmlentities($_GET['label'])."\""; } ?>>

<hr>
<div style="display:none" id="more">
	<b><i>Optional:</i></b><br><br>

	We are looking semi-formal definitions of each term. Where a term may have multiple meanings looking for the Geography and Geology themed definition, only.

	The definition would help the curator select images for the term, as well as provide explanation for viewers.<br><br>

	Definition:<br>
	<textarea name="description" rows=4 cols=80 maxlength=2048></textarea><br>
	Source: <input name="source" maxlength=512 size=50 value=""><br><br>

	<i>Please provide source for any definition, you add. Either a URL, or at least a short reference. If wrote the definition yourself, put your name in box!</i><br><br>


	Label in <b>Welsh</b>: (if known)<br>
	<input name="welsh" maxlength=64 size=50 value="">

	<hr>
</div>

<input type=submit value="create">

</form>

<?

$smarty->display('_std_end.tpl');



