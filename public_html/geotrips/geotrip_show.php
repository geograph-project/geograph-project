<?php
/**
 * $Project: GeoGraph $
 * $Id: geotrip_show.php 9094 2020-03-24 10:31:56Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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
 

if ((!preg_match('/\/geotrips\/\d+/',$_SERVER["REQUEST_URI"]) && isset($_GET['trip'])) || strlen($_GET['trip']) !== strlen(intval($_GET['trip']))) {
        //keep urls nice and clean - esp. for search engines!
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
        header("Location: /geotrips/".intval($_GET['trip']));
        print "<a href=\"/geotrips/".intval($_GET['trip'])."\">Continue to view this trip</a>";
        exit;
}


if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
	require_once('./geograph_snub.inc.php');
} else {
	require_once('geograph/global.inc.php');
}
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');

init_session();

//temp as page doesnt work on https (mainly maps!)
pageMustBeHTTP();


$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}


$smarty = new GeographPage;

include('./geotrip_func.php');
$db = GeographDatabaseConnection(true);



  // get track from database
  $trk=$db->getRow("select * from geotrips where id=".intval($_GET['trip']));
  $foll=$db->getRow("select id from geotrips where contfrom=".intval($_GET['trip']));
  
  if (empty($trk)) {
    header("HTTP/1.0 404 Not Found");
    $smarty->display('static_404.tpl');
    exit;
  }
  

  if (!empty($trk['title'])) $hdr2=$trk['title'];
  else $hdr2=$trk['location'].' from '.$trk['start'];
  
$smarty->assign('page_title', $hdr2.' :: Geo-Trips');
$smarty->assign('meta_description', "A ".whichtype($trk['type'])." near $trk[location], starting from $trk[start], with pictures and plotted on a map.");

$smarty->display('_std_begin.tpl','trip'.$trk['id']);
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';


?>

<script src="https://osopenspacepro.ordnancesurvey.co.uk/osmapapi/openspace.js?key=A493C3EB96133019E0405F0ACA6056E3" type="text/javascript"></script>

<?php
  $bbox=explode(' ',$trk['bbox']);
  $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
  $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
  if ($bbox[2]-$bbox[0]>4000||$bbox[3]-$bbox[1]>3000) $scale=7;
  else $scale=8;
  $track=explode(' ',$trk['track']);
  $len=count($track);
  // fetch Geograph data
	$engine = new SearchEngine($trk['search']);
	$engine->criteria->resultsperpage = 250; // FIXME really?
	$recordSet = $engine->ReturnRecordset(0, true);
	while (!$recordSet->EOF) {
		$image = $recordSet->fields;
		if (    $image['nateastings']
		    &&  $image['viewpoint_eastings']
		    &&  $image['user_id'] == $trk['uid']
		    &&  $image['viewpoint_grlen'] > 4
		    &&  $image['natgrlen'] > 4
		    && (   $image['view_direction'] != -1
		        || $image['viewpoint_eastings']  != $image['nateastings']
		        || $image['viewpoint_northings'] != $image['natnorthings'])
		    &&  $image['imagetaken'] === $trk['date']
		) {
			$geograph[] = $image;
		}
		$recordSet->MoveNext();
	}
?>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;                             // track
  var vdir,vdirFeature,vdirString;                                   // view directions
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
  var points = [];
  var moveTimer = null;
  function initmap() {
    osMap=new OpenSpace.Map('map',{products: ["OV0", "OV1", "OV2", "MSR", "MS", "250KR", "250K", "50KR", "50K", "25KR", "25K", "VMLR", "VML"], controls:[],centreInfoWindow:false});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
    <?php print("osMap.setCenter(new OpenSpace.MapPoint($cen[0],$cen[1]),$scale);\n"); ?>
    trkLayer=osMap.getVectorLayer();
    <? if (!empty($trk['track']) && $len>0) { ?>
    // Define track
    trk=new Array();
    <?php for ($i=0;$i<$len-1;$i+=2) print("trk.push(new OpenLayers.Geometry.Point({$track[$i]},{$track[$i+1]}));\n"); ?>
    trkString=new OpenLayers.Geometry.LineString(trk);
    trkFeature=new OpenLayers.Feature.Vector(trkString,null,style_trk);
    trkLayer.addFeatures([trkFeature]);
<?php 
    }
    $len=count($geograph);
    for ($i=0;$i<$len;$i++) {
      // shift marker to centre of square indicated by GR
      fake_precision($geograph[$i]);
      $image = new GridImage();
      $image->fastInit($geograph[$i]);
?>
      // Define camera marker
      pos=new OpenSpace.MapPoint(<?php print("{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']}");?>);
      size=new OpenLayers.Size(9,9);
      offset=new OpenLayers.Pixel(-4,-4);
      infoWindowAnchor=new OpenLayers.Pixel(4,4);
      icon=new OpenSpace.Icon('walk.png',size,offset,null,infoWindowAnchor);
      popUpSize=new OpenLayers.Size(300,320);
      var marker = osMap.createMarker(pos,icon,null,popUpSize);
      marker.events.register('click', marker, function(evt) {
          scrollIntoView(<?php echo $geograph[$i]['gridimage_id']; ?>);
      });
      points.push([<?php print("{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']},{$geograph[$i]['gridimage_id']}");?>]);


      // Define view direction
      vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']}");?>));
<?php
      if ($geograph[$i]['nateastings']!=$geograph[$i]['viewpoint_eastings']||$geograph[$i]['natnorthings']!=$geograph[$i]['viewpoint_northings']) {  // subject GR != camera GR
?>
        vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i]['nateastings']},{$geograph[$i]['natnorthings']}");?>));
<?php
      } else {
        $ea=$geograph[$i]['nateastings']+round(100.*sin($geograph[$i]['view_direction']*M_PI/180.));
        $no=$geograph[$i]['natnorthings']+round(100.*cos($geograph[$i]['view_direction']*M_PI/180.));
?>
        vdir.push(new OpenLayers.Geometry.Point(<?php print("$ea,$no");?>));
<?php
      }
?>
      vdirString=new OpenLayers.Geometry.LineString(vdir);
      vdirFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([vdirFeature]);
<?php
    }
?>

    osMap.events.register('move', osMap, function(evt) {
      if (moveTimer) {
        clearTimeout(moveTimer);
      }
	if (!document.getElementById('enableScroll').checked)
		return;
      moveTimer = setTimeout(function() {
      var point = osMap.getCenter();
      var east = point.getEasting();
      var north = point.getNorthing();
      var distance;
      var idx = -1;
      for(i=0;i<points.length;i++) {
        var d = Math.pow(east  - points[i][0],2) +
                Math.pow(north - points[i][1],2); //no point bothering with sqrt, as just want shortest.
        if (idx == -1 || d < distance) {
          distance = d;
          idx = i;
        }
      }
      if (idx > 0) {
        scrollIntoView(points[idx][2]);
      }
      },100);
    });


  }

  AttachEvent(window,'load',initmap,false);

</script>

<h2><a href="./">Geo-Trips</a> :: <? echo htmlentities2($hdr2); ?></h2>

<?php
  print('<h3>'.htmlentities2($trk['location']).'</h3>');
  $date=date('D, j M Y',strtotime($trk['date']));
  print('<h4 style=text-align:left>A '.whichtype($trk['type']).' from '.htmlentities2($trk['start']).", $date by <a href=\"/profile/$trk[uid]\">".htmlentities2($trk['user'])."</a></h4>");

  if (!empty($trk['contfrom'])||!empty($foll['id'])) {
    print('<table class="ruled mapwidth"></tr>');
    if ($trk['contfrom']) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"/geotrips/{$trk['contfrom']}\">preceding leg</a></td>");
    else print('<td></td>');
    print('<td style="margin:20px;text-align:center"><b>This trip is part of a series.</b></td>');
    if ($foll) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"/geotrips/{$foll['id']}\">next leg</a></td>");
    else print('<td></td>');
    print('</tr></table>');
  }
?>
  <p class="mapwidth">
<?php print(str_replace("\n",'</p><p class="mapwidth">',GeographLinks(htmlentities2($trk['descr'])))); ?>
  </p>
<? if ($trk['uid'] == $USER->user_id) { ?>
  <div class="mapwidth">
    [<a href="geotrip_edit.php?trip=<? echo $trk['id']; ?>">edit this trip</a>]
  </div>
<? } ?>
  <div class="mapwidth"> <p><small>
<?php if ($trk['track']) print('On the map below, the grey line is the GPS track from this trip. ');?>
Click the blue circles to see a photograph
taken from that spot and read further information about the location.  The blue lines indicate
the direction of view.  There is also a
<a href="/search.php?i=<?php print($trk['search']);?>&amp;displayclass=slide">slideshow</a> of this trip.
  </small>

( <i> <input type=checkbox id="enableScroll" checked> <label for="enableScroll">Auto-sync scrolling and map dragging</label></i> )

</p></div>

<div style="width:1020px">
  <div id="map" style="width:700px;height:800px;"></div>
  <div id="scroller">
	<div>
	<p>&darr; Scroll down here &darr;</p>
<?php

                                if (!function_exists('smarty_modifier_truncate')) {
                                        require_once("smarty/libs/plugins/modifier.truncate.php");
                                }


  $len=count($geograph);
  for ($i=0;$i<$len;$i++) {
      $image = new GridImage();
      $image->fastInit($geograph[$i]);
      print("<p data-id=\"{$geograph[$i]['gridimage_id']}\" data-position=\"{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']}");
      if ($geograph[$i]['nateastings']!=$geograph[$i]['viewpoint_eastings']||$geograph[$i]['natnorthings']!=$geograph[$i]['viewpoint_northings']) {  // subject GR != camera GR
		print ",{$geograph[$i]['nateastings']},{$geograph[$i]['natnorthings']}";
      } else {
        $ea=$geograph[$i]['nateastings']+round(100.*sin($geograph[$i]['view_direction']*M_PI/180.));
        $no=$geograph[$i]['natnorthings']+round(100.*cos($geograph[$i]['view_direction']*M_PI/180.));
		print ",$ea,$no";
      }
      print("\"><a href=\"/photo/{$geograph[$i]['gridimage_id']}\" title=\"".htmlentities2($geograph[$i]['title'])."\" target=\"_blank\">");
      print($image->getThumbnail(213,160,false,true,$src)."</a><br>");
      print("<strong>".htmlentities2($geograph[$i]['title'])."</strong>");
	if (!empty($geograph[$i]['comment'])) {
		print("<br><small title=\"".htmlentities2($geograph[$i]['comment'])."\">".smarty_modifier_truncate(htmlentities2($geograph[$i]['comment']),90,"... <i><a href=\"/photo/{$geograph[$i]['gridimage_id']}\" target=_blank>more</a></i>")."</small>");
	}
      print("</p>");
  }
?>
	</div>
  </div>
</div>

  <p class="mapwidth"><small>
All images &copy; <?php print("<a href=\"/profile/{$trk['uid']}\">".htmlentities2($trk['user'])."</a>");?> and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">
Creative Commons licence</a> <img alt="external link" title="" src="<?php echo $CONF['STATIC_HOST']; ?>/img/external.png" />. </small>
  </p>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script src="//cdn.jsdelivr.net/jquery.scrollto/2.1.2/jquery.scrollTo.min.js"></script>

<script>
var performScroll = true;
var highlightMarker = null;
var highlightFeature = null;
$('#scroller').scroll(function() {
        if (!performScroll) { //hacky way to avoid code initialted event
		performScroll = true; //do it next time!
		return;
	}

	var elements = $(this).find('p');
	var offset =  elements.first().offset().top;
	var position = $(this).scrollTop();

	var element = 1;
	if (position > 20) //bodge, to make sure can always select the first image!
	elements.each(function(index) {
	     //for some unknown reason, .position() doesnt seem to work, so use .offset() instead,
	     //but need remove the offset of the first element to get the actual position WITHIN the scrolling div

	     if (($(this).offset().top - offset - position) < 250)
                 element = index;
	});

	if (element > -1) {
		element = $(elements.get(element));
		if (!element.hasClass('selected')) {
			elements.removeClass('selected');
			element.addClass('selected');

			var bits = element.data('position').split(/,/);
			if (bits.length > 1) {
				var pos = new OpenSpace.MapPoint(bits[0],bits[1]);
				var zoom = (moveTimer)?null:10;
				if (document.getElementById('enableScroll').checked)
					osMap.setCenter(pos,zoom,false);
				newHighlightMarker(bits);
			}
		}
	}
});

function newHighlightMarker(bits) {
	if (highlightMarker) {
		osMap.removeMarker(highlightMarker);
	        trkLayer.removeFeatures([highlightFeature]);
	}

      var pos = new OpenSpace.MapPoint(bits[0],bits[1]);
      var size=new OpenLayers.Size(35,35);
      var offset=new OpenLayers.Pixel(-17,-17);
      var infoWindowAnchor=new OpenLayers.Pixel(17,17);
      var icon=new OpenSpace.Icon('walk_focus_big_dark.png',size,offset,null,infoWindowAnchor);
      highlightMarker = osMap.createMarker(pos,icon);

     if (bits.length==2)
	return;

      // Define view direction
      var vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point(bits[0],bits[1]));
      vdir.push(new OpenLayers.Geometry.Point(bits[2],bits[3]));
      var vdirString=new OpenLayers.Geometry.LineString(vdir);
      var style_vdir={strokeColor:"#880088",strokeOpacity:0.3,strokeWidth:9.};
      highlightFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([highlightFeature]);

}

function scrollIntoView(gridimage_id) {

	var elements = $('#scroller').find('p');

	elements.each(function(index) {
             if ($(this).data('id') == gridimage_id) {
                        elements.removeClass('selected');
                        $(this).addClass('selected');
                    performScroll = false;
                    //$('#scroller').scrollTop($(this).position().top-200);
                    $('#scroller').scrollTo($(this),0,{offset:-200});

			var bits = $(this).data('position').split(/,/);
			if (bits.length > 1) {
				pos = new OpenSpace.MapPoint(bits[0],bits[1]);
				newHighlightMarker(bits);
			}
             }
	});
}
</script>

<style>
.mapwidth {
	width:800px;
	clear:both;
}
#map {
	width:700px;
	height:800px;
	float:left;
}
#scroller {
	position:relative;
	float:left;
	height:800px;
	width:300px;
	overflow-y:scroll;
	overflow-x:hidden;
}

#scroller div {
	padding-bottom:500px;
	text-align:center;
}

#scroller p {
	border:1px solid white;
}
#scroller p.selected {
	background-color:#eee;
	border:1px solid DarkOrchid;
}
</style>


<?php


        if ($src == 'data-src')
		print "<script src=\"".smarty_modifier_revision("/js/lazynew.js")."\"></script>";


$smarty->display('_std_end.tpl');


if (!isset($_GET['dontcount']) && appearsToBePerson()) {

	if (empty($db) || $db->readonly)
		$db = GeographDatabaseConnection(false);

	//yes, updated is currently a integer column (and so not 'on update set current_timestamp') - but set it just in case it gets changed. Noop currently.
	$db->Execute("UPDATE LOW_PRIORITY geotrips SET views=views+1, last_view=NOW(), updated=updated WHERE id = ".$_GET['trip']);
}


