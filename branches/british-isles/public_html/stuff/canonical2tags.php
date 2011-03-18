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

$USER->mustHavePerm("basic");


$smarty = new GeographPage;


	
	$smarty->display('_std_begin.tpl');
	
	print "<h2>Converting Canonical Categories to Tags</h2>";
	
	if (!empty($_POST['map'])) {
	
		$db = GeographDatabaseConnection(false);
		
		
		foreach ($_POST['map'] as $imageclass => $canonical) {
			$sql = "INSERT INTO category_canonical_2tag SET imageclass = '".mysql_real_escape_string($imageclass)."',canonical = '".mysql_real_escape_string($canonical)."',user_id = {$USER->user_id},created=NOW()";
			
			$db->Execute($sql);
		}
		
		print "Your category list has been saved - please note it can take 24 hours for the tags to be created";
		
	} else {
	
	
		$db = GeographDatabaseConnection(true);
		
		if ($db->getOne("SELECT imageclass FROM category_canonical_2tag WHERE user_id = {$USER->user_id}")) {
			print "This process has already been run by you.";
			
		} else {
		
		
			if (isset($_GET['single'])) {
				$table = "category_canonical_log AS cc";
				$where = ' AND cc.user_id = 10354';

				print "<p>Choose one: <b>Single User Set</b> | <a href=\"?\">Colaboritive set</a></p>";
			} else {
				$table = "category_canonical AS cc";
				$where = '';

				print "<p>Choose one: <a href=\"?single=1\">Single User Set</a> | <b>Colaboritive set</b></p>";
			}


			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$list = $db->getAll("
			SELECT 
				imageclass,COALESCE(canonical_new,canonical) AS canonical,COUNT(*) AS images
			FROM $table 
				INNER JOIN gridimage_search gi USING (imageclass)
				LEFT JOIN category_canonical_rename ON (canonical=canonical_old)
			WHERE gi.user_id = {$USER->user_id} $where
			AND imageclass != canonical AND canonical != '-bad-'
			AND CONCAT(imageclass,'s') != canonical
			GROUP BY imageclass
			");



			print "<form method=post><table cellspacing=0 cellpadding=3 border=1 bordercolor=#dddddd>";
				print "<tr>";
				print "<th>Category</th>";
				print "<th>Images</th>";
				print "<th>Canonical</th>";
				print "<th>Apply</th>";
				print "</tr>";
			foreach ($list as $row) {
				print "<tr>";
				print "<td>".htmlentities($row['imageclass'])."</td>";
				print "<td align=right>".htmlentities($row['images'])."</td>";
				print "<td>".htmlentities($row['canonical'])."</td>";
				print "<td><input type=checkbox name=\"map[".htmlentities($row['imageclass'])."]\" value=\"".htmlentities($row['canonical'])."\" checked /></td>";
				print "</tr>";
			}
			print "</table>";
			print "<p><input type=submit value=\"add these tags\"> NOTE: This button can ONLY BE PRESSED ONCE - double check you want to apply the selected canonicals above.</p>";

			print "</form>";
		}
	}
	$smarty->display('_std_end.tpl');

