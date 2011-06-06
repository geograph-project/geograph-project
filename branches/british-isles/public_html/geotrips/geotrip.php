<?php
  $lastmod='';
  // get tracks from database
  $db=sqlite_open('../db/geotrips.db');
  if (isset($_GET['debug'])) $trk=sqlite_fetch_all(sqlite_query($db,"select * from geotrips where location='debug' order by id desc"));
  else $trk=sqlite_fetch_all(sqlite_query($db,"select * from geotrips where location!='debug' order by id desc"));
//  sqlite_close($db);
  $hdr1='Geo-Trips';
  $hdr2="Overview map";
  $descr="A collection of square-bagging trips by members of the Geograph project, with photographs, descriptions and GPS tracks plotted on an Ordnance Survey map.";
  $prev='';
  $next='';
  $lect=0;     // whether lecture style sheet is available
  $cym=0;      // 0- English only, 1- English selected, 2- Welsh selected
  $cyfiethwyd='';
  $noidx=0;    // don't allow indexing by search engines if TRUE
  $dir=dirname($_SERVER['SCRIPT_NAME']);
  if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
    $docroot=$_SERVER['DOCUMENT_ROOT'].'/ruw';
  } else {     // include() can't cope with the symlinks on the AU server - not even using realpath()
    $docroot='/ceri/staff1/base/r/ruw/public_html';
  }
  include('geotrip_func.php');
  include($docroot.'/templates/head.php');
  // authentication stuff from Geograph
  require_once('token.class.php');
  $login_url='http://www.geograph.org.uk/auth.php?a=WohlJL5405owauhVbuZ4VZbbZh4';
  $token=new Token;
  $token->magic='79438906cb765eea3670da00c96328ee';
  $token->setValue("action",'authenticate');
  $token->setValue("callback","http://users.aber.ac.uk/ruw/misc/geograph_callback.php");
  $login_url.='&amp;t='.$token->getToken();
?>

  <!--RSS feed via Geograph-->
  <link rel="alternate" type="application/rss+xml" title="Geo-Trips RSS" href="http://www.geograph.org.uk/content/syndicator.php?scope[]=trip" />

<script type="text/javascript" src="http://openspace.ordnancesurvey.co.uk/osmapapi/openspace.js?key=8BEE466DD1F7476FE0405F0ACA6011E3"></script>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;
  var cont,contFeature,contString;
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  function initmap() {
    osMap=new OpenSpace.Map('map',{controls:[]});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
    <?php print("osMap.setCenter(new OpenSpace.MapPoint(350000,630000),1);\n"); ?>
    trkLayer=osMap.getVectorLayer();
<?php
    foreach ($trk as $track) {
      $bbox=explode(' ',$track['bbox']);
      $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
      $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
      $date=date('D, j M Y',strtotime($track['date']));
      // fetch Geograph thumbnail
      $csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i={$track['search']}&count=250&en=1&thumb=1&desc=1&dir=1&ppos=1&checkbig=1"),'r');
      fgets($csvf);  // discard header
      $line=fgetcsv($csvf,4092,',','"');   // take the thumb of the first pic in case the requested one is beyond the 250 pic search limit...
      $thumb=$line[6];
      while ($line=fgetcsv($csvf,4092,',','"')) if ($line[0]==$track['img']) $thumb=$line[6];  // ...then replace it if we can
      fclose($csvf);
      $thumb=str_replace("_120x120.jpg","_213x160.jpg",$thumb);
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
      content+='<a href=\"<?php print("geotrip_show.php?osos&trip={$track['id']}");?>\">';
      content+='<img alt=\"<?php print(str_replace("'","\'",$loc));?>\" src=\"';
      content+='<?php print($thumb);?>\" />';
      content+='</a>';
      content+='</p><p>';
      content+='<strong><?php print(str_replace("'","\'",$title));?></strong><br />';
      content+='<?php print("by <a href=\"http://www.geograph.org.uk/profile/{$track['uid']}\">{$track['user']}</a> - $date<br />");?>';
      content+='<small>Click image to see details of this trip.</small>';
      content+='</p>';
//]]>
      popUpSize=new OpenLayers.Size(400,300);
      osMap.createMarker(pos,icon,content,popUpSize);
<?php
      // Link multi-day trips
      if ($track['contfrom']) {
        $prevbbox=sqlite_fetch_array(sqlite_query($db,"select bbox from geotrips where id={$track['contfrom']}"));
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
</script>

<?php
  include($docroot.'/templates/top.php');
?>

<div class="panel maxi" style="max-width:800px">
  <h3>Geo-Trips overview map</h3>
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
  <p class="inner hlt">
If you are a <em>Geograph</em>-er and would like to put your own square-bagging
expeditions on the map - on foot, by bike, in a car or by any other mode of transport -
please use the <a href="geotrip_submit.php">Geo-Trip submission form</a>.
If you upload a GPS track log in GPX format, the track will also be shown.
You can also <a href="geotrip_edit.php">edit your existing Geo-Trips</a>.
  </p>
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
  while ($trk[$i]['updated']>date('U')-86400||$i<$max) {  // show all uploaded in last 24 hours, but at least three
    if ($trk[$i]['title']) $title=str_replace('\\','',$trk[$i]['title']);
    else $title=str_replace('\\','',$trk[$i]['location']).' from '.str_replace('\\','',$trk[$i]['start']);
    $start=str_replace('\\','',$trk[$i]['start']);
    $descr=str_replace('\\','',preg_replace('/\n/','</p><p>',$trk[$i]['descr']));
    if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
    $gr=bbox2gr($trk[$i]['bbox']);
    // fetch Geograph thumbnail
    $csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i={$trk[$i]['search']}&count=250&en=1&thumb=1&desc=1&dir=1&ppos=1"),'r');
    fgets($csvf);  // discard header
    $line=fgetcsv($csvf,4092,',','"');   // take the thumb of the first pic in case the requested one is beyond the 250 pic search limit...
    $thumb=$line[6];
    while ($line=fgetcsv($csvf,4092,',','"')) if ($line[0]==$trk[$i]['img']) $thumb=$line[6];  // ...then replace it if we can
    fclose($csvf);
    $thumb=str_replace("_120x120.jpg","_213x160.jpg",$thumb);
    $mmmyy=explode('-',$trk[$i]['date']);
    $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"http://www.geograph.org.uk/profile/{$trk[$i]['uid']}\">{$trk[$i]['user']}</a> and available under a<br /><a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"http://users.aber.ac.uk/ruw/templates/external.png\" /> via<br /><a href=\"http://www.geograph.org.uk\">Geograph Britain&amp;Ireland</a><img alt=\"external link\" title=\"\" src=\"http://users.aber.ac.uk/ruw/templates/external.png\" /></span>";
    print('<div class="inner">');
    print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"$thumb\" alt=\"\" title=\"$title\" /><br />$cred</div>");
    print("<b>$title</b><br />");
    print("<em>{$trk[$i]['location']}</em> -- A ".whichtype($trk[$i]['type'])." from $start<br />");
    print("by <a href=\"http://www.geograph.org.uk/profile/{$trk[$i]['uid']}\">{$trk[$i]['user']}</a>");
    print("<div class=\"inner flt_r\">$gr</div>");
    print("<p>$descr&nbsp;[<a href=\"geotrip_show.php?osos&trip={$trk[$i]['id']}\">more</a>]</p>");
    print('<div class="row"></div>');
    print('</div>');
    $i++;
  }
?>
</div>

<?php include($docroot.'/templates/bottom.php'); ?>
