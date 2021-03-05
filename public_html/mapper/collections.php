<?

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);

$limit = 2000;
$graydiv = 15000;
$order = "stddev(gs.x) desc";

$types = array('centoid'=>'Center of Gravity','hectad'=>'All Hectads');//,'square'=>'Choosen Square','image'=>'Featured Image');
$sources = array('article'=>'Articles','gallery'=>'Galleries','themed'=>'Themed Topics','snippet'=>'Shared Descriptions');

if (empty($CONF['forums']))
	unset($sources['themed']);

if (empty($_GET['type']) || !isset($types[$_GET['type']]))
	$_GET['type'] = 'centoid';

if (empty($_GET['source']) || $_GET['source'] == 'article') {
	$gc_table = "gridimage_content gc using (content_id)";
	$_GET['source'] = 'article';
} elseif ($_GET['source'] == 'themed') {
	$gc_table = "gridimage_post gc on (c.foreign_id = gc.topic_id and c.source = 'themed')";
} elseif ($_GET['source'] == 'gallery') {
	$gc_table = "gridimage_post gc on (c.foreign_id = gc.topic_id and c.source = 'gallery')";
} elseif ($_GET['source'] == 'gsd') {
	$gc_table = "gridimage_post gc on (c.foreign_id = gc.topic_id and c.source = 'gsd')";
} elseif ($_GET['source'] == 'snippet') {
	$gc_table = "gridimage_snippet gc on (c.foreign_id = gc.snippet_id and c.source = 'snippet')";
	$order = "stddev(gs.x) asc"; //there are too many that dont need to worry about showing bigger sircles first!
} else {
	die("huh");
}

$source = $db->Quote($_GET['source']);

if ($_GET['type'] == 'hectad') {
	ini_set('memory_limit', '128M');
	$limit = 2000;
	$graydiv = 3000;

	/*
	$result = $db->getAll("SELECT c.content_id, url, c.title, count(gc.gridimage_id) as images, avg(gs.x) ax, avg(gs.y) ay, stddev(gs.x) sx, stddev(gs.y) sy
			,CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1)) AS hectad
		from content c inner join $gc_table inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
		where c.type != 'document'
		group by hectad,content_id order by $order limit $limit");
	*/

	$order = str_replace('stddev(gs.x)', 'sx', $order);
	$result = $db->getAll("SELECT c.content_id, url, c.title, cl.images, ax, ay, sx, sy, hectad
		FROM content_location cl INNER JOIN content c USING (content_id)
		WHERE c.type != 'document' AND source = $source
		ORDER BY $order LIMIT $limit");


} elseif ($_GET['type'] == 'centoid') {

	/*
	$result = $db->getAll("SELECT c.content_id, url, c.title, count(gc.gridimage_id) as images, avg(gs.x) ax, avg(gs.y) ay, stddev(gs.x) sx, stddev(gs.y) sy
		from content c inner join $gc_table inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
		where c.type != 'document'
		group by content_id order by $order limit $limit");
	*/

	$order = str_replace('stddev(gs.x)', 'sx', $order);
	$result = $db->getAll("SELECT c.content_id, url, c.title, c.images, AVG(ax) AS ax, AVG(ay) AS ay, SUM(sx) AS sx, SUM(sy) AS sy
		FROM content_location cl INNER JOIN content c USING (content_id)
		WHERE c.type != 'document' AND source = $source
		GROUP BY content_id
		ORDER BY $order LIMIT $limit");



} elseif ($_GET['type'] == 'square') {
	die("no longer available. Sorry");

	/*
	$result = $db->getAll("SELECT c.content_id, url, c.title, count(gc.gridimage_id) as images, g.x ax, g.y ay, stddev(gs.x) sx, stddev(gs.y) sy
		from content c inner join $gc_table inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
			inner join gridsquare g using (gridsquare_id)
		where c.type != 'document'
		group by content_id order by $order limit $limit");
	*/

} elseif ($_GET['type'] == 'image') {
	die("no longer available. Sorry");

	/*

	$result = $db->getAll("SELECT c.content_id, url, c.title, count(gc.gridimage_id) as images, gi.x ax, gi.y ay, stddev(gs.x) sx, stddev(gs.y) sy
		from content c inner join $gc_table inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
			inner join gridimage_search gi on (gi.gridimage_id = c.gridimage_id)
		where c.type != 'document'
		group by content_id order by $order limit $limit");
	*/
}


$smarty->display("_std_begin.tpl");
?>

<h2>Collections on a Map - <span style=color:red>Experimental!</span></h2>

<p>Source:
<?
$type = urlencode($_GET['type']);
$source = urlencode($_GET['source']);
foreach($sources as $key => $title) {
        if ($_GET['source'] == $key) {
                print "<b>$title</b> &middot; ";
        } else {
                print "<a href=?type=$type&amp;source=$key>$title</a> &middot; ";
        }
}
?></p>
<p>Plot by:
<? foreach($types as $key => $title) {
        if ($_GET['type'] == $key) {
                print "<b>$title</b> &middot; ";
        } else {
                print "<a href=?type=$key&amp;source=$source>$title</a> &middot; ";
        }
}

print " <i>".count($result)." collections loaded</i>";

?></p>


    <div id="titlePreview" style="font-size:2em"></div>
    <div id="map" style="width: 800px; height: 800px;"></div>
    <script>

var map;

function createMarker(map, lat, lng, radius, title, url, id) {
	if (radius > 1000 && radius<25000) {
        var circle = new google.maps.Circle({
            strokeColor: '#FF0000',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#FF0000',
            fillOpacity: 0.0001,
            map: map,
            center: {lat: lat, lng: lng},
            radius: radius
          });
	}
        var marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
	    strokeColor: radius<<? echo $graydiv; ?>?'black':'gray',
            scale: 4
          },
          draggable: false,
          clickable: true,
	  title: title,
          map: map
        });

        var infowindow = new google.maps.InfoWindow({
	    content: "<b>"+title+"</b><br><a href='"+url+"'>View Article</a> "+
		"<a href='/browser/#/content_title="+encodeURIComponent(title)+"/content_id="+id+"/display=map_dots/pagesize=100'>View images</a>"
	});

	marker.addListener('click', function() {
	    infowindow.open(map, marker);
	});
	marker.addListener('mouseover', function() {
		document.getElementById('titlePreview').innerText = title;
	});

	return marker;
}

      function initMap() {

        map = new google.maps.Map(document.getElementById('map'), {
          zoom: 6,
          center: {lat: 54.9, lng: -3.8},
        });

<?
	$conv = new Conversions;
	foreach ($result as $row) {
		list($lat,$long) = $conv->internal_to_wgs84($row['ax'],$row['ay'],0,0);
		$title = json_encode($row['title']);
		$url = json_encode($row['url']);
		$r = intval(($row['sx']+$row['sy'])*500); //units is meters
		if (!empty($lat) && !empty($title))
			print "createMarker(map,{$lat},{$long},$r,$title,$url,{$row['content_id']});\n";
	}
?>


      }
      document.body.onload = initMap;
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3&amp;key=<? echo $CONF['google_maps_api3_key']; ?>"></script>

<?

$smarty->display("_std_end.tpl");



