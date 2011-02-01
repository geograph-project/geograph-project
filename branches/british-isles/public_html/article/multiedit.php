<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

$db = GeographDatabaseConnection(false);
	
if (!empty($_POST)) {
	
	foreach ($_POST['complete'] as $id => $complete) {
		
		$updates= array();
		$updates['complete'] = $complete;
		foreach (array('article_cat_id','edit_prompt','parent_url') as $key) {
			if (isset($_POST[$key])) {
				$updates[$key] = $_POST[$key][$id];
			}
		} 
	
		$db->Execute($sql = "UPDATE article SET update_time=update_time,`".implode('` = ?,`',array_keys($updates)).'` = ? WHERE article_id = '.$id.' AND user_id = '.$USER->user_id,array_values($updates));
	}
}




$data = $db->getAll("SELECT article_id,url,title,article_cat_id,licence,complete,edit_prompt,parent_url,approved FROM article WHERE user_id = {$USER->user_id} ORDER BY article_id");

$completes = array_merge(array(0=>'',1),range(10,90,10),array(98,100));

$apps = array(-1=>'deleted',0=>'draft',1=>'Published',2=>'Collaborative');

if (!empty($_GET['cat'])) {
	#$cats = $db->getAssoc("SELECT article_cat_id, category_name FROM article_cat ORDER BY sort_order,article_cat_id");

	// now, retrieve all descendants of the $root node 
	$results = $db->getAssoc('SELECT article_cat_id, category_name, rgt FROM article_cat ORDER BY lft ASC'); 

	// start with an empty $right stack 
	$cats = $right = array();

	// display each row
	foreach ($results as $id => $row) {
		// only check stack if there is one
		if (count($right)>0) {
			// check if we should remove a node from the stack
			while ($right[count($right)-1]<$row['rgt'] && count($right)) {
				array_pop($right);
			}
		}

		// display indented node title 
		$cats[$id] = str_repeat('&middot;&nbsp;&nbsp;',count($right)).$row['category_name'];

		// add this node to the stack
		$right[] = $row['rgt'];
	}
}

?>
<script src="/sorttable.js"></script>

<h2>Article Multi-Editor</h2>

<ul>
<li><b>Completeness</b> - a rough estimate of how complete the article is compared to what it could/should be</li>
<li><b>Edit Prompt</b> - For open collaboration articles, a short message to prompt users to edit the article. (adding a message marks the article to be opened for public editing)
</li>
<li><b>Parent URL</b> - Optional, full URL to parent article if there is one. To be used by articles that are in a group.</li>
</ul>

<form method=post><table border=1 cellspacing=0 cellpadding=3 id="table" class="report sortable">
	<thead>
	<tr>
	<th>Article</th>
	<th>Complete?</th>
	<? if (!empty($_GET['cat'])) { ?>
		<th>Category</th>
	<? } else { ?>
		<th>Edit Prompt</th>
		<th>Parent URL</th>
	<? } ?>
	<th>Licence</th>
	<th>Status</th>
	</tr>
	</thead>
	<tbody>
	<? foreach($data as $row) {
		$id = $row['article_id'];
		print "<tr>";
		print "<td><a href=\"./{$row['url']}\" target=preview>{$row['title']}</a></td>";
		print "<td id=\"com$id\" sortvalue=\"{$row['complete']}\"><select name=\"complete[$id]\" style=text-align:right onchange=\"document.getElementById('com$id').setAttribute('sortvalue',this.value);\">";
		foreach ($completes as $value) {
			printf("<option value=\"%s\"%s>%s</option>",$value,($row['complete']==$value)?' selected':'',$value);
		}
		print "</select>%</td>";
		if (!empty($_GET['cat'])) {
			print "<td id=\"cat$id\" sortvalue=\"{$row['article_cat_id']}\"><select name=\"article_cat_id[$id]\"  onchange=\"document.getElementById('cat$id').setAttribute('sortvalue',this.value);\"><option></option>";
			foreach ($cats as $id => $value) {
				printf("<option value=\"%s\"%s>%s</option>",$id,($row['article_cat_id']==$id)?' selected':'',$value);
			}
			print "</select></td>";
		} else {
			print "<td><input type=text name=\"edit_prompt[$id]\" value=\"".htmlentities($row['edit_prompt'])."\"></td>";
			print "<td><input type=text name=\"parent_url[$id]\" value=\"".htmlentities($row['parent_url'])."\"></td>";
		}
		print "<td>{$row['licence']}</td>";
		if ($row['licence'] == 'none' && $row['approved']) {
			print "<td>Approved but hidden</td>";
		} else {
			print "<td>{$apps[$row['approved']]}</td>";
		}
		print "</tr>";
	} ?>
	</tbody>
</table><input type=submit value="Save Changes"/></form>


<?

$smarty->display('_std_end.tpl');

