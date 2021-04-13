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

print "<h2>Curated Education images, collecting images - part 1</h2>";

if (!empty($_GET['x'])) {
        $updates = array();
	$updates['user_id'] = intval($USER->user_id);

	$updates['group'] = $_GET['group'];
	$updates['label'] = $_GET['label'];

	$updates['gridimage_id'] = intval($_GET['x']);

	$db->Execute($sql = 'UPDATE curated1 SET active=0 WHERE `'.implode('` = ? AND `',array_keys($updates)).'` = ?',array_values($updates)) or die("$sql;<hr>".$db->ErrorMsg());

} elseif (!empty($_POST['ids'])) {

        $updates = array();
	$updates['user_id'] = intval($USER->user_id);

	$updates['group'] = $_POST['group'];
	$updates['label'] = $_POST['label'];

	$updates['active'] = empty($_POST['active'])?1:2;

	$str = preg_replace('/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/','$1',$_POST['ids']); //replace any thumbnail urls with just the id.
        $str = trim(preg_replace('/[^\d]+/',' ',$str));
	$done = 0;
        foreach (explode(' ',$str) as $id) {
		$updates['gridimage_id'] = intval($id);

		$db->Execute($sql = 'INSERT IGNORE INTO curated1 SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

		$done+=$db->Affected_Rows();
	}

	print "<p>$done image(s) added. Thank you.</p>";

	if ($done>0) {

		$param = array('table' => 'curated1', 'debug'=>0);
		include "../../scripts/process_regions.php";


		//copied from process_decade. So trivial, might as well just copy!
		$sql = "UPDATE {$param['table']} INNER JOIN gridimage_search USING (gridimage_id)
        	SET decade = CONCAT(SUBSTRING(imagetaken,1,3),'0s')
	        WHERE decade ='' AND imagetaken NOT LIKE '0000%'";
		$db->Execute($sql);
	}

}

$skip = 0;
if (!empty($_GET['skip']))
	$skip = intval($_GET['skip']);

$where= array();

if (isset($_GET['group']))
        $where[] = "t2.`group` = ".$db->Quote($_GET['group']);
if (isset($_GET['label']))
        $where[] = "t2.`label` = ".$db->Quote($_GET['label']);
if (isset($_GET['region']))
        $where[] = "t1.`region` = ".$db->Quote(($_GET['region']=='unspecified')?'':$_GET['region']);

$where = empty($where)?'1':implode(' AND ',$where);

//$row = $db->getRow("SELECT `group`,`label`,count(*) AS `cnt` FROM curated1 GROUP BY `label` ORDER BY `cnt` ASC LIMIT $skip,1");

//a regfined version, that also picks a region (note the full outer join between the region and label list to create this!)
$row = $db->getRow("
	select t1.region,t2.label,t2.`group`,count(c.gridimage_id) c
	from (select distinct region from curated1 where region !='') t1
	inner join (select distinct label,`group` from curated1 where label NOT IN ('season','weather') and active=1 and `group` != 'Landforms') t2
	left join curated1 c on (t1.region = c.region and t2.label = c.label)
	WHERE $where
	group by t1.region,t2.label order by c
	LIMIT $skip,1");





if (empty($row) && !empty($_GET['label']) && $db->getOne('SELECT label FROM curated_headword WHERE label = '.$db->Quote($_GET['label']))) {
	$row = array(
		'group' => 'Geography and Geology',
		'label' => $_GET['label'],
		'region' => $db->getOne("SELECT region FROM curated1 WHERE label NOT IN ('season','weather') AND region != '' and active=1 ORDER BY rand()")
	);
}
if (empty($row)) {
	die("Nothing to do right now. Please check back later");
}



$filter = '';
if (!empty($row['region'])) {
	$counties = explode(",","Republic of Ireland,Northern Ireland,Isle of Man,Scotland,England,Unknown,Wales");
	foreach ($counties as $country) {
//print "strpos($country,{$row['region']})";
		if (strpos($row['region'],$country) !== FALSE) {
			$filter = "/country+%22".urlencode($country)."%22";
			break;
		}
	}
}

#######################################################################

$labels = $db->getCol("select label from curated1 where active=1 AND `group` = '{$row['group']}' group by label order by max(created) desc limit 7");
if (!empty($labels)) {
	print "<div class=\"tabHolder\">Current: ";
	$link = "?group=".urlencode($row['group']);
	foreach ($labels as $value) {
	        if (!empty($row['label']) && strcasecmp($row['label'],$value) ==0) {
	                print "<a class=\"tabSelected nowrap\">".htmlentities($value)."</a> ";
	        } else {
	                print "<a class=\"tab nowrap\" href=\"$link&label=".urlencode($value)."\">".htmlentities($value)."</a> ";
	        }
	}
	print " <a href=\"topics.php$link\">more...</a>";
	print "</div>";

}

print "<div class=interestBox>Collection: <span>{$row['group']}</span> :: <big style=background-color:yellow>".ucfirst($row['label'])."</big></div>";

$url = "https://t0.geograph.org.uk/tile-coveragethumb.png.php?label=".urlencode($row['label'])."&fudge=3&scale=2";
print "<div style=float:right;x-index:10000><img src=\"$url\"></div>";


#######################################################################

print '<ul class=explore style="max-width:800px">';

if (strpos($row['group'],'ks') === 0) {
	$others = $db->getCol($sql = "SELECT DISTINCT `label` FROM curated1 WHERE `group` = '{$row['group']}' AND label != '{$row['label']}'");
	$last = array_pop($others);
	print "<li>ideally that <u>wouldn't</u> be mistaken as any of, <i>".implode(', ',$others)."</i> and <i>$last</i>.";

	if ($row['group'] == 'ks1 > key physical features')
		print "<br>E.g. if looking for a picture of Beach, it shouldn't have a prominent Hill, as could mistake is as being an image of the Hill!";

} elseif ($desc = $db->getOne("SELECT description FROM curated_headword WHERE label = '{$row['label']}'")) {
	print "<li style=background-color:lightgreen>".nl2br(htmlentities(strip_tags($desc),false))."</li>";
}

if (!empty($row['region']))
	print "<li>Suggested region to focus on looking for images: <b>{$row['region']}</b> (but welcome to submit images of any region)";

if (strpos($row['group'],'ks1') !== FALSE)
	print "<li>Aiming that a child of 5-7 could reconsise the subject from the photo.</li>";

#######################################################################

?>

</ul>
<?


#######################################################################

?>

<div id="preview" style="float:right;max-width:40vw" class=shadow></div>

<form method=post style="background-color:#eee;max-width:700px;padding:20px" name=theForm>
<input type=hidden name=opened value="<? echo $db->getOne("SELECT NOW()"); ?>">
<input type=hidden name=group value="<? echo htmlentities($row['group']); ?>">
<input type=hidden name=label value="<? echo htmlentities($row['label']); ?>">

<b>Add <b><? echo htmlentities($row['label']); ?></b> Image(s) by ID</b>: (enter unique id(s), or links to images/thumbnails, seperated by spaces, commas or semicolons, or even in [])<br>

<div id="markedLink"></div>
<textarea name=ids id=theids rows=10 cols=80 onkeyup="parseIds(this)" onpaste="parseIds(this)"></textarea><br>
<i>(tip: You should be able to drag thumbnails direct from Browser or search results, into the box above, and will just be a link. Dont need to add spaces/lines betweem the links!)</i>


<br>
<input type=submit id=subutton value="Add..." disabled>

(<a target=browser href="/browser/#!/q=<? echo urlencode($row['label']).$filter; ?>/larger+%221024%22">open [<tt><? echo htmlentities($row['label']); ?></tt>] keyword search in browser</a> to get started)
</form>

<?

#######################################################################



//show images!

$bys = array(
	'region'=>'Region',
	'hectad'=>'Hectad',
	'myriad'=>'Myriad',
	'super'=>'Super-Myriad',
	'curator'=>'Curator',
	'decade'=>'Decade',
);
$tables = '';

if (empty($_GET['by']))
	$_GET['by'] = 'region';

if ($_GET['by'] == 'region') {
	$section = "`region`"; //MySQL expression!

} elseif ($_GET['by'] == 'curator') {
	$section = "user.realname";
	$section = "if(length(value)>4,user.realname,'Anonymous')";
	$tables = " left join user_preference p on (p.user_id = c.user_id and pkey = 'curated.credit')";

} elseif ($_GET['by'] == 'decade') {
	$section = $_GET['by'];

} elseif ($_GET['by'] == 'myriad') {
	$section = "SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4)";

} elseif ($_GET['by'] == 'super') {
	$section = "SUBSTRING(LPAD(gi.grid_reference,6,'I'),1,1)";

} elseif ($_GET['by'] == 'hectad') {
        $section = "CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1))";

} else {
	$section = "`region`"; //MySQL expression!
	$_GET['by'] = 'region';
}
$title = $bys[$_GET['by']];


 $imagelist = new ImageList();

	$imagelist->cols = str_replace(',',',gi.',$imagelist->cols);

	$sql = "SELECT {$imagelist->cols}, $section as section, c.user_id as cuid
		FROM curated1 c
		INNER JOIN gridimage_search gi USING (gridimage_id)
			INNER JOIN user ON (user.user_id = c.user_id)
			$tables
		WHERE label = ".$db->Quote($row['label'])." AND `group` = ".$db->Quote($row['group'])."
		AND active = 1
		ORDER BY $section, c.score desc, (moderation_status = 'geograph') DESC, sequence
		LIMIT 500";


 $imagelist->_getImagesBySql($sql);

if ($cnt = count($imagelist->images)) {
	//todo. (group selctor
	print "<form method=get>";
		print "<input type=hidden name=group value=\"".htmlentities($row['group'])."\">";
		print "<input type=hidden name=label value=\"".htmlentities($row['label'])."\">";
	print "<h3>{$cnt} current images for ".htmlentities($row['label']).", by <select name=by onchange=this.form.submit()>";
	foreach ($bys as $key => $value)
		printf('<option value="%s"%s>%s</option>', $key, ($_GET['by'] == $key)?' selected':'', $value);
	print "</select></h3>";
	print "</form>";

	$last = -1; $cnt=0;

	$stat = array();
	foreach ($imagelist->images as $image)
		@$stat[$image->section]++;

	foreach ($imagelist->images as $image) {
		if ($image->section != $last) {
			$last = $image->section;
			if (empty($image->section))
				$image->section = 'unknown';
			print "<div style=\"clear:both;margin-top:10px;\" class=interestBox>$title: <b>".htmlentities($image->section)."</b>";
			if ($stat[$last] > 6) {
				if ($_GET['by'] == 'decade' || $_GET['by'] == 'region') {
					$link = "?group=".urlencode($row['group'])
					."&label=".urlencode($row['label'])
					."&{$_GET['by']}=".urlencode($last);
					print " (<a href=sample.php$link target=_blank>{$stat[$last]} images</a>)";
				} else {
					print " ({$stat[$last]} images)";
				}
			}
			print "</div>";
			$cnt=0;
		}

		if ($cnt > 5)
			continue;

		?>
		 <div class="thumb shadow" id="t<? echo $image->gridimage_id; ?>">
                          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?>" target=_blank href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a>
		<?
		if ($image->cuid == $USER->user_id) {
			$link = "?group=".urlencode($row['group'])
        	               ."&label=".urlencode($row['label']);
			print "<a href=$link&x={$image->gridimage_id} onclick=\"return confirm('Are you sure remove this image?');\" style=color:red>remove</a>";
		}
		print "</div>";

		$cnt++;
	}
	print "<hr style=clear:both>";
	if (count($imagelist->images) > 2) {
		$link = "?group=".urlencode($row['group'])
                       ."&label=".urlencode($row['label']);

		print "<br><a href=\"map.php$link\" target=_blank>View images on map</a> (note currently implemeneted via marked list, so will add these images to your marked list!)</p>";
		print "<hr style=clear:both>";
	}

	if ($_GET['by'] == 'region') {
		$regions = $db->getCol("select distinct region from curated1 where `group` = 'Geography and Geology' and region != ''");
		$missing = array();
		foreach ($regions as $region)
			if (empty($stat[$region]))
				$missing[] = $region;
		if (!empty($missing))
			print "Region(s) without any images currently: <b>".implode(', ',$missing)."</b><hr>";
	}
}

#######################################################################

?>
<style>
div.thumb {
	float:left;width:130px;height:140px;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">

//////////////////////////////////

var lastcnt = 0;
setInterval(function() {
	current = readCookie('markedImages');
	if (lastcnt != current.length) {
		if (current && current != '') {
			splited = current.commatrim().split(',');
			$('#markedLink').html('Marked Images['+(splited.length+0)+']: <a title="Insert marked image list" href="#" onclick="useMarked()">Use Current Marked List</a>  <a href="javascript:void(clearMarkedImages())" style="color:red">Clear</a>');
		} else {
			$('#markedLink').empty();
		}
	}
	lastcnt = current.length;
}, 1000);

function useMarked() {
	var ele = document.getElementById('theids');
	current = readCookie('markedImages');
	ele.value = ele.value + ', '+ current.commatrim();
	parseIds(ele);
}

//////////////////////////////////

// special support for catching drags.
// https://stackoverflow.com/questions/7237436/how-to-listen-for-drag-and-drop-plain-text-in-textarea-with-jquery
//Looks like you must cancel the dragover (and dragenter) event to catch the drop event in Chrome.

$(function() {
	$("textarea")
	    .bind("dragover", false)
	    .bind("dragenter", false)
	    .bind("drop", function(e) {
		var str = e.originalEvent.dataTransfer.getData("text") || e.originalEvent.dataTransfer.getData("text/plain");

		str = str.replace(/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/g,'$1'); //replace any thumbnail urls with just the id.
		str = str.replace(/[\w:\/\.]+\/photo\/(\d{1,7})$/,'$1');

	        this.value = this.value + ", "+ str;

		parseIds(this);
	    return false;
	});
});

//////////////////////////////////

var last;
var debounceDelay = null;
function parseIds(that) {
	var str= that.value;

		str = str.replace(/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/g,'$1'); //replace any thumbnail urls with just the id.
	        str = str.replace(/[^\d]+/g,' ').replace(/(^ +| +$)/g,'');

	if (last && last == str)
		return;
	last = str;

	if (debounceDelay)
		clearTimeout(debounceDelay);
	debounceDelay = setTimeout(function() {
		debounceDelay = null;
		var ids = str.split(/ /);
		for(var i=0;i<ids.length;i++) {
			if (ids[i]) {
				var id = ids[i];
				if ($('#a'+id).length == 0) {
					$('#preview').append('<div class=thumb><a href="/photo/'+id+'" id=a'+id+' target=_blank><img src=about:blank></a></div>');
					pupulateImage(id); //call a function, so it can use function closure!
				}
			}
		}
		$('#subutton').val("Add "+ids.length+" image"+(ids.length==1?'':'s')).prop('disabled',false);
	}, 400);
}

var images = new Object();
function pupulateImage(id) {
	if (images[id]) {
		value = images[id];

		$('#a'+value.id+' img').prop('src',value.thumbnail);
		 $('#a'+value.id).prop('title',value.title+' by '+value.realname);
		return;
	}

        var data = {
                select: 'id,title,grid_reference,hash,realname,user_id',
                where: 'id='+id,
                limit: 1
        };

        $.ajax('https://api.geograph.org.uk/api-facetql.php',{
                data: data,
                cache: true,
                dataType: 'json'
        }).done(function(data){
                if (data && data.rows && data.rows.length) {
                        $.each(data.rows, function(index,value) {

                                value.thumbnail = getGeographUrl(value.id, value.hash, 'small');
				images[value.id] = value;

				$('#a'+value.id+' img').prop('src',value.thumbnail);
				 $('#a'+value.id).prop('title',value.title+' by '+value.realname);
			});
		}
	});
}


//////////////////////////////////

function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

//////////////////////////////////


function unloadMess() {
        var ele = document.forms['theForm'].elements['ids'];
        if (ele.value == ele.defaultValue) {
                return;
        }
        return "**************************\n\nYou have unsaved changes in the content box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;

function cancelMess() {
        window.onbeforeunload=null;
}
function setupSubmitForm() {
        AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


</script>



<?

$link = "?skip=".($skip+1);

if (!empty($_GET['group']))
	$link .= "&group=".urlencode($row['group']);

print " ... or <a href=$link>I can't add any images for this. Give me another subject please.</a>";


$smarty->display('_std_end.tpl');



