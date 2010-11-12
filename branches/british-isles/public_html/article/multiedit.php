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
		$updates['edit_prompt'] = $_POST['edit_prompt'][$id];
		$updates['parent_url'] = $_POST['parent_url'][$id];
		
		$db->Execute($sql = "UPDATE article SET update_time=update_time,`".implode('` = ?,`',array_keys($updates)).'` = ? WHERE article_id = '.$id.' AND user_id = '.$USER->user_id,array_values($updates));
		print "$sql<hr/>";
	}
}




$data = $db->getAll("SELECT article_id,url,title,licence,complete,edit_prompt,parent_url,approved FROM article WHERE user_id = {$USER->user_id} ORDER BY article_id");

$completes = array_merge(array(0=>'',1),range(10,90,10),array(98,100));

$apps = array(-1=>'deleted',0=>'draft',1=>'Published',2=>'Collaborative');

?>

<h2>Article Multi-Editor</h2>

<ul>
<li><b>Completeness</b> - a rough estimate of how complete the article is compared to what it could/should be</li>
<li><b>Edit Prompt</b> - For open collaboration articles, a short message to prompt users to edit the article. (adding a message marks the article to be opened for public editing)
</li>
<li><b>Parent URL</b> - Optional, full URL to parent article if there is one. To be used by articles that are in a group.</li>
</ul>

<form method=post><table border=1 cellspacing=0 cellpadding=3>
	<tr>
	<th>Article</th>
	<th>Completeness</th>
	<th>Edit Prompt</th>
	<th>Parent URL</th>
	<th>Licence</th>
	<th>Status</th>
	</tr>
	<? foreach($data as $row) {
		$id = $row['article_id'];
		print "<tr>";
		print "<td><a href=\"./{$row['url']}\" target=preview>{$row['title']}</a></td>";
		print "<td><select name=\"complete[$id]\" style=text-align:right>";
		foreach ($completes as $value) {
			printf("<option value=\"%s\"%s>%s</option>",$value,($row['complete']==$value)?' selected':'',$value);
		}
		print "</select>%</td>";
		print "<td><input type=text name=\"edit_prompt[$id]\" value=\"".htmlentities($row['edit_prompt'])."\"></td>";
		print "<td><input type=text name=\"parent_url[$id]\" value=\"".htmlentities($row['parent_url'])."\"></td>";
		print "<td>{$row['licence']}</td>";
		print "<td>{$apps[$row['approved']]}</td>";


	} ?>

</table><input type=submit value="Save Changes"/></form>


<?

$smarty->display('_std_end.tpl');

