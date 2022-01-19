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

pageMustBeHTTPS();


$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}
$src = 'loading="lazy" src'; //experimenting with moving to it permentanty!


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



require_once('geograph/conversions.class.php');
$conv = new Conversions;

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


        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>
        <script src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="<? echo smarty_modifier_revision("/js/jquery.storage.js"); ?>"></script>

<script>
 var OSAPIKey = <? echo json_encode(@$CONF['os_api_key']); ?>;
</script>

	<script src="<? echo smarty_modifier_revision("/js/Leaflet.base-layers.js"); ?>"></script>

<script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = <? echo json_encode($CONF['STATIC_HOST']); ?>;
  var points = [];
  var moveTimer = null;
var trackline = null;

	function loadmap() {

	        var mapOptions =  {
	              //  center: [54.4266, -3.1557], zoom: 13,
        	        minZoom: 5, maxZoom: 21
	        };
	        var bounds = L.latLngBounds();

		<?php
		      $bbox=explode(' ',$trk['bbox']);

			$ri = 1; //$ri=1 is GB
			//if there is a 'track' then bbox, was created via wgs2bng() so its actully ri=1! (even for ireland!)
			// ... but if created from the images, then COULD be 2!
			if (strlen($trk['track']) < 10 && $geograph[0]['reference_index'] == 2)
				$ri = 2;

			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($bbox[0],$bbox[1], $ri);
		    print "bounds.extend([$wgs84_lat,$wgs84_long]);\n";

			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($bbox[2],$bbox[3], $ri);
		    print "bounds.extend([$wgs84_lat,$wgs84_long]);\n";
		?>

	        map = L.map('map', mapOptions);
	        //var hash = new L.Hash(map);

        	map.fitBounds(bounds, {padding:[30,30], maxZoom: 14});
		map.setMaxBounds(bounds.pad(2.5));

		//////////////////////////////////////////////////////

		if ($.localStorage && $.localStorage('LeafletBaseMap')) {
			basemap = $.localStorage('LeafletBaseMap');
			<? if ($geograph[0]['reference_index'] == '2') { ?>
				if (basemap.indexOf('- GB') > -1)
					basemap = "OpenTopoMap";
			<? } else { ?>
				if (basemap.indexOf('- Ireland') > -1)
					basemap = "Historic OS - GB";
			<? } ?>

			if (!baseMaps[basemap] || !(
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].options.bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].options.bounds).contains(map.getCenter())     //(need to construct, as MIGHT be object liternal!
				)) {
				basemap = "OpenTopoMap";
			}
		<? if ($geograph[0]['reference_index'] == '1') { ?>
		} else if (baseMaps['Modern OS - GB']) {
			basemap = 'Modern OS - GB';
		<? } ?>
		} else {
			basemap = "OpenTopoMap";
		}

		map.addLayer(baseMaps[basemap]);

		if ($.localStorage) {
			map.on('baselayerchange', function(e) {
		  		$.localStorage('LeafletBaseMap', e.name);
			});
		}

		map.addLayer(overlayMaps["OS National Grid"]);

        	map.fitBounds(bounds, {padding:[30,30], maxZoom: 14});

		addOurControls(map)

		//////////////////////////////////////////////////////

<?php
  $bbox=explode(' ',$trk['bbox']);
  $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
  $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
  if ($bbox[2]-$bbox[0]>4000||$bbox[3]-$bbox[1]>3000) $scale=7;
  else $scale=8;

  $track=explode(' ',$trk['track']);
  $len=count($track);

	if (!empty($trk['track']) && $len>0) { ?>
		    // Define track
               trackline = L.polyline([
                                <?
				$points = array();
				for ($i=0;$i<$len-1;$i+=2) {
	                                list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($track[$i], $track[$i+1], 1); //$ri=1
               		                $points[] = "[$wgs84_lat,$wgs84_long]";
				}
				echo implode(", ",$points); ?>
                        ],{
                        color: "#000000",
                        weight: 4,
                        opacity: 0.7
                        }).addTo(map);
	<?php }


    $len=count($geograph);

    for ($i=0;$i<$len;$i++) {
      // shift marker to centre of square indicated by GR
      fake_precision($geograph[$i]);
      $image = new GridImage();
      $image->fastInit($geograph[$i]);

      list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($geograph[$i]['viewpoint_eastings'], $geograph[$i]['viewpoint_northings'], $geograph[$i]['reference_index']);

      if ($geograph[$i]['nateastings']!=$geograph[$i]['viewpoint_eastings']||$geograph[$i]['natnorthings']!=$geograph[$i]['viewpoint_northings']) {  // subject GR != camera GR

	list($wgs84_lat2,$wgs84_long2) = $conv->national_to_wgs84($geograph[$i]['nateastings'] , $geograph[$i]['natnorthings'], $geograph[$i]['reference_index']); 
      } else {
        $ea=$geograph[$i]['nateastings']+round(100.*sin($geograph[$i]['view_direction']*M_PI/180.));
        $no=$geograph[$i]['natnorthings']+round(100.*cos($geograph[$i]['view_direction']*M_PI/180.));

	list($wgs84_lat2,$wgs84_long2) = $conv->national_to_wgs84($ea , $no, $geograph[$i]['reference_index']);
      }

      // Define camera marker
?>
        createMarker(<? echo "[$wgs84_lat,$wgs84_long], 'walk', {$geograph[$i]['gridimage_id']}, [$wgs84_lat2,$wgs84_long2]"; ?>);
	points.push(<? echo "[$wgs84_lat,$wgs84_long,{$geograph[$i]['gridimage_id']}]"; ?>);
<?php

	$geograph[$i]['position'] = "$wgs84_lat,$wgs84_long,$wgs84_lat2,$wgs84_long2";
    }
?>

    map.on('idle', function(evt) {
      if (moveTimer) {
        clearTimeout(moveTimer);
      }
	if (!document.getElementById('enableScroll').checked)
		return;
      moveTimer = setTimeout(function() {
      var point = map.getCenter();
      var distance;
      var idx = -1;
      for(i=0;i<points.length;i++) {
        var d = Math.pow(point.lat - points[i][0],2) +
                Math.pow(point.lng - points[i][1],2); //no point bothering with sqrt, as just want shortest.
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


  } //loadmap

  AttachEvent(window,'load',loadmap,false);


         var icons = [];
         function createMarker(point,icon,gridimage_id,point2) {
                if (!icons[icon]) {
                        icons[icon] = L.icon({
                            iconUrl: static_host+"/geotrips/"+icon+".png",
                            iconSize:     [9, 9], // size of the icon
                            iconAnchor:   [5, 5], // point of the icon which will correspond to marker's location
                            popupAnchor:  [0, -5] // point from which the popup should open relative to the iconAnchor
                        });
                }
                var marker = L.marker(point, {icon: icons[icon], draggable: false}).addTo(map);

		if (gridimage_id)
		      marker.on('click', function(evt) {
		          scrollIntoView(gridimage_id);
		      });

		if (point2)
                        L.polyline([point, point2],{
                        color: "#0000ff",
                        weight: 2,
                        opacity: 1
                        }).addTo(map);

                return marker;
        }



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

<span class=nowrap>( <input type=checkbox id="enableScroll" checked> <i><label for="enableScroll">Auto-sync scrolling and map dragging</label></i> )</span>

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
      print("<p data-id=\"{$geograph[$i]['gridimage_id']}\" data-position=\"{$geograph[$i]['position']}\">");
      print("<a href=\"/photo/{$geograph[$i]['gridimage_id']}\" title=\"".htmlentities2($geograph[$i]['title'])."\" target=\"_blank\">");
      print($image->getThumbnail(213,160,false,true,$src)."</a><br>");
      print("<strong>".htmlentities2($geograph[$i]['title'])."</strong>");
	if (!empty($geograph[$i]['comment'])) {
		print("<br><small title=\"".htmlentities2($geograph[$i]['comment'])."\">".smarty_modifier_truncate(htmlentities2($geograph[$i]['comment']),90,"... <i><a href=\"/photo/{$geograph[$i]['gridimage_id']}\" target=_blank>more</a></i>")."</small>");
	}
      print("</p>");
	print "\n\n";
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
				if (document.getElementById('enableScroll').checked) {
					//calling getBounds directly here exceeds the call stack!, so launder it via setTimeout
					setTimeout("var pos = ["+bits.slice(0,2).join(',')+"];"+
						"if (!map.getBounds().pad(-0.25).contains(pos)) "+
						"	map.panTo(pos);", 50);
				}
				newHighlightMarker(bits);
			}
		}
	}
});

function newHighlightMarker(bits) {

	if (highlightMarker) {
		highlightMarker.removeFrom(map);
		if (highlightFeature)
			highlightFeature.removeFrom(map);
	}

	var icon = 'walk_focus_big_dark';
                if (!icons[icon]) {
                        icons[icon] = L.icon({
                            iconUrl: static_host+"/geotrips/"+icon+".png",
                            iconSize:     [35, 35], // size of the icon
                            iconAnchor:   [17, 17], // point of the icon which will correspond to marker's location
                        });
                }

       highlightMarker = createMarker([bits[0],bits[1]], icon);

       if (bits.length==2)
		return;

      // Define view direction

       highlightFeature = L.polyline([
				[bits[0],bits[1]],
				[bits[2],bits[3]]
                        ],{
                        color: "#800080",
                        weight: 9,
                        opacity: 0.3
                        }).addTo(map);
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


