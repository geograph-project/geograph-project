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

 customExpiresHeader(3600,false,true);

$smarty = new GeographPage;
$smarty->display('_std_begin.tpl');
$db = GeographDatabaseConnection(true);

// 1. Inputs & Defaults
$type = $_GET['type'] ?? 'clip';
$town = $_GET['town'] ?? 'East Grinstead';
$tag = $_GET['tag'] ?? '';

$towns = array('East Grinstead', 'Fort William/An Gearasdan', 'Abergavenny/Y Fenni', 'Bicester', 'Durham');
//if (!in_array($town, $towns)) $town = 'East Grinstead';
if (!preg_match('/^[A-Z][\w -]+(\/[\w -]+)?$/',$town))  $town = 'East Grinstead'; //very basic check it a simple name. Will need fixing to deal with special chars etc


?> <link rel="stylesheet" href="place-experiment.css?<? echo filemtime('place-experiment.css'); ?>"> <?

// 2. Navigation Menu [cite: 11, 12]
echo '<div class="place-switcher">';
foreach ($towns as $t) {
    $active = ($t === $town) ? 'class="active"' : '';
    $url = "?" . http_build_query(['type' => $type, 'town' => $t]);
    echo "<a href='$url' $active>" . htmlentities($t) . "</a>";
}
echo '</div>';

if (empty($_GET['sample'])) {

$available_types = ['top' => 'Context Tags', 'subject' => 'Subject', 'cluster' => 'Auto Clusters', 'clip' => 'AI Context', 'clipzero'=>'AI Labels', 'md3'=>'AI Tags'];

$sources = array(
    'top'     => 'Human Ground Truth (Context Tags)',
    'subject' => 'Human Labels (Subject Tags)',
    'cluster' => 'Lingo/Carrot2 (Thematic Clustering)',
    'clip'    => 'CLIP the Landscape (Ilyankou et al., 2026): Automated tagging of crowdsourced landscape images. Remote Sensing Applications: Society and Environment, 41. <a href="https://doi.org/10.1016/j.rsase.2025.101824">DOI: 10.1016/j.rsase.2025.101824</a>',
    'clipzero'=> 'CLIP Zero-Shot (Custom prompts)',
    'md3'     => 'Moondream3 generated tags (VLM Preview)',
);

echo '<div class="place-switcher">';
foreach ($available_types as $t_key => $t_label) {
    $active = ($t_key === $type) ? 'class="active"' : '';
    $url = "?" . http_build_query(['type' => $t_key, 'town' => $town]);
    echo "<a href='$url' $active>$t_label</a>";
}
echo '</div>';

}

// 2. Build SQL
##################################################################

$cols = "gridimage_id, gi.grid_reference, gi.user_id, gi.title, realname, gi.imagetaken";
$join_tables = " INNER JOIN gridimage_search gi USING(gridimage_id)";
$spatial_where = '';

$conv = new Conversions;
$conv->_setDB($db);

$gaz = new Gazetteer();
$gaz->_setDB($db);

#############################

$place = $db->getRow("SELECT placename_id,Place,County,Country,images,km_ref,has_dup,reference_index
         FROM sphinx_placenames
         WHERE place = " . $db->Quote($town) . " LIMIT 1");

if (empty($place)) {
	die("unknown place - for now only names exactly as defined in our gazetter work");
}

#############################

$mbr = $gaz->getMBRFromPlace($place, 1000); //dist for the fake mbr!

//use a nice MBR from Gazetters - may not always be available
if (!empty($mbr) && !empty($mbr['geometry_x']) && $type!='type') { //geometry_x comes from gazetter, so signifies it a 'proper' MBR
	//note, we deliberately use the geometry_x to calcuate km_ref, rather than using the one from sphinx_placenames
	list ($gridref,) = $conv->national_to_gridref($mbr['geometry_x'],$mbr['geometry_y'],4,$mbr['reference_index']);
	$cols .= ", ".$db->Quote($gridref)." AS km_ref";

	$table = ($mbr['reference_index'] == 1)?'gb_images':'ie_images';
	if (in_array($town, $towns))
		$table = "gb_images_town"; //special version that includes non-geo, gb_images inadvertly is pre filtered

	$join_tables .= " INNER JOIN $table FORCE INDEX (natnorthings) USING(gridimage_id)"; //force index is very imporant particuly for the tag_public join

	$spatial_where = "nateastings BETWEEN {$mbr['mbr_xmin']} AND {$mbr['mbr_xmax']}
               	     AND natnorthings BETWEEN {$mbr['mbr_ymin']} AND {$mbr['mbr_ymax']}";

	$message = "Images are selected from a rectangle coveraging the general town area";
} else {

#############################

	//for large places, cheat and get a sample from manticore
	if (!empty($place['images']) && $place['images'] > 1500) {
		$cols .= ", ".$db->Quote($place['km_ref'])." AS km_ref";

		$sph = GeographSphinxConnection('sphinxql',true);
		$ids = $sph->getCol("SELECT id FROM sample8 WHERE MATCH('@place ^$town$') ORDER BY sequence ASC LIMIT 1500 OPTION max_matches=1500");

		if (!empty($ids)) {
			$id_list = implode(',', $ids);
			$spatial_where = "gridimage_id IN ($id_list)";

			//can use this to tell user, but will still need to a way to let users view more images (eg link to our 'image browser' which has facetted browsing)
			$message = "Showing a sample of about ".count($ids)." images from this from the place and its immediate surrounds.";
		}

	//sphinx_placenames.images is a materialized count done via the precomputed gridsquare.placename_id value
	} elseif (!empty($place['images'])) { // ?images > 10 ?? (maybe places with few images, would be better just using centered search (incasd the voroni area was very small)
		$cols .= ", km_ref"; //from sphinx_placenames

		$join_tables .= " INNER JOIN gridsquare USING (grid_reference)";
        	$join_tables .= " INNER JOIN sphinx_placenames USING (placename_id)";

		$spatial_where = "place = ".$db->Quote($town);
		$message = "Images selected based on the nearest recorded place name.";
	}

#############################

	//otherwise a generic centered search (MBR, will have already been provided!
	if (empty($spatial_where)) {

		if (!empty($mbr['e'])) { //signifies ita  fake MBR

			//might as well use the one fro sphinx_placenames
			if (!empty($place['km_ref'])) {
				$cols .= ", ".$db->Quote($place['km_ref'])." AS km_ref";
			} else {
				list ($gridref,) = $conv->national_to_gridref($mbr['e'],$mbr['n'],4,$place['reference_index']);
				$cols .= ", ".$db->Quote($gridref)." AS km_ref";
			}

			$table = ($place['reference_index'] == 1)?'gb_images':'ie_images';
			$join_tables .= " INNER JOIN $table FORCE INDEX (natnorthings) USING(gridimage_id)"; //force index is very imporant particuly for the tag_public join

			$spatial_where = "nateastings BETWEEN {$mbr['mbr_xmin']} AND {$mbr['mbr_xmax']}
        		       	     AND natnorthings BETWEEN {$mbr['mbr_ymin']} AND {$mbr['mbr_ymax']}";

			$message = "Images within 1km of center of ".($place['km_ref'] ?? $gridref);
		} else {
			//todo, need some sort of fallback, if it not a match from our gazetters?
			die("unknown placename");
		}
	}
}

#############################

if (preg_match('/^Pre (\d+)/',$tag,$m)) {
    $spatial_where .= " AND gi.imagetaken > '1000-01-01' AND gi.imagetaken < '{$m[1]}-00-00'";
    $tag = ''; //can't filter below by it. So remove the filter.
}

##################################################################

if ($type == 'clip') {

    //	clipthelandscape.labels contains the labels, the  clipthelandscape model predictied, which mimik our 'top' tags
    // types_dataset_1 contains the manual moderation in .types (ie just an easy way to find them
	// and .ai_results contains the predicted 'type' tags by another model. 


    // We fetch all images for the town that contain the tag anywhere in their labels string
    $sql = "SELECT $cols, labels, types, ai_result
            FROM clipthelandscape
	    $join_tables
	    LEFT JOIN types_dataset_1 USING (gridimage_id)
	    WHERE $spatial_where";

    if (!empty($tag)) {
        // Use LIKE to find images that contain this specific tag in the labels list

	//these are all the offical tags in 'types' and MOSTLY what is in ai_result
	$classes = ["Aerial", "Close Look", "Cross Grid", "Extra", "Geograph", "Inside", "From Drone"];
	if ($tag == "From Drone") { //actully for now, we can only match this from types; in ai_result, its 'means' more general "From Above" (which dont want!)
		 $sql .= " AND labels LIKE " . $db->Quote("%$tag%");

	} elseif (in_array($tag,$classes)) {
		$tag2 = $tag;
		if ($tag2 == 'Cross Grid') $tag2 = 'Cross'; //needs to match Cross Near + Cross Far!!
		$sql .= " AND (types LIKE " . $db->Quote("%$tag%")." OR ai_result LIKE " . $db->Quote("%$tag2%").")";

	} elseif ($tag == 'From Above') { //'Above' really just means our AI though it looked like Drone!, 
		$sql .= " AND ai_result LIKE " . $db->Quote("%From Drone%");

	} else {
		//otherwise it more normal 'labels' match
	        $sql .= " AND labels LIKE " . $db->Quote("%$tag%");
	}
    }
    $sql .= " LIMIT 4000";

##################################################################

} elseif ($type == 'types') {
	//clip mode, can include the AI suggestions (but it still proirities tags over ai results) 
	// ... this mode ONLY shows AI type tags!
	//also note, it shows the raw tags, rather than converting them to 'normal' type tags!

    $sql = "SELECT $cols, replace(ai_result,',',';') as labels
            FROM types_dataset_1
            $join_tables
            WHERE $spatial_where AND ai_result IS NOT NULL";

    if (!empty($tag)) {
        // Use LIKE to find images that contain this specific tag in the labels list
        $sql .= " AND ai_result LIKE " . $db->Quote("%" . $tag . "%");
    }

##################################################################

} elseif ($type == 'clipzero') {

    // We fetch all images for the town that contain the tag anywhere in their labels string
    $sql = "SELECT $cols, labels
            FROM clipzero
	    $join_tables WHERE $spatial_where";

    if (!empty($tag)) {
        // Use LIKE to find images that contain this specific tag in the labels list
        $sql .= " AND labels LIKE " . $db->Quote("%" . $tag . "%");
    }
    $sql .= " LIMIT 4000";

##################################################################

} elseif ($type == 'top' || $type == 'subject' || $type == 'type') {
    $sql = "SELECT $cols, group_concat(tag separator '; ') as labels
            FROM tag_public
            $join_tables WHERE $spatial_where AND prefix = " . $db->Quote($type);

    if (!empty($tag)) {
        if ($type == 'subject') {
                //subject prefix will have to revert a wall, as only one subject tag!
	        $sql .= " AND tag = " . $db->Quote($tag) . " GROUP BY gridimage_id LIMIT 100"; $wall = true;
	} else {
                $sql .= " GROUP BY gridimage_id HAVING labels LIKE " . $db->Quote("%$tag%")." LIMIT 1000";
        }
    } else {
        $sql .= " GROUP BY gridimage_id LIMIT 2000";
    }

##################################################################

} elseif ($type == 'cluster') {
    $sql = "SELECT $cols, GROUP_CONCAT(label SEPARATOR '; ') AS labels
            FROM gridimage_group
            $join_tables WHERE $spatial_where";
            //AND label NOT IN ('(Other)', 'Other Topics') -- we dont hard filter them, in case the image only has one group, and would be excluded!

    if (!empty($tag)) {
        // Drill-down filmstrip for specific group label
        $sql .= " GROUP BY gridimage_id HAVING labels LIKE " . $db->Quote("%$tag%") . " LIMIT 1000";
    } else {
        $sql .= " GROUP BY gridimage_id LIMIT 2000";
    }

##################################################################

} elseif ($type == 'md3') {

    $sql = "SELECT $cols, caption
            FROM gridimage_caption c
            $join_tables WHERE $spatial_where AND c.type = 'tags'";

    if (!empty($tag)) {
        // Normalizing search: we search the raw text for the tag
        $sql .= " AND caption LIKE " . $db->Quote("%$tag%") . " LIMIT 1000";
    } else {
        $sql .= " LIMIT 2000";
    }
}

if (!empty($_GET['print']))
	print_r($sql);

##################################################################

// 3. Fetch Images
$imagelist = new ImageList;
$imagelist->_setDB($db);
$imagelist->_getImagesBySql($sql);

print "<div style=float:right>Found ".count($imagelist->images)." Images</div>";

echo "<h2>Geograph Images of <span style=color:blue>" . htmlentities($town)."</span> area <a href=#cite title=\"* and the immediate surrounding area\" style=text-decoration:none>*</a>";
if (!empty($place['Country']) && $place['Country'] != 'Unknown')
	print ", <span style=color:gray>".htmlentities($place['Country'])."</span>";
if (!empty($_GET['tag'])) {
    // Generate a URL that keeps the town and type but drops the tag filter
    $reset_url = "?" . http_build_query(['type' => $type, 'town' => $town]);
    echo ", and matching [<tt>" . htmlentities($_GET['tag']) . "</tt>] ";
    echo "<a href='$reset_url' class='remove-filter'>Remove filter</a>";
}
echo "</h2>";

##################################################################

print "<div class=\"{$type}-mode flex-container\">";

// 4. DISPLAY VIEW: "The Wall" (Specific Tag Search)
if (!empty($wall) && !empty($tag)) { //tags now display grouped!
    echo '<div class="image-wall">';
    foreach ($imagelist->images as $image) {
        $image->_setDB($db);
        renderThumbnail($image);
    }
    echo '</div>';
}

##################################################################

// 5. DISPLAY VIEW: "The Filmstrip" (Grouped View)
else {
    $grouped = [];
    $stat = [];
    foreach ($imagelist->images as $image) {
	//deal with more messy VLM generated lables
        if (!empty($image->caption)) {
		$list = explode(',', strtolower(preg_replace('/[\r\n]+/','',utf8_to_latin1($image->caption))));
		$list = array_unique($list); // De-duplicate in case the VLM repeated itself
	} else {
		//labels is genareted by GROUP_CONCAT(, so clean
	        $list = explode(';', $image->labels);
	}
	if (!empty($_GET['only'])) //just for testing the types filtering!
		$list = [];
	if (!empty($image->types) || !empty($image->ai_result)) {
		// --- perhaps a 'todo', couidl consider removing other labels for some non-geo. Ie if it Close Look, or inside (for exmaple) maybe DONT want to include in the normal breakdown, and ONLY in its own breakdown???

		//prefer human tags if available!
		if (!empty($image->types)) {
			foreach(explode(',', $image->types) as $label)
				if ($label != 'Geograph' && $label != "Cross Grid")
					$list[] = $label;
		} else {
			//ai_result is from types_dataset, so is still specifically list of (predicted) types.
			$image->ai_result = str_replace(' (unsure)','',$image->ai_result); //ignore for now!
			foreach(explode('; ', $image->ai_result) as $label) {
				if ($label == 'Cross Far')
					$list[] = "Cross Grid"; //use our normal label
				elseif ($label == 'From Drone') //was meant to catch POENTIAL drones, but not reliable, so in general is a looking down image
					$list[] = "From Above";
                                elseif ($label != 'Geograph' && $label != 'Cross Near' && $label != 'None') //Cross Near isnt particulyl useful, as it not visually distinctive group
                                        $list[] = $label;
				//will allow Geograph to just be broken down by the Context Tags directly.
			}
		}
        }
        foreach ($list as $t) {
            $t = trim($t, '[]", ');
            if (!empty($tag) && $t == $tag) {
                if (count($list) == 1) // if the ONLY tag on the image, still need to add it.
		    @$grouped[$t][] = $image;
            } elseif ($t == '(Other)' || $t == 'Other Topics') {
                if (count($list) == 1) // simially only use the 'other' groups, if its the ONLY category. otehrwise the images that strugged to be classfied, would never be shown?
		    @$grouped[$t][] = $image;
            } else
                @$grouped[$t][] = $image;
            if ($image->km_ref == $image->grid_reference) @$stat[$t]++;
        }
        if ($image->imagetaken > "1000" && $image->imagetaken < "2000") {
		$t = ($image->imagetaken < "1970")?"Pre 1970":"Pre 2000";
	        @$grouped[$t][] = $image;
        }
    }

##################################################################

//1. Create a map of "Image Set Fingerprints"
//... carrot2 in particular can create duplicate groups
$fingerprints = [];
foreach ($grouped as $tag => $images) {
    // Sort IDs so the order doesn't matter, then stringify them
    $ids = array_map(function($img) { return $img->gridimage_id; }, $images);
    sort($ids);
    $fingerprint = implode(',', $ids);

    if (!isset($fingerprints[$fingerprint])) {
        // First time we've seen this set of images
        $fingerprints[$fingerprint] = $tag;
    } else {
        // We have a duplicate cluster! Keep the longer name.
        if (strlen($tag) > strlen($fingerprints[$fingerprint])) {
            // Remove the old shorter-named group from the main array
            unset($grouped[$fingerprints[$fingerprint]]);
            // Update the fingerprint map to the new longer name
            $fingerprints[$fingerprint] = $tag;
        } else {
            // The current tag is shorter or equal, so discard it
            unset($grouped[$tag]);
        }
    }
}

    //noticed, in particular this category can end up looking very similar to others (eg Roads, because most housing are taken from road)
    //todo could also do with City Center and Businss/Retail, as have so much overlap too!
    if (isset($grouped['Housing, Dwellings']))
        shuffle($grouped['Housing, Dwellings']);


    $stat2 = $stat;
    if (isset($stat['City, Town centre']))   $stat2['City, Town centre'] *= 4; //fudge to show very highly!
    foreach(array('urban scene', 'town', 'buildings', 'street scene', 'high street', 'town center', 'town centre', 'town hall', 'village hall', 'market square', 'Pre 1970','Pre 2000') as $tag)
	if (isset($stat[$tag]))  	$stat2[$tag] *= 3;

    $town2 = substring_index($town,'/',1);
    if (isset($stat["$town2 Landmark"]))  	$stat2["$town2 Landmark"] *= 4;


    // Sort using your custom uksort logic
    uksort($grouped, function($a, $b) use ($stat2, $grouped) {
        $comparison = ($stat2[$b] ?? 0) <=> ($stat2[$a] ?? 0);
        return ($comparison === 0) ? count($grouped[$b]) <=> count($grouped[$a]) : $comparison;
    });

##################################################################

    $shown = array();
    foreach ($grouped as $t => $images) {
        //exlcude small clusters, that are just repeating already seen images
        //.. want to try to make sure unqieu clusters see seen!
        if (count($images) < 4) {
             $unique = 0;
             foreach($images as $image) {
                 if (empty($shown[$image->gridimage_id]))
                     $unique++;
             }
             if (!$unique)
		continue;
        }
        if (empty($_GET['more']) && count($shown) > 700) {
            $truncated = true;
	    break;
        }

if (!empty($_GET['sample']))
$images = array_slice($images, 0, 5);

        echo '<div class="image-entry';
	if (count($images) < 10) {
		//allow small groups to 'tile', rather than using whole width
		$max = count($images)*215;
		print " image-compact\" style=\"flex:0 0 content;border-left:1px solid silver;padding-left:3px;max-width:100%";
	}
	echo '">';
        echo '<h3>' . htmlentities($t) . ' <small>' . (isset($stat[$t]) ? sprintf('%d+%d', $stat[$t], count($images)-$stat[$t]) : "+".count($images)) . '</small></h3>';

        foreach (array_slice($images, 0, 20) as $image) {
            $image->_setDB($db);
            renderThumbnail($image);
	    @$shown[$image->gridimage_id]++;
        }

        if (count($images) > 20) {
            $url = "?" . http_build_query(['type' => $type, 'tag' => $t, 'town' => $town]);
            echo "<div class='image-thumbnail show-more-card'><a href='$url'><span>Load More</span> &gt;</a></div>";
        }
        echo '</div>';
    }

	if (!empty($truncated)) {
	    // Merge existing GET params and add more=1
	    $url = "?" . http_build_query(array_merge($_GET, ['more' => 1]));
	    echo "<div class='truncation-warning'>";
	    echo "Showing the about 700 images across the top groups. ";
	    echo "<a href=\"$url\" rel=\"nofollow\">View all " . count($grouped) . " groups</a>";
	    echo "</div>";
	}

##################################################################

?>
<script>
document.querySelectorAll('.image-entry').forEach(slider => {
    let isDown = false;
    let startX;
    let scrollLeft;
    let moved = false; // Track if movement occurred

    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        moved = false; // Reset on every new click
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
        slider.style.cursor = 'grabbing !important';

        // Prevent highlighting text/images while dragging
        e.preventDefault();
    });

    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.style.cursor = 'grab';
    });

    slider.addEventListener('mouseup', (e) => {
        isDown = false;
        slider.style.cursor = 'grab';
    });

    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        // If the mouse moves more than 5 pixels, consider it a drag
        if (Math.abs(x - startX) > 5) {
            moved = true;
        }
        slider.scrollLeft = scrollLeft - walk;
    });

    // The Fix: Intercept clicks on links
    slider.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
            if (moved) {
                e.preventDefault(); // Stop the link from opening if we were dragging
            }
        });
    });
});
</script>
<?


}
print "</div>";

print "<p><a name=cite>* typically includes the immediate surrounding area</a>, note: $message</p>";

if (!empty($sources[$type]))
	print "<p>Data Source: {$sources[$type]}";

// Helper to keep the loop code clean
function renderThumbnail($image) {
    global $CONF;
    $ref = (strlen($image->grid_reference) == 5) ? 2 : 1;
    $url = $CONF['canonical_domain'][$ref] . "/photo/" . $image->gridimage_id;
    $title = htmlentities2($image->grid_reference . " : " . $image->title . " by " . $image->realname);
    echo "<div class='image-thumbnail'><a title='$title' href='$url'>" . 
         $image->getThumbnail(213, 160, false, true, 'loading=lazy src') . 
         "</a></div>";
}

$smarty->display('_std_end.tpl');

	function substring_index($url,$delimiter,$count) {
	        $segments = explode($delimiter, $url, $count+1);
        	$extracted_segments = array_slice($segments, 0, $count);
	        return implode($delimiter, $extracted_segments);
	}

