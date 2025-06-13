<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

###############################

$db = GeographDatabaseConnection(false);

if (!empty($_POST['check'])) {
	$all = array();
	foreach($db->getAll("SELECT content_id,category FROM content_cat") as $row) {
		@$all[$row['content_id']][$row['category']]=1;
	}

	foreach($_POST['check'] as $content_id => $cats) {
		$content_id = intval($content_id);
		foreach($cats as $category => $dummy) {
			if (!empty($all[$content_id][$category])) {
				unset($all[$content_id][$category]); //any left in the array will be removed, dont want to remove this one! (as still ticked)
			} else {
				//its a new one!
				$sql = "INSERT INTO content_cat SET content_id = $content_id, category = ".$db->Quote($category).", user_id = ".$USER->user_id;
				print "$sql;\n<hr>";
				$db->Execute($sql);
			}
		}
		//any that are still left in this array, where NOT ticked, hence need deleting!
		if (!empty($all[$content_id])) {
			foreach($all[$content_id] as $category => $dummy) {
				$sql = "DELETE FROM content_cat WHERE content_id = $content_id AND category = ".$db->Quote($category);
				print "$sql;\n<hr>";
				$db->Execute($sql);
			}
		}
	}
}


###############################

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

print "<form method=post>";

dump_sql_table("select content_id,source,url,title,group_concat(category) as categories
 from content left join content_cat using (content_id)
where type = 'document'
group by content_id",'Documents');

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;
	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";

        if ($recordSet->EOF) {
                print "0 rows";
                return;
        }

$cats = array();
$cats[] = 'New Contributors';
$cats[] = 'Contributors';
$cats[] = 'General Website';
$cats[] = 'Image Searching';
$cats[] = 'Technical';
$cats[] = 'Project';
$cats[] = 'Outdated';
$cats[] = 'Obsolete';

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\" style=position:relative><THEAD><TR style=\"position:sticky;top:0;\">";
	foreach ($cats as $value) {
		print "<TH style=max-width:45px;overflow:hidden>$value</TH>";
	}
	foreach ($row as $key => $value) {
			if ($key == 'url' || $key == 'content_id' || $key == 'categories')
				continue;
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD><TBODY>";
	$done = array();
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR>";
		$content_id = $row['content_id'];
		$categories = explode(',',$row['categories']??'');
		foreach ($cats as $value) {
			$checked = in_array($value,$categories)?' checked':'';
			print "<td><input type=checkbox$checked name=\"check[$content_id][$value]\" title=\"$value\"></td>";
		}

		$align = "left";
 		foreach ($row as $key => $value) {
			if ($key == 'url' || $key == 'content_id' || $key == 'categories')
				continue;
			if ($key == 'title') {
				print "<TD ALIGN=$align><a href=\"".htmlentities($row['url'])."\">".htmlentities($value)."</a></TD>";
			} elseif (is_numeric($value)) {
				print "<TD ALIGN=$align>".number_format($value,0)."</TD>";
			} else {
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			}
//			$align = "right";
		}
		print "</TR>";
		$done[] = array_shift($row);
                $recordSet->MoveNext();
	}

	print "</TR></TBODY></TABLE>";
	return $done;
}


?>
<input type=submit>
</form>
<style>
tr a {
	opacity:0.6
}
tr:hover {
	background-color:#eee;
}
tr:hover a {
	opacity:1

}

</style>

<?

$smarty->display('_std_end.tpl');

