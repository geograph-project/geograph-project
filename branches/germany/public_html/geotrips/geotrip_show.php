<?php
/**
 * $Project: GeoGraph $
 * $Id: geotrip_show.php 7817 2013-03-31 19:47:52Z barry $
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
 

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

include('./geotrip_func.php');
$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

//FIXME more links:
//show page: link to edit page
//edit page: links to show pages
//index:     links to edit pages if matching uid

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

<!--script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.6&amp;sensor=false&amp;key={$google_maps_api_key}"></script-->
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="/mapper/geotools2.js"></script>
<script type="text/javascript" src="/mappingO.js"></script>

<?php
  $bbox=explode(' ',$trk['bbox']);
  $cen[0]=0.5*($bbox[0]+$bbox[2]);
  $cen[1]=0.5*($bbox[1]+$bbox[3]);
  $search=$trk['search'];
  $track=explode(' ',$trk['track']);
  $len=count($track);
  // fetch Geograph data
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	$geograph = array();
	$realnames = array();
	$engine = new SearchEngine($search);
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
		        || $image['viewpoint_northings'] != $image['natnorthings']
		        || $image['viewpoint_refindex']  != $image['reference_index'])
		    &&  $image['imagetaken'] === $trk['date']
		) {
			$geograph[] = $image;
			$realnames[$image['realname']] = $image['realname'];
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close();
?>

<script type="text/javascript">
//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
  var map;
  var trkLayer,trk,trkFeature,trkString;                             // track
  var vdir,vdirFeature,vdirString;                                   // view directions
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
  var lonmin = <?php print $CONF['gmlonrange'][0][0];?>;
  var lonmax = <?php print $CONF['gmlonrange'][0][1];?>;
  var latmin = <?php print $CONF['gmlatrange'][0][0];?>;
  var latmax = <?php print $CONF['gmlatrange'][0][1];?>;
  var triplatmin = <?php print $bbox[0];?>;
  var triplonmin = <?php print $bbox[1];?>;
  var triplatmax = <?php print $bbox[2];?>;
  var triplonmax = <?php print $bbox[3];?>;
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

			point1 = new OpenLayers.Geometry.Point(triplonmin, triplatmin);
			point2 = new OpenLayers.Geometry.Point(triplonmax, triplatmax);
			point1.transform(epsg4326, epsg900913);
			point2.transform(epsg4326, epsg900913);

			var tripbounds = new OpenLayers.Bounds();
			tripbounds.extend(point1);
			tripbounds.extend(point2);

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
			var point = new OpenLayers.LonLat(<?php print("$cen[1],$cen[0]"); ?>);
			var mt = mapnik; //FIXME
			map.setBaseLayer(mt);
			map.setCenter(point.transform(epsg4326, map.getProjectionObject())/*, iniz*/);
			map.zoomToExtent(tripbounds);
    <?php if (!empty($trk['track']) && $len>0) { ?>
    // Define track
    trk=new Array();
    <?php for ($i=0;$i<$len-1;$i+=2) print("trk.push((new OpenLayers.Geometry.Point({$track[$i+1]},{$track[$i]})).transform(epsg4326, map.getProjectionObject()));\n"); ?>
    trkString=new OpenLayers.Geometry.LineString(trk);
    trkFeature=new OpenLayers.Feature.Vector(trkString,null,style_trk);
    trkLayer.addFeatures([trkFeature]);
<?php 
    }
    $len=count($geograph);
    require_once('geograph/gridimage.class.php');
    require_once('geograph/conversionslatlong.class.php');
    $conv = new ConversionsLatLong;
    foreach($geograph as &$image) {
      // shift marker to centre of square indicated by GR
      fake_precision($image);
      $latlon = $conv->national_to_wgs84($image['viewpoint_eastings'], $image['viewpoint_northings'], $image['viewpoint_refindex'], true);
      $gridimage = new GridImage($image['gridimage_id']);
      if (!$gridimage->isValid()) {
        //FIXME
      }
?>
      // Define camera marker
      pos=new OpenLayers.LonLat(<?php print("$latlon[1],$latlon[0]");?>);
      size=new OpenLayers.Size(9,9);
      offset=new OpenLayers.Pixel(-5,-10);//)(-4,-9);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre //FIXME
      icon=new OpenLayers.Icon('walk.png',size,offset,null);
      content='<p>';
      content+='<a href=\"/photo/<?php print($image['gridimage_id']);?>\">';
      content+='<img alt=\"<?php print(sanitise($image['title']));?>\" src=\"'; // FIXME title1 title2
      content+='<?php print($gridimage->getThumbnail(213,160,true));?>';
      content+='\" /></a>';
      content+='</p><p>';
      content+='<strong><?php print(sanitise($image['title']));?></strong>';// FIXME title1 title2
      content+='</p><p>';
      content+='<?php print(sanitise($image['comment']));?>'; // FIXME comment1 comment2
      content+='</p><p>';
      content+='View full image on ';
      content+='<a href=\"/photo/<?php print($image['gridimage_id']);?>\">';
      content+='Geograph Britain&amp;Ireland</a> ';
      content+='<img alt=\"external link\" title=\"\" src=\"/img/external.png\" />';
      content+='</p>';
      addPopupMarker(pos, GeoPopup, content, true, true, icon);
      // Define view direction
      vdir=new Array();
<?php
      $latlon = $conv->national_to_wgs84($image['viewpoint_eastings'], $image['viewpoint_northings'], $image['viewpoint_refindex'], true);
?>
      pos=new OpenLayers.Geometry.Point(<?php print("$latlon[1],$latlon[0]");?>);
      pos.transform(epsg4326, map.getProjectionObject());
      vdir.push(pos);
<?php
      $ea=$image['nateastings'];
      $no=$image['natnorthings'];
      if (
             $image['viewpoint_eastings']  == $image['nateastings']
          && $image['viewpoint_northings'] == $image['natnorthings']
          && $image['viewpoint_refindex']  == $image['reference_index']
          && $image['view_direction'] != -1
      ) {  // subject GR == camera GR and view direction given
        $ea+=round(20.*sin(deg2rad($image['view_direction'])));
        $no+=round(20.*cos(deg2rad($image['view_direction'])));
      }
      $latlon = $conv->national_to_wgs84($ea, $no, $image['reference_index'], true);
?>
      pos=new OpenLayers.Geometry.Point(<?php print("$latlon[1],$latlon[0]");?>);
      pos.transform(epsg4326, map.getProjectionObject());
      vdir.push(pos);
      vdirString=new OpenLayers.Geometry.LineString(vdir);
      vdirFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([vdirFeature]);
<?php
    }
    unset($image);
?>
  }

  AttachEvent(window,'load',initmap,false);

//]]>
</script>

<h2><a href="./">Geo-Trips</a> :: <?php echo htmlentities($hdr2); ?></h2>

<div class="panel maxi">
<?php 
  print('<h3>'.htmlentities($trk['location']).'</h3>');
  $date=date('D, j M Y',strtotime($trk['date']));
  print('<h4>A '.whichtype($trk['type']).' from '.htmlentities($trk['start'])."</h4><h4>$date</h4><h4>by <a href=\"/profile/{$trk['uid']}\">".htmlentities($trk['user'])."</a></h4><p style=\"text-align:center\">");
  // row of random images
  $selected = array_rand($geograph, 3);
  foreach($selected as $idx) {
      $gridimage = new GridImage($geograph[$idx]['gridimage_id']);// FIXME fast init? (also at other places)
      if (!$gridimage->isValid()) {
        continue;
      }
      print("<a href=\"/photo/{$geograph[$idx]['gridimage_id']}\" title=\"".htmlentities($geograph[$idx]['title'])."\">");
      print("<img alt=\"".htmlentities($geograph[$idx]['title'])."\" class=\"inner\" src=\"".$gridimage->getThumbnail(213,160,true)."\" /></a>&nbsp;");
  }
?>
  </p>
<?php
  $prec=$trk['contfrom'];
  $foll=$foll['id'];
  if ($prec||$foll) {
    print('<table class="ruled" style="margin:auto"></tr>');
    if ($prec) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"geotrip_show.php?trip=$prec\">preceding leg</a></td>");
    else print('<td></td>');
    print('<td style="margin:20px;text-align:center"><b>This trip is part of a series.</b></td>');
    if ($foll) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"geotrip_show.php?trip=$foll\">next leg</a></td>");
    else print('<td></td>');
    print('</tr></table>');
  }
?>
  <p>
<?php print(str_replace("\n",'</p><p>',htmlentities($trk['descr']))) ?>
  </p>
  <div class="inner flt_r">
    [<a href="/geotrips/">overview map</a>]
  </div>
  <div> <p><small>
<?php if ($trk['track']) print('On the map below, the grey line is the GPS track from this trip. ');?>
Click the blue circles to see a photograph
taken from that spot and read further information about the location.  The blue lines indicate
the direction of view.  There is also a
<a href="/search.php?i=<?php print($search);?>&amp;displayclass=slide">slideshow</a>
<img alt="external link" title="" src="/img/external.png" /> of this trip.
  </small></p></div>
  <div class="row"></div>
  <!--div id="map" class="inner" style="width:798px;height:800px"></div-->
  <div id="map" class="inner" style="width:798px;height:650px"></div>
  <p style="font-size:.65em">
All images &copy; <?php print("<a href=\"http://geo.hlipp.de/profile/{$trk['uid']}\">".htmlentities(implode(', ', $realnames))."</a>");?> and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">
Creative Commons licence</a> <img alt="external link" title="" src="http://geo.hlipp.de/img/external.png" />.
  </p>
</div>

<?php 

$smarty->display('_std_end.tpl');

