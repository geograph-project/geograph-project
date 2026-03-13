<?php

//At the moment, this is still a Prototype API

require_once('geograph/global.inc.php');

customExpiresHeader(3600*24, true,true);

$db = GeographDatabaseConnection(false);

if (!empty($_GET['types'])) {
	$sql = "select feature_type_id as id,title from feature_type where status = 1 AND licence != 'none'";
	$data = $db->getAll($sql);

} elseif (!empty($_GET['lat'])) {
	$lat = floatval($_GET['lat']);
	$lng = floatval($_GET['lng']);

	$point = "POINT($lng, $lat)";
	$radius_km = 30;

	//-- 1 degree of Lat is ~111km. 1 degree of Lng varies, 
	//-- but at UK latitudes (55°N), it's roughly 64km.
	//-- We create a bounding box roughly 0.3 to 0.5 degrees wide.
	$db->Execute("SET @bbox = ST_Envelope(ST_Buffer($point, $radius_km / 111))");

	$ids = implode(',',$db->getCol("select feature_type_id,title from feature_type where status = 1 AND licence != 'none'"));

	$data = $db->getAll("SELECT
	    feature_item_id AS id,
	    name AS n,
	    wgs84_lat AS lt,
	    wgs84_long AS lg,
	    feature_type_id AS t
	FROM feature_item
	WHERE
	    ST_Within(point_ll, @bbox)
	    AND nearby_images = 0
	    AND feature_type_id IN ($ids)
	LIMIT 1000");

	if (empty($data))
		$data = array(); //cleaner to return an empty array in JSON
}

outputJSON($data);

