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
$smarty->display('_std_begin.tpl');
$db = GeographDatabaseConnection(true);

// 1. Inputs & Defaults
$type = $_GET['type'] ?? 'clip';
$town = $_GET['town'] ?? 'East Grinstead';
$tag = $_GET['tag'] ?? '';

$towns = array('East Grinstead', 'Fort William/An Gearasdan', 'Abergavenny/Y Fenni', 'Bicester');
if (!in_array($town, $towns)) $town = 'East Grinstead';

?> <link rel="stylesheet" href="place-experiment.css?<? echo filemtime('place-experiment.css'); ?>"> <?

// 2. Navigation Menu [cite: 11, 12]
echo '<div class="place-switcher">';
foreach ($towns as $t) {
    $active = ($t === $town) ? 'class="active"' : '';
    $url = "?" . http_build_query(['type' => $type, 'town' => $t]);
    echo "<a href='$url' $active>" . htmlentities($t) . "</a>";
}
echo '</div>';

$available_types = ['top' => 'Context Tags', 'subject' => 'Subject', 'cluster' => 'Auto Clusters', 'clip' => 'AI Context', 'md3'=>'AI Tags'];
echo '<div class="place-switcher">';
foreach ($available_types as $t_key => $t_label) {
    $active = ($t_key === $type) ? 'class="active"' : '';
    $url = "?" . http_build_query(['type' => $t_key, 'town' => $town]);
    echo "<a href='$url' $active>$t_label</a>";
}
echo '</div>';



// 2. Build SQL
$town_quote = $db->Quote($town);

##################################################################
if ($type == 'clip') {

    // We fetch all images for the town that contain the tag anywhere in their labels string
    $sql = "SELECT gridimage_id, grid_reference, km_ref, gi.user_id, title, realname, labels
            FROM clipthelandscape 
            INNER JOIN gridimage_search gi USING(gridimage_id)
            INNER JOIN gridsquare USING (grid_reference) 
            INNER JOIN sphinx_placenames USING (placename_id)
            WHERE place = $town_quote";

    if (!empty($tag)) {
        // Use LIKE to find images that contain this specific tag in the labels list
        $sql .= " AND labels LIKE " . $db->Quote("%" . $tag . "%");
    }
    $sql .= " LIMIT 4000";

##################################################################

} elseif ($type == 'top' || $type == 'subject') {
    $sql = "SELECT gridimage_id, grid_reference, km_ref, gi.user_id, title, realname, group_concat(tag separator '; ') as labels
            FROM tag_public INNER JOIN gridimage_search gi USING(gridimage_id)
            INNER JOIN gridsquare USING (grid_reference) INNER JOIN sphinx_placenames USING (placename_id)
            WHERE prefix = " . $db->Quote($type). " AND place = $town_quote";
    if (!empty($tag)) {
        if ($type == 'subject') {
                //subject prefix will have to revert a wall, as only one subject tag!
	        $sql .= " AND tag = " . $db->Quote($tag) . " GROUP BY gridimage_id LIMIT 100"; $wall = true;
	} else {
                $sql .= " GROUP BY gridimage_id HAVING labels LIKE " . $db->Quote("%$tag%")." LIMIT 1000";
        }
    } else {
        $sql .= " GROUP BY gridimage_id LIMIT 4000";
    }

##################################################################

} elseif ($type == 'cluster') {
    $sql = "SELECT gridimage_id, grid_reference, km_ref, gi.user_id, title, realname, 
                   GROUP_CONCAT(label SEPARATOR '; ') AS labels
            FROM gridimage_group INNER JOIN gridimage_search gi USING(gridimage_id)
            INNER JOIN gridsquare USING (grid_reference) 
            INNER JOIN sphinx_placenames USING (placename_id)
            WHERE place = $town_quote";
            //AND label NOT IN ('(Other)', 'Other Topics') -- we dont hard filter them, in case the image only has one group, and would be excluded!

    if (!empty($tag)) {
        // Drill-down filmstrip for specific group label
        $sql .= " GROUP BY gridimage_id HAVING labels LIKE " . $db->Quote("%$tag%") . " LIMIT 1000";
    } else {
        $sql .= " GROUP BY gridimage_id LIMIT 4000";
    }

##################################################################

} elseif ($type == 'md3') {

    $sql = "SELECT gridimage_id, grid_reference, km_ref, gi.user_id, title, realname, caption
            FROM gridimage_caption c INNER JOIN gridimage_search gi USING(gridimage_id)
            INNER JOIN gridsquare USING (grid_reference)
            INNER JOIN sphinx_placenames USING (placename_id)
            WHERE place = $town_quote AND c.type = 'tags'";

    if (!empty($tag)) {
        // Normalizing search: we search the raw text for the tag
        $sql .= " AND caption LIKE " . $db->Quote("%$tag%") . " LIMIT 1000";
    } else {
        $sql .= " LIMIT 4000";
    }
}


##################################################################

// 3. Fetch Images
$imagelist = new ImageList;
$imagelist->_setDB($db);
$imagelist->_getImagesBySql($sql);

print "<div style=float:right>Found ".count($imagelist->images)." Images</div>";

echo "<h2>Images of " . htmlentities($town)."*";
if (!empty($tag)) {
    // Generate a URL that keeps the town and type but drops the tag filter
    $reset_url = "?" . http_build_query(['type' => $type, 'town' => $town]);
    echo ", and matching [<tt>" . htmlentities($tag) . "</tt>] ";
    echo "<a href='$reset_url' class='remove-filter'>Remove filter</a>";
}
echo "</h2>";

##################################################################

print "<div class=\"{$type}-mode\">";

// 4. DISPLAY VIEW: "The Wall" (Specific Tag Search)
if (!empty($wall) && !empty($tag)) { //tags now display grouped!
    echo '<div class="image-wall">';
    foreach ($imagelist->images as $image) {
        $image->_setDB($db);
        renderThumbnail($image);
    }
    echo '</div>';
}

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
        foreach ($list as $t) {
            $t = trim($t, '[]", ');
            if (!empty($tag) && $t == $tag) {
                if (count($list) == 1) // if the ONLY tag on the image, still need to add it.
		    $grouped[$t][] = $image;
            } elseif ($t == '(Other)' || $t == 'Other Topics') {
                if (count($list) == 1) // simially only use the 'other' groups, if its the ONLY category. otehrwise the images that strugged to be classfied, would never be shown?
		    $grouped[$t][] = $image;
            } else
                $grouped[$t][] = $image;
            if ($image->km_ref == $image->grid_reference) @$stat[$t]++;
break;
        }
    }

// 1. Create a map of "Image Set Fingerprints"
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

    $stat2 = $stat;
    if (isset($stat['City, Town centre']))   $stat2['City, Town centre'] *= 4; //fudge to show very highly!
    foreach(array('urban scene', 'town', 'buildings', 'street scene', 'high street', 'town center', 'town hall', 'village hall', 'market square') as $tag)
        if (isset($stat[$tag]))  	$stat2[$tag] *= 3;



    // Sort using your custom uksort logic
    uksort($grouped, function($a, $b) use ($stat2, $grouped) {
        $comparison = ($stat2[$b] ?? 0) <=> ($stat2[$a] ?? 0);
        return ($comparison === 0) ? count($grouped[$b]) <=> count($grouped[$a]) : $comparison;
    });

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
        echo '<div class="image-entry">';
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

print "* and the immediate surrounding area";

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

