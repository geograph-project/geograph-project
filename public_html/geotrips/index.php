<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7816 2013-03-31 00:17:09Z barry $
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


/*

  geotrips TODO list

  * make proper use of smarty templates
  * put logic in some geotrips class; would also reduce some code duplication
  * move css and icons to the usual locations
  * make db schema more geograph like?
  * rewrite rules
  * include in "content" (probably port content changes from gbi)
  * robots?
  * sitemap?
  * reduce track size?

*/

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

include('./geotrip_func.php');
$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  


  // get tracks from database
  if (isset($_GET['debug'])) $trks=$db->getAll("select * from geotrips where location='debug' order by id desc");
  else $trks=$db->getAll("select * from geotrips where location!='debug' order by id desc");


$smarty->assign('page_title', 'Overview map :: Geo-Trips');
$smarty->assign('meta_description', 'A collection of square-bagging trips by members of the Geograph project, with photographs, descriptions and GPS tracks plotted on an Ordnance Survey map.');
$smarty->assign('olayersmap', 1);

$smarty->display('_std_begin.tpl','trip_home');
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';




?>

  <!--RSS feed via Geograph-->
  <link rel="alternate" type="application/rss+xml" title="Geo-Trips RSS" href="/content/syndicator.php?scope[]=trip" />

<!--script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.6&amp;sensor=false&amp;key={$google_maps_api_key}"></script-->
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="/mapper/geotools2.js"></script>
<script type="text/javascript" src="/mappingO.js"></script>

<script type="text/javascript">
//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
  // FIXME check if "var" used everywhere
  var map;
  var trkLayer,trk,trkFeature,trkString;
  var cont,contFeature,contString;
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var lat0 = <?php print $CONF['gmcentre'][0];?>;
  var lon0 = <?php print $CONF['gmcentre'][1];?>;
  var iniz = 6;//FIXME
  var lonmin = <?php print $CONF['gmlonrange'][0][0];?>;
  var lonmax = <?php print $CONF['gmlonrange'][0][1];?>;
  var latmin = <?php print $CONF['gmlatrange'][0][0];?>;
  var latmax = <?php print $CONF['gmlatrange'][0][1];?>;
  function initmap() {
			initOL();
			initIconLayer();
			trkLayer = new OpenLayers.Layer.Vector(
				"Lines",
				{
					isBaseLayer: false,
					displayInLayerSwitcher: false
				}
			);
			var point1 = new OpenLayers.Geometry.Point(lonmin, latmin);
			var point2 = new OpenLayers.Geometry.Point(lonmax, latmax);
			point1.transform(epsg4326, epsg900913);
			point2.transform(epsg4326, epsg900913);

			var bounds = new OpenLayers.Bounds();
			bounds.extend(point1);
			bounds.extend(point2);

			var layerswitcher = new OpenLayers.Control.LayerSwitcher({'ascending':false});

			map = new OpenLayers.Map({
				div: "map",
				projection: epsg900913,
				displayProjection: epsg4326,
				units: "m",
				numZoomLevels: 18,
				restrictedExtent: bounds,
				controls : [
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.PanZoomBar(),
					layerswitcher,
					new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),
					new OpenLayers.Control.Attribution()
				]
			});

			var mapnik = new OpenLayers.Layer.XYrZ(
				"Mapnik (Static + OSM)",
				"/tile/osm/${z}/${x}/${y}.png",
				0, 18, OpenLayers.Util.Geograph.MISSING_TILE_URL_BLUE /*FIXME*/,
				{
					attribution: '&copy; <a href="http://www.openstreetmap.org/">OSM</a> contributors (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
					sphericalMercator : true
				},
				16, "http://tile.openstreetmap.org/${z}/${x}/${y}.png"
			);

			var osmmapnik = new OpenLayers.Layer.OSM(
				null,
				null,
				{ numZoomLevels: 19 }
			);

			map.addLayers([
				mapnik,
				osmmapnik,
				trkLayer,
				markers
			]);

			var overview =  new OpenLayers.Control.OverviewMap({
				//maximized: true
			});
			map.addControl(overview);
			var point = new OpenLayers.LonLat(lon0, lat0);
			var mt = mapnik;
			map.setBaseLayer(mt);
			map.setCenter(point.transform(epsg4326, map.getProjectionObject()), iniz);



<?php
    foreach ($trks as $track) {
      $bbox=explode(' ',$track['bbox']);
      $cen[0]=0.5*($bbox[0]+$bbox[2]);
      $cen[1]=0.5*($bbox[1]+$bbox[3]);
      $date=date('D, j M Y',strtotime($track['date']));
      require_once('geograph/gridimage.class.php');
      $image = new GridImage($track['img']);
      if (!$image->isValid()) {
        //FIXME
      }
      if ($track['title']) $title=$track['title'];
      else $title=$track['location'].' from '.$track['start'];
      $loc=$track['location'];
?>
      // Define marker
      pos=new OpenLayers.LonLat(<?php print("$cen[1],$cen[0]");?>);
      size=new OpenLayers.Size(15,15);
      offset=new OpenLayers.Pixel(-7,-7);
      icon=new OpenLayers.Icon('<?php print("{$track['type']}.png");?>',size,offset,null);
      content='<p>';
      content+='<a href=\"<?php print("geotrip_show.php?trip={$track['id']}");?>\">';
      content+='<img alt=\"<?php print(str_replace("'","\'",$loc));?>\" src=\"';
      content+='<?php print($image->getThumbnail(213,160,true));?>\" />';
      content+='</a>';
      content+='</p><p>';
      content+='<strong><?php print(addslashes(htmlentities($title)));?></strong><br />';
      content+='<?php print("by <a href=\"/profile/{$track['uid']}\">".addslashes(htmlentities($track['user']))."</a> - $date<br />");?>';
      content+='<small>Click image to see details of this trip.</small>';
      content+='</p>';
      popUpSize=new OpenLayers.Size(400,300);
      addPopupMarker(pos, GeoPopup, content, true, true, icon);
<?php
      // Link multi-day trips
      if ($track['contfrom']) {
        $prevbbox=$db->getRow("select bbox from geotrips where id={$track['contfrom']}");
        $prevbbox=explode(' ',$prevbbox['bbox']);
        $pcen[0]=0.5*($prevbbox[0]+$prevbbox[2]);
        $pcen[1]=0.5*($prevbbox[1]+$prevbbox[3]);
?>
        point1 = new OpenLayers.Geometry.Point(<?php print("$pcen[1],$pcen[0]");?>);
        point2 = new OpenLayers.Geometry.Point(<?php print("$cen[1],$cen[0]");?>);
        point1.transform(epsg4326, map.getProjectionObject());
        point2.transform(epsg4326, map.getProjectionObject());
        cont=new Array();
        cont.push(point1);
        cont.push(point2);
        contString=new OpenLayers.Geometry.LineString(cont);
        contFeature=new OpenLayers.Feature.Vector(contString,null,style_trk);
        trkLayer.addFeatures([contFeature]);
<?php
      }
    }
?>
  }
   AttachEvent(window,'load',initmap,false);

//]]>
</script>

<h2>Geo-Trips overview map</h2>

<div class="panel maxi" style="max-width:800px">
  <p>
The map below shows Geo-Trips submitted by members of the <a href="/">Geograph</a>
project.  Each point on the map represents a day trip by one Geograph-er to cover a number of
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
Please note that Geo-Trips currently only work in England, Scotland, Wales and the Isle of Man as the
map is based on the <a href="http://www.ordnancesurvey.co.uk">Ordnance Survey</a>'s OpenSpace mapping.
  </p>
  <table class="ruled"><tr>
    <td><b>Legend:</b></td>
    <td><img src="walk.png" alt="" title="Fig.: Walk symbol"> Walk</td><td></td>
    <td><img src="bike.png" alt="" title="Fig.: Bike symbol"> Cycle ride</td><td></td>
    <td><img src="boat.png" alt="" title="Fig.: Boat symbol"> Boat trip</td><td></td>
    <td><img src="rail.png" alt="" title="Fig.: Rail symbol"> Train ride</td><td></td>
    <td><img src="road.png" alt="" title="Fig.: Road symbol"> Drive</td><td></td>
    <td><img src="bus.png"  alt="" title="Fig.: Bus symbol">  Scheduled public transport</td>
  </td></tr></table>
  <!--div id="map" class="inner" style="width:798px;height:1300px"></div-->
  <div id="map" class="inner" style="width:798px;height:650px"></div>
  <table class="ruled"><tr>
    <td><b>Legend:</b></td>
    <td><img src="walk.png" alt="" title="Fig.: Walk symbol"> Walk</td><td></td>
    <td><img src="bike.png" alt="" title="Fig.: Bike symbol"> Cycle ride</td><td></td>
    <td><img src="boat.png" alt="" title="Fig.: Boat symbol"> Boat trip</td><td></td>
    <td><img src="rail.png" alt="" title="Fig.: Rail symbol"> Train ride</td><td></td>
    <td><img src="road.png" alt="" title="Fig.: Road symbol"> Drive</td><td></td>
    <td><img src="bus.png"  alt="" title="Fig.: Bus symbol">  Scheduled public transport</td>
  </td></tr></table>
  <p>
In the spirit if not the scope of Geo-Trips, here's <b>Thomas Nugent</b>'s
<a href="http://www.geograph.org.uk/article/Luton-to-Glasgow-in-50-minutes">flight from Luton to Glasgow</a>,
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
  $i=0;
  if ($_GET['max']) $max=$_GET['max'];
  else $max=3;
  while ($i < count($trks) && (strtotime($trks[$i]['updated'])>date('U')-86400||$i<$max)) {  // show all uploaded in last 24 hours, but at least three
    if ($trks[$i]['title']) $title=htmlentities($trks[$i]['title']);
    else $title=htmlentities($trks[$i]['location'].' from '.$trks[$i]['start']);
    $descr=str_replace("\n",'</p><p>',htmlentities($trks[$i]['descr']));
    if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
    $gr=bbox2gr($trks[$i]['bbox']);
    require_once('geograph/gridimage.class.php');
    $image = new GridImage($trks[$i]['img']);
    if (!$image->isValid()) {
      //FIXME
    }
    $mmmyy=explode('-',$trks[$i]['date']);
    $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"/profile/{$trks[$i]['uid']}\">".htmlentities($trks[$i]['user'])."</a> and available under a <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"/img/external.png\" /></span>";
    print('<div class="inner">');
    print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"".$image->getThumbnail(213,160,true)."\" alt=\"\" title=\"$title\" /><br />$cred</div>");
    print("<b>$title</b><br />");
    print("<em>".htmlentities($trks[$i]['location'])."</em> &ndash; A ".whichtype($trks[$i]['type'])." from ".htmlentities($trks[$i]['start'])."<br />");
    print("by <a href=\"/profile/{$trks[$i]['uid']}\">".htmlentities($trks[$i]['user'])."</a>");
    print("<div class=\"inner flt_r\">$gr</div>");
    print("<p>$descr&nbsp;[<a href=\"geotrip_show.php?trip={$trks[$i]['id']}\">more</a>]</p>");
    print('<div class="row"></div>');
    print('</div>');
    $i++;
  }
?>
</div>

<?php 

$smarty->display('_std_end.tpl');


