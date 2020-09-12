<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm('basic');

$smarty->display('_std_begin.tpl');

	print "<h2>Tasks on Geograph</h2>";

	$db=GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($_GET['category'])) {
	$rows = $db->getAll("SELECT category,COUNT(*) FROM project_task WHERE approved = 1 GROUP BY category");
?>
<p>We've devied the task list, by device type (and location) - as often the tasks want to do vary by location. </p>

<p>Some tasks might well be in each multiple categories, but should be added seperately. Just concentrate on one category at the time</p>

<?
	print "<form method=get>";
	print "<p>Please select a category: <select name=category onchange=\"this.form.submit()\">";
	print "<option></option>";
	foreach ($rows as $row) {
		printf('<option value="%s"%s>%s</option>',htmlentities($row['category']),'',htmlentities($row['category']));
	}
	print "</select>";
	print "<noscript><input type=submit></noscript>";
	print "</form>";


} else {
	$category = $_GET['category'];

	$rows = $db->getAll("SELECT t.*,sum(v.user_id = {$USER->user_id}) as mine,count(v.project_task_id) as votes
				FROM project_task t LEFT JOIN project_task_vote v USING (project_task_id)
				WHERE approved = 1 AND category = ".$db->Quote($category)." GROUP BY project_task_id ORDER BY RAND()");

	if (!empty($rows)) {
		$row = reset($rows);
		print "<h3>".htmlentities($row['category'])."</h3>";

?>
<p>Below is a list of tasks that visitors the to site may want to do. This includes all types of visitors, from general viewers, to contributors.</p>

<p>The list should Task oritentated - WHAT want to do, rather than HOW to do it. Some of these tasks may be possible now, some not. Think about what WANT to do, not what can do NOW. 

<p>Click the vote, if you want to vote for that particular task, ie you think it an important task, that lots of people would want to do.</p>

<p>Please add any tasks you think we have missed!</p>

<?
		print "<form method=post>";
		print "<table cellspacing=0 cellpadding=20 style=\"background-color:#eee\">";
		foreach ($rows as $row) {
			print "<tr>";
			print "<td title=\"".htmlentities($row['content'])."\" style=\"border-bottom:1px solid gray\">";
			print "<b>".htmlentities($row['title'])."</b>";
			print "</td>";

			print "<td>";
			if ($row['mine']) {
				print "Voted";
			} else {
				print "<input type=button value=Vote>";
			}
			print "</td>";
		}
		print "<tr>";
		print "<td><h3>Create new task</h3>";
		print "Category: ".htmlentities($row['category'])."<br>";
		print "<input type=text maxlength=100 size=50 name=title placeholder=\"short summary of task\"><br>";
		print "<textarea name=content rows=5 cols=80 placeholder=\"optional longer explanation\"></textarea>";
		print "</td>";

		print "<td>";
		print "<input type=submit value=\"Add new Task\">";

		print "</td>";
		print "</table>";
		print "</form>";

	} else {
		//categories have to be created direct in database first!
		print "unknown category";
	}
}





$smarty->display('_std_end.tpl');

