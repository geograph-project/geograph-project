<html style="margin:0">
<head>
<title>Search Results</title>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
</head>
<body style="margin:0;padding:0">

{if !$google_maps_api_key}
	<div class="interestBox">
	<p>This page is no longer able to display a map - please use a different display method.</p>
	<p>However you may be able to display a map on {if $engine->currentPage > 1}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`/`$engine->currentPage`.kml" text="Google Maps"}.{else}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`.kml" text="Google Maps"}.{/if}</p>
	</div>

{elseif $engine->resultCount}

	<div id="map" style="width:100%; height:100%; position:relative;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;

	function onLoad() {
		map = new GMap2(document.getElementById("map"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());
		map.addControl(new GScaleControl());
		var mapType = G_NORMAL_MAP;

		var bounds = new GLatLngBounds();

		{/literal}{foreach from=$engine->results item=image}
			bounds.extend(new GLatLng({$image->wgs84_lat}, {$image->wgs84_long}));
		{/foreach}{literal}

		var newZoom = map.getBoundsZoomLevel(bounds);
		var center = bounds.getCenter();

		if (location.hash.length) {
			// If there are any parameters at the end of the URL, they will be in location.search
			// looking something like  "#ll=50,-3&z=10&t=h"

			// skip the first character, we are not interested in the "#"
			var query = location.hash.substring(1);

			var pairs = query.split("&");
			for (var i=0; i<pairs.length; i++) {
				// break each pair at the first "=" to obtain the argname and value
				var pos = pairs[i].indexOf("=");
				var argname = pairs[i].substring(0,pos).toLowerCase();
				var value = pairs[i].substring(pos+1).toLowerCase();

				if (argname == "ll") {
					var bits = value.split(',');
					center = new GLatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {newZoom = parseInt(value);}
				if (argname == "t") {
					if (value == "m") {mapType = G_NORMAL_MAP;}
					if (value == "k") {mapType = G_SATELLITE_MAP;}
					if (value == "h") {mapType = G_HYBRID_MAP;}
					if (value == "p") {mapType = G_PHYSICAL_MAP;}
					if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
			}
		}

		map.setCenter(center, newZoom,mapType);


		{/literal}
		var xml = new GGeoXml("http://{$http_host}/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.kml");
		map.addOverlay(xml);

		{if $markers}
			{foreach from=$markers item=marker}
				map.addOverlay(createMarker(new GLatLng({$marker.1},{$marker.2}),'{$marker.0}'));
			{/foreach}
		{/if}{literal}

		GEvent.addListener(map, "moveend", makeHash);
		GEvent.addListener(map, "zoomend", makeHash);
		GEvent.addListener(map, "maptypechanged", makeHash);
	}

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getCurrentMapType().getUrlArg();
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t;
	}

	function createMarker(point,myHtml) {
		var marker = new GMarker(point, {draggable: true});

		GEvent.addListener(marker, "click", function() {
			map.openInfoWindowHtml(point, myHtml);
		});
		GEvent.addListener(marker, "dragend", function() {
			marker.setPoint(point);
		});

		return marker;
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}{/if}


	{if $engine->results}

	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

</body>
</html>
