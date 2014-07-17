<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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





if (empty($db))
	$db = GeographDatabaseConnection(false);


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['imageclass'])) {
	$category = $_GET['imageclass'];
	$row = $db->getRow("SELECT * FROM category_mapping_change WHERE user_id = {$USER->user_id} AND `action` = 'checked' AND imageclass like ".$db->Quote($category)." ORDER BY change_id DESC LIMIT 1");
} else {
	$row = $db->getRow("SELECT c.* FROM category_mapping_change c LEFT JOIN category_mapping_done d USING (imageclass,user_id) WHERE category_mapping_done_id IS NULL AND user_id = {$USER->user_id} AND `action` = 'checked' ORDER BY change_id DESC LIMIT 1");
}

if (empty($row)) {
	die("no category found, have you checked any? Click this link: <a href=\"category_mapping.php\">View Mapping</a> to view possible mappings, you can approve.");
}

$category = $row['imageclass'];
$approved = $row['created'];


$limit = 1;
$category_quote = $db->Quote($category);
$where = array();
$where[] = "m.imageclass like $category_quote";
$where[] = "gi.user_id = {$USER->user_id}";
$where = implode(' AND ',$where);

$suggestions = $db->getAssoc($sql = "SELECT m.*,c.canonical,count(distinct gridimage_id) images
	from category_mapping m inner join gridimage_search gi using (imageclass)
		left join category_canonical_log c using (imageclass)
		left join category_mapping_done using (gridimage_id)
	where $where and category_mapping_done_id IS NULL group by imageclass limit $limit");



$smarty->display('_std_begin.tpl');

if (empty($suggestions)) {
	die("no images found for this category");
}
if (empty($suggestions[$category]['images'])) {
	die("no images found for this category");
}


print "<div style='float:right;width:300px;background-color:#eee;padding:10px'>";
	print "<h4>Change Log</h4>";
	$row = $suggestions[$category];
	foreach (explode(',','context1,context2,context3,subject,tags') as $key) {
		if (!empty($row[$key])) {
			print "<b>$key</b>: ".htmlentities($row[$key])."<br>";
		}
	}
	print "<br/>";


$delta = $db->getAll("SELECT * FROM category_mapping_change WHERE imageclass like $category_quote AND action != 'checked' AND status > 0 ORDER BY change_id");

include "category_mapping_delta.inc.php";

$more = false;
foreach ($delta as $row) {
	if ($row['created'] > $approved) {
		print "<span style=color:silver>";
		$more = true;
	} else {
		print "<span>";
	}
	print "<b>{$row['field']}</b>: ".strtoupper($row['action'])." ".htmlentities($row['value']);
	print "</span><br>";
}

if (!empty($more)) {
	print "<br/><b>The items in gray where added after you checked this category, would you actully like to approve all of these?</b> ";
	print "<a href=\"category_mapping.php?imageclass=".urlencode($category)."&action=checked\">Click here to approve ALL these changes</a>";
}

print " or <a href=\"category_mapping.php?imageclass=".urlencode($category)."&show=1\">Update the changes for this category</a>";

print "</div>";

print "<h2>Category: ".htmlentities($category)."</h2>";

print "<h4>Will Add</h4>";
print "<ul>";
$row = $suggestions[$category];

$tags = array();
foreach (explode(',','context1,context2,context3,subject,tags') as $key) {
        if (!empty($row[$key])) {
		if (is_array($row[$key])) {
			foreach ($row[$key] as $tag) {
				if (!empty($tag)) {
					print "<li><b>$key</b>: ".htmlentities($tag)."</li>";
					$tags[] = $tag;
				}
			}
		} else {
	                print "<li><b>$key</b>: ".htmlentities($row[$key])."</li>";
			if ($key == 'subject') {
				$tags[] = 'subject:'.$row[$key];
			} elseif (strpos($key,'context') == 0) {
				$tags[] = 'top:'.$row[$key];
			} else {
				$tags[] = $row[$key];
			}
		}
	}
}
print "</ul>";




if (!empty($tags)) {

	$url = "/search.php?imageclass=".urlencode($category)."&do=1&user_id=".$USER->user_id;
	print "Affects <a href=\"$url\">{$row['images']} Images</a>, <b>creating ".(count($tags)*$row['images'])." tags</b>.";
	print "<hr style=clear:both>";

	if (empty($_POST['confirm'])) {
		?>
		Does this mapping perserve ALL information from the category? <small>Consider that the category will be <b>removed from the image</b>, so the above tags being added should perserve all the detail possible.</small><br/><br/>

		Also if at all possible this should be creating a <b>Geographical Context</b> for the image - but appreciate that some categories simply can't be used to create a context.<br/><br/>

		The vast majority of categories should be creating a <b>Subject too</b>, because the subject list was based on the category list<br/><br/>

		If you dont agree with these mappings, use the link on the right to return to edit screen to edit the conversion for this category.<br/><br/>

		<form method=post action="?imageclass=<? echo urlencode($category); ?>">
			<input type=submit name="confirm" value="confirm creating these tags now"/>
		</form>
		<br/><br/>

		To be clear, you are creating standard public tags on these images, and it only works on your own images, so can manually remove tags as if needed. Once this process has been run on your images, no more tags will be automatically added.
		<?
	}
} else {
	print "No Tags found to add";
}

if (!empty($tags) && !empty($_POST['confirm'])) {
	print "Processing...<br>";
	flush();

	$sqls = array();
	$sqlfrom = "FROM gridimage_search gi LEFT JOIN category_mapping_done USING (gridimage_id) WHERE category_mapping_done_id IS NULL AND gi.imageclass LIKE $category_quote AND gi.user_id = {$USER->user_id}";

	//todo, left join category_mapping_done to exclude done images!;

	$tagobj = new Tags;
	$tagobj->_setDB($db);

	$taglist = implode(';',$tags);
	foreach ($tags as $tag) {
		$tag_id = $tagobj->getTagId($tag);
if (empty($tag_id)) {
	die("Unable to find tag_id for ".htmlentities($tag));
}
		$sqls[] = "INSERT INTO gridimage_tag SELECT gridimage_id,$tag_id as tag_id,gi.user_id,NOW() as created, 2 as status ".$sqlfrom;
	}

	$sqls[] = "INSERT INTO category_mapping_done SELECT NULL AS category_mapping_done_id, $category_quote AS imageclass, {$USER->user_id} AS user_id, gridimage_id, NOW() as created, ".$db->quote($taglist)." AS taglist ".$sqlfrom;



	//todo, we could do this, but for now we can just remove the category later using category_mapping_done table!
	//$sqls[] = "UPDATE gridimage SET imageclass = '' WHERE imageclass LIKE $category_quote AND user_id = {$USER->user_id} AND moderation_status IN ('geograph','accepted')";
	//$sqls[] = "UPDATE gridimage_search SET imageclass = '' WHERE imageclass LIKE $category_quote AND user_id = {$USER->user_id}";


	$str = '';
	$total = 0;
	foreach ($sqls as $sql) {
		$str .= $sql.";\n";
		$db->Execute($sql);
		$affected= mysql_affected_rows();
		$str .= "#Affected = $affected\n";
		$total+=$affected;
	}
	$str .= "#Total = $total\n";
	$str .= "\n\n".'---------'."\n";
 	$str .= print_r($_SERVER,1)."\n";
        $str .= print_r($_POST,1)."\n";
        $str .= print_r($USER->realname,1)."\n";
        $str .= print_r($USER->user_id,1)."\n";
        $str .= print_r($USER->email,1)."\n\n\n";

        mail("barry@barryhunter.co.uk","[geograph] category bulk conversion",$str);

	print "Actions Performed = $total. Images affected = $affected. ";


	print "<a href=\"?\">Next &gt;&gt;</a>";

}



$smarty->display('_std_end.tpl');


