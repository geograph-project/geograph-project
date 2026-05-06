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

$where = array(); $extra='';
$where[] = "c.`group` = 'Automated'";
$where[] = "c.active = 1";
$where[] = "c.score > 5";
$where[] = "j.is_gold_standard = 1";

#######################################################################

#######################################################################
// single label

if (!empty($_GET['label'])) {

	$where[] = "c.label = ".$db->Quote($_GET['label']);

	if (!empty($_GET['region'])) {
		$where[] = "c.region = ".$db->Quote($_GET['region']);
	}

        //$order[] = "round(cosine,1) asc";
        $order[] = "(original_width >=1024 or original_height >=1024) desc";
        $order[] = "overall desc";

        $imagelist->cols = str_replace('user_id','gi.user_id', $imagelist->cols);
        $sql = "SELECT {$imagelist->cols}, cosine, imagetaken, region, greatest(width,height,original_width,original_height) as size, avg(sc.score) as overall
                FROM gridimage_search gi

                INNER JOIN curated1 c using (gridimage_id)
		inner join curated_judge j using (label,gridimage_id)

		inner join gridimage_score sc using (gridimage_id)
                inner join gridimage_size s using (gridimage_id)

                WHERE ".implode(" AND ",$where)."
		GROUP BY gridimage_id
                ORDER BY region,".implode(", ",$order)."

                LIMIT 200";

        $imagelist->_getImagesBySql($sql);
        if ($imagelist->images) {

?>
<style>
.gallery-container {font-family: sans-serif;max-width: 1200px;margin: 20px auto;color: #333;}
.label-section {margin-bottom: 40px;border-bottom: 1px solid #eee;padding-bottom: 20px;}
.label-header {display: flex;justify-content: space-between;align-items: baseline;margin-bottom: 10px;}
.label-header h3 {margin: 0;font-size: 1.2rem;color: #555;}
.label-header h3 a {color: #007bff;text-decoration: none;}
.label-header h3 big {color: #0000ff; }
.image-count {font-size: 0.85rem;color: #888;}
.thumbnail-grid {display: flex;flex-wrap: wrap;gap: 10px;}
.thumbnail-grid a {display: inline-block; outline: 2px solid transparent;
        outline-offset: 3px; }
.thumbnail-grid a:hover { outline-color: #007bff60; }
.thumbnail-grid img {border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);display: block; }
</style>
<?

                print "<p class=count>showing ".count($imagelist->images)." images for <b>".htmlentities($_GET['label'])."</b>";
                if (!empty($_GET['region']))
                        print ", found in <i>".htmlentities($_GET['region'])."</i>";

		$region = null;
                foreach ($imagelist->images as $image) {
			if ($region != $image->region) {
				if ($region) {
				        echo '</div>';
				    echo '</section>';
				}

				    echo '<section class="label-section">';

				    echo '<div class="label-header">';
				    echo '<h3>' . htmlentities($image->region) . '</a> '.$avg_display.'</h3>';
				    echo '</div>';

				    if (!empty($row['description']))
					print "<p>".htmlentities($row['description']);

			        echo '<div class="thumbnail-grid">';
			}

	                $imagelist->getThumbnailLink($image);
			$region = $image->region;
		}

		print "</div>";
		print "</section>";
	}

	$smarty->display('_std_end.tpl');
	exit;
}

#######################################################################
//general list

print "<h2>Curated Education images</h2>";

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

$regions = $db->getAll("SELECT region, count(*) as cnt FROM curated1 c inner join curated_judge j using (label,gridimage_id) WHERE ".implode(' AND ',$where)." AND region != '' GROUP BY region ORDER BY region ASC");

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

echo '</form>';
echo '</div>';


	$where = implode(' AND ',$where);

	$raw= $db->getAll("
select gridimage_id,label,1-avg(cosine) as distance, region,
round(ln(greatest(original_width,original_height,width,height))) as resscore,
greatest(original_width,original_height,width,height) as greatest,
max(if(s.model = 'v_bayesian', s.score, null)) as v_bayesian,
max(if(s.model = 'aesthetic', s.score, null)) as aesthetic,
max(if(s.model = 'technical', s.score, null)) as technical,
max(if(s.model = 'baysian', s.score, null)) as baysian,
max(if(s.model = 'score', s.score, null)) as score,
max(if(s.model = 'sds1', s.score, null)) as sds
 from curated1 c inner join curated_judge j using (label,gridimage_id)
 inner join gridimage_score s using (gridimage_id)
 inner join gridimage_size using (gridimage_id)
 where $where
group by label,gridimage_id order by null");

	//finalResults will be limited to 5 per label!
	$finalCounts = array();
	foreach ($raw as $row)
		@$finalCounts[$row['label']]++;

//get list of 5ids grouped by label
$finalResults = getTopByRRF($raw, 15);

//print "<pre>";
//print_r($finalResults);
//exit;


	//get flat list of ids, to do final lookup.
	$ids = array();
	foreach ($finalResults as $label => $images) {
		$ids = array_merge($ids, array_column($images, 'gridimage_id'));
	}

	//lookup image ids by id
	$images = array();
	$imagelist->getImagesByIdList($ids);
	if ($cnt = count($imagelist->images)) {
		foreach ($imagelist->images as $image) {
			$images[$image->gridimage_id] = $image;
		}
	}

	$labels = $db->getAll("select stack,name,description from curated_label where active=1 and length(clip_query) > 10 order by stack, name");


#######################################################################
//hero gallery

if (empty($_GET['old']) && empty($_GET['region'])) { //filtering by region doesnt have enough data!

?>
<style>

.label-header {display: flex;justify-content: space-between;align-items: baseline;margin-bottom: 10px;}
.label-header h3 {margin: 0;font-size: 1.2rem;color: #555;}
.label-header h3 a {color: #007bff;text-decoration: none;}
.label-header h3 big {color: #0000ff; }
.image-count {font-size: 0.85rem;color: #888;}

.gallery-container { font-family: sans-serif; max-width: 1400px; margin: 20px auto; color: #333; }
.label-section { margin-bottom: 50px; border-bottom: 1px solid #eee; padding-bottom: 30px; }

.label-content-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.hero-image-box { flex: 0 0 480px; position: relative; width: 480px; height: 480px; overflow: hidden; border-radius: 8px; }
.hero-image-box img { width: 480px; height: 480px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }

.title-overlay { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px 15px 10px 15px; color: white; font-size: 1rem; font-weight: 500; background: 
    linear-gradient(transparent, rgba(0,0,0,0.7)); pointer-events: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.title-overlay span { font-size: 1.1rem; }

.clusters-container { flex: 1; display: flex; flex-direction: column; gap: 20px; }
.cluster-group { background: #f9f9f9; padding: 12px; border-radius: 6px; }
.cluster-title { font-size: 0.75rem; text-transform: uppercase; color: #888; margin-bottom: 8px; letter-spacing: 0.5px; border-bottom: 1px solid #ddd; }

.thumbnail-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.thumbnail-grid img { 
    width: 100px; height: 100px; /* Adjust size to fit your thumbnail preference */
    object-fit: cover; border-radius: 4px; border: 1px solid #ddd;
}
</style>

<?php
echo '<div class="gallery-container">';

/**
 * Helper function to render a group of thumbnails
 */
function renderCluster($title, $listing, $imagelist) {
    echo '<div class="cluster-group">';
    echo '<div class="cluster-title">' . htmlentities($title) . ' (' . count($listing) . ')</div>';
    echo '<div class="thumbnail-grid">';
    foreach ($listing as $img) {
        // This helper likely echoes the <a><img></a> block directly
        $imagelist->getThumbnailLink($img);
    }
    echo '</div></div>';
}

foreach($labels as $row) {
    $name = $row['name'];
    $stack = $row['stack'];
    $data = $finalResults[$name] ?? null;
    $count = $finalCounts[$name] ?? 0;

    if (empty($data)) continue;
    if ($count < 10) continue;

    // Separate the first image as Hero
    $heroData = array_shift($data);
    $heroImage = $images[$heroData['gridimage_id']] ?? null;

    // Group remaining images by region
    $grouped = [];
    foreach ($data as $item) {
        $imgObj = $images[$item['gridimage_id']] ?? null;
        if (!$imgObj) continue;
        
        // Assuming the image object or the $raw data row has a 'region' key
        // Check both sources for the region name
        $reg = $imgObj->region ?? $item['region'] ?? 'Unknown';
        $grouped[$reg][] = $imgObj;
    }

    // Logic for "Cluster" vs "Others"
    $clusters = [];
    $others = [];
    foreach ($grouped as $regName => $listing) {
        if (count($listing) >= 3) {
            $clusters[$regName] = $listing;
        } else {
            $others = array_merge($others, $listing);
        }
    }

    echo '<section class="label-section">';
    
    // Header
    $url = "?label=" . urlencode($name) . $extra;
    echo '<div class="label-header">';
    echo '<h3>' . htmlentities($stack) . ' &rsaquo;&rsaquo; <a href="'.$url.'">' . htmlentities($name) . '</a></h3>';

    if ($count > count($data) + 1) {
	    echo '<span class="image-count"><a href="'.$url.'">' . number_format($count) . ' images</a></span>';
    } else {
        echo '<span class="image-count">' . (count($data) + 1) . ' top picks</span>';
    }
    echo '</div>';

    echo '<div class="label-content-wrapper">';
        
        // 1. Large Hero Image
        echo '<div class="hero-image-box">';
        if ($heroImage) {
            echo '<a href="/photo/'.$heroImage->gridimage_id.'">';
	    if ($heroData['greatest'] > 800) {
                echo '<img src="'.$heroImage->getImageFromOriginal(800,800,true).'" alt="Hero">';
	    } else {
                echo '<img src="'.$heroImage->_getFullpath().'" alt="Hero">';
            }
            echo '</a>';
	    echo '<div class="title-overlay"><span>'.htmlentities($heroImage->title).'</span> by '.htmlentities($heroImage->realname).'</div>';
        }
        echo '</div>';

        // 2. Sidebar Clusters
        echo '<div class="clusters-container">';
            
            // Render specific clusters
            foreach ($clusters as $regName => $listing) {
                renderCluster($regName, $listing, $imagelist);
            }

            // Render "Others"
            if (!empty($others)) {
                renderCluster(empty($clusters)?"All Regions":"Other Regions", $others, $imagelist);
            }

        echo '</div>'; // end clusters-container
    echo '</div>'; // end label-content-wrapper

    echo '</section>';

//exit;

}
echo '</div>';

exit;
}

#######################################################################
//simple gallery

?>
<style>
.gallery-container {font-family: sans-serif;max-width: 1200px;margin: 20px auto;color: #333;}
.label-section {margin-bottom: 40px;border-bottom: 1px solid #eee;padding-bottom: 20px;}
.label-header {display: flex;justify-content: space-between;align-items: baseline;margin-bottom: 10px;}
.label-header h3 {margin: 0;font-size: 1.2rem;color: #555;}
.label-header h3 a {color: #007bff;text-decoration: none;}
.label-header h3 big {color: #0000ff; }
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
    $data = $finalResults[$name] ?? null;
    $ids = array_column($data ?? [], 'gridimage_id');
    $count = $finalCounts[$name] ?? 0;
    $regions = 0; //todo
    $url = "?label=" . urlencode($name) . $extra;

	if (empty($data))
		continue;

    $avg_display = ''; //todo? rrf?

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

#######################################################################


function getTopByRRF(array $dataset, $number = 5) {
    $numericCols = ['distance', 'resscore', 'v_bayesian', 'aesthetic', 'technical', 'baysian', 'score', 'sds'];
    $k = 60; // Standard RRF constant
    
    // 1. Group by Label
    $grouped = [];
    foreach ($dataset as $row) {
        $grouped[$row['label']][] = $row;
    }

    $finalResults = [];

    foreach ($grouped as $label => $images) {
        $imgCount = count($images);
        $columnAverages = [];

        // 2. Calculate Averages for Null Imputation
        foreach ($numericCols as $col) {
            $values = array_filter(array_column($images, $col), fn($v) => !is_null($v));
            $columnAverages[$col] = count($values) > 0 ? array_sum($values) / count($values) : 0;
        }

        // 3. Score Ranking
        // We store the RRF points for every image ID in this label
        $rrfScores = array_fill_keys(array_column($images, 'gridimage_id'), 0.0);

        foreach ($numericCols as $col) {
            $sortedList = $images;

            // Sort images by this specific column (Descending)
            // Handle nulls by using the pre-calculated average
            usort($sortedList, function($a, $b) use ($col, $columnAverages) {
                $valA = $a[$col] ?? $columnAverages[$col];
                $valB = $b[$col] ?? $columnAverages[$col];
                return $valB <=> $valA; 
            });

            // Assign ranks (1-based) and add to RRF total
            foreach ($sortedList as $rank => $img) {
                $rrfScores[$img['gridimage_id']] += 1 / ($k + ($rank + 1));
            }
        }

        // 4. Final Sort for this Label
        // Re-attach the calculated RRF score to the objects
        foreach ($images as &$img) {
            $img['rrf_score'] = $rrfScores[$img['gridimage_id']];
        }
        unset($img); // Break the reference for safety

        usort($images, fn($a, $b) => $b['rrf_score'] <=> $a['rrf_score']);

        // 5. Take Top 5
        $finalResults[$label] = array_slice($images, 0, $number);
    }

    return $finalResults;
}
