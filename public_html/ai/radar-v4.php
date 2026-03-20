<?php

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm('basic');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Geograph Scout - Dev Prototype</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


    <link rel="stylesheet" href="<?php echo smarty_modifier_revision("/js/leaflet-search-master/src/leaflet-search.css"); ?>">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.67.0/dist/L.Control.Locate.min.css">
    <!--link rel="stylesheet" href="<? echo smarty_modifier_revision("/js/Leaflet.GeographClickLayer.css"); ?>"-->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.0/proj4.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/L.Control.Locate.js"); ?>"></script>

    <script src="<?php echo smarty_modifier_revision("/js/leaflet-search-master/src/leaflet-search.js"); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographGeocoder.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographRecentUploads.js"); ?>"></script>
    <!--script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographClickLayer.js"); ?>"></script-->

    <script src="/js/Leaflet.GeographScout.js?<? echo filemtime(__DIR__.'/../js/Leaflet.GeographScout.js'); ?>"></script>
    <script src="/js/Leaflet.GeographCameraButton.js?<? echo filemtime(__DIR__.'/../js/Leaflet.GeographCameraButton.js'); ?>"></script>
    <script src="/js/Leaflet.enhanceButton.js?<? echo filemtime(__DIR__.'/../js/Leaflet.enhanceButton.js'); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
    <style>
        body { margin: 0; display: flex; flex-direction: column; height: 100vh; font-family: sans-serif; }
        #controls { padding: 15px; background: #2c3e50; color: white; display: flex; gap: 20px; flex-wrap: wrap; }
        #map { flex-grow: 1; }
        .status-bar { background: #ecf0f1; padding: 5px 15px; font-size: 0.8em; border-bottom: 1px solid #ccc; }
	label { white-space: nowrap; }
    </style>
</head>
<body>

<div class="status-bar" id="status">Click the Pin icon to snap to your location</div>
<div id="map"></div>

<script>
                var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

    var mapOptions = {
	maxBounds: bounds,
        minZoom: 10,
	maxZoom: 16,
        attributionControl:false //we add our own manually!
    };



    // --- CONFIG & STATE ---
    const map = L.map('map', mapOptions).addControl(
                        L.control.attribution({ position: 'bottomright', prefix: ''}) );

// Add this guard immediately after creating the map object
// ... because map starts non-centerd, accidental dragging of the map breaks it due to uncaught exception, this guards against that!
map.on('mousedown dragstart', function(e) {
    if (!map.getCenter()) {
        // If no center is set, stop the event from bubbling
        // to the internal Leaflet handlers like _onUp
        L.DomEvent.stopPropagation(e);
        return false;
    }
});

    const photoHistory = new L.FeatureGroup().addTo(map);

//.setView([57.4, -2.9], 11);
    var osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {minZoom: 3, maxZoom: 18, attribution: osmAttrib}).addTo(map);

    let userMarker = L.circleMarker([0,0], {color: 'blue', radius: 8, fillOpacity: 0.8}).addTo(map);
    let geographScout = null;

/////////////////////////////////////////////
// stolen from mappingLeaflet.js

	let geocoder;
        const issubmit = false;

        var baseMaps = {};
        var overlayMaps = {};

                if (L.GeographRecentUploads)
                        overlayMaps["Recent Uploads"] = L.geographRecentUploads();

                // dots layer
                var layerUrl='https://t0.geograph.org.uk/tile/tile-density.php?z={z}&x={x}&y={y}&match=&l=1&6=1';
                var layerAttrib='&copy; Geograph Project';
                overlayMaps['Photo Subjects'] = new L.TileLayer(layerUrl, {minZoom: 6, maxZoom: 18, attribution: layerAttrib, bounds: bounds, opacity: 0.7});

                if (L.britishGrid) {
                        var gridOptions = {
                                opacity: 0.3,
                                weight: 0.7,
                                showSquareLabels: [100000,10000,100]
                        };

                        overlayMaps['OSGB Grid'] = L.britishGrid(gridOptions);
                        overlayMaps['Irish Grid'] = L.irishGrid(gridOptions);
                        if (!issubmit) {
                                overlayMaps['OSGB Grid'].addTo(map);
                                overlayMaps['Irish Grid'].addTo(map);
                        }
                }

                if (L.geographGeocoder && !geocoder)
                        map.addControl(geocoder = L.geographGeocoder());

                if (L.control.locate) {
                        L.control.locate({
                                keepCurrentZoomLevel: [13,18],
                                locateOptions: {
                                        maxZoom: 13,
                                        enableHighAccuracy: true
                        }}).addTo(map).start();
		}

                //actully no. Requires juery!
//                if (L.GeographClickLayer)
  //                  clickLayer = L.geographClickLayer().addTo(map);

		if (L.geographCameraButton)
			cameraButton = L.geographCameraButton({historyPoints: photoHistory}).addTo(map);


/////////////////////////////////////////////

		// 1. Initialize the plugin
		geographScout = L.geographScout({
		    user_id: <? echo intval($USER->user_id); ?>,
		    apiUrl: '/api-scout.php'
		});


		overlayMaps['Geograph Scout'] = geographScout;
		geographScout.addTo(map);

	 L.control.layers(baseMaps,overlayMaps).addTo(map);

		L.control.enhanceButton({ position: 'topright' }).addTo(map);

		L.control.opacityMenu({ baseMaps, overlayMaps, position: 'topright'}).addTo(map);

/////////////////////////////////////////////

</script>
</body>
</html>
