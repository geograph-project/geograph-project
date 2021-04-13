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


$smarty->display('_std_begin.tpl');

?>
<div class="tabHolder">
        <a class="tab nowrap" href="category_mapping.php">A) Check Conversions</a>
        <a class="tabSelected nowrap">B) Apply Conversions</a>
        <a class="tab nowrap" href="category_mapping_stats.php">Statistics</a>
</div>
<div class="interestBox">
        <h2>Bulk Category --> Context, Subjects and Tags convertor :: Apply</h2>
</div>
<?


if (!empty($_GET['imageclass'])) {
	$category = $_GET['imageclass'];
	$row = $db->getRow("SELECT * FROM category_mapping_change WHERE user_id = {$USER->user_id} AND `action` = 'checked' AND imageclass like ".$db->Quote($category)." ORDER BY change_id DESC LIMIT 1");
} else {
	$row = $db->getRow("SELECT c.* FROM category_mapping_change c LEFT JOIN category_mapping_done d USING (imageclass,user_id) WHERE category_mapping_done_id IS NULL AND user_id = {$USER->user_id} AND `action` = 'checked' ORDER BY change_id DESC LIMIT 1");
}

if (empty($row)) {
	die("No checked, but unapplied categories found. Just the Check Conversions link above to see if you have any categories still to check");
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




if (empty($suggestions)) {
	die("no images found for this category");
}
if (empty($suggestions[$category]['images'])) {
	die("no images found for this category");
}


print "<div style='float:right;width:300px;background-color:#eee;padding:10px;border:1px solid black'>";
	print "<h4>Change Log for this Category</h4>";
	$row = $suggestions[$category];
	foreach (explode(',','context1,context2,context3,subject,tags,canonical') as $key) {
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
print "<hr/>";
if (!empty($more)) {
	print "<b>The items in gray where added AFTER you checked this category - and so NOT reflected on the left.</b> ";
	print "<a href=\"category_mapping.php?imageclass=".urlencode($category)."&action=checked\">Click here to approve the additions, so you can use them on your images.</a> or ";
}

print "<a href=\"category_mapping.php?imageclass=".urlencode($category)."&show=1\">Make further changes for this category conversion</a>";

print "</div>";

print "<br>Category: <big style=background-color:yellow>".htmlentities($category)."</big>";

print "<h4>Tag(s) to be added: (as when clicked 'checked')</h4>";
print "<ul>";
$row = $suggestions[$category];

if (!empty($row['canonical']) && strtolower($row['canonical']) == strtolower($row['canonical']) ) {
	$row['canonical'] = '';
}

$tags = array();
foreach (explode(',','context1,context2,context3,subject,tags,canonical') as $key) {
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
			} elseif (strpos($key,'context') === 0) {
				$tags[] = 'top:'.$row[$key];
			} else {
				$tags[] = $row[$key];
			}
		}
	}
}
print "</ul>";




if (!empty($tags)) {

	$words = preg_split('/[^\w]+/',strtolower($category));
	$taglist = implode(';',$tags);

	print "Words: ";
	foreach ($words as $word) {
		if (preg_match('/\b'.preg_quote($word,'/').'\b/i',$taglist)) {
			print "<span style=background-color:lightgreen>".htmlentities($word)."</span> ";
		} else {
			print "<span style=background-color:pink>".htmlentities($word)."</span> ";
		}
	}
	print "(red means the word is not mentioned in the tags)<br/><br/>";


	$url = "/search.php?imageclass=".urlencode($category)."&do=1&user_id=".$USER->user_id;
	print "Affects <a href=\"$url\">{$row['images']} Images</a>, <b>creating ".(count($tags)*$row['images'])." tags</b>.";
	print "<hr style=clear:both>";

	if (empty($_POST['confirm'])) {
		?>
		<b>Does this mapping perserve ALL information from the category?</b> Consider that the category will be <b>removed from the image</b>, so the above tags being added should perserve all the detail possible.<br/><br/>

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

	$tagobj = new Tags;
	$tagobj->_setDB($db);

	$taglist = implode(';',$tags);
	foreach ($tags as $tag) {
		$tag_id = $tagobj->getTagId($tag);
		if (empty($tag_id)) {
			die("Unable to find tag_id for ".htmlentities($tag));
		}
		$sqls[] = "INSERT INTO gridimage_tag SELECT gridimage_id,$tag_id as tag_id,gi.user_id,NOW() as created, 2 as status,NOW() as updated ".$sqlfrom." ON DUPLICATE KEY UPDATE status = 2";
	}

	$sqls[] = "INSERT INTO category_mapping_done SELECT NULL AS category_mapping_done_id, $category_quote AS imageclass, {$USER->user_id} AS user_id, gridimage_id, NOW() as created, ".$db->quote($taglist)." AS taglist ".$sqlfrom;



	//todo, we could do this, but for now we can just remove the category later using category_mapping_done table!
	//$sqls[] = "UPDATE gridimage SET imageclass = '' WHERE imageclass LIKE $category_quote AND user_id = {$USER->user_id} AND moderation_status IN ('geograph','accepted')";
	//$sqls[] = "UPDATE gridimage_search SET imageclass = '' WHERE imageclass LIKE $category_quote AND user_id = {$USER->user_id}";


	$total = 0;
	foreach ($sqls as $sql) {
		$db->Execute($sql);
		$affected= $db->Affected_Rows();
		$total+=$affected;
	}

	print "Actions Performed = $total. Images affected = $affected. ";


	print "<a href=\"?\">Next &gt;&gt;</a>";

}



$smarty->display('_std_end.tpl');


