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

//the gridimage_embedding is only on DEV instance for now!
$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!


$smarty = new GeographPage;

// customExpiresHeader(3600,false,true);


	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//currently this demo repurposed for testing s3vectors, ratther than the manticore index!
$_GET['s3'] = 1;
if (empty($_GET['dist']))
	$_GET['dist'] = 5000;

	if (!empty($_GET['s3'])) {

		$orig = $memcache;
		$memcache = false; //need to disable memcache with FileSystem!

		$filesystem = new FileSystem(); //sets up configuation automagically
		//the vector lip needs S3 class setup already!

		require_once('geograph/imagelists3vector.class.php');
		$imagelist=new ImageListS3Vector;

		$memcache = $orig;
	} else {
		$rt = GeographSphinxConnection('manticorert',true);

		require_once('geograph/imagelistknn.class.php');
		$imagelist=new ImageListKNN;
		$imagelist->_setSph($rt); //need to force it to use RT backend
	}

	$thumbw=213; $thumbh=160;

        require_once "geograph/locationselector.class.php";
        $location = new LocationSelector();

if (empty($_GET['inner'])) {
	$smarty->display('_std_begin.tpl');

	?>

        <h2>CLIP-based Similarity Search (Demo Dataset)</h2>

<? if (!empty($_GET['s3'])) { ?>
	<p style=max-width:900px;font-size:0.9em>Currently this demo uses a sample of about <b>650k
	images</b>, you might notice that while the initial results are visually similar, the quality 
	quickly declines as it displays 30 images without filtering for relevance. While the 
	initial results are often good, the quality of the matches can quickly decline.

<? } else { ?>
	<p style=max-width:900px;font-size:0.9em>Currently this demo uses a small and rather limited image 
	sample, you might notice that while the initial results are visually similar, the quality 
	quickly declines as it displays 100 images without filtering for relevance. While the 
	initial results are often good, the quality of the matches can quickly decline.
<? } ?>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<link href="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.css"); ?>" rel="stylesheet"/>
	<script src="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.js"); ?>"></script>

	<? echo $location->getScripts(); ?>
	<script>
	var mapOpened = false;
	function quickFetch() {
		let query = $('form[name=theForm]').serialize();
		$('#results').load("?inner=1&"+query)
		if (mapOpened)
			openMap(); //no way to turn it off!
	}
	function openMap() {
		mapOpened = true;
		let query = $('form[name=theForm]').serialize();
		window.open('?inner=2&map=1&'+query, 'maptab');
	}
	$(function() {
		//this enforces the dropdowns to be mutually exclusive!
		$('form[name=theForm] select').each(function() {
			$(this).on('change', function() {
				$(this).siblings('select').each(function() { //automatically only finds OTHERS
					this.selectedIndex=0;
				});
				quickFetch();
			});
		});

		$('select[name=label]').select2({width:"600px"});
	});

function jumpLocation(form) {
      //form.submit();
	quickFetch();
}
	</script>
	<hr>
	<?

####################################################

	print "<form method=get name=theForm>";
	/////////////////////

	//use the RT backend if possible!
	if (!empty($rt)) {

	//	$sql = "select id, user_id, title from gridimage_embedding"; //tends to be domindated by images from dave hitchborne!
	//	$sql = "select id,realname,title from gridimage_embedding where id > 2519432 order by rand() limit 100 option rand_seed=3";
		$sql = "select id, user_id, title from gridimage_embedding where user_id != 4330"; //tends to be domindated by images from dave hitchborne! (titles dont work as have placenames!) 
		$imagelist->getImagesBySphinxQL($sql);
	} else {
		$sql = "select gridimage_id, user_id, title from gridimage_embedding inner join gridimage_search using (gridimage_id) where type = 'image' limit 30";
		$imagelist->_getImagesBySql($sql);
	}

	print "<b>Base Image</b>: (random selection of images from current demo dataset)<br>";
	print "<select name=id style=max-width:400px>";
	print "<option></option>";
	foreach($imagelist->images as $image) {
		printf('<option value=%d%s>%s</value>', $image->gridimage_id, (@$_GET['id'] == $image->gridimage_id)?' selected':'', htmlentities($image->title)); //its actutty utf8 in manticore!
	}
	print "</select><hr>";

	/////////////////////

	$list = $db->getAssoc("select label,round((1-nearest_image)*100,1) as percent from label_embedding where nearest_image is not null and model = 'clip' group by floor(nearest_image*100000) order by label");
	print "<b>Label</b>: (selection of terms to try, arbitary input not supported yet)<br>";
	print " <select name=label style=max-width:400px>";
	print "<option></option>";
	if (!empty($_GET['label']) && !isset($list[$_GET['label']]))
		$list[$_GET['label']] = '50';

	foreach($list as $label => $percent) {
		printf('<option value="%s"%s>%s (%d%%)</value>', $l=htmlentities($label), (@$_GET['label'] == $label)?' selected':'', $l, $percent);
	}
	print "</select><hr>";

	/////////////////////

	print "<b>Optional Location</b>: (works with Label only)<br>";
	print $location->getInput($_GET['loc']??'');
	print "<br><b>Distance</b>: <input type=number min=0 max=100000 step=1000 name=dist value=\"".htmlentities($_GET['dist'])."\">m";
	if (!empty($_GET['s3'])) {
		print " (enter distance to get a radius search)";
	} else {
		print " (optional, enter distance to get a radius search, otherwise gets a fused simialrity/location ordering)";
	}
	print "<hr>";
	print "<input type=button onclick=quickFetch() value=Update>";

	if (empty($_GET['s3']))
		print " <input type=button value='Open Map' onclick='openMap()'>";

	print " - Note: if select a Base image, the Label/Location are currently ignored";
	/////////////////////
	print "</form>";

	print "<hr>";

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
if (!empty($_GET['map'])) {
	$imagelist->knncols .= ",wgs84_lat,wgs84_long";

	//bodge. for now the map is always opened in new window, but then lacks the template
	//todo, use _basic_begin??
	print "<script src=/js/geograph.js></script>";
}

//print htmlentities(print_r($_GET,true));

####################################################
// simply id search

	if (!empty($_GET['id'])) {
		$id = intval($_GET['id']);


		//..its clearer to show the actual source image, natuallt excluded from the KNN query!
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
		$imagelist->outputThumbs($thumbw,$thumbh);

####################################################
//push the boat out!

	} elseif (!empty($_GET['keywords'])) {
		//with keywords have to use the combined
		$criteria = [
			'lat' => $_GET['lat']??0,
			'lng' => $_GET['lon']??0,
			'dist' => $_GET['dist']??1000,
			'label' => $_GET['label'] ??'',
			'keywords' => $_GET['q'],
		];
		print "These images match the combined criteria.<br>";
		if ($imagelist->getImagesByCriteria($criteria))
			$imagelist->outputThumbs($thumbw, $thumbh);
		else
			print "no results found";

####################################################
//gerenal centered serach

	} elseif (!empty($_GET['lat']) && !empty($_GET['lon']) && !empty($_GET['dist'])) {
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		$dist = $_GET['dist'];
		$label = !empty($_GET['label']) ? $_GET['label'] : null;

		print "These images are visually similar to the term <b>".htmlentities($label)."</b> and within ".round($dist/1000,1)."km of ".round($lat,6).", ".round($lon,6).".<br>";

		if ($imagelist->getImagesByLocation($lat, $lon, $dist, $label)) {
			if (!empty($_GET['map'])) {
				$imagelist->outputMap(true);
			} else {
				$imagelist->outputThumbs($thumbw, $thumbh);
			}
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
		$label = $_GET['label'];

		print "These images are visually similar to the term <b>".htmlentities($label)."</b> and location ".round($lat,6).", ".round($lon,6).". (using experimental vector append method)<br>";

		if ($imagelist->getImagesByLocationVector($lat, $lon, $label)) {
			if (!empty($_GET['map'])) {
				$imagelist->outputMap(true);
			} else {
				$imagelist->outputThumbs($thumbw, $thumbh);
			}
		} else
			print "no results found";

####################################################
//and even a plan ol label search! (no location)

	} elseif (!empty($_GET['label'])) {
		$label = $_GET['label'];

//		print "<div style=float:left;width:450px;padding:20px>";
		print "These images are visually similar to the term <b>".htmlentities($label)."</b>, but the similarity is (currently) based purely on appearance, not on the image's title or location, or other data.<br>";
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
