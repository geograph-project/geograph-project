<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$smarty->display('_std_begin.tpl');

if (empty($_GET['query']))
	$_GET['query'] = 'beach';

//simplify the query a bit, in the vague hope of increasing cachablity of tiles!
$_GET['query'] = strtolower(trim(preg_replace('/\s+/',' ',$_GET['query'])));



//in theory, could just use s3vectors - directly as we dont need actual list, we just want raw vector, but the imagelist class has the filtering that works on the image index!

                $filesystem = new FileSystem(); //sets up configuation automagically
                //the vector lib needs S3 class setup already!

                //note, if switich to imagelistknn, will have to make sure getKNNResults returns the lat/long!
                require_once('geograph/imagelists3vector.class.php');
                $imagelist=new ImageListS3Vector;

$extra = "&query=".urlencode($_GET['query']); //&max=...
$criteria = array('label' => $_GET['query']);

if (!empty($_GET['user_id']))
        $extra .= "&user_id=".intval($_GET['user_id']);
elseif (!empty($_GET['mine']))
        $extra .= "&user_id=".intval($USER->user_id);
//for lets NOT add this to critiera?

$results = $imagelist->getRawVectorsByCriteria($criteria, 30, false); //dont need metadata, only the distances!

if (!empty($results['vectors'])) {
	$last = count($results['vectors'])-1;
	$dist = $results['vectors'][$last]['distance'];
	if (!empty($_GET['sq'])) {
		$dist = sqrt($dist);
	} elseif (!empty($_GET['pw'])) {
		$dist = pow($dist,2);
	}
	$dist = round($dist,5);

	$extra .= "&max=$dist";
	print "<p>Query: <b>".htmlentities($_GET['query'])."</b>. Max Distance: $dist</p>";

} else {
	die("no results - vector query posisibly failed");
}

#####################################

?>

<div id="map" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

        <script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

        function loadmap() {

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(
		var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
                overlayMaps["Geograph Coverage"] = coverageCoarse;


		//this is our special layer (coverage included above, just for reference)

		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://development.geograph.org.uk/tile/tile-vectors.php?z={z}&x={x}&y={y}<? echo $extra; ?>';
                var vectorLayer = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 18, attribution: layerAttrib, bounds: bounds, opacity:0.6});
                overlayMaps["Similarity Matches"] = vectorLayer;

		setupBaseMap(); //creates the map, but does not initialize a view
		overlayMaps["Similarity Matches"].addTo(map);
		map.fitBounds(bounds,{maxZoom:15});

		var hash = new L.Hash(map);


        // --- New: Add click event to map to show lat/long in a popup ---
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6); // Format to 6 decimal places
            const lng = e.latlng.lng.toFixed(6); // Format to 6 decimal places

		var p1 = map.containerPointToLatLng([window.innerWidth/2, window.innerHeight/2]);
		var p2 = map.containerPointToLatLng([(window.innerWidth/2) + 40, (window.innerHeight/2) + 40]);
		var dist = p1.distanceTo(p2).toFixed(0);

            L.popup()
                .setLatLng(e.latlng)
                .setContent(`<a href="/ai/clip-query.php?query=<? echo urlencode($_GET['query']); ?>&amp;loc=${lat},${lng}&amp;dist=${dist}" target="_blank">View Thumbnail Results</a>`)
                .openOn(map);
        });

        }
        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');


