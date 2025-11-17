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

//allow cloudflare to cache
customExpiresHeader(3600*6, !empty($_GET['inner']), true);


	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($_GET['dist']))
	$_GET['dist'] = 5000;


		$orig = $memcache;
		$memcache = false; //need to disable memcache with FileSystem!

		$filesystem = new FileSystem(); //sets up configuation automagically
		//the vector lip needs S3 class setup already!

		require_once('geograph/imagelists3vector.class.php');
		$imagelist=new ImageListS3Vector;
		$imagelist->setModel('pe');

		$memcache = $orig;

#######################################
// experiment at asking tranditiaonl search to compute a vector!

if (!empty($_GET['ask']) && !empty($_GET['query']) && !preg_match('/id:\d/',$_GET['query'])) { //id is not an field in sphinx, maybe we could find title or location?

	$results = $imagelist->getImagesBySphinx($_GET['query'], 100, 1, true);
	if ($results) {
		require_once('3rdparty/vector.class.php');
		$ids = array();
		foreach($imagelist->images as $image) {
			$ids[] = $image->gridimage_id;
		}
		$idstr = implode(',',$ids);
		$db=$imagelist->_getDB(false);
		$rows = $db->getAll("SELECT * FROM gridimage_embedding WHERE type='image' AND gridimage_id IN ($idstr)");
		if (empty($rows)) { //it could happen, particlly as not all images processed! this is jus a demo
			die("sorry, unable to run this now!");
		}
		$list = array();
		foreach($rows as $row) {
			$list[] = new EmbeddingVector($row['embeddings']);
		}
		if (count($list) > 30) {
			$average = EmbeddingVector::robustAverage($list, 0.8, true);
		} else {
			$average = EmbeddingVector::average($list, true);
		}
		$bytes = $average->getBytes();
		$label = "[".md5($bytes)."]"; //create a fake label!

		//it might technically alrady exist! todo, chance of hash collision??
		$db->Execute("INSERT IGNORE INTO label_embedding_1024 (label, model, embeddings) VALUES (?, ?, ?)", [$label, 'pe', $bytes]);

		$_GET['query'] = $label;
		unset($_GET['ask']);
		$url = "?".http_build_query($_GET); //preserve other values!

		header("Location: $url");
		exit;

		print "<pre>";
		print_r($ids);
		print_r($label);
		print_r($url);
		exit;
	} else {
		die("query returned no results");
	}
}

#######################################


	$thumbw=213; $thumbh=160;

        require_once "geograph/locationselector.class.php";
        $location = new LocationSelector();

if (empty($_GET['inner'])) {
	$smarty->assign('page_title','Concept Search Demo');
	$smarty->display('_std_begin.tpl',$_SERVER['PHP_SELF']);


	if (!empty($db)) {
		$count = $db->getOne("select sum(count) from embedding_progress_pe where done is not null");
		$count = formatApproximateNumber($count);
	} else {
		$count = "1.6 million";
	}
	?>

        <h2>Concept Search Demo (PE-based Similarity Search)</h2>

	<div style="max-width:900px;font-size:0.9em">
	<? if (rand(0,2) > 1) { ?>

		<p>This demo draws from a sample of <b><? echo $count; ?> images</b>. While initial results are often
		visually strong, their quality can decline quickly as the system displays 30 images without further
		relevance filtering.

		<p>This isn't a named entity search. While the model has a broad understanding of the world and can
		recognize many prominent landmarks like the Giant's Causeway or Harlech Castle, it won't recognize every
		specific place. For example, it likely won't know a small, specific landmark like a particular church in
		Crawley.

		<p>To get the best results, use general visual concepts rather than specific names. For instance, instead
		of searching for "the cathedral in Chichester", try "Gothic cathedral". You can then use the location
		filter to refine your search.

		<p>The system's strength lies in combining visual concepts. Feel free to try queries such as: "castle and red
		sunset" "headland from the sea", "high street without people", "red cottages with a blue sky" or "cars
		driving in the rain".

		<p>The underlying model is general-purpose. While it understands broad concepts (e.g., 'rock formations'
		or 'flowers'), it hasn't been trained to identify or distinguish exact species or specific geological
		features (e.g., it recognizes a cliff but not the unique basalt columns of the Giant's Causeway).
		Therefore, while better matches should generally float to the top, expect some mismatches; the system
		prioritizes showing the most visually similar images, even if the resemblance isn't exact.

	 <? } else { ?>

		<p>This demo uses a sample of about <b><? echo $count; ?> images</b>. While initial results are often
		visually similar, the quality can decline quickly as it displays 30 images without further relevance
		filtering. This is a visual similarity search, so it cannot search for specific names or places (although
		might work for notable places like 'Harlech Castle' or 'Newcastle'). Instead, search for a general term like
		'castle' and then use the location filter to center your search around Harlech using the dedicated Location
		box.

		<p>A neat feature is the ability to combine concepts in your search! Try queries like "castle and red sunset",
		"headland from the sea", "high street without people", "red cottages with a blue sky" or "cars driving in the
		rain". Just be aware that you might not get perfectly precise matches, the system sorts the results, such that
		better matches should float to the top, even if the resemblance isn't exact.

		<p>The underlying model is designed for general-purpose visual similarity. While it understands concepts like
		'rock formations' in general, it hasn't been trained to identify or distinguish exact geological features (e.g.,
		it knows what a cliff looks like but not the specific types of Igneous intrusion).

	<? } ?>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<? echo $location->getScripts(); ?>
	<script>
	function quickFetch() {
		let query = $('form[name=theForm]').serialize();
		$('#results').load("?inner=1&"+query)

		history.pushState({query:query}, '', "?"+query);
	}
	var timer=null;
        let lastSearchQuery = <? echo json_encode($_GET['query']??''); ?>;

	$(function() {
		$('#query').on('keypress',function(event) {

			if (event.keyCode === 13) {
				// Prevent the default form submission action
				event.preventDefault();
				return; //should have already ititiated a search before enter pressed! (skips resetting timer)
			}

			//prevent needless searches when just add a space (the new search will come when actually add a word!
			const trimmedQuery = $('#query').val().trim();
			if (trimmedQuery === lastSearchQuery) {
				return;
			}

			if(timer)
				clearTimeout(timer);
			timer = setTimeout(function() {
				quickFetch();
				lastSearchQuery = trimmedQuery;
				timer = null;
			},500);

		}).on('drop',function(event) {
			var droppedData = event.originalEvent.dataTransfer.getData('text/plain');
			//intercept photo URLs, and transform it into our ID syntax
			if (m = droppedData.match(/\/photo\/(\d+)/)) {
				this.value = "id:"+m[1];
				event.preventDefault();
				quickFetch(); //update right away
			}
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
            if (name == "query")
                lastSearchQuery = value;
        }
    });
}

function openSearch(open) {
	let query = $('#query').val();
	let loc = $('#loc').val();
	let dist = parseInt($('#dist').val(),10);
	let url = '/of/';
	//todo, use 'urlplus()'
	if (loc && loc.length) {
		if (m = loc.match(/^([A-Z]{1,2}\d{4}) (.+)/)) {
			url = '/near/'+encodeURIComponent(m[2])+'/'+encodeURIComponent(m[1]);
		} else {
			url = '/near/'+encodeURIComponent(loc);
		}
		let bits = [];
		if (query && query.length)
			bits.push("filter="+encodeURIComponent(query));
		if (dist && dist > 0)
			bits.push("dist="+dist);
		if (bits.length)
			url = url + '?' + bits.join('&');
	} else {
		url = '/of/'+encodeURIComponent(query);
	}
	if (open) {
		window.open(url,'_blank');
		return false;
	} else {
		return url;
	}
}
function openMap(open) {
	var url = "/ai/pe-mapper.php";
	let query = $('#query').val();
	url = url + '?query='+encodeURIComponent(query);
	if (open) {
		window.open(url,'_blank');
		return false;
	} else {
		return url;
	}
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

        <label for="dist">Max Distance:</label>
        <div>
            <input type="number" min="0" max="100000" step="1000" name="dist" id="dist" value="<?php echo htmlentities($_GET['dist'])??''; ?>">m
            <span style="font-size: 0.9em; color: #666;"> (max=100000m)</span>

		<div style="float:right;font-size:small">
			<a href="#" onclick="return openSearch(true)" onmouseover="this.href = openSearch(false);" title="reminder: the keyword search might not understand a 'similarity' query!">Open in keyword searcher</a>
			or <a href="#" onclick="return openMap(true)" onmouseover="this.href = openMap(false);">Map</a>
		</div>

            <input type="button" onclick="quickFetch()" value="Update">
		<input type=submit name=ask value="Ask keywords" style=font-size:small title="Enter a KEYWORDS search above, this then runs that search and gets results most samantically similar to the results">
        </div>
    </div>
</form>

	<?

####################################################

	$list = $db->getAssoc("select label,round((1-nearest_image)*100,1) as percent from label_embedding_1024 where nearest_image is not null and model='pe' group by floor(nearest_image*1000) order by label");
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
// simple id search

        if (preg_match('/^id:(\d+)$/',$_GET['query'],$m) && empty($_GET['lat'])) { //getImagesSimilarToID doesnt actully support geofiltering!
		//note that imagelist class natively understands id: queries now. This is left as a demo, but is not strictly needed :)
                $id = intval($m[1]);

		//..its clearer to show the actual source image, natuallt excluded from the KNN query!
		//... but note, manticore excludes it, but s3vectors does NOT!
		print "<div style=float:left;width:450px;padding:20px>";
		print "These images are visually similar to the source image, but the similarity is based purely on appearance, not on the image's title or location. For instance, you'll see other churches, but not necessarily the same church from a different angle.";
		//print "The results are showing images visually similar to this, <b>not based on the image title</b>. This similarity index is NOT location aware. So for example wont be the same Church, just Churches in general.";
		print "</div>";
		if (!empty($rt)) { //might as well use it!
			$sql = "select id, user_id, realname, title, 1 as reference_index, grid_reference from gridimage_embedding where id = $id";
			$imagelist->getImagesBySphinxQL($sql);
		} else {
			$imagelist->getImagesByIdList(array($id));
		}
		$imagelist->outputThumbs($thumbw,$thumbh);

		// get results
		$imagelist->getImagesSimilarToID($id);
		if ($imagelist->images[0]->gridimage_id == $id) {
			unset($imagelist->images[0]);
		}
		$imagelist->outputThumbs($thumbw,$thumbh);

#####################################################
//gerenal centered serach

	} elseif (!empty($_GET['lat']) && !empty($_GET['lon']) && !empty($_GET['dist'])) {
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
		die("this search method isnt yet supported, specify a distance above");

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





function formatApproximateNumber($number) {
    if (!is_numeric($number)) {
        return $number;
    }

    $units = ['', 'K', 'M', 'B', 'T'];
    $unitIndex = 0;

    // Determine the appropriate unit
    while ($number >= 1000 && $unitIndex < count($units) - 1) {
        $number /= 1000;
        $unitIndex++;
    }

    // Round the number to one decimal place
    $formattedNumber = round($number, 1);

    // If the number is a whole number (e.g., 2.0), remove the .0
    if ($formattedNumber == round($formattedNumber)) {
        $formattedNumber = round($formattedNumber);
    }

    // Construct the final string
    return 'about ' . $formattedNumber . $units[$unitIndex];
}
