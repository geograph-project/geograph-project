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

// customExpiresHeader(3600,false,true);


	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//currently this demo repurposed for testing s3vectors, ratther than the manticore index!
$_GET['s3'] = 1;
if (empty($_GET['dist']))
	$_GET['dist'] = 5000;


		$orig = $memcache;
		$memcache = false; //need to disable memcache with FileSystem!

		$filesystem = new FileSystem(); //sets up configuation automagically
		//the vector lip needs S3 class setup already!

		require_once('geograph/imagelists3vector.class.php');
		$imagelist=new ImageListS3Vector;

		$memcache = $orig;


	$thumbw=213; $thumbh=160;

        require_once "geograph/locationselector.class.php";
        $location = new LocationSelector();

if (empty($_GET['inner'])) {
	$smarty->display('_std_begin.tpl');

	?>

        <h2>CLIP-based Similarity Search (Demo Dataset)</h2>

	<p style=max-width:900px;font-size:0.9em> This demo uses a sample of about 800,000 images. While initial results are 
	often visually similar, the quality tends to decline quickly as it displays 30 images without further relevance 
	filtering. This is a visual similarity search, so it cannot search for specific names or places like 'Harlech Castle'. 
	Instead, search for a general term like 'castle' and then use the location filter to center your search around Harlech.

	<p style=max-width:900px;font-size:0.9em> A neat feature is the ability to combine concepts in your search! Try queries 
	like "castle and red sunset", "headland from the sea", "high street without people", "red cottages with a blue sky" or 
	"cars driving in the rain". Just be aware that you might not get perfectly precise matches, the system aims to show the 
	most visually similar results, even if the resemblance isn't exact.

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<? echo $location->getScripts(); ?>
	<script>
	function quickFetch() {
		let query = $('form[name=theForm]').serialize();
		$('#results').load("?inner=1&"+query)

		history.pushState({query:query}, '', "?"+query);
	}
	var timer=null;
	$(function() {
		$('#query').on('keyup',function() {
			if(timer)
				clearTimeout($timer);
			timer = setTimeout(function() {
				quickFetch();
				timer = null;
			},500);
		});
	});

	//automaticall callabck from the location autocomplete
	function jumpLocation(form) {
	      //form.submit();
		quickFetch();
	}

window.onpopstate = function(event) {
    if (event.state && event.state.query) {
        // Restore form fields from the state
        restoreForm(event.state.query);
        // Load the results based on the restored state
        $('#results').load("?inner=1&" + event.state.query);
    } else {
        // This handles cases where popstate is triggered for a state that doesn't have your custom data
        // e.g., initial page load or a state pushed by another script.
        // You might want to reset the form or load default content.
        // For example, if it's the very first state (no custom state data), you might want to clear the form
        // or re-evaluate window.location.search to populate from the URL.
        const currentQuery = window.location.search.substring(1);
        restoreForm(currentQuery);
        $('#results').load("?inner=1&" + currentQuery);
    }
};

/**
 * Parses a query string and populates the form fields.
 * @param {string} queryString The URL query string (e.g., "query=test&dist=1000")
 */
function restoreForm(queryString) {
    // Clear existing form values before populating
    $('form[name=theForm]')[0].reset(); // Resets all form fields to their initial state/value attributes

    // Use URLSearchParams for robust parsing of query strings
    const params = new URLSearchParams(queryString);

    params.forEach((value, name) => {
        const input = $('form[name=theForm] [name="' + name + '"]');
        if (input.length) {
            // For text/number/search inputs
            input.val(value);
        }
    });
}

	</script>

    <style>
        .form-grid-container {
            display: grid;
            /* Define two columns: one for labels (auto-sized) and one for inputs (fills remaining space) */
            grid-template-columns: auto 1fr;
            /* Add some gap between rows and columns */
            gap: 3px 5px;
            /* Optional: Add some padding to the container */
            padding: 4px;
            /* Optional: Max width for better form layout */
            max-width: 600px;
            border: 1px solid #ccc; /* Just for visualization */
            border-radius: 8px;
	    background-color:#eee;
        }

        /* Style for labels to ensure they align nicely */
        .form-grid-container label {
            text-align: right; /* Align labels to the right of their cell */
            padding-right: 5px; /* Add some space between label and input */
            align-self: center; /* Vertically center the label in its grid cell */
        }

        /* Style for inputs to occupy their full column width */
        .form-grid-container input[type="search"] {
            width: 100%; /* Make inputs take up full width of their grid cell */
            padding: 8px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-grid-container input[type="number"] {
            padding: 8px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            border: 1px solid #ddd;
	    text-align: right;
            border-radius: 4px;
        }

        /* Button styling */
        .form-grid-container .button-row {
            grid-column: 1 / -1; /* Make the button row span all columns */
            text-align: right; /* Align button to the right */
            padding-top: 10px;
        }

        .form-grid-container input[type="button"] {
            padding: 4px 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .form-grid-container input[type="button"]:hover {
            background-color: #0056b3;
        }
    </style>

<form method="get" name="theForm">
    <div class="form-grid-container">
        <label for="query">Query:</label>
        <input type="search" name="query" id="query" value="<?php echo htmlentities($_GET['query']??'') ?>" placeholder="describe what want to see" list="examples">

        <label for="loc">Optional Location:</label>
	<? print $location->getInput($_GET['loc']??''); ?>

        <label for="dist">Distance:</label>
        <div>
            <input type="number" min="0" max="100000" step="1000" name="dist" id="dist" value="<?php echo htmlentities($_GET['dist'])??''; ?>">m
            <span style="font-size: 0.9em; color: #666;"> (max=100000m)</span>

            <input type="button" onclick="quickFetch()" value="Update">
        </div>
    </div>
</form>

	<?

####################################################

	$list = $db->getAssoc("select label,round((1-nearest_image)*100,1) as percent from label_embedding where nearest_image is not null group by floor(nearest_image*1000) order by label");
	print " <datalist id=\"examples\">";
	if (!empty($_GET['label']) && !isset($list[$_GET['label']]))
		$list[$_GET['label']] = '50';

	foreach($list as $label => $percent) {
		printf('<option value="%s"%s>%s (%d%%)</value>', $l=htmlentities($label), (@$_GET['label'] == $label)?' selected':'', $l, $percent);
	}
	print "</datalist>";

	print "<div id=\"results\">";
}

####################################################

if (!empty($_GET['loc'])) {
        list($lat, $lng) = $location->extractLatLng($_GET['loc']);

        if (!empty($lat) && isset($lng)) { //lng COULD be e
		$_GET['lat'] = $lat;
		$_GET['lon'] = $lng;
        }
}

####################################################
//gerenal centered serach

	if (!empty($_GET['lat']) && !empty($_GET['lon']) && !empty($_GET['dist'])) {
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		$dist = $_GET['dist'];
		$label = !empty($_GET['query']) ? $_GET['query'] : null;

		print "<p>These images are visually similar to the term <b>".htmlentities($label)."</b> and within ".round($dist/1000,1)."km of ".round($lat,6).", ".round($lon,6).".<br>";

		if ($imagelist->getImagesByLocation($lat, $lon, $dist, $label)) {
			$imagelist->outputThumbs($thumbw, $thumbh);
		} else
			print "no results found";

####################################################
//new vector search

	} elseif (!empty($_GET['lat']) && !empty($_GET['lon'])) {
		if (!empty($_GET['s3'])) {
			die("this search method isnt yet supported, specify a distance above");
		}
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		$label = $_GET['query'];

		print "<p>These images are visually similar to the term <b>".htmlentities($label)."</b> and location ".round($lat,6).", ".round($lon,6).". (using experimental vector append method)<br>";

		if ($imagelist->getImagesByLocationVector($lat, $lon, $label)) {
			$imagelist->outputThumbs($thumbw, $thumbh);
		} else
			print "no results found";

####################################################
//and even a plan ol label search! (no location)

	} elseif (!empty($_GET['query'])) {
		$label = $_GET['query'];

//		print "<div style=float:left;width:450px;padding:20px>";
		print "<p>These images are visually similar to the term <b>".htmlentities($label)."</b>, but the similarity is (currently) based purely on appearance, not on the image's title or location, or other data.<br>";
//		print "</div>";

		if ($imagelist->getImagesSimilarToLabel($label))
			$imagelist->outputThumbs($thumbw,$thumbh);
		else
			print "unknown term";

####################################################

	}

if (empty($_GET['inner'])) {
	print "</div>"; //#results

	$smarty->display('_std_end.tpl');
}
