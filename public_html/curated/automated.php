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

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

 $imagelist = new ImageList();

#######################################################################
//submit results

if (!empty($_GET['label'])) {
	if (!empty($_POST['submit'])) {

		function submit_results($gridimage_id, $set) {
			global $db;

			$wheres = array();
			$wheres['user_id'] = 23277; //Socket
                        $wheres['group'] = 'Automated';
                        $wheres['label'] = $_GET['label'];

			//we need to ne able to affect ALL imags!
			if ($gridimage_id)
				$wheres['gridimage_id'] = intval($gridimage_id);
			else
				$wheres['score'] = 10;

			$where = array();
			foreach($wheres as $key=>$value) {
				if (is_numeric($value))
					$where[] = "$key = $value";
				else
					$where[] = "`$key` = ".$db->Quote($value);
			}

			$update = "UPDATE curated1 SET $set WHERE ".implode(' AND ',$where);
		//	print "$update;<hr>";
			$db->Execute($update)  or die("$sql\n\n".$db->ErrorMsg()."\n");
		}

		if ($_POST['submit'] == 'bad') {
			//affects all!
			submit_results(0, "score = 1");
		} else {
			foreach($_POST['result'] as $gridimage_id => $verdict) {
				if ($gridimage_id = intval($gridimage_id)) {
					if ($verdict == 'good') {
						submit_results($gridimage_id, "score = score * 2");
					} elseif ($verdict == 'bad') {
						submit_results($gridimage_id, "score = score div 2");

					} //ok = noop
				}
			}
		}

		if ($_POST['submit'] == 'next') {
			if (empty($_SESSION['avoid']))
				$_SESSION['avoid'] = array($_GET['label']);
			else
				$_SESSION['avoid'][] = $_GET['label'];

			$list = "(".implode(',',array_map(array($db,'Quote'),array_values($_SESSION['avoid']))).")";
			$label = $db->getOne("  SELECT label FROM curated1 WHERE user_id = 23277 AND `Group` = 'Automated' AND score >=10 AND label NOT IN $list LIMIT 1");
			if ($label) {
				header("Location: ?label=".urlencode($label));
				exit;
			}
		}
		header("Location: ?");
		exit;
	}


#######################################################################
//render curation

	?>
	<link rel="stylesheet" href="automated.css?<? echo filemtime('automated.css'); ?>">

	<a href="?">&lt;&lt; Back to Listing</a> (without saving)</a>

	<form name="theForm" method=post>

<div class="curation-header">
    <h3>Images marked with <?
	$row = $db->getRow("SELECT stack,description FROM curated_label WHERE name = ".$db->Quote($_GET['label']));
	if (!empty($row['stack']))
		print htmlentities($row['stack']." > ");
	print "<cite>".htmlentities($_GET['label'])."</cite>";
	if (!empty($row['description']))
		print "<span>".htmlentities($row['description'])."</span>";
    ?></h3>
    
    <div class="instruction-card">
        <div class="instruction-grid">
            <div class="instruction-item">
                <span class="icon">&#128077;</span>
                <p><strong>Promote:</strong> Mark the best examples as <b>Stellar</b> to help the AI learn top-tier matches.</p>
            </div>
            <div class="instruction-item">
                <span class="icon">&#128078;</span>
                <p><strong>Demote:</strong> Mark irrelevant images. You only need to mark the first few where quality begins to drop.</p>
            </div>
        </div>
        
        <div class="instruction-footer">
            <p>Everything else is considered <strong>OK</strong>. If most images (over 50%) are bad matches: 
               <button class="btn-danger" type="submit" name="submit" value="bad">AI Search Didn't Work!</button>
            </p>
        </div>
    </div>
</div>

	<?

	$order = $where = array();
	$where[] = "c.`group` = 'Automated'";
	$where[] = "c.label = ".$db->Quote($_GET['label']);
	$where[] = "c.active = 1";
	$where[] = "c.score > 5";
	//$where[] = "cosine <0.75";
	//$where[] = "(original_width >=1024 or original_height >=1024)";

	$order[] = "round(cosine,1) asc";
	$order[] = "(original_width >=1024 or original_height >=1024) desc";

	$imagelist->cols = str_replace('user_id','gi.user_id', $imagelist->cols);
        $sql = "SELECT {$imagelist->cols}, cosine, imagetaken, greatest(width,height,original_width,original_height) as size
                FROM gridimage_search gi
                INNER JOIN curated1 c using (gridimage_id)
		inner join gridimage_size s using (gridimage_id)
		WHERE ".implode(" AND ",$where)."
		ORDER BY ".implode(", ",$order)."
                LIMIT 5";
	$imagelist->_getImagesBySql($sql);
        if ($imagelist->images) {
                print "<p class=count>showing ".count($imagelist->images)." images</p>";

		print "<div class=\"thumb-grid\">";
		foreach ($imagelist->images as $image) {
	                $image->reference_index = (strlen($image->grid_reference) == 5)?2:1;
			print "<div class=thumb-card>";
			 $imagelist->getThumbnailLink($image);
			if ($image->size>=3000)
				print "<div class=\"size-label\">3000px+</div>";
			elseif ($image->size>1024)
				print "<div class=\"size-label\">1024px+</div>";
			elseif ($image->size==1024)
				print "<div class=\"size-label\">1024px</div>";
			if ($image->imagetaken>'1000-00-00')
				print "<div class=\"year-label\">".substr($image->imagetaken,0,4)."</div>";
    print "<div class='rating-bar'>";
        // Bad
        print "<input type='radio' id='bad-$image->gridimage_id' name='result[$image->gridimage_id]' value='bad'>";
        print "<label for='bad-$image->gridimage_id' class='label-bad'>&#128078;</label>";
        
        // OK (Default)
        print "<input type='radio' id='ok-$image->gridimage_id' name='result[$image->gridimage_id]' value='ok' checked>";
        print "<label for='ok-$image->gridimage_id' class='label-ok'>OK</label>";
        
        // Good
        print "<input type='radio' id='good-$image->gridimage_id' name='result[$image->gridimage_id]' value='good'>";
        print "<label for='good-$image->gridimage_id' class='label-good'>&#128077;</label>";
    print "</div>";

			print "</div>";
		}
		print "</div>";
	}

	print "<div class=\"bottom-bar\">";
	print "<button type=submit name=submit value=ok>Submit [".htmlentities($_GET['label'])."] Results</button>";
        print " ";
	print "<button type=submit name=submit value=next>Save and move to next subject</button>";
	print "</div>";
	print "</form>";

	?>
        <script src="/js/geograph-api-libs.js?<? echo filemtime("../js/geograph-api-libs.js"); ?>"></script>
	<script src="automated.js?<? echo filemtime('automated.js'); ?>"></script>
	<?
	exit;

}

#######################################################################

print "<h2>Curated Education images</h2>";

$raw = $db->getAll("select label,region,count(*) as images,avg(cosine),group_concat(gridimage_id order by cosine limit 1) as gridimage_id
	from curated1 inner join gridimage_size using (gridimage_id)
	 where `group` = 'Automated' and active = 1 and score > 5 and cosine <0.75 and (original_width >=1024 or original_height >=1024)
	 group by label,region with rollup");

$total = null;
$labels = array();
$regions = array();
$ids = array();
foreach ($raw as $row) {
	if (empty($row['label'])) {
		//the final rollup!
		$total = $row['images'];
		continue;
	}
	if (empty($row['region'])) {
		//the label rollup!
		$row['region'] = 'TOTAL';
		$ids[] = $row['gridimage_id']; //just load the best in all for now!
	}
	$labels[$row['label']][$row['region']] = $row;
	@$regions[$row['region']]++;
}

#######################################################################

 $imagelist->getImagesByIdList($ids);

//reindex by ID
if ($cnt = count($imagelist->images)) {
	foreach ($imagelist->images as $image) {
		$images[$image->gridimage_id] = $image;
	}
}

#######################################################################

arsort($regions);

print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee id=\"table\" style=\"position:relative\">";
print "<tr style=\"position:sticky;top:0;left:0;background-color:#ddd\">";
print "<td>";
foreach($regions as $region => $count) {
	print "<th>".htmlentities($region);
		if ($region == 'TOTAL')
			print "<td>"; //for the thumbnail
}

foreach ($labels as $label => $rows) {
	print "<tr>";
	$url = "?label=".urlencode($label);
	$labelh = htmlentities($label);
	print "<th><a href=\"$url\">$labelh</a>";

	foreach($regions as $region => $count) {
		print "<td align=right>";
		if (!empty($rows[$region])) {
			print number_format($rows[$region]['images'],0);
		}
		if ($region == 'TOTAL') {
			print "<td align=center>";
			$gridimage_id = $rows['TOTAL']['gridimage_id'];
			if (!empty($images[$gridimage_id]))
				$imagelist->getThumbnailLink($images[$gridimage_id]);
		}
	}
}

print "</table>";

#######################################################################

?>
<style>
</style>
<?


$smarty->display('_std_end.tpl');



