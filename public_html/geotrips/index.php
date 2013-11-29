<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

include('./geotrip_func.php');
$db = GeographDatabaseConnection(false);
// can now use mysql_query($sql); directly, or mysql_query($sql,$db->_connectionID);

$updated = $db->GetOne("SELECT MAX(updated) FROM geotrips")+1;

customCacheControl($updated,$updated);


$smarty->assign('page_title', 'Overview map :: Geo-Trips');
$smarty->assign('meta_description', 'A collection of square-bagging trips by members of the Geograph project, with photographs, descriptions and GPS tracks plotted on an Ordnance Survey map.');

$smarty->display('_std_begin.tpl','trip_home');
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';


$str =& $memcache->name_get('geotrip_home',$USER->registered.".".$updated);

if (!empty($str)) {
  print $str;

} else {
  ob_start();

  // get tracks from database
  if (isset($_GET['debug'])) $trks=$db->getAll("select * from geotrips where location='debug' order by id desc");
  else $trks=$db->getAll("select * from geotrips where location!='debug' order by id desc");


?>

  <!--RSS feed via Geograph-->
  <link rel="alternate" type="application/rss+xml" title="Geo-Trips RSS" href="http://www.geograph.org.uk/content/syndicator.php?scope[]=trip" />

<script src="http://osopenspacepro.ordnancesurvey.co.uk/osmapapi/openspace.js?key=A493C3EB96133019E0405F0ACA6056E3" type="text/javascript"></script>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;
  var cont,contFeature,contString;
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  function initmap() {
    osMap=new OpenSpace.Map('map',{controls:[],centreInfoWindow:false});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
    <?php print("osMap.setCenter(new OpenSpace.MapPoint(350000,630000),1);\n"); ?>
    trkLayer=osMap.getVectorLayer();
<?php
    foreach ($trks as $track) {
      $bbox=explode(' ',$track['bbox']);
      $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
      $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
      $date=date('D, j M Y',strtotime($track['date']));
      // fetch Geograph thumbnail
      $image = new GridImage($track['img']);
		if (!	$image->isValid()) {
			//FIXME error handling
		}
      $thumb=$image->getThumbnail(213,160,true);
      if ($track['title']) $title=$track['title'];
      else $title=$track['location'].' from '.$track['start'];
      $loc=$track['location'];
?>
      // Define marker
      pos=new OpenSpace.MapPoint(<?php print("$cen[0],$cen[1]");?>);
      size=new OpenLayers.Size(15,15);
      offset=new OpenLayers.Pixel(-7,-7);
      infoWindowAnchor=new OpenLayers.Pixel(7,7);
      icon=new OpenSpace.Icon('<?php print("{$track['type']}.png");?>',size,offset,null,infoWindowAnchor);
//<![CDATA[
      content='<p>';
      content+='<a href=\"<?php print("/geotrips/{$track['id']}");?>\">';
      content+='<img alt=\"<?php print(str_replace("'","\'",$loc));?>\" src=\"';
      content+='<?php print($thumb);?>\" />';
      content+='</a>';
      content+='</p><p>';
      content+='<strong><?php print(addslashes(htmlentities($title)));?></strong><br />';
      content+='<?php print("by <a href=\"http://www.geograph.org.uk/profile/{$track['uid']}\">".addslashes(htmlentities($track['user']))."</a> - $date<br />");?>';
      content+='<small>Click image to see details of this trip.</small>';
      content+='</p>';
//]]>
      popUpSize=new OpenLayers.Size(400,300);
      osMap.createMarker(pos,icon,content,popUpSize);
<?php
      // Link multi-day trips
      if ($track['contfrom']) {
        $prevbbox=$db->getRow("select bbox from geotrips where id={$track['contfrom']}");
        $prevbbox=explode(' ',$prevbbox['bbox']);
        $pcen[0]=(int)(($prevbbox[0]+$prevbbox[2])/2);
        $pcen[1]=(int)(($prevbbox[1]+$prevbbox[3])/2);
?>
        cont=new Array();
        cont.push(new OpenLayers.Geometry.Point(<?php print("$pcen[0],$pcen[1]");?>));
        cont.push(new OpenLayers.Geometry.Point(<?php print("$cen[0],$cen[1]");?>));
        contString=new OpenLayers.Geometry.LineString(cont);
        contFeature=new OpenLayers.Feature.Vector(contString,null,style_trk);
        trkLayer.addFeatures([contFeature]);
<?php
      }
    }
?>
  }
   AttachEvent(window,'load',initmap,false);

</script>

<h2>Geo-Trips overview map</h2>

<div class="panel maxi" style="max-width:800px">
  <p>
The map below shows Geo-Trips submitted by members of the <a href="http://www.geograph.org.uk">Geograph</a>
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
  <div id="map" class="inner" style="width:798px;height:1300px"></div>
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
There is a <a href="http://www.geograph.org.uk/content/?scope[]=trip">full list of Geo-Trips</a> (updated once daily
in the early morning) in the Collections area of Geograph, which can be filtered by keyword or author.  You can also
subscribe to an
<a href="http://www.geograph.org.uk/content/syndicator.php?scope[]=trip">RSS feed</a> with new Geo-Trips as they
come in.  The list below includes all trips uploaded in the last 24 hours.
  </p>
<?php
  $i=0;
  if ($_GET['max']) $max=$_GET['max'];
  else $max=3;
  while ($trks[$i]['updated']>date('U')-86400||$i<$max) {  // show all uploaded in last 24 hours, but at least three
    if ($trks[$i]['title']) $title=htmlentities($trks[$i]['title']);
    else $title=htmlentities($trks[$i]['location'].' from '.$trks[$i]['start']);
    $descr=str_replace("\n",'</p><p>',htmlentities($trks[$i]['descr']));
    if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
    $gr=bbox2gr($trks[$i]['bbox']);
    // fetch Geograph thumbnail
    $image = new GridImage($trks[$i]['img']);
		if (!	$image->isValid()) {
			//FIXME error handling
		}
    $thumb=$image->getThumbnail(213,160,true);
    $mmmyy=explode('-',$trks[$i]['date']);
    $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"http://www.geograph.org.uk/profile/{$trks[$i]['uid']}\">".htmlentities($trks[$i]['user'])."</a> and available under a <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"http://s1.geograph.org.uk/img/external.png\" /></span>";
    print('<div class="inner">');
    print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"$thumb\" alt=\"\" title=\"$title\" /><br />$cred</div>");
    print("<b>$title</b><br />");
    print("<em>".htmlentities($trks[$i]['location'])."</em> -- A ".whichtype($trks[$i]['type'])." from ".htmlentities($trks[$i]['start'])."<br />");
    print("by <a href=\"http://www.geograph.org.uk/profile/{$trks[$i]['uid']}\">".htmlentities($trks[$i]['user'])."</a>");
    print("<div class=\"inner flt_r\">$gr</div>");
    print("<p>$descr&nbsp;[<a href=\"/geotrips/{$trks[$i]['id']}\">more</a>]</p>");
    print('<div class="row"></div>');
    print('</div>');
    $i++;
  }
?>
</div>

<?php
	$str = ob_get_flush();

	$memcache->name_set('geotrip_home',$USER->registered.".".$updated,$str,$memcache->compress,$memcache->period_long);

}

$smarty->display('_std_end.tpl');


