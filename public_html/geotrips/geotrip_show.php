<?php
  $lastmod='';
  include('geotrip_func.php');
  // get track from database
  $db=sqlite_open('../db/geotrips.db');
  $trk=sqlite_fetch_array(sqlite_query($db,"select * from geotrips where id='{$_GET['trip']}'"));
  $foll=sqlite_fetch_array(sqlite_query($db,"select id from geotrips where contfrom='{$_GET['trip']}'"));
  sqlite_close($db);
  $hdr1='Geo-Trips';
  if ($trk['title']) $hdr2=str_replace('\\','',preg_replace('/\n/','</p><p>',$trk['title']));

  else $hdr2=$trk['location'].' from '.$trk['start'];
  $descr="A ".whichtype($trk['type'])." near $trk[location], starting from $trk[start], with pictures and plotted on a map.";
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
  include($docroot.'/templates/head.php');
?>

<script type="text/javascript" src="http://openspace.ordnancesurvey.co.uk/osmapapi/openspace.js?key=8BEE466DD1F7476FE0405F0ACA6011E3"></script>

<?php
  $bbox=explode(' ',$trk['bbox']);
  $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
  $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
  if ($bbox[2]-$bbox[0]>4000||$bbox[3]-$bbox[1]>3000) $scale=7;
  else $scale=8;
  $search=$trk['search'];
  $track=explode(' ',$trk['track']);
  $len=count($track);
  // fetch Geograph data
  @$csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i=$search&count=250&en=1&thumb=1&desc=1&dir=1&ppos=1&big=1"),'r') or die('Geograph seems to be down at the moment.  Please try again in a few moments.');
  fgets($csvf);  // discard header
  while ($line=fgetcsv($csvf,4092,',','"')) if ($line[10]) $geograph[]=$line;  // only show pictures with camera position
  fclose($csvf);
?>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;                             // track
  var vdir,vdirFeature,vdirString;                                   // view directions
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
  function initmap() {
    osMap=new OpenSpace.Map('map',{controls:[],centreInfoWindow:false});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
    <?php print("osMap.setCenter(new OpenSpace.MapPoint($cen[0],$cen[1]),$scale);\n"); ?>
    trkLayer=osMap.getVectorLayer();
    // Define track
    trk=new Array();
    <?php for ($i=0;$i<$len-1;$i+=2) print("trk.push(new OpenLayers.Geometry.Point({$track[$i]},{$track[$i+1]}));\n"); ?>
    trkString=new OpenLayers.Geometry.LineString(trk);
    trkFeature=new OpenLayers.Feature.Vector(trkString,null,style_trk);
    trkLayer.addFeatures([trkFeature]);
<?php 
    $len=count($geograph);
    for ($i=0;$i<$len;$i++) {
      // shift marker to centre of square indicated by GR
      $geograph[$i]=fake_precision($geograph[$i]);
?>
      // Define camera marker
      pos=new OpenSpace.MapPoint(<?php print("{$geograph[$i][10]},{$geograph[$i][11]}");?>);
      size=new OpenLayers.Size(9,9);
      offset=new OpenLayers.Pixel(-4,-9);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre
      infoWindowAnchor=new OpenLayers.Pixel(4,4);
      icon=new OpenSpace.Icon('walk.png',size,offset,null,infoWindowAnchor);
//<![CDATA[
      content='<p>';
      content+='<a href=\"http://www.geograph.org.uk/photo/<?php print($geograph[$i][0]);?>\">';
      content+='<img alt=\"<?php print(sanitise($geograph[$i][1]));?>\" src=\"';
      content+='<?php print(str_replace("_120x120.jpg","_213x160.jpg",$geograph[$i][6]));?>';
      content+='\"</a>';
      content+='</p><p>';
      content+='<strong><?php print(sanitise($geograph[$i][1]));?></strong>';
      content+='</p><p>';
      content+='<?php print(sanitise($geograph[$i][5]));?>';
      content+='</p><p>';
      content+='View full image on ';
      content+='<a href=\"http://www.geograph.org.uk/photo/<?php print($geograph[$i][0]);?>\">';
      content+='Geograph Britain&amp;Ireland</a> ';
      content+='<img alt=\"external link\" title=\"\" src=\"http://users.aber.ac.uk/ruw/templates/external.png\" />';
      content+='</p>';
//]]>
      popUpSize=new OpenLayers.Size(300,320);
      osMap.createMarker(pos,icon,content,popUpSize);
      // Define view direction
      vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i][10]},{$geograph[$i][11]}");?>));
<?php
      if ($geograph[$i][7]!=$geograph[$i][10]||$geograph[$i][8]!=$geograph[$i][11]) {  // subject GR != camera GR
?>
        vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i][7]},{$geograph[$i][8]}");?>));
<?php
      } else {
        $ea=$geograph[$i][7]+round(100.*sin($geograph[$i][13]*M_PI/180.));
        $no=$geograph[$i][8]+round(100.*cos($geograph[$i][13]*M_PI/180.));
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
  }
</script>

<?php
  include($docroot.'/templates/top.php');
?>

<div class="panel maxi">
<?php 
  print('<h3>'.str_replace('\\','',$trk['location']).'</h3>');
  $date=date('D, j M Y',strtotime($trk['date']));
  print('<h4>A '.whichtype($trk['type']).' from '.str_replace('\\','',$trk['start'])."</h4><h4>$date</h4><h4>by <a href=\"http://www.geograph.org.uk/profile/$trk[uid]\">$trk[user]</a></h4><p style=\"text-align:center\">");
  // row of random images
  $selected=array();
  for ($i=0;$i<3;$i++) {
    $imgno=mt_rand(0,$len-1);
    if (!in_array($imgno,$selected)) {
      $thumb=str_replace("_120x120.jpg","_213x160.jpg",$geograph[$imgno][6]);
      print("<img alt=\"$geograph[$imgno][1]\" class=\"inner\" src=\"$thumb\" />&nbsp;");
      $selected[]=$imgno;
    } else {
      $i--;
    }
  }
?>
  </p>
<?php
  $prec=$trk['contfrom'];
  $foll=$foll['id'];
  if ($prec||$foll) {
    print('<table class="ruled" style="margin:auto"></tr>');
    if ($prec) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"geotrip_show.php?osos&trip=$prec\">preceding leg</a></td>");
    else print('<td></td>');
    print('<td style="text-align:center"><b>This trip is part of a series.</b></td>');
    if ($foll) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"geotrip_show.php?osos&trip=$foll\">next leg</a></td>");
    else print('<td></td>');
    print('</tr></table>');
  }
?>
  <p>
<?php print(str_replace('\\','',preg_replace('/\n/','</p><p>',$trk['descr']))) ?>
  </p>
  <div class="inner flt_r">
    [<a href="geotrip.php?osos">overview map</a>]
  </div>
  <div> <p><small>
<?php if ($trk['track']) print('On the map below, the grey line is the GPS track from this trip. ');?>
Click the blue circles to see a photograph
taken from that spot and read further information about the location.  The blue lines indicate
the direction of view.  There is also a
<a href="http://www.geograph.org.uk/search.php?i=<?php print($search);?>&amp;displayclass=slidebig">slideshow</a>
<img alt="external link" title="" src="http://users.aber.ac.uk/ruw/templates/external.png" /> of this trip.
  </small></p></div>
  <div class="row"></div>
  <div id="map" class="inner" style="width:798px;height:800px"></div>
  <p style="font-size:.65em">
All images &copy; <?php print("<a href=\"http://www.geograph.org.uk/profile/{$trk['uid']}\">{$trk['user']}</a>");?> and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">
Creative Commons licence</a> <img alt="external link" title="" src="http://users.aber.ac.uk/ruw/templates/external.png" /> via
<a href="http://www.geograph.org.uk">Geograph Britain&amp;Ireland</a> 
<img alt="external link" title="" src="http://users.aber.ac.uk/ruw/templates/external.png" />.
  </p>
</div>

<?php include($docroot.'/templates/bottom.php'); ?>
