{include file="_basic_begin.tpl"}

<h2>Geograph Coverage Map (v4)</h2>

	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.css" />

	<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.css?v=2" />

	<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.GeographClickLayer.css" />

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.css" />

		<div id="map" style="width:100%; height:100%; max-width:1024px; max-height:800px"></div>
		<div id="message" style="z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8"></div>
		<div id="gridref" style="z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;"></div>

	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.js" charset="utf-8"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.js?v=2"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographClickLayer.mobile.js"></script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.js"></script>
	<script src="https://www.geograph.org/leaflet/Leaflet.GeographGeocoder.js"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>{literal}

	var mapOptions =  {
                center: [54.4266, -3.1557], zoom: 13,
                minZoom: 6, maxZoom: 18
        };

{/literal}
{dynamic}
	{if $gridref}
		 var wgs84=new GT_WGS84();
                 wgs84 = wgs84.parseGridRef('{$gridref}'); //technically a factory method

                 if (wgs84)
                          mapOptions.center = L.latLng( wgs84.latitude, wgs84.longitude );
	{/if}
{/dynamic}
{literal}

	var map = L.map('map', mapOptions);
        var hash = new L.Hash(map);

	L.control.locate().addTo(map);

        map.addLayer(baseMaps["OpenStreetMap"]); //todo, make this configure like in mappingLeaflet.js
        map.addLayer(overlayMaps["OSGB Grid"]);
        map.addLayer(overlayMaps["Irish Grid"]);

        map.addLayer(overlayMaps["Coverage - Close"]);

	if (baseMaps["Geograph PhotoMap"]) delete baseMaps["Geograph PhotoMap"];
	if (baseMaps["Watercolour"]) delete baseMaps["Watercolour"];
	if (overlayMaps["PhotoMap Overlay"]) delete overlayMaps["PhotoMap Overlay"];

{/literal}
{dynamic}
	{if $stats && $stats.images}
		overlayMaps["(Personalize Coverage)"].options.user_id = {$stats.user_id};
                overlayMaps["(Personalize Coverage)"].options.minZoom = 5;
                        //todo - optionally could add to map now to personalize the layers!
	{else}
		 delete overlayMaps["(Personalize Coverage)"];
	{/if}
{/dynamic}
{literal}
	
	L.control.layers(baseMaps, overlayMaps).addTo(map);

	map.on('baselayerchange', function(e) {
		var color = (e.name.indexOf('Imagery') > -1)?"#fff":"#00f";
		var opacity = (e.name.indexOf('Imagery') > -1)?0.8:0.3;
		for(i in overlayMaps) {
			if (i.indexOf('Grid') > 0) {
				overlayMaps[i].options.color = color;
				overlayMaps[i].setOpacity(opacity);
				overlayMaps[i]._reset();
			}
		}
	});

	map.addControl(L.geographGeocoder());

	map.addLayer(L.geographClickLayer());

{/literal}</script>


{include file="_basic_end.tpl"}
