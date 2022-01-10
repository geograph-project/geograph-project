<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 9056 2020-03-06 14:50:21Z barry $
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
 

if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
	require_once('./geograph_snub.inc.php');
} else {
	require_once('geograph/global.inc.php');
}
init_session();

$smarty = new GeographPage;

//temp as page doesnt work on https (mainly maps!)
pageMustBeHTTP();

include('./geotrip_func.php');
$db = GeographDatabaseConnection(false);


  // get tracks from database
  if (isset($_GET['debug'])) $where = "location='debug'";
  else $where = "location!='debug'";
  if (!empty($_GET['u']))
    $where .= " and uid = ".intval($_GET['u']);
  if (!empty($_GET['type']) && ctype_alpha($_GET['type']))
    $where .= " and type = ".$db->Quote($_GET['type']);
  if (!empty($_GET['track']))
    $where .= " and track != ''";


$updated = $db->GetOne("SELECT MAX(updated) FROM geotrips WHERE $where")+1;

customCacheControl($updated,$updated);


$smarty->assign('page_title', 'Overview map :: Geo-Trips');
$smarty->assign('meta_description', 'A collection of square-bagging trips by members of the Geograph project, with photographs, descriptions and GPS tracks plotted on an Ordnance Survey map.');

$smarty->display('_std_begin.tpl','trip_home');
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';


$mkey = $USER->registered.".".$updated.md5($where);


if (empty($_GET['refresh']))
	$str = $memcache->name_get('geotrip_home',$mkey);

if (!empty($str)) {
  print $str;

} else {
  ob_start();

  $trks=$db->getAssoc("select * from geotrips where $where order by id desc");


require_once('geograph/conversions.class.php');
$conv = new Conversions;


?>

  <!--RSS feed via Geograph-->
  <link rel="alternate" type="application/rss+xml" title="Geo-Trips RSS" href="/content/syndicator.php?scope[]=trip" />


        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>
        <script src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

<script>
 var OSAPIKey = <? echo json_encode(@$CONF['os_api_key']); ?>;
</script>

	<script src="<? echo smarty_modifier_revision("/js/Leaflet.base-layers.js"); ?>"></script>

<script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = <? echo json_encode($CONF['STATIC_HOST']); ?>;

	function loadmap() {

	        var mapOptions =  {
	              //  center: [54.4266, -3.1557], zoom: 13,
        	        minZoom: 5, maxZoom: 21
	        };
	        var bounds = L.latLngBounds();

		<?php
		    $min = $max = array(0,0);
		    foreach ($trks as $track) {
		      $bbox=explode(' ',$track['bbox']);
		      $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
		      $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
			if (!$min[0] || $cen[0] < $min[0]) $min[0] = $cen[0];
			if (!$min[1] || $cen[1] < $min[1]) $min[1] = $cen[1];
			if ($cen[0] > $max[0]) $max[0] = $cen[0];
			if ($cen[1] > $max[1]) $max[1] = $cen[1];
		    }
			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($min[0],$min[1], 1); //$ri=1 is GB
		    print "bounds.extend([$wgs84_lat,$wgs84_long]);\n";

			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($max[0],$max[1], 1); //$ri=1 is GB
		    print "bounds.extend([$wgs84_lat,$wgs84_long]);\n";
		?>

	        map = L.map('map', mapOptions);
	        var hash = new L.Hash(map);

		//////////////////////////////////////////////////////

		if ($.localStorage && $.localStorage('LeafletBaseMap')) {
			basemap = $.localStorage('LeafletBaseMap');
			if (baseMaps[basemap] && basemap != "Ordnance Survey GB" && (
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
				))
				map.addLayer(baseMaps[basemap]);
			else
				map.addLayer(baseMaps["OpenStreetMap"]);
		} else {
			map.addLayer(baseMaps["OpenStreetMap"]);
		}
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

    foreach ($trks as $track_id => $track) {
      $bbox=explode(' ',$track['bbox']);
      $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
      $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
      $date=date('D, j M Y',strtotime($track['date']));
      // fetch Geograph thumbnail
      $image = new GridImage($track['img'],true);
      if ($image->isValid() && $image->moderation_status!='rejected') {
        $thumb=$image->getThumbnail(213,160,true);
      } else {
        $thumb=$CONF['STATIC_HOST'].'/photos/error120.jpg';
      }
      if ($track['title']) $title=$track['title'];
      else $title=$track['location'].' from '.$track['start'];
      $loc=$track['location'];

?>
      var content='<p>';
      content+='<a href=\"<?php print("/geotrips/{$track_id}");?>\">';
      content+='<img alt=\"<?php print(str_replace("'","\'",$loc));?>\" src=\"';
      content+='<?php print($thumb);?>\" />';
      content+='</a>';
      content+='</p><p>';
      content+='<strong><?php print(addslashes(htmlentities2($title)));?></strong><br />';
      content+='<?php print("by <a href=\"/profile/{$track['uid']}\">".addslashes(htmlentities2($track['user']))."</a> - $date<br />");?>';
      content+='<small>Click image to see details of this trip.</small>';
      content+='</p>';

<?php

	list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($cen[0],$cen[1], 1); //$ri=1
	print "createMarker([$wgs84_lat,$wgs84_long],'{$track['type']}', content);\n";

      // Link multi-day trips
      if ($track['contfrom'] && ($prevbbox=$trks[$track['contfrom']])) {
        $prevbbox=explode(' ',$prevbbox['bbox']);
        $pcen[0]=(int)(($prevbbox[0]+$prevbbox[2])/2);
        $pcen[1]=(int)(($prevbbox[1]+$prevbbox[3])/2);
?>
			L.polyline([
                                <? echo "[$wgs84_lat,$wgs84_long],\n";
				list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($pcen[0],$pcen[1], 1); //$ri=1
				echo "[$wgs84_lat,$wgs84_long]\n"; ?>
                        ],{
                        color: "#000000",
                        weight: 4,
                        opacity: 0.7
                        }).addTo(map);
<?php
      } //if contfrom

    } //foreach
?>
  }  //loadmap

	 var icons = [];
	 function createMarker(point,icon,html) {
                if (!icons[icon]) {
	                icons[icon] = L.icon({
        	            iconUrl: static_host+"/geotrips/"+icon+".png",
	                    iconSize:     [9, 9], // size of the icon
        	            iconAnchor:   [5, 5], // point of the icon which will correspond to marker's location
                	    popupAnchor:  [0, -5] // point from which the popup should open relative to the iconAnchor
	                });
		}
                var marker = L.marker(point, {icon: icons[icon], draggable: false}).addTo(map);
		if (html)
			marker.bindPopup(html);
      		return marker;
	}

   AttachEvent(window,'load',loadmap,false);

</script>

<h2>Geo-Trips overview map</h2>

<div class="maxi" style="max-width:800px">
  <p>
The map below shows Geo-Trips submitted by Geograph members.
Each point on the map represents a day trip by one Geograph-er to cover a number of
grid squares of the British National Grid as shown on Ordnance Survey maps.  The Geograph project aims
to collect photographs and information for each grid square.
  </p>
  <p>
Pan around the map using the left mouse button, or use the arrows in the top left corner of the map.
The +/- buttons on the map allow you to zoom in or out.  Double click zooms in on the spot.
Each Geo-Trip is marked on the map by a round
symbol.  Clicking the symbol gives details in a pop-up, and clicking the thumbnail in the pop-up
takes you to the map page for the trip, with all the pictures and information shown on the map.
  </p>
  <?php if ($USER->registered) { ?>
  <p class="inner hlt">
If you are a <em>Geograph</em>-er and would like to put your own square-bagging
expeditions on the map - on foot, by bike, in a car or by any other mode of transport -
please use the <a href="geotrip_submit.php">Geo-Trip submission form</a>.
If you upload a GPS track log in GPX format, the track will also be shown.
You can also <a href="geotrip_edit.php">edit your existing Geo-Trips</a>.
  </p>
  <?php } ?>
  <p>
Please note that Geo-Trips currently only work in England, Scotland, Wales and the Isle of Man. (Support for Ireland coming soon)
  </p>

<form method=get action="/content/" class="panel" style="padding:10px;margin:10px">
<b>Search Geotrips</b> - Keywords:<input type=text name=q value=""/> <input type=submit value="Search"/>
<input type=hidden name="scope[]" value="trip"/>
</form>

  <table class="ruled"><tr>
    <td><b>Legend:</b></td>
    <td><a href="?type=walk"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/walk.png" alt="" title="Fig.: Walk symbol"></a> Walk</td><td></td>
    <td><a href="?type=bike"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/bike.png" alt="" title="Fig.: Bike symbol"></a> Cycle ride</td><td></td>
    <td><a href="?type=boat"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/boat.png" alt="" title="Fig.: Boat symbol"></a> Boat trip</td><td></td>
    <td><a href="?type=rail"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/rail.png" alt="" title="Fig.: Rail symbol"></a> Train ride</td><td></td>
    <td><a href="?type=road"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/road.png" alt="" title="Fig.: Road symbol"></a> Drive</td><td></td>
    <td><a href="?type=bus"><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/bus.png"  alt="" title="Fig.: Bus symbol"></a>  Scheduled public transport</td>
  </td></tr></table>
  <div id="map" class="inner" style="width:798px;height:1300px"></div>
  <table class="ruled"><tr>
    <td><b>Legend:</b></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/walk.png" alt="" title="Fig.: Walk symbol"> Walk</td><td></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/bike.png" alt="" title="Fig.: Bike symbol"> Cycle ride</td><td></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/boat.png" alt="" title="Fig.: Boat symbol"> Boat trip</td><td></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/rail.png" alt="" title="Fig.: Rail symbol"> Train ride</td><td></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/road.png" alt="" title="Fig.: Road symbol"> Drive</td><td></td>
    <td><img src="<? echo $CONF['STATIC_HOST']; ?>/geotrips/bus.png"  alt="" title="Fig.: Bus symbol">  Scheduled public transport</td>
  </td></tr></table>
  <p>
In the spirit if not the scope of Geo-Trips, here's <b>Thomas Nugent</b>'s
<a href="/article/Luton-to-Glasgow-in-50-minutes">flight from Luton to Glasgow</a>,
plotted on a Google Map, with tips for other flying Geograph-ers.
  </p>
</div>

<div class="panel maxi">
  <h3>Recently uploaded Geo-Trips</h3>
  <p>
There is a <a href="/content/?scope[]=trip">full list of Geo-Trips</a> (updated once daily
in the early morning) in the Collections area of Geograph, which can be filtered by keyword or author.  You can also
subscribe to an
<a href="/content/syndicator.php?scope[]=trip">RSS feed</a> with new Geo-Trips as they
come in.  The list below includes all trips uploaded in the last 24 hours.
  </p>
<?php
  $i=1;
  if (!empty($_GET['max'])) $max=$_GET['max'];
  else $max=3;

  foreach ($trks as $track_id => $track) {
    if ($track['title']) $title=htmlentities2($track['title']);
    else $title=htmlentities2($track['location'].' from '.$track['start']);
    $descr=str_replace("\n",'</p><p>',htmlentities2($track['descr']));
    if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
    $gr=bbox2gr($track['bbox']);
    // fetch Geograph thumbnail
    $image = new GridImage($track['img'],true);
      if ($image->isValid() && $image->moderation_status!='rejected') {
        $thumb=$image->getThumbnail(213,160,true);
      } else {
        $thumb=$CONF['STATIC_HOST'].'/photos/error120.jpg';
      }
    $mmmyy=explode('-',$track['date']);
    $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"/profile/{$track['uid']}\">".htmlentities2($track['user'])."</a> and available under a <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"{$CONF['STATIC_HOST']}/img/external.png\" /></span>";
    print('<div class="inner">');
    print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"$thumb\" alt=\"\" title=\"$title\" /><br />$cred</div>");
    print("<b>$title</b><br />");
    print("<em>".htmlentities2($track['location'])."</em> -- A ".whichtype($track['type'])." from ".htmlentities2($track['start'])."<br />");
    print("by <a href=\"/profile/{$track['uid']}\">".htmlentities2($track['user'])."</a>");
    print("<div class=\"inner flt_r\">$gr</div>");
    print("<p>$descr&nbsp;[<a href=\"/geotrips/{$track_id}\">more</a>]</p>");
    print('<div class="row"></div>');
    print('</div>');

    // show all uploaded in last 24 hours, but at least three
    if ($track['updated']<date('U')-86400 && $i >= $max)
	break;

    $i++;
  }
?>
</div>

<?php
	$str = ob_get_flush();

	$memcache->name_set('geotrip_home',$mkey,$str,$memcache->compress,$memcache->period_long);

}

$smarty->display('_std_end.tpl');


