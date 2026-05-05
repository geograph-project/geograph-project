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

$gen = intval($_GET['gen'] ?? 1);

#######################################################################

if (!empty($_GET['jump'])) {
	//may contain a label, need to avoid! (doesnt matter if add '1' to the list!

	//todo, track this per region??
	if (empty($_SESSION['avoid']))
		$_SESSION['avoid'] = array($_GET['jump']);
	else
		$_SESSION['avoid'][] = $_GET['jump'];

	$list = "(".implode(',',array_map(array($db,'Quote'),array_values($_SESSION['avoid']))).")";
	//the order by c, is just so not in alphabetial order!
	$label = $db->getOne("  SELECT label,count(*) c,sum(score!=10) d FROM curated1 WHERE `group` = 'Automated' AND label NOT IN $list GROUP BY label ORDER BY d asc,c desc LIMIT 1");

	if ($label) {
		$url = "?label=".urlencode($label);
		if (!empty($_GET['region']))
			$url .= "&region=".urlencode($_GET['region']);
		if (!empty($_GET['gen']))
			$url .= "&gen=".urlencode($_GET['gen']);
		header("Location: $url");
		exit;
	}
}

#######################################################################
//submit results

if (!empty($_GET['label'])) {
	if (!empty($_POST['submit'])) {

        function submit_results($gridimage_id, $verdict) {
            global $db, $USER, $gen;

            $group = 'Automated';
            $label = $_GET['label'];

            // --- SINGLE IMAGE UPSERT ---
            if ($gridimage_id > 0) {
                // Determine the SET clause and a starting score for new entries
                if ($verdict == 'good') {
                    $update_calc = "score = score * 2";
                    $starting_score = 20;
                } elseif ($verdict == 'bad') {
                    $update_calc = "score = score div 2";
                    $starting_score = 5;
                } else { // 'ok'
                    //if user provides this, still to create
                    $starting_score = 10;
                    //but its a noop, on existing rows!
                    $update_calc = "score = score"; //noop!
                }

                $user_id = intval($USER->user_id);

                // This creates the record if it's new, or updates it if it exists.
                $sql = "INSERT INTO curated1 (`user_id`, `group`, `label`, `gridimage_id`, `score`)
                        VALUES ($user_id, " . $db->Quote($group) . ", " . $db->Quote($label) . ", $gridimage_id, $starting_score)
                        ON DUPLICATE KEY UPDATE $update_calc";

                $db->Execute($sql) or die($db->ErrorMsg());

            // --- GLOBAL UPDATE (The "Bad" button case) ---
            } else {
                if ($verdict == 'good') {
                    $update_calc = "score + 1";
                } elseif ($verdict == 'bad') {
                    $update_calc = "score = 1";
		}

                $user_id = 23277; //socket

                // This still uses your original UPDATE logic since it affects existing rows only
                $where = "user_id = $user_id AND `group` = " . $db->Quote($group) . " AND `label` = " . $db->Quote($label) . " AND score = 10 AND `gen`=$gen";
                $sql = "UPDATE curated1 SET $update_calc WHERE $where";

                $db->Execute($sql) or die($db->ErrorMsg());
            }
        }

        // --- Main Execution Logic ---
        if ($_POST['submit'] == 'bad') {
            submit_results(0, 'bad');
        } elseif ($_POST['submit'] == 'bad') {
            submit_results(0, 'bad');
        } else {
            foreach($_POST['result'] as $gridimage_id => $verdict) {
                $id = intval($gridimage_id);
                if ($id > 0) {
                    submit_results($id, $verdict);
                }
            }
        }

		if ($_POST['submit'] == 'next') {
			//todo, track this per region??
			if (empty($_SESSION['avoid']))
				$_SESSION['avoid'] = array($_GET['label']);
			else
				$_SESSION['avoid'][] = $_GET['label'];

			$list = "(".implode(',',array_map(array($db,'Quote'),array_values($_SESSION['avoid']))).")";
//			$label = $db->getOne("  SELECT label FROM curated1 WHERE user_id = 23277 AND `Group` = 'Automated' AND score >=10 AND label NOT IN $list LIMIT 1");
			$label = $db->getOne("  SELECT label,count(*) c,sum(score!=10) d FROM curated1 WHERE `group` = 'Automated' AND label NOT IN $list GROUP BY label ORDER BY d asc,c desc LIMIT 1");

			if ($label) {
				$url = "?label=".urlencode($label);
				if (!empty($_GET['region']))
					$url .= "&region=".urlencode($_GET['region']);
				if (!empty($_GET['gen']))
					$url .= "&gen=".urlencode($_GET['gen']);
				header("Location: $url");
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

	<a href="?">&lt;&lt; Back to Listing</a> or
	<a href="?jump=<? echo urlencode($_GET['label']); ?>">Jump to another subject</a> (without saving)</a>

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
                <p><strong>Demote:</strong> Mark irrelevant images. You only need to mark the first few, which notes where quality begins to drop.</p>
            </div>
        </div>
        <? if ($gen === 1) { ?>
        <div class="instruction-footer">
            <p>Everything else is considered <strong>OK</strong>. If most images (over 50%) are bad matches:
               <button class="btn-danger" type="submit" name="submit" value="bad">AI Search Didn't Work!</button> (returns to homepage)
            </p>
            <p>or, if <b>most images (over 90%)</b> are ok matches, but none stand out as exceptional, nor particully bad:
               <button class="btn-danger" style="background-color:#7aca00" type="submit" name="submit" value="ok">AI Search Seems OK</button> (returns to homepage)
            </p>
        </div>
	<? } ?>
    </div>
</div>
	<div style="float:right">
		<button type=button id="add-manual-btn">Add image Manually</button>
	</div>

	<?

	$order = $where = array();
	$where[] = "c.`group` = 'Automated'";
	$where[] = "c.label = ".$db->Quote($_GET['label']);
	$where[] = "c.active = 1";
	if (empty($_GET['ignore']))
		$where[] = "c.score > 5";
	//$where[] = "cosine <0.75";
	//$where[] = "(original_width >=1024 or original_height >=1024)";

	if (!empty($_GET['region'])) {
		$where[] = "region = ".$db->Quote($_GET['region']);
	}
	$where[] = "gen = ".$db->Quote($gen);

	//$order[] = "round(cosine,1) asc";
	$order[] = "cosine asc"; //might be better without striping
	$order[] = "(original_width >=1024 or original_height >=1024) desc";

	$imagelist->cols = str_replace('user_id','gi.user_id', $imagelist->cols);
        $sql = "SELECT {$imagelist->cols}, cosine, imagetaken, greatest(width,height,original_width,original_height) as size
                FROM gridimage_search gi
                INNER JOIN curated1 c using (gridimage_id)
		inner join gridimage_size s using (gridimage_id)
		WHERE ".implode(" AND ",$where)."
		ORDER BY ".implode(", ",$order)."
                LIMIT 200";
	$imagelist->_getImagesBySql($sql);
        if ($imagelist->images) {
                print "<p class=count>showing ".count($imagelist->images)." images for <b>".htmlentities($_GET['label'])."</b>";
		if (!empty($_GET['region']))
			print ", found in <i>".htmlentities($_GET['region'])."</i>";

		print "<div class=\"thumb-grid main-grid\">";
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
	} else {
		print "<p>No Images Found. Can add some manually:- ";
	}

	print "<div class=\"bottom-bar\">";
	print "Label: <b>".htmlentities($_GET['label'])."</b> - ";
	print "<button type=submit name=submit value=ok>Submit Results</button>";
        print " - ";
	print "<button type=submit name=submit value=next>Submit and move to Next</button>";
	print "</div>";
	print "</form>";

	?>
        <script src="/js/geograph-api-libs.js?<? echo filemtime("../js/geograph-api-libs.js"); ?>"></script>
	<script src="automated.js?<? echo filemtime('automated.js'); ?>"></script>
	<?

	$smarty->display('_std_end.tpl');
	exit;
}

#######################################################################
//simply gallery

print "<h2>Curated Education images</h2>";

if (empty($_GET['matrix'])) {



$selectedRegion = $_GET['region'] ?? '';

echo '<div class="filter-bar">';
echo '<form method="GET" action="">';
// Preserve the label if it's already set in the URL
if (isset($_GET['label'])) {
    echo '<input type="hidden" name="label" value="'.htmlentities($_GET['label']).'">';
}

echo '<label for="region-select">Filter by Region:</label>';
echo '<select name="region" id="region-select" onchange="this.form.submit()">';
echo '<option value="">All Regions</option>';

$where = array(); $extra='';
$where[] = "`group` = 'Automated'";
$where[] = "active = 1";
$where[] = "score > 5";
$where[] = "gen = ".$db->Quote($gen);


$regions = $db->getAll("SELECT region, count(*) as cnt FROM curated1 WHERE ".implode(' AND ',$where)." AND region != '' GROUP BY region ORDER BY region ASC");

foreach ($regions as $reg) {
    $sel = ($selectedRegion == $reg['region']) ? ' selected' : '';
    echo '<option value="' . htmlentities($reg['region']) . '"' . $sel . '>';
    echo htmlentities($reg['region']) . ' (' . $reg['cnt'] . ')';
    echo '</option>';

	if ($selectedRegion == $reg['region']) {
		$where[] = "region = ".$db->Quote($selectedRegion);
		$extra .= "&amp;region=".urlencode($selectedRegion);
	}
}

echo '</select>';

	print " <a href=?matrix=1>View Regional Breakdown</a>";

	print ' or <a href="?jump=1">Jump to an arbitary subject</a> (most in need of review)<hr>';

$done = $db->getOne("  SELECT format_percent(sum(done)/2, count(*),1) as done from (select label,floor(ln(sum(score!=10))) as done from curated1 where ".implode(' AND ',$where)." AND cosine is not null group by label order by null) t2");
if (!empty($done)) {
	print " Percentage Verified: $done";
}

echo '</form>';
echo '</div>';


	$where = implode(' AND ',$where);
	//still use an initial query, because want to get the top x per group (by distance)
	//without using row functions would be harder in one query
        $raw = $db->getAssoc("select label,region,count(*) as images,avg(cosine) as avg,group_concat(gridimage_id order by score desc, cosine asc limit 5) as gridimage_id, count(distinct region) as regions
	from curated1 where $where
	group by label with rollup");

	$ids = array();
	foreach ($raw as $row) {
		foreach(explode(',',$row['gridimage_id']) as $id) $ids[] = $id;
	}

	$images = array();
	$imagelist->getImagesByIdList($ids);
	if ($cnt = count($imagelist->images)) {
		foreach ($imagelist->images as $image) {
			$images[$image->gridimage_id] = $image;
		}
	}

	$labels = $db->getAll("select stack,name,description from curated_label where active=1 and length(clip_query) > 10 order by stack, name");

?>
<style>
.gallery-container {font-family: sans-serif;max-width: 1200px;margin: 20px auto;color: #333;}
.label-section {margin-bottom: 40px;border-bottom: 1px solid #eee;padding-bottom: 20px;}
.label-header {display: flex;justify-content: space-between;align-items: baseline;margin-bottom: 10px;}
.label-header h3 {margin: 0;font-size: 1.2rem;color: #555;}
.label-header h3 a {color: #007bff;text-decoration: none;}
.image-count {font-size: 0.85rem;color: #888;}
.thumbnail-grid {display: flex;flex-wrap: wrap;gap: 10px;}
.thumbnail-grid a {display: inline-block; outline: 2px solid transparent;
        outline-offset: 3px; }
.thumbnail-grid a:hover { outline-color: #007bff60; }
.thumbnail-grid img {border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);display: block; }
</style>
<?


echo '<div class="gallery-container">';

foreach($labels as $row) {
    $name = $row['name'];
    $stack = $row['stack'];
    $data = $raw[$name] ?? null;
    $ids = !empty($data['gridimage_id']) ? explode(',', $data['gridimage_id']) : [];
    $count = $data['images'] ?? 0;
    $regions = $data['regions'] ?? 0;
    $url = "?label=" . urlencode($name) . $extra;

	if ($gen > 1 && empty($data))
		continue;


    $avg_display = '';
    if (!empty($data['avg'])) {
	if ($gen == 1) $min = 0.656312;
	if ($gen == 2) $min = 0.10;
        $max = 0.849066;

        $clamped = max($min, min($max, $data['avg']));

	    // Linear Normalization: (Value - Min) / (Max - Min)
	    // We subtract from 1 to invert it (lower cosine = higher percentage)
	    $normalized = 1 - (($clamped - $min) / ($max - $min));
	    $score = round($normalized * 100);

	    // Choose a color based on the score (Optional but "prettier")
	    $color = ($score > 70) ? '#28a745' : (($score > 40) ? '#ffc107' : '#dc3545');
	    $avg_display = "<small style='color: $color; font-weight: bold; margin-left: 10px;'>($score% Match)</small>";
    }

    echo '<section class="label-section">';

    // Header Row
    echo '<div class="label-header">';
    echo '<h3>' . htmlentities($stack) . ' &rsaquo;&rsaquo; <a href="'.$url.'">' . htmlentities($name) . '</a> '.$avg_display.'</h3>';
    if ($count)
	    echo '<span class="image-count"><a href="'.$url.'">' . number_format($count) . ' images</a>, '.$regions.' regions</span>';
    echo '</div>';

    if (!empty($row['description']))
	print "<p>".htmlentities($row['description']);

    // Image Grid
    if (!empty($ids)) {
        echo '<div class="thumbnail-grid">';
        foreach ($ids as $id) {
            if (isset($images[$id])) {
                $imagelist->getThumbnailLink($images[$id]);
            }
        }
        echo '</div>';
    } else {
        echo '<p style="color:#ccc; font-style:italic;">No images found in this category.</p>';
    }

    echo '</section>';
}

echo '</div>';


	$smarty->display('_std_end.tpl');
	exit;
}

#######################################################################
//full matrix display!

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
			if ($region != 'TOTAL')
				$url = "?label=".urlencode($label)."&amp;region=".urlencode($region);

			print "<a href=\"$url\">".number_format($rows[$region]['images'],0)."</a>";
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



