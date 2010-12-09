<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

$db = NewADOConnection($GLOBALS['DSN']);


if (empty($_GET['topic_id'])) {
	?>
	<form>
		<input name="topic_id"><input type=submit>
	</form>
	<?
	exit;
}

if (!empty($_POST['new_id'])) {
	$ids = implode(',',array_keys($_POST['tick']));
	if (preg_match('/[^\d,]/',$ids))
		die("oops");

	$sql = "UPDATE geobb_posts SET topic_id = ".intval($_POST['new_id'])." WHERE topic_id = ".intval($_GET['topic_id'])." AND post_id IN ($ids)";

	print $sql;
	$db->Execute($sql);	
}


?>
<form action="?topic_id=<? echo intval($_GET['topic_id']); ?>" method="post">

<table>

<?

$data = $db->getAll("SELECT post_id,poster_name,post_time FROM geobb_posts WHERE topic_id = ".intval($_GET['topic_id'])." ORDER BY post_id");

foreach ($data as $row) {
	print "<tr>";
	print "<td><input type=checkbox name=\"tick[{$row['post_id']}]\"></td>";
	print "<td>{$row['poster_name']}</td>";
	print "<td>{$row['post_time']}</td>";
	print "</tr>";
}

?>
</table>
<input name="new_id">
<input type=submit>
</form>
	
