<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
$USER->mustHavePerm("tagsmod");

	$db = GeographDatabaseConnection(false);

###########################################

if (!empty($_POST['tag'])) {
	foreach ($_POST['tag'] as $tag_id => $text) {
		$sql = "update IGNORE tag set tag = ".$db->Quote(trim($text))." WHERE tag_id = ".intval($tag_id);
		$db->Execute($sql);

		if (!$db->Affected_Rows()) {
			//if failed, it means there is already a tag!

			$u = $db->getRow("SELECT tag_id,prefix,tag FROM tag WHERE tag_id = ".intval($tag_id));
			$u['user_id'] = $USER->user_id;
			$u['tag2'] = trim($text); //prfix added back below!
			if (!empty($u['prefix'])) {
                        	$u['tag'] = $u['prefix'].":".$u['tag'];
                        	$u['tag2'] = $u['prefix'].":".$u['tag2'];
			}
			unset($u['prefix']);
			$u['type'] = 'spelling';
			$u['status'] = 'approved';
			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		}
	}
}

###########################################

	$smarty->display('_std_begin.tpl');

	print "<h2>Tags with unsupported chars</h2>";

	print "<p>Note this form, can only be used to edit the tags (to remove the unsupported chars) - not to split, or delete a tag";

	$data = $db->getAll("select p.tag_id,prefix,p.tag,count(gridimage_id) as count,gridimage_id
		from tag_public p
			left join tag_report r using (tag_id)
		where binary p.tag regexp '[^\\\\w ()+\\.&\\/!?%@#-]' and prefix != 'top' and gridimage_id < 10000000
			and r.tag_id IS NULL
		group by p.tag_id");

	if (!empty($data)) {
		print "<p>Allows chars: A-Z a-z 0-9 _ ( ) + . & / ! ? % @ # - (plus space)</p>";
		print "<form method=post>";
		print "<table>";
		foreach ($data as $row) {
			print "<tr>";
			print "<td>{$row['tag_id']}</td>";
			print "<td align=right>".htmlentities($row['prefix'])."</td>";
			print "<td>".htmlentities($row['tag'])."</td>";
			print "<td><input type=text data-name=\"tag[{$row['tag_id']}]\" size=60 value=\"".htmlentities($row['tag'])."\"></td>";
			 print "<td>({$row['count']})</td>";
			 print "<td>{$row['gridimage_id']}</td>";
		}
		print "</table>";
		print "<input type=submit>";
		print "</form>";

?>

 <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script>
$(function() {
	$('input[type="text"]').keyup(function() {
		if (this.value != this.defaultValue) {
			$(this).attr('required','required')
			.attr('pattern','^[\\\w ()+\\.&\\/!?%@#-]+$')
			.css({'backgroundColor':'lightgreen'})
			.attr('name',$(this).data('name')); //only add the name after editing, so only submit edited fields!
		}
	});
});
</script>

<?

	} else {
		print "<p>No outstanding tags right now</p>";
	}

	$smarty->display('_std_end.tpl');
