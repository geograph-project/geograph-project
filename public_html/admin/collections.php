<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

($USER->user_id == 4827) || $USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$smarty->display('_std_begin.tpl');
flush();

?><h2>Featured Collection Editor</h2>
<ul>
	<li>Use this page to edit featured collections - Currently only shown on the geograph.org.uk homepage.
	<li>Can set a date that a collection <b>begins</b> showing - will continue showing until a new collection is available.
	<li><b>Can Schedule collections to show on future dates. Will automatically become live on that day</b>
	<li>Can also add items to the pool, which will be remembered, but wont show until a date is added. (at the moment no automatic selection from the pool is made)
	<li>Can add a new collection to either the list, by pasting in the url (with or without the domain name). Omit a date to add to the pool, or can schedule it immidatly by including date.
	<li>Note: only urls found in the Collections section of the site will work. But excludes themes topics (which aren't generally public)
	<li>There are four main sections to the list <ol>
		<li>the first is submission of a new collection,
		<li>then collections already scheduled with a future date (can change, or remove the date to move back to the pool),
		<li>then collections featured in the past
		<li>then collections in the pool, add a date to move to the scheduled section
	</ol></li>
</ul>
<?

if (!empty($_POST)) {
	if (!empty($_POST['url'])) {
		$updates = array();

		$p = parse_url($_POST['url']);

		if (!empty($p['path'])) {
			$updates['url'] = $p['path'];
		} elseif (strpos($updates['url'],'/') === 0) {
			$updates['url'] = $_POST['url'];
		}

		if (!empty($updates['url'])) {
			$updates['url'] = preg_replace('/(article|gallery)(\/.+)\/\d+$/','$1$2',$updates['url']);
			$updates['content_id'] = $db->getOne("SELECT content_id FROM content WHERE url = ".$db->Quote($updates['url']));

			if (!empty($updates['content_id'])) {
				if (!empty($_POST['showday']))
					$updates['showday'] = $_POST['showday'];

				$db->Execute('INSERT INTO content_featured SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			} else {
				print htmlentities($updates['url'])." does not appear to be a valid collection. ";
			}

		} else {
			print htmlentities($_POST['url'])." does not appear to be a valid url. ";
		}
	}

	if (!empty($_POST['day'])) {
		foreach($_POST['day'] as $content_id => $showday) {
			if (empty($showday))
				$db->Execute('UPDATE content_featured SET showday = NULL WHERE content_id = '.$db->Quote($content_id) );
			else
				$db->Execute('UPDATE content_featured SET showday = '.$db->Quote($showday).' WHERE content_id = '.$db->Quote($content_id) );
		}
	}
}



?>
<form method=post>
<table cellpadding=10>
	<tr>
		<th>Content</th>
		<th>Day</th>
		<th>Added</th>
	</tr>
	<tr style="background-color:#eee">
		<td><input type=text name=url value="" size=50></td>
		<td><input type=text name=showday value="" placeholder="YYYY-MM-DD" size=10></td>
		<td><input type=submit value="Add"></td>
	</tr>
<?

$today = date('Y-m-d');
$ids = array();




foreach ($db->getAll("SELECT f.*,title,realname,user_id FROM content_featured f INNER JOIN content USING (content_id) LEFT JOIN user USING (user_id) ORDER BY showday DESC") as $row) {
	if (empty($row['showday'])) $ids[] = $row['content_id'];
?>
        <tr>
                <td><b><a href="<? echo htmlentities($row['url']); ?>"><? echo htmlentities($row['title']); ?></a></b>
			by <a href="/profile/<? echo $row['user_id']; ?></a>"><? echo htmlentities($row['realname']); ?></a>
			<br><? echo htmlentities($row['url']); ?></td>
                <td><? if (empty($row['showday']) || $row['showday'] > $today) { ?>
			<input type=text name="day[<? echo $row['content_id']; ?>]" value="<? echo $row['showday']; ?>" placeholder="YYYY-MM-DD" size=10>
		<? } else { ?>
			<? echo $row['showday']; ?>
		<? } ?></td>
                <td><? echo htmlentities($row['created']); ?></td>
        </tr>
<?


}
?>

	<tr>
		<td></td>
		<td><input type=submit value="Update"></td>
		<td></td>
	</tr>

</table>
</form>
<?

if (!empty($ids))
	print "<a href=\"/content/?scope=*&amp;ids=".implode(',',$ids)."\">View all unallocated collections in detailed form</a>";


$smarty->display('_std_end.tpl');

