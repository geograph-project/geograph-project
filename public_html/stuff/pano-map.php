<?

require_once('geograph/global.inc.php');

######################

if (!empty($_GET['csv'])) {
	$db = GeographDatabaseConnection(true);

	$where = array();
	$where[] = "original_width > 640";
	$where[] = "prefix = 'panorama'";
	$where[] = "vlat > 1";
	$limit = 20;
	if (!empty($_GET['limit']))
		$limit = min(100,intval($_GET['limit']));

	if (!empty($_GET['tag']) && $_GET['tag'] == 360)
		$where[] = "tag IN ('360','photosphere')"; //photosphere are still 360!
	elseif (!empty($_GET['tag']) && preg_match('/^\w+$/',$_GET['tag']))
		$where[] = "tag = ".$db->Quote($_GET['tag']);

	if (!empty($_GET['user_id']))
		$where[] = "g.user_id = ".intval($_GET['user_id']);


	$where = implode(' AND ',$where);

	$rows = $db->getAll($sql = "
	select distinct gridimage_id as id, vlat as latitude, vlong as longitude, concat(title,' by ',realname) as name, tag as type
	 from gridimage_size s inner join gridimage_search g using (gridimage_id) inner join tag_public t using (gridimage_id)
	 where $where
	 order by imagetaken desc limit $limit
	");

	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"pano.csv\"");
        $f = fopen("php://output", "w");

	fputcsv($f, array('id','latitude','longitude','name','type','color','url'));

	$colors = array('673ab7','607d8b','ff5722');
	$tags = array('photosphere','360','wideangle');

	foreach ($rows as $row) {

		$row['color']  = $colors[array_search($row['type'],$tags)];
		$row['url'] = "/pano.php?id={$row['id']}";

		fputcsv($f, $row);
	}
	exit;
}

#######################

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link href="https://www.nearby.org.uk/voronoi-map/examples/gaz250/base.css" rel="stylesheet" />
<link href='https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css' rel='stylesheet' />
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <div id='map'>
  </div>
  <div id='selections' class="selections">
    <a href='#' class="show">Choose what markers to display</a>
    <div class='content'>
      <a href='#' class="hide">Hide</a>
      <div id="toggles">
      </div>
    </div>
  </div>
  <div id='loading'>
  </div>
  <div id='selected'>
    <h1>Panos</h1>
  </div>
  <div id='about'>
    <a href='#' class="show">About</a>
    <p class='content'>
      <a href='https://www.mapbox.com/about/maps/' target='_blank'>Mapbox and OpenStreetMap</a>.
      <a href='#' class="hide">Hide</a>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.4.8/d3.min.js"></script>
  <script src="https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js"></script>
  <script src="https://www.nearby.org.uk/voronoi-map/lib/voronoi_map-fixed.js"></script>
  <script>
    L.mapbox.accessToken = 'pk.eyJ1IjoiemV0dGVyIiwiYSI6ImVvQ3FGVlEifQ.jGp_PWb6xineYqezpSd7wA';
    map = L.mapbox.map('map')
      .addLayer(L.mapbox.styleLayer('mapbox://styles/zetter/ckudv86zx659017mw9km17j4u'))
      .fitBounds([[59.355596 , -9.052734], [49.894634 , 3.515625]]);

    url = '?csv=1&<? echo str_replace('&amp;','&',htmlentities($_SERVER['QUERY_STRING'])); ?>';
    initialSelection = d3.set(['photosphere','360','wideangle']);
    voronoiMap(map, url, initialSelection);

    map.fire('ready'); //seems leaflet doesnt fire automatically
  </script>
</body>
</html>

