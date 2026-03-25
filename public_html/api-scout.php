<?php

//At the moment, this is still a Prototype API

require_once('geograph/global.inc.php');

$db = GeographDatabaseConnection(false);
$where = array();

if (!empty($_GET['types'])) {
	customExpiresHeader(3600*24, true,true);

	$sql = "select feature_type_id as id,title from feature_type where status = 1 AND licence != 'none'";
	$data = $db->getAll($sql);

} elseif (!empty($_GET['hectad'])) {

	//lots of ways this could be done, using hactad_stat makes it fail fast for unknown areas

        $row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($_GET['hectad']));

        if (!empty($row['map_token'])) {

		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

                $ri = $row['reference_index'];
                $x = ( intval(($row['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
                $y = ( intval(($row['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

		//this gets coordinates of southwest corner, which is fine
		list($e1,$n1) =  $conv->internal_to_national($x,$y,$row['reference_index'],0); //no fudge to center of square
		list($e2,$n2) =  $conv->internal_to_national($x+10,$y+10,$row['reference_index'],0);

		$where[] = "e between $e1 AND $e2"; //technically should be <$e2, not <=$e2 with between, but doesnt really matter
		$where[] = "n between $n1 AND $n2";
		$where[] = "reference_index = {$row['reference_index']}";

                //todo, switch to point_en index! (needs creating)

        } else {
		//this isnt likly to change!
		customExpiresHeader(3600*24*30, true,true);

		//$data = array(['error'] => "unknown hectad");
		$data = array(); //cleaner to return an empty array in JSON
        }

} elseif (!empty($_GET['lat'])) {
	$lat = floatval($_GET['lat']);
	$lng = floatval($_GET['lng']);

	$point = "POINT($lng, $lat)";
	$radius_km = 30;

	//-- 1 degree of Lat is ~111km. 1 degree of Lng varies,
	//-- but at UK latitudes (55°N), it's roughly 64km.
	//-- We create a bounding box roughly 0.3 to 0.5 degrees wide.
	$db->Execute("SET @bbox = ST_Envelope(ST_Buffer($point, $radius_km / 111))");

	$where[] = "ST_Within(point_ll, @bbox)";
}

if (!empty($where)) {
	customExpiresHeader(3600*24, true,true);

	$ids = implode(',',$db->getCol("select feature_type_id,title from feature_type where status = 1 AND licence != 'none'"));
	$where[] = "nearby_images between 0 and 4"; //want to avoid null
	$where[] = "feature_type_id IN ($ids)";

	$where = implode(" AND ",$where);
	$data = $db->getAll("SELECT
	    feature_item_id AS id,
	    name AS n,
	    wgs84_lat AS lt,
	    wgs84_long AS lg,
	    feature_type_id AS t,
	    nearby_images as c
	FROM feature_item
	WHERE $where
	LIMIT 1000");

	if (empty($data))
		$data = array(); //cleaner to return an empty array in JSON
}

outputJSON($data);

