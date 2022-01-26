<html style="margin:0">
<head>
<title>Search Results</title>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
</head>
<body style="margin:0;padding:0">


{if $engine->resultCount}
<style>{literal}
#map {
	background-color:white;
	color:black;
}
#map a {
	color:inherit;
}
{/literal}</style>
	{if $engine->results}

	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="{"/js/mappingLeaflet.css"|revision}" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.css?v=2" />

	<div style="position:relative; width:800px; height:600px; max-width:100vw; max-height:100vh">
		<div id="map" style="width:800px; height:600px; max-width:100vw; max-height:100vh"></div>
		<div id="message" style="z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8"></div>
		<div id="gridref" style="z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;"></div>
	</div>

	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/QuadTree.js"></script>
	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/L.GridLayer.MaskCanvas.js"></script>

        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>
        <script src="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.js"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

<script>
	{if $os_api_key}
		 var OSAPIKey = "{$os_api_key}";
	{else}
		 var OSAPIKey = null;
	{/if}
</script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="{"/js/jquery.storage.js"|revision}"></script>
	
{literal}
<script type="text/javascript">

var map = null;
var totalImages = 0;

var layerData = new Array;
var messageTimer = null;
var fallbackLayer = false;

function loadMap() {

	        var mapOptions =  {
	              //  center: [54.4266, -3.1557], zoom: 13,
//		        minZoom: 5, maxZoom: 21
	        };
	        map = L.map('map', mapOptions);
	        //var hash = new L.Hash(map);

		//////////////////////////////////////////////////////

		if ($.localStorage && $.localStorage('LeafletBaseMap')) {
			basemap = $.localStorage('LeafletBaseMap');
			if (baseMaps[basemap] && basemap != "Ordnance Survey GB" && (
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
				))
				map.addLayer(baseMaps[basemap]);
			else
				map.addLayer(baseMaps["OpenStreetMap"]);
		} else if (baseMaps['Modern OS - GB']) { // && ri=1
			map.addLayer(baseMaps['Modern OS - GB']);
		} else {
			map.addLayer(baseMaps["OpenStreetMap"]);
		}
		if ($.localStorage) {
			map.on('baselayerchange', function(e) {
				if (!fallbackLayer)
					$.localStorage('LeafletBaseMap', e.name);
			});

			map.on('zoom', function(e) {
				var currentbase = $.localStorage('LeafletBaseMap'); //leaflet doesnt have a easy way to find the base layer, but the Layers control fires an event which we store!
				if (currentbase != "OpenTopoMap" && baseMaps[currentbase] && baseMaps[currentbase].options && baseMaps[currentbase].options.minZoom) {
					if (baseMaps[currentbase].options.crs || currentbase == 'Modern OS - GB')
						return; // the current basemap has a non-standard crs!
					if (map.getZoom() < baseMaps[currentbase].options.minZoom) {
						if (!fallbackLayer) { // might already be added!
							fallbackLayer = "OpenTopoMap";
							map.addLayer(baseMaps[fallbackLayer]);
						}
					} else if (map.getZoom() > baseMaps[currentbase].options.maxZoom) {
						if (!fallbackLayer) { // might already be added!
							fallbackLayer = "OpenStreetMap";
							map.addLayer(baseMaps[fallbackLayer]);
						}
					} else {
						if (fallbackLayer) {
							map.removeLayer(baseMaps[fallbackLayer]);
							fallbackLayer = false;
						}
					}
				}
			});
		}

		map.addLayer(overlayMaps["OS National Grid"]);

		//////////////////////////////////////////////////////

		if (L.GridLayer && L.GridLayer.MaskCanvas) {
			overlayMaps['Search Points'] = new L.GridLayer.MaskCanvas({noMask:true, radius: 2, useAbsoluteRadius: false });
			map.addLayer(overlayMaps['Search Points']);
		}

		overlayMaps['Search Results'] = L.photo.cluster({maxClusterRadius:80, showCoverageOnHover: true, spiderfyDistanceMultiplier: 2}).on('click', function (evt) {
			var photo = evt.layer.photo,
				template = '<a href="{link}" target=newwin><img src="{url}"/></a><p>{caption}</p>';

			evt.layer.bindPopup(L.Util.template(template, photo), {
				className: 'leaflet-popup-photo',
				minWidth: 300
			}).openPopup();
		}).addTo(map);


		addOurControls(map)

		//////////////////////////////////////////////////////

		var rows = [];

		//this format is emulating the 'json' feed format. So both can just be passed to addRows!
{/literal}{foreach from=$engine->results item=image}
		{literal}rows.push({{/literal}
			title: {"`$image->grid_reference` : `$image->title`"|latin1_to_utf8|json_encode},
			author: {$image->realname|latin1_to_utf8|json_encode},
			lat: {$image->wgs84_lat},
			long: {$image->wgs84_long},
			link: "/photo/{$image->gridimage_id}",
			thumb: {$image->getThumbnail(120,120,true)|json_encode},
			guid: {$image->gridimage_id}
		{literal}});{/literal}
{/foreach}

		addRows(rows);

                {if $markers}
                        {foreach from=$markers item=marker}
				L.marker([{$marker.1}, {$marker.2}], {literal}{{/literal}title: {$marker.0|json_encode}, draggable: false {literal}}{/literal}).addTo(map);
                        {/foreach}
                {/if}

{literal}
}

//////////////////////////////////////////////////////

	var done = new Array();

function getFullPath(thumb) {
	return thumb.replace(/s[1-9]\.geograph/,'s0.geograph').replace(/_\d+x\d+\./,'.');
}

function addRows(rows,updateLayer) {
	var newRows = new Array();

        for(q=0;q<rows.length;q++) {
		if (!done[rows[q].guid]) {
                        row = rows[q];

			//we accept the json feed format, which isnt exactly the same as the photolayer!
			newRows.push({
				link: row.link,
				thumbnail: row.thumb,
				url: getFullPath(row.thumb),
				caption: row.title+' by '+row.author,
				lat: row.lat,
				lng: row.long
			});
			//if (!row.centi || (row.centi!=1000000000 && row.centi!=2000000000)) .. we have no way of detecting the gr-resolution here!
				layerData.push([row.lat,row.long]);
			done[row.guid] = 1;
			totalImages++;
		}
        }
	if (newRows.length) {
		overlayMaps['Search Results'].add(newRows);

		if (!map._loaded || !map.getBounds().contains(overlayMaps['Search Results'].getBounds()) )
			map.fitBounds(overlayMaps['Search Results'].getBounds());

		$('#message').show().text('Added '+newRows.length+' images, now '+totalImages+' total');
		if (messageTimer)
			clearTimeout(messageTimer);
		messageTimer = setTimeout(function() {
			$('#message').hide('slow');
			messageTimer = null;
		}, 10000);

		if (overlayMaps['Search Points'] && map.hasLayer(overlayMaps['Search Points']))
			overlayMaps['Search Points'].setData(layerData);
	} else if (overlayMaps['Search Points'] && updateLayer) {
		overlayMaps['Search Points'].setData(layerData);
	}
	return rows[rows.length-1].id;
}

//////////////////////////////////////////////////////

AttachEvent(window,'load',loadMap,false);
//]]>
</script>
	{/literal}{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

</body>
</html>


