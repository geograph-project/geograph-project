<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;



$map=new GeographMap;
//standard 1px national map
$map->setOrigin(0,-10);
$map->setImageSize(900,1300);
$map->setScale(1);

$map->type_or_user = -10;

$blankurl=$map->getImageUrl();

	$overlays = array();
	$db = GeographDatabaseConnection(true);

	if (!empty($_GET['tags'])) {
		foreach (explode(';',$_GET['tags']) as $idx => $tag) {
			if ($idx > 7)
				break;

			$original = $tag;
			$andwhere = '';
			if (strpos($tag,':') !== FALSE) {
				list($prefix,$tag) = explode(':',$tag,2);
				$andwhere = " AND prefix = ".$db->Quote($prefix);
			}
			if (!($tag_id= $db->getOne($sql = "SELECT tag_id FROM tag WHERE status = 1 AND tag=".$db->Quote($tag).$andwhere))) {
				die("unable to find tag [$sql]");
			}

			$map->transparent = true;
			$map->tagId = $tag_id;
			$map->setPalette(3);
			$map->type_or_user = -12;

			$target=$_SERVER['DOCUMENT_ROOT'].$map->getImageFilename();

			if (!empty($_GET['refresh']) && $USER->hasPerm("admin")) {
			        unlink($target);
			        $map->_renderMap();
			} elseif (!file_exists($target)) {
			        $map->_renderMap();
			}
			$overlays[] = array(
				'url'=>$map->getImageUrl(),
				'tag'=>$original,
			);
		}
	}


$smarty->display('_std_begin.tpl');

?>
<h2>Tag Coverage map</h2>

<noscript>Sorry, you need JavaScript enabled to use this page.</noscript>

<form method=get>
	<input type=hidden name="tags" id="tags" value="<? echo htmlentities($_GET['tags']); ?>" size="50" style="width:500px"/><input type=submit value="Go"/>
</form>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<link href="/js/select2-3.3.2/select2.css" rel="stylesheet"/>
		<script src="/js/select2-3.3.2/select2.min.js"></script>
<script>
$(function() {
	$('#tags').select2({
		multiple: true,
		separator: ';',
		placeholder: 'enter tags here',
		ajax: {
	quietMillis: 250,
        url: "/tags/tags.json.php",
	cache: true,
        dataType: 'jsonp',
        data: function (term, page) {
            return {
                term: term, // search term
		page: page
            };
        },
        results: function (data, page) { // parse the results into the format expected by Select2.
            var more = (data.length == 60 && (page*60) < 1000);
            var results = [];
            $.each(data, function(){
		results.push({id: this, text: this });
            });
            return {results: results, more: more};
        }
		},
		/* createSearchChoice: function (term) { return {id: term, text: term}; }, */
		initSelection: function (element, callback) {
                                var data = [];
                                $(element.val().split(/;/)).each(function () {
                                    data.push({id: this, text: this});
                                });
                                callback(data);
                }
	});
});
</script>
<?
	if (!empty($overlays)) {
		$colours = array('FF0000','FFDDFF','FFFFAA','FFAAFF','AAFFFF','DDDDDD','DDDDFF','DDFFDD','BBBBFF','BBFFBB','FFBBBB','FFFFDD','FFDDDD');
		foreach ($colours as $idx => $value) {
			$colours[$idx] = array(hexdec(substr($value,0,2)),hexdec(substr($value,2,2)),hexdec(substr($value,4,2)));
		}

		$colours = array(
			array(213,62,79),
			array(244,109,67),
			array(253,174,97),
			array(254,224,139),
			array(255,255,191),
			array(230,245,152),
			array(171,221,164),
			//array(102,194,165), //too close to our green
			array(50,136,186),
		);

                $colours = array(
                        array(215,48,39),
                        array(244,109,67),
                        array(253,174,97),
                        array(254,224,114),
                        array(255,255,191),
                        array(224,243,248),
                        array(171,217,233),
                        array(116,173,209),
                        array(69,117,180),
		);

		$cnt = count($colours);

		function hh($val) {
			return str_pad(dechex($val), 2, '0', STR_PAD_LEFT);
		}

		print "<ul>";
		foreach ($overlays as $idx => $overlay) {
			$arr = $colours[$idx%$cnt];
			print "<li style=\"background-color:#".hh($arr[0]).hh($arr[1]).hh($arr[2])."\">".htmlentities($overlay['tag'])."</li>";
		}
		print "</ul>";

	}

?>
<div style="position:relative; height:1300px;width:900px">
	<div style="position:absolute;top:0;left:0">
		<img src="<? echo $blankurl; ?>" width="900" height="1300"/>
	</div>
	<?

	if (!empty($overlays)) foreach ($overlays as $idx => $overlay) {
		if (true) {?>
                        <div style="position:absolute;top:0;left:0">
			<canvas id="myCanvas<? echo $idx; ?>" width="900" height="1300"></canvas>
                        </div>
                        <script>
			 AttachEvent(window,'load',function() {
                            drawImage('myCanvas<? echo $idx; ?>','<? echo str_replace('t0.','www.',$overlay['url']); ?>',<? echo implode(',',$colours[$idx%$cnt]); ?>,240);
			 },false);
			</script>
		<? } else { ?>
			<div style="position:absolute;top:0;left:0;opacity:0.9;filter:alpha(opacity=90);">
			<img src="<? echo $overlay['url']; ?>" title="<? echo htmlentities($overlay['tag']); ?>" width="900" height="1300"/>
			</div>
		<? }
	} ?>
</div>

<script>

var elem = document.createElement('canvas');
  if (!(elem.getContext && elem.getContext('2d'))) {
    alert("Sorry, your browser is not able to render these maps. Suggest heading over to www.browserchoice.eu and picking a better browser.");
  }

function drawImage(canvasid,src,newred,newgreen,newblue,newalpha){
  //based on http://stackoverflow.com/questions/6268856/image-png-color-css-or-html-or-javascript

  var imageObj = new Image();
  imageObj.onload = function(){

    var canvas = document.getElementById(canvasid);
    var context = canvas.getContext("2d");

    context.drawImage(imageObj, 0, 0);

    var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
    var data = imageData.data;

    for (var i = 0, n = data.length; i < n; i += 4) {
        if(data[i] == 255 && data[i+1] == 0 && data[i+2] ==0){ //if black, ie. red, green, and blue are all 0
            data[i] = newred; //red
            data[i+1] = newgreen; //green
            data[i+2] = newblue; //blue
	    data[i+3] = newalpha; //alpha
        }
    }

    // overwrite original image
    context.putImageData(imageData, 0, 0);
  }
  imageObj.src = src;
}

</script>
<?


$smarty->display('_std_end.tpl');


