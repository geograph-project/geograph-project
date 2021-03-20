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

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_POST['results']) && !empty($_POST['label'])) {
        $ids = array();
        parse_str($_POST['results'],$ids);

        $updates = array();
        $updates['user_id'] = intval($USER->user_id);
        $updates['group'] = "Geography and Geology";
        $updates['label'] = $_POST['label'];
        $updates['active'] = 1;
        $updates['score'] = 5; //label it in some way as being 'bulk' curated!

        $where= "`label` = ".$db->Quote($_POST['label']);

	$plus = $minus = 0;
        foreach ($ids as $key => $value) {
		if ($value > 0) {
			//if selected, insert into curated1

	                $updates['gridimage_id'] = intval($key);

	                $db->Execute($sql = 'INSERT IGNORE INTO curated1 SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates)) or die("$sql\n".$db->ErrorMsg()."\n\n");
			$plus+=$db->Affected_Rows();
		} else {
			//if not, update in the preselect table!

	                $sql = "UPDATE curated_preselect SET active=0 WHERE $where AND gridimage_id = ".intval($key);

	                $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
			$minus+=$db->Affected_Rows();
		}
        }
	print "$plus added. $minus not;";


        if ($plus>0) {

                $param = array('table' => 'curated1', 'debug'=>0);
                include "../../scripts/process_regions.php";


                //copied from process_decade. So trivial, might as well just copy!
                $sql = "UPDATE {$param['table']} INNER JOIN gridimage_search USING (gridimage_id)
                SET decade = CONCAT(SUBSTRING(imagetaken,1,3),'0s')
                WHERE decade ='' AND imagetaken NOT LIKE '0000%'";
                $db->Execute($sql);
        }

}



if (empty($_GET)) {
	if (!empty($_POST['label'])) {
		$_GET['label'] = $_POST['label']; //if in the middle of curating, keep them on the same label!
	} else
		$_GET['label'] = $db->getOne("SELECT label,COUNT(*) as count FROM curated_preselect WHERE active>0 ORDER BY count DESC");
}


if (!empty($_GET['prime'])) {
	$sph = GeographSphinxConnection('sphinxql',true);

	//todo, hardcoded. maybe select this from a column in curated_headword??
	//$sql = "select label,query from curated_headword where query != ''";
	$label = "wave-cut platform";
	$q = '@(title,comment,tags,groups) "wave cut" | wavecut platform';


	$label = "mill race";
	$q = '@(title,tags) "mill race" -old -former';


	$label = "limestone pavement";
	$q = '@(title,tags) "limestone pavement" -old -former';


	$q .= " MAYBE @larger 1024 MAYBE @status geograph";

	$q = $sph->Quote($q);

		//tdo, if much more results than 1000, maybe we could bais it to unselected areas somehow?
	$sql = "select id,weight() as weight from sample8 where match($q) limit 1000 option field_weights=(title=10,tags=8,comment=1)";
	$data = $sph->getAll($sql);
	print "Found ".count($data)." images.";
	$label = $db->Quote($label);
	$c=0;
	foreach ($data as $row) {
		$sql = "INSERT IGNORE INTO curated_preselect SET gridimage_id = {$row['id']}, label=$label, `weight` = {$row['weight']}, created=NOW()";
//		print "$sql;<br>";
		$db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
		$c+=$db->Affected_Rows();
	}
	print "$c affected row(s)";
	exit;
}



?>
<h2>Bulk-Curate Images</h2>

<form method=post onsubmit="countResults()" name=theForm>

<div class="interestBox">
<?

if (!empty($_GET['label'])) {
	$row = $db->getRow("SELECT * FROM curated_label WHERE label = ".$db->Quote($_GET['label']));
	if (!empty($row)) {
		print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($row['label'])."<small><br>".htmlentities($row['description'])."</small></h3>";
		if (!empty($row['notes'])) {
			print "<div style=padding:10px>".htmlentities($row['notes'])."</div>";
			print "<hr>";
		}
	} else {
		$row = $db->getRow("SELECT * FROM curated_headword WHERE label = ".$db->Quote($_GET['label']));
		if (!empty($row)) {
			print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($row['label'])."<small><br>".htmlentities($row['description'])."</small></h3>";
		} else {
			print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($_GET['label'])."</h3>";
		}
	}
}

$where= array();


if (!empty($_GET['label'])) {
	$where[] = "p.`label` = ".$db->Quote($_GET['label']);
	print "Select any image(s) that <b>really</b> illustrate '<big style=background-color:yellow>".htmlentities($_GET['label'])."</big>'";
	print '<input type=hidden name="label" value="'.htmlentities($_GET['label']).'">';

	print "<br><ol>";
	print "<li>If unsure what sort of images to select, perhaps look at what <a href=\"sample.php?label=".urlencode($_GET['label'])."\">already been pre-selected</a>";
	print "<li>The point is to be quite selective, pre-selecting the more useful results. Rather than just selecting all images!";
	print "<li>While an image might technically be of a ".htmlentities($_GET['label']).", we <i>most interested</i> in images that are <b>particully</b> representative of the subject";
		print "<br> - for example in foreground and <i>clearly</i> visible - a substantative area of the photo";
	print "<li>At this stage, don't worry too much about image quality and resolution. While we are looking for high resolution images mainly, suggest it now, and we can further cull the list if needbe";
	if (!empty($_GET['myriad'])) {
		print "<li>In general aim to select <b>two or three particulully good examples</b> (providing there are some) from this selection";
	} else {
		print "<li>Also dont worry about selecting 'too many' images - while if spot multiple photos of same feature/location, can select just the better ones, dont worry about sticking to particular number. select all the better ones!";
	}
	print "</ul>";
}



$imagelist=new ImageList;
$imagelist->_setDB($db); //imagelist, typically defaults to read-replica connection. make sure connected to master (like we did!)

if (!empty($_GET['myriad'])) {
	if (empty($row)) {
		die("<h2>this is not a known label, that is accepting currated images as yet</h2>");
	}
	if ($_GET['myriad'] == 'auto') {
		$l = 1;
		while ($l<10) {
			$_GET['myriad'] = $db->getOne("select prefix from gridprefix where landcount > 10 AND prefix NOT IN(select SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) as myriad from curated1 inner join gridimage_search gi using (gridimage_id) where label = 'waterfall' group by SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4)) order by rand()");
			if (empty($_GET['myriad']))
				die("<h2>no more unphotographed myriads!</h2>\n");
			$imagelist->getImagesBySphinx("myriad:{$_GET['myriad']} text:{$_GET['label']}",50,1);
			if (!empty($imagelist->images))
				break;
			$l++;
		}
		if (empty($imagelist->images))
			die("<h2>no unphotographed myriads found, please try again later</h2>\n");
	} else {

		$imagelist->getImagesBySphinx("myriad:{$_GET['myriad']} text:{$_GET['label']}",50,1);
	}

} elseif (true) {
	//look for images in the primed table!
        $imagelist->cols = str_replace(',',',gi.',$imagelist->cols);

	if (!empty($_GET['review'])) {
		$where[] = "p.updated LIKE ".$db->Quote($_GET['review']."%");
	} else {
		$where[] = "p.active=1"; //not been excluded yet!
		$where[] = "c.gridimage_id IS NULL"; //not been selected already!
	}

	$where = implode(" AND ",$where);

		//todo, COULD try baising this by unphotographed myriads or something??
        $sql = "SELECT {$imagelist->cols}
                FROM curated_preselect p
			LEFT JOIN curated1 c USING (gridimage_id,label)
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE $where
                ORDER BY p.`weight` desc, sequence asc
                LIMIT 50";

//print_r("<pre>$sql</pre>");

	$imagelist->_getImagesBySql($sql);
} else {
	//old demo version!
	$imagelist->getImagesBySphinx("text:{$_GET['label']}",30,1);
}

?>
</div>
<div style=float:right;font-size:small;max-width:640px>If you know of other images for <b><? echo htmlentities($_GET['label']); ?></b>, that wouldn't show in keyword search (eg use different terms), can <a href="collecter.php?label=<? echo urlencode($_GET['label']); ?>">submit them manually</a> or if your own image, add exactly [<tt><? echo htmlentities($_GET['label']); ?></tt>] as a tag!</div>

<br style=clear:both>
<br>
<p>Click a thumbnail to toggle selected on/off. Images marked in green are ones have selected.<br>
 Right click an image and use 'open in new tab' (or similar) if want to view larger.
<b>Or <input type=button value="open slideshow" onclick="showLightbox()"> to view larger images one by one</b>.

<div class=thumbs>
<?

if (empty($imagelist->images)) {
	print "No images! <a href=\"collecter.php?".http_build_query($_GET)."\">Add now</a>";
} else {

                        $thumbh = 160;
                        $thumbw = 213;

	foreach ($imagelist->images as $image) {

?>
		<div class="thumb shadow" id="t<? echo $image->gridimage_id; ?>">
                                <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - RIGHT click to view full size image" target=_blank href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a>
		</div>
<?
	}
	print "<br>";
}

?>
</div>

<p>You have selected <input type=text size=2 value=0 id="counter" readonly> images as matching. Is that correct? <input type=checkbox> (tick to confirm)</p>

<input type=hidden name="results">
<input type=submit value="Submit Results" disabled>

<p>Note: Only submit the form, if have looked though all <? echo count($imagelist->images); ?> images. ie you are selecting the good images, but ones you don't select will not be shown again in this interface. (but can still be added explicitly by others!)

Please do still submit the form even if don't spot any matching, you adding confirmation that of that too.</p>


</form>

<style>
.lightbox-background {
 display:none;
 background:#555555;
 opacity:0.8;
 -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=70)";
 filter:alpha(opacity=80);
 zoom:1;
 position:fixed;
 top:0px;
 left:0px;
 min-width:100%;
 min-height:100%;
 z-index:99;
}
#lightbox {
  display:none;
  position:fixed !important;
  top:110px;
	width:700px;
	max-width:95vw;

    left: 50%;
    transform: translate(-50%, 0);

  overflow:auto;
  background-color:silver;
  padding:20px;
  z-index:100;
border-radius:22px;
	text-align:center;
}


div.thumbs div.thumb {
	float:left;
	width: 216px;
	height: 163px;
	text-align:center;
}
div.thumbs br {
	clear:both;
}
div.thumbs div.selected {
	background-color:lightgreen;
}
div.thumbs div.selected img {
	opacity:0.5;
}
#counter {
	text-align:right;
}

</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>

var currentIdx = null;
function showLightbox(idx) {
	if (idx) {
		currentIdx = idx;
	} else {
		$('#lightbox').show();
		$('.lightbox-background').show('slow');
		currentIdx = 0;
	}

	var source = $($("div.thumbs div.thumb").get(currentIdx));

	var thumbnail = source.find('img').attr('src');
	var image = thumbnail.replace(/_213x160.jpg/,'.jpg').replace(/s\d\.geograph/,'s0.geograph');
	$('#lightbox').find('img').attr('src',image);

	var link = source.find('a').attr('href');
	$('#lightbox').find('a').attr('href',link);

	var title = source.find('img').attr('alt');
	$('#lightbox').find('span').text(title);
}

document.addEventListener('keyup', function(event) { //keyup used, as keydown auto-repeats, and keypress doesnt fire esc/backspace
	if (currentIdx===null)
		return;

	if (event.key == 'y' || event.key == 'Y') {
		if ((currentIdx+1) < $("div.thumbs div.thumb").length) {
			$($("div.thumbs div.thumb").get(currentIdx)).addClass('selected');
			$('#lightbox img').css('opacity',0.3);//test to see if blanking out the image, makes user seees it change!
			showLightbox(currentIdx+1);
			countResults();
		} else {
			 $('#lightbox').hide();
			 $('.lightbox-background').hide('fast');
			currentIdx = null;
		}
	} else if (event.key == 'Backspace') {
		if (currentIdx > 0)
			showLightbox(currentIdx-1);
	} else if (event.key == 'Escape') {
		$('#lightbox').hide();
		$('.lightbox-background').hide('fast');
			currentIdx = null;
	} else {
                if ((currentIdx+1) < $("div.thumbs div.thumb").length) {
			$($("div.thumbs div.thumb").get(currentIdx)).removeClass('selected');
			$('#lightbox img').css('opacity',0.2);//test to see if blanking out the image, makes user seees it change!
                        showLightbox(currentIdx+1);
			countResults();
                } else {
                         $('#lightbox').hide();
                         $('.lightbox-background').hide('fast');
			currentIdx = null;
                }
	}
});


$(function() {
	$("div.thumbs div.thumb a").click(function( event ) {
		event.preventDefault();
	});

	$("div.thumbs div.thumb").click(function( event ) {
		if ($(this).hasClass('selected')) {
			$(this).removeClass('selected')
		} else {
			$(this).addClass('selected')
		}
		countResults();
	});

	$('input[type=checkbox]').click(function() {
		if ($(this).prop('checked')) {
			$('input[type=submit]').prop('disabled',false);
		} else {
			$('input[type=submit]').prop('disabled',true);
		}
		countResults();
	});

});

function countResults() {
	$('#counter').val($("div.thumbs div.selected").length);

	var c = {};
	$("div.thumbs div.thumb").each(function(index) {
		c[$(this).attr('id')]=-1;
	});
	$("div.thumbs div.selected").each(function(index) {
		c[$(this).attr('id')]=1;
	});
	var r = [];
	$.each(c, function( index, value ) {
		r.push(index.replace(/t/,'') +'='+ value);
	});
	document.forms['theForm'].elements['results'].value = r.join('&');
}

</script>
<?

$smarty->display('_std_end.tpl');

?>
<div class="lightbox-background"></div>
<div id="lightbox">
Does this image <b>really</b> illustrate <big style=background-color:yellow><? echo htmlentities($_GET['label']); ?></big>?<br><br>
<a href="#" target="_blank"><img src=about:blank onload="$(this).css('opacity',1);"></a><br><a href=""><span></span></a>
<br><hr> Press <b>Y</b> to accept this image, or any other key to say no. Press <b>ESC</b> to quit, or <b>Backspace</b> to go back.
</div>

