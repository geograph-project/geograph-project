<?php
require_once('geograph/global.inc.php');
?>
<html>
<head>
<title>Koord Test...</title>
</head>
<body>
<h1>Koord Test...</h1>
<?php
	require_once('geograph/conversionslatlong.class.php');
	$conv = new ConversionsLatLong;
	$points = array(
		array (3, 200 + 90, 5600 + 54, 2), // UKB9054 >  6.0 32 2
		array (3, 300 + 75, 5400 + 42, 2), // ULV7542 <  7.5 32 2
		array (3, 300 + 99, 5400 + 37, 3), // ULV9937 >  7.5 32 3
		array (3, 500 + 07, 5400 + 00, 3), // UNV0700 ~  9.0 32 3
		array (3, 500 + 94, 5200 + 41, 3), // TNT9441 < 10.5 32 3
		array (3, 600 + 19, 5200 + 74, 4), // TPT1974 > 10.5 32 4
		array (3, 700 + 19, 5300 + 99, 4), // UQU1999 < 12.0 32 4
		array (4, 200 + 96, 5700 + 52, 4)  // UTT9652 > 12.0 33 4
	);
	foreach ($points as $point) {
		$ri = $point[0];
		$east = $point[1]*1000;
		$nort = $point[2]*1000;
		$gk = $point[3];

		list ($lat,$long) =   $conv->national_to_wgs84($east+ 500,$nort+ 500,$ri);
		list ($ge, $gn) = $conv->wgs84_to_gk($lat,$long, $gk);
		list ($lat1,$long1) = $conv->national_to_wgs84($east+1500,$nort+ 500,$ri);
		list ($ge1, $gn1) = $conv->wgs84_to_gk($lat1,$long1, $gk);
		list ($lat2,$long2) = $conv->national_to_wgs84($east+ 500,$nort+1500,$ri);
		list ($ge2, $gn2) = $conv->wgs84_to_gk($lat2,$long2, $gk);
		$dx1 = $ge1 - $ge;
		$dx2 = $ge2 - $ge;
		$dy1 = $gn1 - $gn;
		$dy2 = $gn2 - $gn;
		$len1 = sqrt($dx1*$dx1+$dy1*$dy1);
		$len2 = sqrt($dx2*$dx2+$dy2*$dy2);
		$rot = rad2deg(atan2($dy1, $dx1));
		$del = rad2deg(acos(($dx1*$dx2+$dy1*$dy2)/$len1/$len2));
		$rotn = $rot / sin(deg2rad($lat));
		echo "<p>";
		echo "Source coordinates: $east / $nort ($ri) <br />";
		echo "Geogr. coordinates: $lat / $long <br />";
		echo "Dest. coordinates:  $ge / $gn <br />";
		echo "Distorsion: $len1 / $len2, $rot ($rotn), $del";
		echo "</p>";
	}
?>
</body>
</html>
