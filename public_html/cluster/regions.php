<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (!empty($_POST['name'])) {


	$db = GeographDatabaseConnection(false);

	$bits = explode(',',$_POST['bbox']);
	//'southwest_lng,southwest_lat,northeast_lng,northeast_lat'
	$area = floatval(($bits[3]-$bits[1])*($bits[2]-$bits[0]));

	$ins = "INSERT INTO cluster_region SET
        name = ".$db->Quote($_POST['name']).",
        bbox = ".$db->Quote($_POST['bbox']).",
        `area` = ".$db->Quote($area).",
        points = ".$db->Quote($_POST['points']).",
	created = NOW(),
        user_id = ".intval($USER->user_id);

	$db->Execute($ins);
} elseif (!empty($_GET['delete'])) {
        $db = GeographDatabaseConnection(false);

	$sql = "UPDATE cluster_region SET active=0 WHERE region_id = ".intval($_GET['delete'])." AND user_id = ".intval($USER->user_id);
	$db->Execute($sql);

} elseif (!empty($_GET['update'])) {
        $db = GeographDatabaseConnection(false);

	$sql = "UPDATE cluster_region SET name = ".$db->Quote($_GET['name'])." WHERE region_id = ".intval($_GET['update'])." AND user_id = ".intval($USER->user_id);
	$db->Execute($sql);
}


?>
<html>
<head>
	<title>Geograph Friendly Area Names</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <!--script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script-->
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>



    <script src="https://leaflet.github.io/Leaflet.draw/src/Leaflet.draw.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/Leaflet.Draw.Event.js"></script>
    <link rel="stylesheet" href="https://leaflet.github.io/Leaflet.draw/src/leaflet.draw.css" />

    <script src="https://leaflet.github.io/Leaflet.draw/src/Toolbar.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/Tooltip.js"></script>

    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/GeometryUtil.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/LatLngUtil.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/LineUtil.Intersect.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/Polygon.Intersect.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/Polyline.Intersect.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/ext/TouchEvents.js"></script>

    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/DrawToolbar.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Feature.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.SimpleShape.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Polyline.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Marker.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.CircleMarker.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Circle.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Polygon.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/draw/handler/Draw.Rectangle.js"></script>

    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/EditToolbar.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/EditToolbar.Edit.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/EditToolbar.Delete.js"></script>

    <script src="https://leaflet.github.io/Leaflet.draw/src/Control.Draw.js"></script>

    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.Poly.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.SimpleShape.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.Marker.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.CircleMarker.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.Circle.js"></script>
    <script src="https://leaflet.github.io/Leaflet.draw/src/edit/handler/Edit.Rectangle.js"></script>


	<script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
</head>


<body>

<h2>Geograph Friendly Area Names</h2>
<p>The goal of this page is to create a database of <b>very approximate</b> areas, using relatively familar names. The idea being would know roughly where the area is. </p>

<p>If you want to add a area to the database, click the polygon icon top left of the map. Click on the map to add points.
Click on the start point to 'finish' the shape. You will then be able to Name and save the shape to the database.
(TIP: can turn off the current areas via the Layer Switcher, to make it easier to seee the map to draw your own) 

<p>The Goal it to have LOTS of different and overlapping areas, so that can pick the best match from the shapes. 
Do not need to trace or define the border exactly. The shape should eb slightly larger (to encompose) than the actual area,
 to make sure it covers it completely. We are looking for different size areas, that people would have a fair idea of its general area, 
so things like 'Snowdonia' and 'Norfolk Broads' equally useful as 'Wales'. 



<div id="map" style="width:900px; height:900px; max-width:80vw; max-height:80vh"></div>

<script>

        var map = null ;
        var issubmit = false;
        var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

	var layer;

function loadmap() {

/////////////////////////////////////

                var mbToken = 'pk.eyJ1IjoiZ2VvZ3JhcGgiLCJhIjoiY2lteXI3cmlpMDBmenY5bTF5dHFqMnh0NiJ9.sPXF2s1niWNNEfqGjs2HGw';
                var mbAttr = 'Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                                '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                                'Imagery &copy; <a href="https://mapbox.com">Mapbox</a>',
                mbUrl = 'https://api.mapbox.com/styles/v1/{id}/tiles/256/{z}/{x}/{y}?access_token=' + mbToken + '&';

                baseMaps["MapBox Grayscale"] = L.tileLayer(mbUrl, {id: 'geograph/ckxte5u8hucaf15ns8hz5ucmd', attribution: mbAttr, minZoom: 1, maxZoom: 18});

/////////////////////////////////////

     // FeatureGroup is to store editable layers
     var drawnItems = new L.FeatureGroup();

<?
if (empty($db))
	$db = GeographDatabaseConnection(true);

$data = $db->getAll("SELECT *,round(log(1+sqrt(area),2)) as scale FROM cluster_region WHERE active=1 order by area desc");
$scales= array();

$last = count($data)-1;
print "var color = d3.scale.category20().domain(d3.range([0, $last]));\n";

foreach ($data as $idx=>$row) {
	//LatLng(55.37911, -7.207031),LatLng(53.357109, -10.327148),La ...
	if ($row['active'] && preg_match('/^(LatLng\(-?\d+\.\d+,\s*-?\d+\.\d+\),?)+$/', $row['points'])) {

		if (empty($scales[$row['scale']])) {
			print "drawnItems[{$row['scale']}] = new L.FeatureGroup();\n";
			$scales[$row['scale']] = 1;
		}

		print "L.polygon([".str_replace('LatLng','L.latLng',$row['points'])."], {title:".json_encode($row['name']).", color:color($idx)})";
		print ".addTo(drawnItems[{$row['scale']}]); \n\n";
	}
}

foreach ($scales as $scale =>$dummy) {
?>
                drawnItems[<? echo $scale; ?>].bindTooltip(function(layer) {
                        if (layer.options && layer.options.title)
                                return layer.options.title.toString();
                        else
                                return 'untitled';
                });

	overlayMaps['Current Areas (Scale <? echo $scale; ?>)'] = drawnItems[<? echo $scale; ?>];
<? } ?>
/////////////////////////////////////

        setupBaseMap(); //creates the map, but does not initialize a view

	map.setView([54.4266, -3.1557], 5);

<? foreach ($scales as $scale =>$dummy) {
        print "map.addLayer(drawnItems[$scale]);\n";
} ?>

     var drawControl = new L.Control.Draw({
	draw: {
             polygon: true,
		polyline: false,
		rectangle: false,
		circle: false, 
		circlemarker: false, 
             marker: false
         }
     });

map.on(L.Draw.Event.CREATED, function (e) {
   var type = e.layerType;
   layer = e.layer;

   map.addLayer(layer);

layer.bindPopup('<form method=post>Provide Name for your Area: <input type=text name=name placeholder="enter name here" maxlength=64><br>'+
'<input type=hidden name=points value="'+layer.getLatLngs().toString()+'">'+
'<input type=hidden name=bbox value="'+layer.getBounds().toBBoxString()+'">'+
'<input type=submit value="Save New Area"></form>');
layer.openPopup();

});

     map.addControl(drawControl);

/////////////////////////////////////
}





        //AttachEvent(window,'load',loadmap,false);
        $(function() {
                 loadmap() ;
        });

	function renameArea(region_id,region_name) {
		var result = prompt('Edit the name as required:', region_name);
		if (result) {
			location.href='?update='+region_id+'&name='+encodeURIComponent(result);
		}
		return false;
	}
</script>

Current List: <ul>
<?

foreach ($data as $row) {
	print "<li>".htmlentities($row['name']);
	if ($row['user_id'] == $USER->user_id) {
		print " <a href=# onclick='return renameArea({$row['region_id']},".json_encode($row['name']).");'>Rename</a>";
		print " <a href=\"?delete={$row['region_id']}\" onclick='return confirm(".json_encode("Are you sure you wish to delete {$row['name']}?").");'>Delete</a>";
	}
}
?>
</ul>

</html>
