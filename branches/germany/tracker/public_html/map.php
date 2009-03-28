<?php
require ("config.php");
require_once ("funcsv2.php");



//connect to database and grab each torrent in database
if ($GLOBALS["persist"])
	$db = mysql_pconnect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Tracker error: can't connect to database - " . mysql_error() . "</p>");
else
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Tracker error: can't connect to database - " . mysql_error() . "</p>");
mysql_select_db($database) or die(errorMessage() . "Tracker error: can't open database $database - " . mysql_error() . "</p>");


$where = '';
$query = "SELECT * FROM ".$prefix."summary LEFT JOIN ".$prefix."namemap ON ".$prefix."summary.info_hash = ".$prefix."namemap.info_hash $where ORDER BY ".$prefix."namemap.filename";


$results = mysql_query($query) or die(errorMessage() . "Can't do SQL query - " . mysql_error() . "</p>");
$numtorrents = 0;
while ($data = mysql_fetch_row($results))
{
	$xhash = "x" . $data[0];
	$query2 = "SELECT * FROM ".$prefix."$xhash";
	$results2 = mysql_query($query2) or die(errorMessage() . "Can't do SQL query - " . mysql_error() . "</p>");

	if (mysql_num_rows($results2) > 0 ) 
	{
		while ($data2 = mysql_fetch_row($results2))
		{
			//calculate percent done for user
			$percent_done = 1.00;
			if ($data2[1] != 0) //only run calculation if they are still downloading
			{
				$size_in_bytes = $data[13];
				if ($size_in_bytes == 0) //thou shalt not divide by zero
					$percent_done = 0;
				else
					$percent_done = round(($size_in_bytes - $data2[1]) / $size_in_bytes, 3);
			}
			$total[$percent_done*100][$data2[2]]++;
		}
		$numtorrents++;
	}
}

include("geoip/geoipcity.inc");
include("geoip/geoipregionvars.php");
$gi = geoip_open("./geoip/GeoLiteCity.dat",GEOIP_STANDARD);

arsort($total);
#print_r($total);exit;
foreach ($total as $percent_done => $hosts) {
	#print "<h3>$percent_done%</h3>";
	arsort($hosts);
	foreach ($hosts as $host => $count) {
		#$name = gethostbyaddr($host);
		#print "$count torrents, ip:$host, name:$name<br>";
		
		$record = geoip_record_by_addr($gi,$host);
		#print $record->country_code . " " . $record->country_code3 . " " . $record->country_name . "\n";
		#print $record->region . " " . $GEOIP_REGION_NAME[$record->country_code][$record->region] . "\n";
		#print $record->city . "\n";
		#print $record->postal_code . "\n";
		#print $record->latitude . "\n";
		#print $record->longitude . "\n";
		#print "<br>";
		
		$locations["".$record->longitude]["".$record->latitude]++;
		$loctotal["".$record->longitude]["".$record->latitude]+=$count;
		
	}
}
#print "<pre>";print_r($locations);exit;
$markers = array();
foreach ($locations as $lon => $row) {
	foreach ($row as $lat => $count) {
		$num = $loctotal["".$lon]["".$lat];
		$markers[] = "markers.push(createMarker(new GLatLng({$lat},{$lon}),'$num/$numtorrents Torrents available',$count));";
		
	}
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Seed Map :: Geograph British Isles - Torrent Archive</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" href="./css/style.css" type="text/css" />
	<script type="text/JavaScript" src="/rounded_corners_lite.inc.js"></script>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAw3BrxANqPQrDF3i-BIABYxTrrtw2mV-obTJq2QiTqv3c8Yk79xThFjUo4a_G8K3n-lgp5AihIg3I7g"
      type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[
	var map;
	var markers = new Array();
	
	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("map"));
			map.addMapType(G_PHYSICAL_MAP);
			map.setCenter(new GLatLng(54.55, -3.88), 5);
			map.addControl(new GLargeMapControl());
			map.addControl(new GScaleControl());
			map.addControl(new GMapTypeControl());

			map.disableDoubleClickZoom(); //(due to become on by default in 2.89)
			map.enableContinuousZoom();
			map.enableScrollWheelZoom();
<?php
echo implode("\n",$markers); 
?>
			
			var bounds = new GLatLngBounds();
			for(q in markers) {
				bounds.extend(markers[q].getPoint());
			}
			var newZoom = map.getBoundsZoomLevel(bounds);
			if (newZoom > 10)
				newZoom = 10;
			var center = bounds.getCenter();
			map.setCenter(center, newZoom);

			for(q in markers) {
				map.addOverlay(markers[q]);
			}
			
		}
	}


	function createMarker(point, html, count) {
		var Clickable = (html.length > 0);
		if (count != '-' && count > 0) {
			var Icon = new GIcon(G_DEFAULT_ICON,'http://www.nearby.org.uk/images/mapIcons/marker'+count+'.png');
			var marker = new GMarker(point, {icon: Icon, clickable: Clickable});
			marker.myImage = 'http://www.nearby.org.uk/images/mapIcons/marker'+count+'.png';
		} else {
			var marker = new GMarker(point, {clickable: Clickable});
			marker.myImage = 'http://maps.google.com/intl/en_ALL/mapfiles/ms/micons/red.png';
		}

		if (html.length > 0) {
			GEvent.addListener(marker, 'click', function() {
				marker.openInfoWindowHtml('<div style="width:300px">'+html+'</div>');
			});
		}

		return marker;
	}

  function loadCorners()
  {
      /*
      The new 'validTags' setting is optional and allows
      you to specify other HTML elements that curvyCorners
      can attempt to round.

      The value is comma separated list of html elements
      in lowercase.

      validTags: ["div", "form"]

      The above example would enable curvyCorners on FORM elements.
      */
      settings = {
          tl: { radius: 20 },
          tr: { radius: 20 },
          bl: { radius: 20 },
          br: { radius: 20 },
          antiAlias: true,
          autoPad: true,
          validTags: ["div"]
      }

      /*
      Usage:

      newCornersObj = new curvyCorners(settingsObj, classNameStr);
      newCornersObj = new curvyCorners(settingsObj, divObj1[, divObj2[, divObj3[, . . . [, divObjN]]]]);
      */
      var myBoxObject = new curvyCorners(settings, "intro");
      myBoxObject.applyCornersToAll();
  
  
      //var myBoxObject2 = new curvyCorners(settings, "intro2");
      //myBoxObject2.applyCornersToAll();
     var myBoxObject3 = new curvyCorners(settings, "helpful");
      myBoxObject3.applyCornersToAll();
  }

    //]]>
    </script>
  </head>
  <body onload="loadCorners();load()" onunload="GUnload()">
<div id="header" >

  <div id="info">
   <h1>Geograph Torrent Archive</h1>

<a href="/">Homepage</a> 
<?php
if (file_exists("rss/rss.xml"))
{
	echo "<a href='rss/rss.xml'><img src='images/feed-icon-14x14.png' border='0' class='icon' alt='RSS 2.0 Feed' title='RSS 2.0 Feed' /></a><a href='rss/rss.xml'>RSS 2.0 Feed</a>";
}
?>

  </div>
  
  <div id="logo">
  <a title="View Geograph British Isles website" href="http://www.geograph.org.uk"><img align="right" src="http://s0.geograph.org.uk/templates/basic/img/logo.gif" width="257" height="74" border="0"></a>
  </div>
  
<br style="clear:both"/>
</div>

<div class="intro" style="width:700px"> 
	<h2>Map of Current Seed &amp; Peer Locations</h2>

	<div id="map" style="width: 640px; height: 500px; border:3px solid #000066; margin-left:20px"></div>
	<small>Note: Pin locations are approximate, based on IP address of seeder</small>
</div>

<br style="clear:both"/>

<div class="helpful"> 

<p style="color:#880000">These torrents are large! Ensure than downloading and
	seeding them will not put you over your Internet provider's bandwidth limits.</p>

<p>If you need any help or have questions about this service, contact Paul Dixon
(<a href='ma&#105;lto&#58;&#108;%6Fr%&#54;4&#101;&#108;ph&#37;40g&#109;a&#105;&#108;&#37;2&#69;%6&#51;&#37;&#54;&#70;%6D'>&#108;o&#114;de&#108;p&#104;&#64;g&#109;ail&#46;co&#109;</a>)
</p>
</div>


</div>



</body></html>


  </body>
</html>
