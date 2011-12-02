{assign var="olayersmap" value="1"}
{if $inner}
{assign var="page_title" value="Geograph Mercator Map"}
{include file="_basic_begin.tpl"}
{else}
{assign var="page_title" value="Geograph Mercator Map"}
{include file="_std_begin.tpl"}
{/if}
{if $google_maps_api_key}
<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false&amp;key={$google_maps_api_key}"></script>
{/if}
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>
{* FIXME/TODO

text like "Loading Map (JavaScript Required)..."

host our own osm tiles (hills+mapnik, zoom level <= 14, approx 15GB?)?
overview map: level <= 9

ommap.tpl, rastermap.class.php:
- pan when moving marker out of box?
- continue panning when moving mouse pointer out of box?

*}

{literal}
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
{dynamic}
		var iniz = {$iniz};
		var initype = '{$initype}';
		var inio = {$inio};
		var inior = {$inior};
		var inilat = {$inilat};
		var inilon = {$inilon};
		var inimlat = {$inimlat};
		var inimlon = {$inimlon};
		var iniuser = {$iniuser};
{/dynamic}
{literal}

		function openGeoWindow(prec, url) {
			var curgridref = document.theForm.grid_reference.value.replace(/ /g,'');
			if (curgridref.search(/^[A-Za-z]+(\d\d)+$/) != 0)
				return;
			var preflen = curgridref.search(/\d/);
			var curprec = (curgridref.length-preflen)/2;
			prec = Math.min(prec, curprec);
			var gridref = curgridref.substr(0, preflen) + curgridref.substr(preflen, prec) + curgridref.substr(preflen+curprec, prec);
			window.open(url+gridref,'_blank');
		}

		function clearMarker() {
			if (currentelement) {
				dragmarkers.removeFeatures([currentelement]);
			        currentelement.destroy();
				currentelement = null;
				document.theForm.grid_reference.value = '';
				map.events.triggerEvent("dragend");
			}
		}

		function loadmapO() {
			//OpenLayers.Lang.setCode("de"); /* TODO Needs OpenLayers/Lang/de.js built into OpenLayers.js */

			var op = 0.5;
			if (inio >= 0)
				op = inio;
			var opr = 1.0;
			if (inior >= 0)
				opr = inior;
			var user = 0;
			if (iniuser >= -1)
				user = iniuser;

			var point1 = new OpenLayers.Geometry.Point(lonmin, latmin);
			var point2 = new OpenLayers.Geometry.Point(lonmax, latmax);
			point1.transform(epsg4326, epsg900913);
			point2.transform(epsg4326, epsg900913);

			var bounds = new OpenLayers.Bounds();
			bounds.extend(point1);
			bounds.extend(point2);

			OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
				defaultHandlerOptions: {
					'single': true,
					'double': false,
					'pixelTolerance': 0,
					'stopSingle': false,
					'stopDouble': false
				},

				initialize: function(options) {
					this.handlerOptions = OpenLayers.Util.extend(
						{}, this.defaultHandlerOptions
					);
					OpenLayers.Control.prototype.initialize.apply(
						this, arguments
					); 
					this.handler = new OpenLayers.Handler.Click(
						this, {
							'click': this.trigger
						}, this.handlerOptions
					);
				},

				trigger: function(e) {
				}
			});

			var layerswitcher = new OpenLayers.Control.LayerSwitcher({'ascending':false});

			map = new OpenLayers.Map({
				div: "map",
				projection: epsg900913,
				displayProjection: epsg4326,
				units: "m",
				//minZoomLevel : 4,
				//maxZoomLevel : 18,
				//numZoomLevels : null,
				/* Restricted zoom levels seem to be a major pain with OpenLayers, especially when
				   including arbitrary layers that allow different zoom ranges... So, we just allow
				   any zoom level usual services provide und use transparent tiles for levels we
				   don't support...
				*/
				numZoomLevels: 18,
				restrictedExtent: bounds,
				geoBase: false,
				hillBase: false,
				user: user,
				controls : [
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.PanZoomBar(),
					layerswitcher,
					new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),
					new OpenLayers.Control.Attribution(),
				]
			});
{/literal}
{if $google_maps_api_key}
{literal}
			// FIXME numZoomLevels: are these values sensible?
			var gphy = new OpenLayers.Layer.Google(
				"Google: Gel&auml;nde",
				{type: google.maps.MapTypeId.TERRAIN, numZoomLevels: 16}
			);

			var gmap = new OpenLayers.Layer.Google(
				"Google: Stra&szlig;enkarte",
				{numZoomLevels: 20}
			);

			var ghyb = new OpenLayers.Layer.Google(
				"Google: Hybrid",
				{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20}
			);

			var gsat = new OpenLayers.Layer.Google(
				"Google: Satellit",
				{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
			);
{/literal}
{/if}
{literal}
			var geo = new OpenLayers.Layer.XYrZ(
				"Geograph",
				//"/tile/0/${z}/${x}/${y}.png",
				"/tile.php?x=${x}&y=${y}&Z=${z}&t=${u}",
				4, 13, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href="/">Geograph</a> und <a href="http://www.openstreetmap.org/">OSM</a>-User (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
					sphericalMercator : true,
					userParam : user,
				}
			);
			var geosq = new OpenLayers.Layer.XYrZ(
				"Geograph: Abdeckung",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=2&o=1&t=${u}",
				4, 14, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href="/">Geograph</a> (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
					userParam : user,
				}
			);
			var geogr = new OpenLayers.Layer.XYrZ(
				"Geograph: Gitternetz",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=8&o=1",
				4, 14, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href="/">Geograph</a> (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
				}
			);

			// FIXME numZoomLevels: are these values sensible?
			var mapnik = new OpenLayers.Layer.OSM(
				null,
				null,
				{ numZoomLevels: 19 }
			);
			var osmarender = new OpenLayers.Layer.OSM(
				"OpenStreetMap (Tiles@Home)",
				"http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png",
				{ numZoomLevels: 19 }
			);

			var hills = new OpenLayers.Layer.XYrZ( //FIXME our own version?
				"Relief",
				[ "http://wanderreitkarte.de/hills/${z}/${x}/${y}.png", "http://www.wanderreitkarte.de/hills/${z}/${x}/${y}.png"], // ol: 9..19 tiles: 8..\infty // 8..15
				9/*8*/, 15, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: 'H&ouml;hen: <a href="http://www.wanderreitkarte.de/">Nops Wanderreitkarte</a> mit <a href="http://www.wanderreitkarte.de/licence_de.php">CIAT-Daten</a>',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
				}
			);

			var topobase = new OpenLayers.Layer.XYrZ(
				"Nop's Wanderreitkarte",
				[ "http://base.wanderreitkarte.de/base/${z}/${x}/${y}.png", "http://base2.wanderreitkarte.de/base/${z}/${x}/${y}.png"],
				4, 16, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href="http://www.wanderreitkarte.de/">Nops Wanderreitkarte</a> (<a href="http://www.wanderreitkarte.de/licence_de.php">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : true,
				}
			);
			var topotrails = new OpenLayers.Layer.XYrZ(
				"Nop's Wanderreitkarte (Wege)",
				[ "http://topo.wanderreitkarte.de/topo/${z}/${x}/${y}.png", "http://topo2.wanderreitkarte.de/topo/${z}/${x}/${y}.png"],
				4, 16, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href="http://www.wanderreitkarte.de/">Nops Wanderreitkarte</a> (<a href="http://www.wanderreitkarte.de/licence_de.php">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
					displayInLayerSwitcher: false,
				}
			);
			map.events.register("changebaselayer", map, function(e) {
				/* Topographical map: always show trails layer */
				var showtopotrails = topobase == e.layer;
				if (topotrails.getVisibility() != showtopotrails)
					topotrails.setVisibility(showtopotrails);
			});

			gphy.hasHills = true;
			gsat.hasHills = true;
			ghyb.hasHills = true;

			map.events.register("changebaselayer", map, function(e) {
				var redrawlayerswitcher = false;
				/* Geograph map: don't show overlays */
				if (e.layer == geo) {
					if (!map.geoBase) {
						geosq.savedVisibility = geosq.getVisibility();
						geogr.savedVisibility = geogr.getVisibility();
						geosq.setVisibility(false);
						geogr.setVisibility(false);
						geosq.displayInLayerSwitcher = false;
						geogr.displayInLayerSwitcher = false;
						redrawlayerswitcher = true;
						map.geoBase = true;
					}
				} else if (map.geoBase) {
					if (geosq.savedVisibility)
						geosq.setVisibility(true);
					if (geogr.savedVisibility)
						geogr.setVisibility(true);
					geosq.displayInLayerSwitcher = true;
					geogr.displayInLayerSwitcher = true;
					redrawlayerswitcher = true;
					map.geoBase = false;
				}
				/* Don't show relief if already shown in base layer */
				if (('hasHills' in e.layer) && e.layer.hasHills) {
					if (!map.hillBase) {
						hills.savedVisibility = hills.getVisibility();
						hills.setVisibility(false);
						hills.displayInLayerSwitcher = false;
						redrawlayerswitcher = true;
						map.hillBase = true;
					}
				} else if (map.hillBase) {
					if (hills.savedVisibility)
						hills.setVisibility(true);
					hills.displayInLayerSwitcher = true;
					redrawlayerswitcher = true;
					map.hillBase = false;
				}
				if (redrawlayerswitcher) {
					layerswitcher.layerStates = [];
					layerswitcher.redraw();
				}
				if (e.layer instanceof OpenLayers.Layer.XYrZ) {
					var z = map.zoom; // FIXME map.getZoom()?
					if (z > e.layer.maxZoomLevel)
						map.setCenter(map.center, e.layer.maxZoomLevel); // FIXME is there really no "map.setZoom(zoom)"?
					else if (z < e.layer.minZoomLevel)
						map.setCenter(map.center, e.layer.minZoomLevel); // FIXME is there really no "map.setZoom(zoom)"?
				}
			});
			map.events.register("zoomend", map, function(e) {
				if (map.baseLayer instanceof OpenLayers.Layer.XYrZ) {
					var z = map.zoom; // FIXME map.getZoom()?
					if (z > map.baseLayer.maxZoomLevel)
						map.setCenter(map.center, map.baseLayer.maxZoomLevel); // FIXME is there really no "map.setZoom(zoom)"?
					else if (z < map.baseLayer.minZoomLevel)
						map.setCenter(map.center, map.baseLayer.minZoomLevel); // FIXME is there really no "map.setZoom(zoom)"?
				}
			});

			initMarkersLayer();

			mapnik.gmaxz = mapnik.numZoomLevels-1;
			osmarender.gmaxz = osmarender.numZoomLevels-1;
			topobase.gmaxz = topobase.maxZoomLevel;
			geo.gmaxz = geo.maxZoomLevel;
{/literal}
{if $google_maps_api_key}
{literal}
			gphy.gmaxz = gphy.numZoomLevels-1;
			gmap.gmaxz = gmap.numZoomLevels-1;
			gsat.gmaxz = gsat.numZoomLevels-1;
			ghyb.gmaxz = ghyb.numZoomLevels-1;
{/literal}
{/if}
{literal}
			var ovltypes = {
				'S' : geosq,
				'G' : geogr,
				'H' : hills
			}
			var maptypes = {
{/literal}
{if $google_maps_api_key}
{literal}
				'm' : gmap,
				'k' : gsat,
				'h' : ghyb,
				'p' : gphy,
{/literal}
{/if}
{literal}
				'g' : geo,
				'o' : mapnik,
				'w' : topobase,
				't' : osmarender
			}
			for (var key in maptypes) {
				maptypes[key].gurlid = key;
			}
			for (var key in ovltypes) {
				ovltypes[key].savedVisibility = false;
			}

			/* For opacity form */
			map.hills = hills;
			map.geosq = geosq;

			map.setUser = function(u) {
				if (u < -1 || u == map.user)
					return;
				map.user = u;
				geo.userParam = u;
				geosq.userParam = u;
				if (map.baseLayer == geo) {
					geo.redraw();
					map.events.triggerEvent("changelayer", { layer: geo, property: "user" });
				} else if (geosq.getVisibility()) {
					geosq.redraw();
					map.events.triggerEvent("changelayer", { layer: geosq, property: "user" });
				}
			}
			map.trySetUserId = function(s) {
				u = parseInt(s, 10);
				if (u > 0) {
					map.setUser(u);
					return true;
				}
				return false;
			}
			map.trySetOpacity = function(layer, s) {
				o = parseFloat(s);
				if (isNaN(o) || o < 0 || o > 100)
					return false;
				layer.setOpacity(o/100.0);
				return true;
			}
			function updateMapLink() {
				var ll = map.center.clone().transform(map.getProjectionObject(), epsg4326);
				var mt = map.baseLayer;
				var mtHasHills = ('hasHills' in mt) && mt.hasHills;
				var type = mt.gurlid;
				for (var key in ovltypes) {
					ot = ovltypes[key];
					var isvisible;
					if (ot == hills && !mtHasHills || ot != hills && mt != geo)
						isvisible = ot.getVisibility();
					else
						isvisible = ot.savedVisibility;
					if (isvisible)
						type += key;
				}
				var url = '?z=' + map.zoom
					+ '&t=' + type
					+ '&ll=' + ll.lat + ',' + ll.lon;
				if (map.user != 0) {
					url += '&u=' + map.user;
				}
				if (geosq.opacity != 0.5) {
					url += '&o=' + geosq.opacity;
				}
				if (hills.opacity != 1) {
					url += '&or=' + hills.opacity;
				}
				if (currentelement) {
					ll = new OpenLayers.LonLat(currentelement.geometry.x, currentelement.geometry.y);
					ll.transform(map.getProjectionObject(), epsg4326);
					url += '&mll=' + ll.lat + ',' + ll.lon;
				}
				var curlink = document.getElementById("maplink");
				curlink.setAttribute("href", url);
			}


			/* first layer: map type for overview map */
			map.addLayers([
				mapnik, osmarender,
				geo,
				topobase, topotrails,
				hills,
				geosq, geogr,
{/literal}
{if $google_maps_api_key}
{literal}
				gphy, gmap, gsat, ghyb,
{/literal}
{/if}
{literal}
				dragmarkers
			]);

			var overview =  new OpenLayers.Control.OverviewMap({
				//maximized: true
			});
			map.addControl(overview);

			function moveMarker(e) {
				var coords = map.getLonLatFromViewPortPx(e.xy);
				if (currentelement) {
					currentelement.move(coords);
				} else {
					coords.transform(map.getProjectionObject(), epsg4326);
					currentelement = createMarker(coords, 0);
				}
				markerDrag(currentelement, null);
			}

			var dragFeature = new OpenLayers.Control.DragFeature(dragmarkers, {'onDrag': markerDrag, 'onComplete': markerCompleteDrag});
			map.addControl(dragFeature);
			dragFeature.activate();
			var click = new OpenLayers.Control.Click({'trigger': moveMarker});
			map.addControl(click);
			click.activate();

			var point = new OpenLayers.LonLat(lon0, lat0);
			var zoom = 5;
			var mt = geo;
			if (inilat < 90)
				point = new OpenLayers.LonLat(inilon, inilat);
			if (initype != '')
				mt = maptypes[initype.charAt(0)];
			if (iniz >= 4 && iniz <= mt.gmaxz)
				zoom = iniz;
			map.setBaseLayer(mt);
			map.setCenter(point.transform(epsg4326, map.getProjectionObject()), zoom);
			var mtHasHills = ('hasHills' in mt) && mt.hasHills;
			for (var i = 1; i < initype.length; ++i) {
				var ot = ovltypes[initype.charAt(i)];
				if (ot == hills && !mtHasHills || ot != hills && mt != geo) {
					ot.setVisibility(true);
				} else {
					ot.savedVisibility = true;
				}
			}
			map.geoBase = mt == geo;
			map.hillBase = mtHasHills;
			if (inimlat < 90) {
				var mpoint = new OpenLayers.LonLat(inimlon, inimlat);
				currentelement = createMarker(mpoint, 0);
				markerDrag(currentelement, null);
			}
			geosq.setOpacity(op);
			hills.setOpacity(opr);

			map.events.on({'zoomend': updateMapLink}); // == map.events.register("zoomend", map, updateMapLink);
			map.events.on({'moveend': updateMapLink});
			map.events.on({'dragend': updateMapLink});
			map.events.on({'changelayer': updateMapLink});
			updateMapLink();
		}


		AttachEvent(window,'load',loadmapO,false);

	// ]]>
	</script>
{/literal}

{if $ext}
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px\"/>
<p>
This feature is still in development. Please use with care and try to avoid high server load.
</p>
<p>
Diese Kartenansicht ist noch in einem frühen Entwicklungsstadium! Bitte nicht übermäßig nutzen um zu hohe Serverlast zu vermeiden.
</p>
</div>
{/if}

<p>Bitte Karte anklicken um einen verschiebbaren Marker zu erzeugen...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{elseif $ext}action="javascript:void()"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if !$ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Nächster Schritt &gt; &gt;"/> <span id="dist_message"></span></div>
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
{/if}

<div class="smallmap" id="map" style="width:600px; height:500px;border:1px solid blue"></div><!-- FIXME Karte wird geladen... (JavaScript nötig) -->

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Planquadrat zeigen" onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Bild einreichen"    onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Bilder suchen"      onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Marker löschen"     onclick="clearMarker();" />
<a id="maplink" href="#">Link zur Karte</a>
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
<br />
{dynamic}
<input type="radio" name="mtradio" value="coverage" onclick="map.setUser(0);" {if $iniuser == 0}checked{/if} />Abdeckung |
<input type="radio" name="mtradio" value="depth" onclick="map.setUser(-1);" {if $iniuser == -1}checked{/if} />Dichte |
{if $userid}<input type="radio" name="mtradio" value="personal" onclick="map.setUser({$userid});" {if $iniuser == $userid}checked{/if} />Persönlich |{/if}
<input type="radio" name="mtradio" value="user" onclick="if(!map.trySetUserId(document.theForm.mtuser.value)){ldelim}document.theForm.mtradio[{if $userid}3{else}2{/if}].checked=false;document.theForm.mtradio[0].checked=true;map.setUser(0);{rdelim};"
{if $iniuser > 0 and $iniuser != $userid}checked{/if} />Nutzer:
<input type="text" size="5" name="mtuser"  value="{if $iniuser > 0}{$iniuser}{elseif $userid}{$userid}{/if}" />
<br />
Deckkraft (%):
Abdeckung
<input type="text" size="5" name="opcoverage" value="{if $inio >= 0}{$inio*100}{else}50{/if}" />
<input type="button" value="set"   onclick="map.trySetOpacity(map.geosq, document.theForm.opcoverage.value);"/>
| Relief
<input type="text" size="5" name="oprelief" value="{if $inior >= 0}{$inior*100}{else}100{/if}" />
<input type="button" value="set"   onclick="map.trySetOpacity(map.hills, document.theForm.oprelief.value);"/>
{/dynamic}
</div>
{/if}


</form>
{*<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address:</label>
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Find"/><small><small><br/>
	(Powered by the Google Maps API Geocoder)</small></small>
</div>
</form>*}

{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
