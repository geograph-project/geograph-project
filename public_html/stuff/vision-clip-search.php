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

	$rt = GeographSphinxConnection('manticorert',true);

		require_once('geograph/imagelistknn.class.php');
		$imagelist=new ImageListKNN;
		$imagelist->_setSph($rt); //need to force it to use RT backend

		$thumbw=213; $thumbh=160;


if (empty($_GET['inner'])) {
	$smarty->display('_std_begin.tpl');

	?>

        <h2>CLIP-based Similarity Search (Demo Dataset)</h2>

	<p style=max-width:900px>Currently this demo uses a small and rather limited image 
	sample, you might notice that while the initial results are visually similar, the quality 
	quickly declines as it displays 100 images without filtering for relevance. While the 
	initial results are often good, the quality of the matches can quickly decline.

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<link href="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.css"); ?>" rel="stylesheet"/>
	<script src="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.js"); ?>"></script>

	<script>
	$(function() {
		$('form[name=theForm] select').each(function() {
			$(this).on('change', function() {
				var name = this.name;
				var value = this.value;
				$('#results').load("?inner=1&"+encodeURIComponent(name)+"="+encodeURIComponent(value));
				$(this).siblings('select').each(function() { //automatically only finds OTHERS
					this.selectedIndex=0;
				});
			});
		});

		$('select[name=label]').select2({width:"600px"});
	});
	</script>
	<hr>
	<?

####################################################

	print "<form method=get name=theForm>";
//	$sql = "select id, user_id, title from gridimage_embedding"; //tends to be domindated by images from dave hitchborne!
//	$sql = "select id,realname,title from gridimage_embedding where id > 2519432 order by rand() limit 100 option rand_seed=3";
	$sql = "select id, user_id, title from gridimage_embedding where user_id != 4330"; //tends to be domindated by images from dave hitchborne! (titles dont work as have placenames!) 

	$imagelist->getImagesBySphinxQL($sql);
	print "Base Image: <select name=id style=max-width:400px>";
	print "<option></option>";
	foreach($imagelist->images as $image) {
		printf('<option value=%d%s>%s</value>', $image->gridimage_id, (@$_GET['id'] == $image->gridimage_id)?' selected':'', htmlentities($image->title)); //its actutty utf8 in manticore!
	}
	print "</select> (random selection of images from current demo dataset)<br>";

//	$list = $db->getAssoc("SELECT label,round((1-nearest_image)*100) as percent FROM label_embedding WHERE embeddings IS NOT NULL ORDER BY rand(42) LIMIT 1000");
	//ksort($list);
	$list = $db->getAssoc("select label,round((1-nearest_image)*100,1) as percent from label_embedding where nearest_image is not null group by floor(nearest_image*1000) order by label");
	print "Query: <select name=label style=max-width:400px>";
	print "<option></option>";
	foreach($list as $label => $percent) {
		printf('<option value="%s"%s>%s (%d%%)</value>', $l=htmlentities($label), (@$_GET['label'] == $label)?' selected':'', $l, $percent);
	}
	print "</select> (selection of terms to try)<br>";

	print "</form>";

	print "<hr>";

	print "<div id=\"results\">";
}


$host = $rt->getOne("SELECT hostname FROM test");
print "Host = $host<hr>";

####################################################

	if (!empty($_GET['id'])) {
		$id = intval($_GET['id']);

		$sql = "select id, user_id, realname, title, 1 as reference_index, grid_reference from gridimage_embedding where id = $id";

		print "<div style=float:left;width:450px;padding:20px>";
		print "These images are visually similar to the source image, but the similarity is based purely on appearance, not on the image's title or location. For instance, you'll see other churches, but not necessarily the same church from a different angle.";
		//print "The results are showing images visually similar to this, <b>not based on the image title</b>. This similarity index is NOT location aware. So for example wont be the same Church, just Churches in general.";
		print "</div>";
		$imagelist->getImagesBySphinxQL($sql);
		$imagelist->outputThumbs($thumbw,$thumbh);


		$imagelist->getImagesSimilarToID($id);
		$imagelist->outputThumbs($thumbw,$thumbh);

####################################################

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
