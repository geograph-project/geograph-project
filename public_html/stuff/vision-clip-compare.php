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

	$sph = GeographSphinxConnection('sphinxql',true);

		$imagelist=new ImageList;
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
				var params = {'inner':1};
				params[this.name] = this.value;
				const urlParams = new URLSearchParams(window.location.search);
				if (urlParams.has('neg'))
					params['neg']=1;
				$('#results').load("?"+$.param(params));
				$(this).siblings('select').each(function() { //automatically only finds OTHERS
					this.selectedIndex=0;
				});
			});
		});

		$('select[name=label]').select2({width:"600px"});
	});
	</script>

	<style>
.grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 4px; /* Space between grid items */
}

.grid-item {
  background-color: lightblue;
  padding: 4px;
  border: 0px solid steelblue;
  text-align: center;
}
.item-header {
  position:sticky;top:0;background-color:white;padding:2px;z-index:100;
}
.grid-item > div:not(.item-header) {
	float:left;position:relative; width:<? echo ($thumbw+10); ?>px; height:<? echo ($thumbh+10); ?>px;
	text-align: center;
}


@media (max-width: 968px) {
  .grid-container {
    grid-template-columns: 1fr; /* One column on narrow screens */
  }

  .grid-item {
    display: inline-block; /* Make items display inline to allow horizontal scrolling */
    height: 220px;
    white-space: nowrap; /* Prevent items from wrapping */
    overflow-x: auto; /* Enable horizontal scrolling */
    overflow-y:hidden;
  }
  .item-header {
    position:inherit;
  }
  .grid-item > div:not(.item-header) {
	display: inline-block;
	float:inherit;
  }
}

	</style>
	<hr>
	<?

####################################################

	print "<form method=get name=theForm>";

	$list = $db->getCol("SELECT label FROM label_embedding WHERE embeddings IS NOT NULL ORDER BY rand(42) LIMIT 1000");
	sort($list);
	print "Query: <select name=label style=max-width:400px>";
	print "<option></option>";
	foreach($list as $label) {
		printf('<option value="%s"%s>%s</value>', $l=htmlentities($label), (@$_GET['label'] == $label)?' selected':'', $l);
	}
	print "</select> (selection of terms to try)<br>";

	print "</form>";

	print "<hr>";

	print "<div id=\"results\">";
}

####################################################
//prep
	if (!empty($_GET['label'])) {
		$quoted= $db->Quote($_GET['label']);

		$binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted"); //limit 1 added

		if (empty($binary))
			die("unknown term");

####################################################
//find via vector

		print "These images are visually similar to the term <b>".htmlentities($quoted)."</b>, but the similarity is (currently) based purely on appearance, not on the image's title or location, or other data.";
		print " We also check if images would be found by a keyword search, and seperate them below.";
		if (!empty($_GET['neg'])) {
			print " (The third column isnt very accurate, its tricky to do negative similarity searches, but in theory these results would have low chance of being matched visually, so is tending toward possible false positives)<br>";
		} else {
			print " (does not currently look for ones found by keywords, but wouldnt be via vision)<br>";
		}
		print "<hr>";

		$list = unpack('g*', $binary);
                $value = "(".implode(', ',$list).")";
		$vector = "image_vector";
	//	$vector = "title_vector";

		//always needs (id,user_id, title) (ideally realname,grid_reference too)
                $sql = "select id, user_id, realname, title, 1 as reference_index, knn_dist() as grid_reference from gridimage_embedding where knn($vector, 100, $value) limit 100";

                $imagelist->getImagesBySphinxQL($sql);

####################################################
//check keywords

		$ids = array();
		foreach($imagelist->images as $image) {
			$ids[] = $image->gridimage_id;
		}
		$query = preg_replace('/.+> (.+?)$/','$1',$_GET['label']); //onlt use last term in stack
		$query = $sph->Quote(preg_replace('/[^\w]+/',' ',$query)); //just to avoid operators!
		$ids = "(".implode(',',$ids).")";
		$sql = "select id,1 as d FROM sample8 WHERE MATCH($query) AND id IN $ids LIMIT 100";

		$keywords = $sph->getAssoc($sql);

		print "<div class=\"grid-container\">";

####################################################

		print "<div class=\"grid-item\">";
			print "<div class=\"item-header\">";
			print "<b>Only found via Vision</b> (wouldn't be found with keywords)";
			print "</div><br>";
                        $domain = '';
                        foreach ($imagelist->images as $idx => $image) {
				if (isset($keywords[$image->gridimage_id]))
					continue;
                                if (!empty($image->reference_index))
                                        $domain = $CONF['canonical_domain'][$image->reference_index];

                                print '<div>';
                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
                                print ' href="'.$domain.'/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src').'</a>';
                                print '</div>';
                        }
		print "</div>";

		print "<div class=\"grid-item\">";
			print "<div class=\"item-header\">";
			print "<b>Also found by keywords</b> (so found via either)";
			print "</div>(includes title,description,tags etc)<br>";
			if (!empty($keywords)) {
                        foreach ($imagelist->images as $idx => $image) {
				if (!isset($keywords[$image->gridimage_id]))
					continue;
                                if (!empty($image->reference_index))
                                        $domain = $CONF['canonical_domain'][$image->reference_index];

                                print '<div>';
                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
                                print ' href="'.$domain.'/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src').'</a>';
                                print '</div>';
                        }
			} else {
				print "<p>Not enough results in this demo. We only checked 100 vision matches, in reality could be results just not checked";
			}
		print "</div>";

####################################################

		if (!empty($_GET['neg'])) {
			print "<div class=\"grid-item\">";
			print "<div class=\"item-header\">";
			print "<b>Found by Keywords, but LOW vision similarity</b>";
			print "</div><br>";

			$sql = "select id, user_id, realname, title, 1 as reference_index, grid_reference, knn_dist() d from gridimage_embedding where knn($vector, 10000, $value) and match($query) limit 1000";
			$imagelist->getImagesBySphinxQL($sql);

			if (count($imagelist->images) > 40) {
				//tehre is no easy way to just get the LAST results in above query (always orders by KNN dist!)
				$imagelist->images = array_slice($imagelist->images, -20);

        	                foreach ($imagelist->images as $idx => $image) {
 	                               if (!empty($image->reference_index))
                                        	$domain = $CONF['canonical_domain'][$image->reference_index];

                                	print '<div>';
                        	        print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
                	                print ' href="'.$domain.'/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src').'</a>';
        	                        print '</div>';
	                        }

				print "<p>In theory, these results keyword match term <b>".htmlentities($quoted)."</b> (in the title only!), but are low vision matches";
			} else {
				print "<p>Not enough results in this demo";
			}
			print "</div>";
		}

####################################################

		print "</div>";

	}

if (empty($_GET['inner'])) {
	print "</div>"; //#results

	$smarty->display('_std_end.tpl');
}
