<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

//keep this at least during dev. maybe turn it cachable once out of dev!
customNoCacheHeader();

//todo, check user_stat, if no images, pointless even trying to do custmization

$CONF['API_HOST'] = "https://development.geograph.org.uk"; //live=1

$hashesUrl = $CONF['API_HOST']."/viewer/hashes.json.php";

$token=new Token;
$token->setValue("id", $USER->user_id);

$hashesUrl .= "?t=".$token->getToken();

?>
<html>
	<head>
		<title>Local File Viewer</title>

		<link rel="stylesheet" type="text/css" href="style.css?<? echo filemtime('style.css'); ?>" />

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
$.noConflict();
// Code that uses other library's $ can follow here. (something within imagehash conflicts with $)
</script>

		<script src="https://s1.geograph.org.uk/js/jquery.storage.v111.js"></script>
		<script src="exif.js"></script>
		<script type="text/javascript" src="https://s1.geograph.org.uk/mapper/geotools2.v7300.js"></script>

<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
<script type="text/javascript" src="https://s1.geograph.org.uk/js/Leaflet.MetricGrid.v8933.js"></script>
<script type="text/javascript" src="https://s1.geograph.org.uk/js/mappingLeaflet.v18291016.js"></script>

<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/QuadTree.js"></script>
<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/L.GridLayer.MaskCanvas.js"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.css?v=2" />
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>
<script src="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.js"></script>

<!--script src="https://unpkg.com/imagehash-web/dist/imagehash-web.min.js"></script-->
<script src="https://www.geograph.org/viewer/imagehash-web/dist/imagehash-web.js"></script>
<script>
let user_id = <? echo intval($USER->user_id); ?>;
let hashes_url = <? echo json_encode($hashesUrl); ?>;
</script>

		<script src="files.js?<? echo filemtime('files.js'); ?>"></script>
		<script src="viewer.js?<? echo filemtime('viewer.js'); ?>"></script>
	</head>
	<body>
		<form enctype="multipart/form-data" name="theForm" onsubmit="return false">
			File(s): <input type=file name="files" multiple accept="image/jpeg">
			and/or Folder: <input type=file name="folder" multiple accept="image/jpeg" webkitdirectory> (on some browsers)<br>

			Mode: <select name="mode" onchange="updateViewer()">
				<option value="table">Table</option>
				<option value="black">GeoRiver</option>
				<option value="thumbs">Thumbnails</option>
				<option value="map">Map</option>
			</select>
			Order: <select name="order" onchange="updateViewer(true)">
                                <option value="date up">Oldest First</option>
                                <option value="date down">Newest First</option>

                                <option value="geo">Geographical</option>
                                <option value="square">By Square</option>

                                <option value="name up">By Filename Asc</option>
                                <option value="name down">By Filename Desc</option>

                                <option value="size up">Size Asc</option>
                                <option value="size down">Size Desc</option>

			</select>

			Breaks:
			<!--label><input type=checkbox name=break1km onclick="updateViewer()" disabled>Day</label-->
			<label><input type=checkbox name=break1km checked onclick="updateViewer()">Square</label>
			<!--label><input type=checkbox name=break500m onclick="updateViewer()" disabled>500m</label-->
			<label><input type=checkbox name=break10m checked onclick="updateViewer()">10min</label>
			<label><input type=checkbox name=break10s onclick="updateViewer()">10s</label>

			<label>(<input type=checkbox name=square onclick="updateViewer()">Square Image)</label>

			<select name=method style="max-width:100px">
				<option value="">Choose Method Later</option>
				<option value="submit">Submit v1</option>
				<option value="submit2">Submit v2</option>
				<option value="submit2_tabs">Submit v2 (tabs)</option>
				<option value="close">Upload only (Submit Later)</option>
			</select>

			<div id="locations" style="display:none">
			<input type=button value="Guess Location" onclick="if (guessLocations()) {updateViewer(true);}"> - Add Locations for images without location, by picking closest in time.
			</div>
			<div id="hashes" style="display:none">
			<input type=button value="Check Hashes" onclick="if (checkHashes()) {updateViewer(true);}"> - check images against hashes
			</div>
		</form>
		<div id="message"></div>
		<div id="output">
			<ul>
				<li>Select files above - on some browsers can use the folder option to load the folder AND all subfolders<ul>
					<li>Be patient - can easily take 3/4 seconds per image to process!
					<li>No formal limit - loading 'few hundred' is probably OK, but thousends might struggle
				</ul>
				<li>Can select mutliple files at once, can also add more files later
				<li><b>The data remains on your browser, it's NOT uploaded anywhere</b>
				<li>There is no save. Once close the window/tab, processing is lost. Will have to reload the images next time
				<li>Coordiates are only loaded from the EXIF-Geo tags (not the filename)<ul>
					<li>If <b>some</b> image(s) DON'T have coordinates, can click the 'Guess' button above, to add square from the image closest in time.
					Not perfect (can be mistakes) but in general it can deal pretty well if just a small number of images are missing location, but have good timestamps.
					This is intended for when only a few images are missing location
					<li>Currently if dont have location for any of the images, this function will load the images, but matching on date alone (or the image itself) is not currently supported
				</ul>
				<li>Date/Time is loaded from EXIF, not the modfication of the file itself
				<li>When the grid-reference is shown in Yellow, means you dont appear to have submitted to that square
				<li>Reload the browser/tab to restart from scratch
			</ul>
			Limitations:
			<ul>
				<li>Only loads <b>JPEG</b> (.jpg or jpeg) files (other files in folder(s) ignored)
				<li>Currently only checks submissions against <b>Geograph Britain and Ireland</b>. (also only works with OSGB and Irish Grid)
				<li>Stats are heavily cached, and doesn't pickup new submissions right away (so wont keep track during a submission session) 
				<li>Does <b>not check for duplicates</b>, can load the same file multiple times!
				<li>Selecting a <b>folder may not work</b> on all browsers
				<li>Can't <b>remove images</b> from selection currently (once uploaded remain on the list)
				<li>Images are only matched by square, not the precise coordinates (also means it matching the exif-geo location, against subject location, which can be different)
				<li>This does include a basic 'image matching' to note if the exact image has been uploaded already
			</ul>
		</div>
		<div id="preview"></div>
	</body>
</html>
