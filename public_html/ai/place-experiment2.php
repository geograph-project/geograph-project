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
    $url = "?" . http_build_query(['town' => $t]);
    echo "<a href='$url' $active>" . htmlentities($t) . "</a>";
}
echo '</div>';


$extras = array(
	0 => 'Central',
	1 => 'Including Surrounds',
	2 => 'Just Countryside',
);

$type = intval($_GET['expand'] ?? 0);
echo '<div class="place-switcher">';
foreach ($extras as $key => $label) {
    $active = ($key === $type) ? 'class="active"' : '';
    $url = "?" . http_build_query(['expand' => $key, 'town' => $town]);
    echo "<a href='$url' $active>$label</a>";
}
echo '</div>';


// 2. Build SQL
##################################################################


$sql = array();
$sql['wheres'] = array();
$sql['columns'] = explode(',','gridimage_id,user_id,realname,title,imagetaken,p.place,gi.grid_reference,spatial_scale,temporal_state,reference_index,ai.ai_result');
$sql['tables'] = array('gridimage_search gi');

$sql['tables'][] = "inner join images_place_joined p using (gridimage_id)"; //to get "place"

//$sql['tables'][] = "inner join gridimage_spc1 l using (gridimage_id)";
//$sql['wheres'][] = "l.model = 'spc1'";

//this view is used to get the updated name from spc2 model
$sql['tables'][] = "inner join gridimage_spc1_view l using (gridimage_id)"; //automatically filters to spc1, but joins in spc2 too!

//this works to canonicalize the duplicate anchors
$sql['tables'][] = "left join spc_geographic_anchor ca using (place,geographic_anchor)";
$sql['columns'][] = "COALESCE(ca.canonical_anchor,l.geographic_anchor) AS geographic_anchor";

//this works to canonicalize the duplicate subjects
$sql['tables'][] = "left join spc_primary_subject cs using (place,primary_subject)";
$sql['columns'][] = "COALESCE(cs.canonical_subject,l.primary_subject) AS primary_subject";


//$sql['tables'][] = "left join types_dataset_1 ai using (gridimage_id)";
$sql['tables'][] = "left join gridimage_type_forspc ai using (gridimage_id)";

$sql['wheres'][] = "p.place = ".$db->Quote($town);

if (!empty($_GET['subject']))
	$sql['wheres'][] = "primary_subject = ".$db->Quote($_GET['subject']);

if (!empty($_GET['scale'])) {
	if ($_GET['scale'] == 'Inside') {
		$sql['wheres'][] = "ai_result LIKE 'Inside%'";
	} elseif ($_GET['scale'] == 'From Above') {
		$sql['wheres'][] = "(ai_result LIKE 'Aerial%' OR ai_result LIKE 'From Drone%')";
	} else
		$sql['wheres'][] = "spatial_scale = ".$db->Quote($_GET['scale']);
}

if (!empty($_GET['expand'])) {
	if ($_GET['expand'] === '2') {
		//this is JUST srround!
//		$sql['wheres'][] = "geographic_anchor like 'near %'";

		//finally this adds in the clasification
		$sql['tables'][] = "left join spc_classification cls on (cls.place = p.place and cls.geographic_anchor = COALESCE(ca.canonical_anchor,l.geographic_anchor))";
		$sql['wheres'][] = "cls.classification IN ('Other Area','Other POI', 'Settlement', 'Nearby')";


	} else {
		//this is BOTH! ... so no filtering!
	}
	$sql['limit'] = 2500;
	$percell = 4;
} else {
	//just show the town

//	$sql['wheres'][] = "geographic_anchor NOT like 'near %'"; -- the problem with this it filters out otehr nearbys, not JUST the 'Near Main Town'
	//... hence use classification=Nearby below, but, also can use to exclude seperate settlements, and out of town points!

	//finally this adds in the clasification
	$sql['tables'][] = "left join spc_classification cls on (cls.place = p.place and cls.geographic_anchor = COALESCE(ca.canonical_anchor,l.geographic_anchor))";
	$sql['wheres'][] = "cls.classification NOT IN ('Other Area','Other POI', 'Settlement', 'Nearby')";

	$sql['limit'] = 2000; //250;
	$percell = 3;
}

//print htmlentities(sqlBitsToSelect($sql));exit;

$data = $db->getAll(sqlBitsToSelect($sql));

$thumbw=120; $thumbh=120;

##################################################################
// try plotting these differently using the primary_subject?

if (!empty($_GET['scale']) && $_GET['scale'] == 'Corridor') {

	//just a 1D  group by
	$subjects = array();
	foreach ($data as $row) {
		$originalLabel = $row['primary_subject'];
		    
		    // Group rows by their original label so we don't lose anything
		    if (!isset($subjects[$originalLabel])) {
		        $subjects[$originalLabel] = [
		            'rows' => [],
		            'sort_key' => getCleanKey($originalLabel) // Generate the key once per unique label
		        ];
		    }
		    $subjects[$originalLabel]['rows'][] = $row;
	}

	// 2. Sort the group based on the 'sort_key' element
	uasort($subjects, function($a, $b) {
	    return strcmp($a['sort_key'], $b['sort_key']);
	});

?>
<style>
.main-gallery-container { column-width: 300px; column-gap: 40px; padding: 20px; }
.subject-group { display: inline-block; width: 100%; margin-bottom: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #eee; break-inside: avoid; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden; }
.gallery-wrapper { display: flex; flex-wrap: wrap; gap: 4px; padding:6px; }
.subject-group h3 { margin: 0; font-size: 1.1em; background-color: #e0e0e0; padding:10px; }
</style>
<?

echo '<div class="main-gallery-container">'; // Wrap the entire loop
foreach ($subjects as $subject => $info) {
    echo '<section class="subject-group">';
    echo "<h3>" . htmlentities($subject) . "</h3>";
    echo '<div class="gallery-wrapper">'; // New wrapper div
    
    foreach ($info['rows'] as $i => $row) {
        $image = new GridImage();
        $image->fastInit($row);

        $titleAttr = $image->grid_reference . ' : ' . htmlentities($image->title) . ' by ' . htmlentities($image->realname);
        $href = $CONF['canonical_domain'][$image->reference_index] . '/photo/' . $image->gridimage_id;

        echo '<div class="gallery-item">';
        echo '<a title="' . $titleAttr . '" href="' . $href . '">';
        echo $image->getThumbnail($thumbw, $thumbh, false, true);
        echo '</a>';
        echo '</div>';
    }
    
    echo '</div>'; // End gallery-wrapper
    echo '</section>';
}
echo '</div>';

	$smarty->display('_std_end.tpl');
	exit;
}

##################################################################

$friendly = [
    'Settlement'   => 'Street Scenes',
    'Structure'    => 'Buildings and Structures',
    'Corridor'     => 'Paths and Waterways',
    'Landscape'    => 'Countryside & Views',
    'Site Feature' => 'Local Feature',
    'Detail'       => 'Object Close-up',
    'From Above'   => 'From Above',
    'Inside'       => 'Interior'
];

// 1. Define the custom sort orders
$spatialOrder = ['Settlement', 'Structure', 'Corridor', 'Landscape', 'From Above', 'Site Feature', 'Detail', 'Inside'];
$primaryAnchor =  explode('/',$town)[0];
$trailingAnchorPrefix = 'Near';

if ($_GET['expand'] ?? 0 === '2')
	$primaryAnchor = "Near $primaryAnchor";


##################################################################

// 2. Pivot the data into a 3D structure: [Anchor][Scale][State][]
$pivoted = [];
$allStates = ['Typical']; // Ensure 'Typical' is always tracked for the first column
$subjects = array();

$datecrit = date('Y-m-d', time()-86400*365*30);

foreach ($data as $row) {
    $anchor = trim(explode('/',$row['geographic_anchor'])[0]);
    $scale  = $row['spatial_scale'];
    $state  = $row['temporal_state'];

	if ($state == 'Typical' && $row['imagetaken'] > '1000-01-01' && $row['imagetaken'] < $datecrit)
		$state = 'Historical';

    @$subjects[$row['primary_subject']]++;

	if (strpos($row['ai_result'],'Aerial') !== FALSE) $scale = 'From Above';
	if (strpos($row['ai_result'],'From Drone') !== FALSE) $scale = 'From Above';
	if (strpos($row['ai_result'],'Inside') !== FALSE) $scale = 'Inside';
//	else continue;

    if (!in_array($state, $allStates)) {
        $allStates[] = $state;
    }

    $pivoted[$anchor][$scale][$state][] = $row;
}

##################################################################

// 3. Custom sort for Geographic Anchors
uksort($pivoted, function($a, $b) use ($primaryAnchor) {
    // 1. Handle the Absolute Primary (East Grinstead always #1)
    if ($a === $primaryAnchor) return -1;
    if ($b === $primaryAnchor) return 1;

    //also promote high-street to top
    if ($a === "$primaryAnchor High Street") return -1;
    if ($b === "$primaryAnchor High Street") return 1;


    // 2. Identify the "Base" name and whether it's a "Near" variant
    $aBase = str_replace('Near ', '', $a);
    $bBase = str_replace('Near ', '', $b);
    $aIsNear = (strpos($a, 'Near ') === 0);
    $bIsNear = (strpos($b, 'Near ') === 0);

    // 3. Special Case: "Near [PrimaryAnchor]" always goes to the very bottom
    if ($a === "Near $primaryAnchor") return 1;
    if ($b === "Near $primaryAnchor") return -1;

    // 4. If they belong to the same base (e.g., 'Durham' and 'Near Durham')
    if ($aBase === $bBase) {
        return $aIsNear ? 1 : -1; // The one WITHOUT 'Near' comes first
    }

    // 5. Otherwise, sort alphabetically by the Base Name
    // This keeps 'Durham' & 'Near Durham' together, and 'Sherburn Road' separate
    return strcmp($aBase, $bBase);
});

##################################################################

//todo, if GET[subject] will be filtered, so could do a seperate sql qyert to get subjects?

if (count($subjects) > 1) {
	print "<form>";
	print "<input type=hidden name=town value=\"".htmlentities($town)."\">";
	print "<select name=\"subject\" onchange=\"this.form.submit()\">";
	print "<option>Select Subject</option>";
	ksort($subjects);
	foreach($subjects as $subject => $count) {
		if ($count > 1)
			printf('<option value="%s"%s>%s (%d)</option>', $subject, '', $subject, $count);
	}
	print "</select>";
	print "</form>";
}

##################################################################

print "<p> This page uses an automated AI process to locate and group similar images based on geographic points of interest, scale, and the nature of the scene. These classifications are
 subjective and contain occasional inaccuracies, but they hopefully offer a useful way to explore the collection in a broad sense.";

print "<p>The Change column, aims to highlight images either showing active change or it's known the location has changed since the photo was taken";

// 4. Render the Table
echo '<table border="1" style="border-collapse: collapse; width: 100%; font-family: sans-serif;">';

// Header Row (Temporal States)
echo '<tr style="position:sticky;top:0;background: #eee;"><th></th>';
foreach ($allStates as $state) {
    echo "<th>" . htmlentities($state) . "</th>";
}
echo '</tr>';

foreach ($pivoted as $anchor => $scales) {
    // Geographic Anchor Header (Spans all columns)
    echo '<tr style="background: #ddd; font-weight: bold; background-color:black; color:white; position:sticky;top:20px;">';
if ($anchor == $primaryAnchor)
	$anchor .= " (General)";
    echo '<td colspan="' . (count($allStates) + 1) . '" style="padding:10px;font-size:1.3em">' . htmlentities($anchor) . '</td>';
    echo '</tr>';

    // Sort the spatial scales based on your defined list
    uksort($scales, function($a, $b) use ($spatialOrder) {
        $posA = array_search($a, $spatialOrder);
        $posB = array_search($b, $spatialOrder);
        return ($posA === false ? 999 : $posA) <=> ($posB === false ? 999 : $posB);
    });

    foreach ($scales as $scale => $states) {
        echo '<tr>';
        echo '<td style="background: #f9f9f9; font-weight: bold; width: 120px;">' . htmlentities($friendly[$scale]) . '</td>';

        foreach ($allStates as $state) {
            echo '<td style="vertical-align: top; padding: 5px; min-width: 100px;">';
            if (isset($states[$state])) {
                foreach ($states[$state] as $i => $row) {
	            if ($i == $percell && empty($_GET['scale']) && empty($_GET['subject'])) break;

                    // Logic from your snippet
                    $image = new GridImage();
                    $image->fastInit($row);
                    
                    $titleAttr = $image->grid_reference . ' : ' . htmlentities($image->title) . ' by ' . htmlentities($image->realname);
                    $href = $CONF['canonical_domain'][$image->reference_index] . '/photo/' . $image->gridimage_id;
                    
                    echo '<div style="display:inline-block; margin: 2px;">';
                    echo '<a title="' . $titleAttr . '" href="' . $href . '">';
                    echo $image->getThumbnail($thumbw, $thumbh, false, true);
                    echo '</a>';
                    echo '</div>';
                }
		if (count($states[$state]) > $percell && empty($_GET['scale'])) {
			$url = "?" . http_build_query(['town' => $town, 'scale'=>$scale]); //for now NOT state!
			print " <a href=\"$url\">(".count($states[$state]).")</a>";
		}
            }
            echo '</td>';
        }
        echo '</tr>';
    }
}
echo '</table>';

print "<p>The AI is still learning its way around the map - thanks for your patience with any misplaced landmarks!";

##################################################################

$smarty->display('_std_end.tpl');



function getCleanKey($label) {
    // 1. Convert to lowercase for uniform matching
    $key = strtolower($label);

    // 2. Remove common prefixes and "noise" words
    // We use a regex to strip start-of-string noise and common fillers
    $noise = [
        '/^a\s+/', '/^the\s+/', '/^public\s+/', 
        '/\b(towards|near|north of|south of|from|to|in|at|by|through|alongside)\b/',
        '/\b(footpath|path|bridleway|railway line|railway|track|road|lane|way|alleyway|bridge|cutting)\b/'
    ];

    $key = preg_replace($noise, '', $key);

    // 3. Clean up extra spaces and punctuation
    $key = trim(preg_replace('/\s+/', ' ', $key));
    
    return $key ?: 'miscellaneous'; // Fallback for very short strings
}
