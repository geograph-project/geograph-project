{include file="_search_begin.tpl"}

{if !$engine->resultCount}
{include file="_search_noresults.tpl"}
{elseif $google_maps_api_key}
	<div id="map" style="width:100%; height:500px; position:relative;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;
	var infoWindow;

	var maptypes;
	var shorttypes;

	function GetTileUrl_Mapnik(a, z) {
	    return "http://tile.openstreetmap.org/" +
			z + "/" + a.x + "/" + a.y + ".png";
	}

	function GetTileUrl_Top(a, z) {
	    //return "http://topo.openstreetmap.de/topo/" +
	    sd = "topo"; //inthash("" + a.x + a.y + z, 2) == 0 ? "topo" : "topo2" ; // currently available: topo, topo2, topo3, topo4
	    return "http://" + sd + ".wanderreitkarte.de/topo/" +
			z + "/" + a.x + "/" + a.y + ".png";
	}

	function GetTileUrl_GeoH(a, z) {
	    return "http://geo.hlipp.de/tile/hills/" +
			z + "/" + a.x + "/" + a.y + ".png";
	}

	function GetTileUrl_GeoS(a, z) {
		return (z < 16 ? "http://geo.hlipp.de/tile/osm/" : "http://tile.openstreetmap.org/") +
			z + "/" + a.x + "/" + a.y + ".png";
	}

	function GetTileUrl_Cycle(a, z) {
		return "http://a.tile.opencyclemap.org/cycle/" +
			z + "/" + a.x + "/" + a.y + ".png";
	}

	function onLoad() {
		var point = null;
		var zoom = null;
		var mapType = google.maps.MapTypeId.HYBRID;

		var mapnik_map_static = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_GeoS,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 18,
			minZoom: 0,
			name: "OSM(s)",
			alt: "OSM: Static Mapnik",
		});
		var mapnik_map = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_Mapnik,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 18,
			minZoom: 0,
			name: "OSM",
			alt: "OSM: Mapnik",
		});

		// tile overlays have become much worse with v3...
		var relief_map = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_GeoH,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 15,
			minZoom: 4,
			name: "Relief",
			alt: "Relief",
		});
		var mapnik_map_rel = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_Mapnik,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 18,
			minZoom: 0,
			name: "OSM+Relief",
			alt: "OSM: Mapnik + Relief",
		});
		var topo_map = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_Top,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 17,
			minZoom: 0,
			name: "Nop-RWK",
			alt: "Nop: Reit- und Wanderkarte",
		});
		var cycle_map = new google.maps.ImageMapType({
			getTileUrl: GetTileUrl_Cycle,
			tileSize: new google.maps.Size(256, 256),
			isPng: true,
			maxZoom: 19,
			minZoom: 0,
			name: "Cycle Map",
			alt: "OpenCycleMap",
		});

		maptypes = [ google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, google.maps.MapTypeId.TERRAIN, "mapniks", "mapnik", "mapnikh", "topo", "cycle" ];
		shorttypes = [ 'm', 'k', 'h', 'p', "mapniks", "mapnik", "mapnikh", "topo", "cycle" ];

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
					point = new google.maps.LatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {
					zoom = parseInt(value);
				}
				if (argname == "t") {
					var index = shorttypes.indexOf(value);
					if (index != -1) {
						mapType = maptypes[index];
					}
					//if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
			}
		} else {
			var mt = readCookie('GMapType');
			if (mt !== false && maptypes.indexOf(mt) != -1) {
				mapType = mt;
			}
		}

		var fitbounds = point === null || zoom === null;
		if (fitbounds) {
			point = new google.maps.LatLng(0,0);
			zoom = 0;
		}

		infoWindow = new google.maps.InfoWindow();

		map = new google.maps.Map(
			document.getElementById('map'), {
			center: point,
			zoom: zoom,
			mapTypeId: mapType,
			streetViewControl: false, //true,
			fullscreenControl: false,
			gestureHandling: 'greedy',
			mapTypeControlOptions: {
				mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, google.maps.MapTypeId.TERRAIN, "mapniks", "mapnik", "mapnikh", "topo", "cycle"],
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
				position: google.maps.ControlPosition.RIGHT_TOP
			},
			zoomControlOptions: {
				position: google.maps.ControlPosition.LEFT_TOP
			}
		});

		// copyright messages for custom tile layers have become much worse with v3...
		var copyrightDiv = document.createElement("div");
		copyrightDiv.id = "map-copyright";
		copyrightDiv.style.fontSize = "11px";
		copyrightDiv.style.fontFamily = "Arial, sans-serif";
		copyrightDiv.style.margin = "0 2px 2px 0";
		copyrightDiv.style.whiteSpace = "nowrap";
		map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(copyrightDiv);
		var copyrights = {};
		function updateCopyright() {
		    newMapType = map.getMapTypeId();
		    copyrightDiv.innerHTML = newMapType in copyrights ? copyrights[newMapType] : "";
		}

		map.mapTypes.set("mapniks", mapnik_map_static);
		copyrights["mapniks"] = "<a target=\"_blank\" href=\"http://www.openstreetmap.org/\">OSM</a> (<a target=\"_blank\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC</a>)";
		map.mapTypes.set("mapnik", mapnik_map);
		copyrights["mapnik"] = "<a target=\"_blank\" href=\"http://www.openstreetmap.org/\">OSM</a> (<a target=\"_blank\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC</a>)";
		map.mapTypes.set("relief", relief_map);
		map.mapTypes.set("mapnikh", mapnik_map_rel);
		copyrights["mapnikh"] = "<a target=\"_blank\" href=\"http://www.openstreetmap.org/\">OSM</a> (<a target=\"_blank\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC</a>), DEM <a target=\"_blank\" href=\"http://srtm.csi.cgiar.org/\">CIAT</a>";
		map.mapTypes.set("topo", topo_map);
		copyrights["topo"] = "<a target=\"_blank\" href=\"http://www.wanderreitkarte.de/licence_de.php\">Nops RWK</a> DEM <a target=\"_blank\" href=\"http://srtm.csi.cgiar.org/\">CIAT</a>";
		map.mapTypes.set("cycle", cycle_map);
		copyrights["cycle"] = "<a target=\"_blank\" href=\"http://opencyclemap.org/\">OpenCycleMap</a> (<a target=\"_blank\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC</a>)";

		google.maps.event.addListener(map, "maptypeid_changed", function(e) {
			if (map.mapTypeId === "mapnikh") {
				if (map.overlayMapTypes.getLength() == 0) {
					map.overlayMapTypes.insertAt(0, relief_map);
				}
			} else {
				if (map.overlayMapTypes.getLength() > 0){
					map.overlayMapTypes.removeAt(0);
				}
			}
		});
		google.maps.event.addListener(map, "maptypeid_changed", updateCopyright);
		if (map.mapTypeId === "mapnikh") {
				if (map.overlayMapTypes.getLength() == 0) {
					map.overlayMapTypes.insertAt(0, relief_map);
				}
		}
		updateCopyright();

		if (fitbounds) {
			var bounds = new google.maps.LatLngBounds();

			{/literal}{foreach from=$engine->results item=image}
				bounds.extend(new google.maps.LatLng({$image->wgs84_lat}, {$image->wgs84_long}));
			{/foreach}
			{if $markers}
				{foreach from=$markers item=marker}
					bounds.extend(new google.maps.LatLng({$marker.1},{$marker.2}));
				{/foreach}
			{/if}{literal}

			map.fitBounds(bounds);
		}
		
		{/literal}{if $markers}
			{foreach from=$markers item=marker}
				createMarker(new google.maps.LatLng({$marker.1},{$marker.2}),'{$marker.0}');
			{/foreach}
		{/if}{literal}

		var html;
		var thumburl;
		var thumbwidth;
		var thumbheight;
		{/literal}{foreach from=$engine->results item=image}
			thumburl = '{$image->getThumbnail(120,120,3)|escape:"javascript"}';
			thumbwidth = {$image->last_width};
			thumbheight = {$image->last_height};
			html = makeHtml(
				'/photo/{$image->gridimage_id}',
				'{$image->realname|escape:"html"|escape:"javascript"}',
				'{$image->grid_reference|escape:"html"|escape:"javascript"}',
				'{$image->title1|escape:"html"|escape:"javascript"}',
				'{$image->title2|escape:"html"|escape:"javascript"}',
				'{$image->comment1|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				'{$image->comment2|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				thumburl, thumbwidth, thumbheight
			);
			createMarker(new google.maps.LatLng({$image->wgs84_lat}, {$image->wgs84_long}), html, thumburl, thumbwidth, thumbheight);
		{/foreach}{literal}

		google.maps.event.addListener(map, "dragend", makeHash);
		google.maps.event.addListener(map, "zoom_changed", makeHash);
		google.maps.event.addListener(map, "maptypeid_changed", makeHash);
		google.maps.event.addListener(map, 'maptypeid_changed', function(e) {
			var mt = map.getMapTypeId();
			createCookie('GMapType',mt,365);
		});
		// FIXME tilt_changed?
	}

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getMapTypeId();//CurrentMapType().getUrlArg();
		var index = maptypes.indexOf(t);
		t = index != -1 ? shorttypes[index] : 'h';
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t;
	}

	function makeHtml(photourl, realname, gridref, title1, title2, comment1, comment2, thumburl, thumbwidth, thumbheight) {
		var title = title2 === '' ? title1 : (title1 === '' ? title2 : title1 + ' (' + title2 + ')');
		title = gridref + ' : ' + title;
		var comment = comment2 === '' ? comment1 : (comment1 === '' ? comment2 : comment1 + '</p><hr style="width:3em"/><p style="font-weight:bold">' + comment2);
		var html = '<h4 style="font-family:Arial,sans-serif;font-weight:bold;font-size:medium">'+title+'</h4>';
		html += '<div style="font-family:Arial,sans-serif;text-align:center;font-size:small">';
		html += '<p><a href="'+photourl+'" target="_blank"><img src="'+thumburl+'" width="'+thumbwidth+'" height="'+thumbheight+'" alt="'+title+'"/></a></p>';
		if (comment !== '') {
			html += '<p style="font-weight:bold">' + comment + '</p>';
		}
		html += '<p>&copy; Copyright <i>'+realname+'</i> and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a></p>';
		html += '<p><a href="'+photourl+'" target="_blank">View photo page</a></p>';
		html += '</div>';
		return html;
	}

	function createMarker(point,myHtml,url,w,h) {
		if (typeof url !== "undefined") {
			var maxdim = Math.max(w, h);
			var scale = maxdim <= 40 ? 1 : 40.0/maxdim;
			var sw = Math.round(scale * w);
			var sh = Math.round(scale * h);
			var ax = Math.round(0.5 * sw);
			var ay = Math.round(0.5 * sh);
			var icon = new google.maps.MarkerImage(url, new google.maps.Size(w, h), null, new google.maps.Point(ax, ay), new google.maps.Size(sw, sh));
			var marker = new google.maps.Marker({
				position: point,
				map: map,
				icon: icon,
			});
		} else {
			var marker = new google.maps.Marker({
				position: point,
				map: map,
				draggable: true,
			});
		}

		google.maps.event.addListener(marker, "click", function() {
			infoWindow.setContent(myHtml);
			infoWindow.open(map, marker);
		});
		google.maps.event.addListener(marker, "dragend", function() {
			marker.setPosition(point);
		});

		return marker;
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}{/if}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}


	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	
	<script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
{else}
	{*<div class="interestBox">
	<p>This page is no longer able to display a map - please use a different display method.</p>
	<p>However you may be able to display a map on {if $engine->currentPage > 1}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`/`$engine->currentPage`.kml" text="Google Maps"}.{else}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`.kml" text="Google Maps"}.{/if}</p>
	</div>*}
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
{*<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>*}
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>
	<div id="map" style="width:100%; height:500px; position:relative;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var issubmit = 1;
	var iscmap = 0;
	var ri = -1;
	var map;
{/literal}
	var lat0 = {$lat0};
	var lon0 = {$lon0};
	var latmin = {$latmin};
	var latmax = {$latmax};
	var lonmin = {$lonmin};
	var lonmax = {$lonmax};
{literal}

	function onLoad() {
		initOL();
		//initMarkersLayer(); // Combining a markers layer for fixed icons and a combined drag/select features layer did not work...
		initIconLayer();

		var point1 = new OpenLayers.Geometry.Point(lonmin, latmin);
		var point2 = new OpenLayers.Geometry.Point(lonmax, latmax);
		point1.transform(epsg4326, epsg900913);
		point2.transform(epsg4326, epsg900913);

		var bounds = new OpenLayers.Bounds();
		bounds.extend(point1);
		bounds.extend(point2);

		var mapnik = new OpenLayers.Layer.XYrZ(
			"Mapnik (Static + OSM)",
			"/tile/osm/${z}/${x}/${y}.png",
			0, 18, OpenLayers.Util.Geograph.MISSING_TILE_URL_BLUE /*FIXME*/,
			{
				attribution: '&copy; <a href="http://www.openstreetmap.org/">OSM</a> contributors (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
				sphericalMercator : true
			},
			16, "http://tile.openstreetmap.org/${z}/${x}/${y}.png"
		);
		var osmmapnik = new OpenLayers.Layer.OSM(
			null,
			null,
			{ numZoomLevels: 19 }
		);

		var layerswitcher = new OpenLayers.Control.LayerSwitcher({'ascending':false});

		map = new OpenLayers.Map({
			div: "map",
			projection: epsg900913,
			displayProjection: epsg4326,
			units: "m",
			numZoomLevels: 18,
			restrictedExtent: bounds,
			controls : [
				new OpenLayers.Control.Navigation(),
				new OpenLayers.Control.PanZoomBar(),
				layerswitcher,
				new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),
				new OpenLayers.Control.Attribution()
			]
		});

		var point = null;
		var zoom = null;
		var mapType = mapnik;

		// TODO: location.hash ...

		var fitbounds = point === null || zoom === null;
		if (fitbounds) {
			point = new OpenLayers.LonLat(lon0, lat0);
			zoom = 5; // FIXME
		}

		map.addLayers([
			mapnik,
			osmmapnik,
			markers
		]);

		var overview =  new OpenLayers.Control.OverviewMap({
			//maximized: true
		});
		map.addControl(overview);
		map.setBaseLayer(mapType);
		if (fitbounds) {
			var resbounds = new OpenLayers.Bounds();

			{/literal}{foreach from=$engine->results item=image}
				resbounds.extend(new OpenLayers.LonLat({$image->wgs84_long},{$image->wgs84_lat}));{*FIXME server side? also google maps part*}
			{/foreach}
			{if $markers}
				{foreach from=$markers item=marker}
					resbounds.extend(new OpenLayers.LonLat({$marker.2},{$marker.1}));
				{/foreach}
			{/if}{literal}

			map.zoomToExtent(resbounds.transform(epsg4326, map.getProjectionObject()));
		}
		
		var html;
		var thumburl;
		var thumbwidth;
		var thumbheight;
		{/literal}{foreach from=$engine->results item=image}
			thumburl = '{$image->getThumbnail(120,120,3)|escape:"javascript"}';
			thumbwidth = {$image->last_width};
			thumbheight = {$image->last_height};
			html = makeHtml(
				'/photo/{$image->gridimage_id}',
				'{$image->realname|escape:"html"|escape:"javascript"}',
				'{$image->grid_reference|escape:"html"|escape:"javascript"}',
				'{$image->title1|escape:"html"|escape:"javascript"}',
				'{$image->title2|escape:"html"|escape:"javascript"}',
				'{$image->comment1|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				'{$image->comment2|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				thumburl, thumbwidth, thumbheight
			);
			createPopupMarker(new OpenLayers.LonLat({$image->wgs84_long}, {$image->wgs84_lat}), html, thumburl, thumbwidth, thumbheight, 40);
		{/foreach}{literal}

		{/literal}{if $markers}
			{foreach from=$markers item=marker}
				createPopupMarker(new OpenLayers.LonLat({$marker.2},{$marker.1}),'{$marker.0}');
			{/foreach}
		{/if}{literal}
	}

	function makeHtml(photourl, realname, gridref, title1, title2, comment1, comment2, thumburl, thumbwidth, thumbheight) {
		var title = title2 === '' ? title1 : (title1 === '' ? title2 : title1 + ' (' + title2 + ')');
		title = gridref + ' : ' + title;
		var comment = comment2 === '' ? comment1 : (comment1 === '' ? comment2 : comment1 + '</p><hr style="width:3em"/><p style="font-weight:bold">' + comment2);
		var html = '<h4 style="font-family:Arial,sans-serif;font-weight:bold;font-size:medium">'+title+'</h4>';
		html += '<div style="font-family:Arial,sans-serif;text-align:center;font-size:small">';
		html += '<p><a href="'+photourl+'" target="_blank"><img src="'+thumburl+'" width="'+thumbwidth+'" height="'+thumbheight+'" alt="'+title+'"/></a></p>';
		if (comment !== '') {
			html += '<p style="font-weight:bold">' + comment + '</p>';
		}
		html += '<p>&copy; Copyright <i>'+realname+'</i> and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a></p>';
		html += '<p><a href="'+photourl+'" target="_blank">View photo page</a></p>';
		html += '</div>';
		return html;
	}


	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}{/if}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}


	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	
	<script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
{/if}

{include file="_search_end.tpl"}
