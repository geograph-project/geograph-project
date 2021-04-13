<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 barry hunter (geo@barryhunter.co.uk)
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

	$USER->mustHavePerm("tagsmod");


$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);

	if (!empty($_POST)) {
		$sql = "UPDATE category_primary SET sort_order = sort_order+1 WHERE sort_order >= ".intval($_POST['sort_order']);
		$db->Execute($sql);

		$updates = $_POST;
		$updates['ids'] = trim(preg_replace('/[^\d]+/',', ',$_POST['ids']),', ');
		$updates['description'] = implode(' | ',$updates['description']);

		$db->Execute('INSERT INTO category_primary SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

	}



		//	$db->Execute("INSERT INTO tag_report_skip SET report_id = $report_id, user_id = {$USER->user_id}, created = NOW()");

	dump_sql_table("select tag_id,t.tag,t.user_id,t.created,t.updated,canonical,sum(gt.status = 2) as images from tag t left join category_primary on (t.tag = top) left join gridimage_tag gt using (tag_id) where prefix = 'top' and grouping is null and t.status = 1 group by tag_id",
		'Unoffical Content (been created as a top:prefix tag, but not in the offical tag list)');

	dump_sql_table("select tag_id,tag.user_id,tag_report.tag,tag2,tag_report.status,approver_id from tag inner join tag_report using (tag_id) where prefix = 'top' and tag.status = 0 order by tag_id",
		'Previouslly created top: tags, that have already been redirected');
	print "Any time one of these tags is added to an image, the tag will automatically be converted to the second form";

	?>

	<p>For each context above, there are three options</p>

	<h3>1. Rename the tag to be the same as existing Context tag.</h3>

	<p>Effectively merges the two tags, and removes the unapproved tag. Use <a href="/tags/report.php?admin=1">Admin Report form</a>. Will get a warning about not using the form to merge, or edit top: tags. In this instance it can be igored</p>


        <h3>2. Rename the tag remove the 'top:' prefix</h3>

	<p>Make it jsut like any other normal tag. Goto <a href="/tags/report.php?admin=1">Admin Report form</a>. Will get a warning about not using the form to merge, or edit top: tags. In this instance it can be igored</p>


	<h3>3. Create a New Approved Geographical Context</h3>
	(Note, if the capitaliation of the tag needs fixing first (capitalization IS import for Context tags), then use the admin form to change. <b>Once the action has been actioned, return here</b>)
	<br>
	<form method=post>
		Name: <select name="top" required><option></option>
			<?
			foreach ($db->getCol("SELECT t.tag FROM tag t left join category_primary on (t.tag = top) where prefix = 'top' and grouping is null and t.status = 1 ORDER BY t.tag") as $value) {
				print "<option>$value</option>";
			} ?>
		</select><br><br>

		Group: <select name="grouping" required><option></option>
			<?
			foreach ($db->getCol("SELECT DISTINCT grouping FROM category_primary") as $value) {
				print "<option>$value</option>";
			} ?>
		</select><br><br>

		Description:  <input type="text" name="description[]" required size="64" maxlength="128"> (short puncy descrtiption - about 7 words - less than 128 chars. Full stop at end)<br><br>

		Longer Desc:<textarea name="description[]" required rows=3 cols=70></textarea> (shown after the short version, as a continuation)<br><br>

		Images: <input type="text" name="ids" required size="64" maxlength="64"> (List of FIVE image ids seperated by comma, dont actully need to be using the tag)<br><br>

		Insert before: <select name="sort_order" required><option></option>
                        <?
                        foreach ($db->getAll("SELECT sort_order,top FROM category_primary ORDER BY sort_order") as $row) {
                                print "<option value={$row['sort_order']}>{$row['top']}</option>";
                        } ?>
                </select><br><br>

		<input type=submit>
	</form>


<?


dump_sql_table("SELECT grouping,sort_order,top,substring_index(description,'|',1) as short,ids FROM category_primary ORDER BY sort_order","Existing Context for reference");


$smarty->display('_std_end.tpl');

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;

        $recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

        print "<H3>$title</H3>";

	if ($recordSet->EOF) {
	        print "0 rows";
	        return;
	}

	$row = $recordSet->fields;

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><THEAD><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR></THEAD><TBODY>";
        $keys = array_keys($row);
        $first = $keys[0];
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

                print "<TR>";
                $align = "left";
                if (is_null($row[$first])) {
                        $row['team'] = '-EVERYONE-';
                }
                foreach ($row as $key => $value) {
                        $align = is_numeric($value)?"right":'left';
                        print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
                }
                if (!empty($row['ip'])) {
                        print "<td>".gethostbyaddr($row['ip'])."</TD>";
                }
                print "</TR>";
		$recordSet->MoveNext();
	}
        print "</TR></TBODY></TABLE>";
}


