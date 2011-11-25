{if $inner}
{assign var="page_title" value="Geograph Mercator Map"}

{include file="_basic_begin.tpl"}
{else}
{assign var="page_title" value="Geograph Mercator Map"}
{assign var="extra_meta" value="
    <link rel=\"stylesheet\" href=\"/ol/theme/default/style.css\" type=\"text/css\" />
    <link rel=\"stylesheet\" href=\"/ol/theme/default/google.css\" type=\"text/css\" />
    <!--[if lte IE 6]>
        <link rel=\"stylesheet\" href=\"/ol/theme/default/ie6-style.css\" type=\"text/css\" />
    <![endif]-->
    <!--link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\" /-->
    <style type=\"text/css\">
        .olImageLoadError `$smarty.ldelim`
            background-color: transparent;
            /*background-color: pink;
	    opacity: 0.5;
	    filter: alpha(opacity=50);*/ /* IE */
	`$smarty.rdelim`

        .olControlAttribution `$smarty.ldelim`
            bottom: 0px;
        `$smarty.rdelim`
        /*#map `$smarty.ldelim`
            height: 512px;
        `$smarty.rdelim`*/
    </style>
    <!--script src=\"http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false&amp;key=`$google_maps_api_key`\"></script>
    <script src=\"/ol/OpenLayers.js\"></script-->
"}
{include file="_std_begin.tpl"}
{/if}
{if $google_maps_api_key}
<script src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false&amp;key={$google_maps_api_key}"></script>
{/if}
<script src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>
<!--script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script-->
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
		
		//the google map object
		var map;

		//the geocoder object
		//var geocoder;
		//var running = false;

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
				//GEvent.trigger(map, "markergone");//FIXME
			}
		}

		function loadmapO() {
			var point1 = new OpenLayers.Geometry.Point(lonmin, latmin);
			var point2 = new OpenLayers.Geometry.Point(lonmax, latmax);
			point1.transform(epsg4326, epsg900913);
			point2.transform(epsg4326, epsg900913);

			var bounds = new OpenLayers.Bounds();
			bounds.extend(point1);
			bounds.extend(point2);
			 //bounds.toBBOX();

			//OpenLayers.Util.onImageLoadErrorColor = "transparent";//FIXME?

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

				trigger: function(e) { //FIXME
					//var lonlat = map.getLonLatFromViewPortPx(e.xy);
					//alert("You clicked near " + lonlat.lat + " N, " +
					//                          + lonlat.lon + " E");
				}
			});

			map = new OpenLayers.Map({
				div: "map",
				projection: epsg900913,
				displayProjection: epsg4326,
				units: "m",
				numZoomLevels: 18,
				//minZoomLevel : 4,
				//maxZoomLevel : 13,
				//numZoomLevels : null,
				maxResolution: 156543.0339,
				//maxResolution: 156543.0339/16,
				//zoomOffset: 13, resolutions: [19.1092570678711,9.55462853393555,4.77731426696777,2.38865713348389]
				//maxExtent: bounds,
				maxExtent: [-20037508, -20037508, 20037508, 20037508],
				restrictedExtent: bounds,
				controls : [
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.PanZoomBar(),
					new OpenLayers.Control.LayerSwitcher({'ascending':false}),//FIXME?
					new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),//FIXME position
					new OpenLayers.Control.Attribution(),
				]
			});
{/literal}
{if $google_maps_api_key}
{literal}
			var gphy = new OpenLayers.Layer.Google(
				"Google Physical",
				{type: google.maps.MapTypeId.TERRAIN}
			);

			var gmap = new OpenLayers.Layer.Google(
				"Google Streets",
				{numZoomLevels: 20}
			);

			var ghyb = new OpenLayers.Layer.Google(
				"Google Hybrid",
				{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20}
			);

			var gsat = new OpenLayers.Layer.Google(
				"Google Satellite",
				{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
			);
{/literal}
{/if}
{literal}
			OpenLayers.Util.Geograph = {};
			OpenLayers.Util.Geograph.MISSING_TILE_URL = "/maps/transparent_256_256.png";
			OpenLayers.Util.Geograph.originalOnImageLoadError = OpenLayers.Util.onImageLoadError;
			OpenLayers.Util.onImageLoadError = function() { // TODO
				if (this.src.contains("hills")) { // FIXME
					// do nothing - this layer is transparent
					//this.src = OpenLayers.Util.Geograph.MISSING_TILE_URL;
				} else if (this.src.match(/tile\.php\?.*&o=1/)) {
					// do nothing - this layer is transparent
				} else if (this.src.match(/tile\.php\?/)) {
					// do nothing
				} else {
					OpenLayers.Util.OSM.originalOnImageLoadError;
				}
			};
			//subclass XYZ -> XYrZ
			//errorTileUrl="/maps/transparent_256_256.png"
			//regMinZoomLevel, regMaxZoomLevel
			/*OpenLayers.Layer.XYZrZ = OpenLayers.Class(OpenLayers.Layer.Grid, {
			    errorTileUrl: null,
			    regMinZoomLevel: null,
			    regMaxZoomLevel: null,
			    initialize: function(name, url, errorTileUrl, regMinZoomLevel, regMaxZoomLevel, options) {
			    }
			    });*/

			var geo = new OpenLayers.Layer.XYZ(
				"Geo",
				//"/tile/0/${z}/${x}/${y}.png",
				"/tile.php?x=${x}&y=${y}&Z=${z}&t=0",
				{
					attribution: '&copy; <a href="http://geo.hlipp.de">Geograph</a> and <a href="http://www.openstreetmap.org/">OSM</a> Contributors (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
					sphericalMercator : true,
					minZoomLevel : 4,
					maxZoomLevel : 13,
					//maxResolution: 156543.0339/16,
					numZoomLevels : null,
					//maxExtent:      bounds,
					//maxExtent: [-20037508, -20037508, 20037508, 20037508],
					//restrictedExtent: bounds,
				}
			);
			//FIXME attribution
			//FIXME displayInLayerSwitcher: false
			/*var ogeo = new OpenLayers.Layer.OSM(
				"OSM+Geo",
				"http://tile.openstreetmap.org/${z}/${x}/${y}.png"
			);*/
			var geosq = new OpenLayers.Layer.XYZ(
				"Squares",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=2&o=1&t=0",
				{
					attribution: '',
					sphericalMercator : true,
					//minZoomLevel : 4,
					//maxZoomLevel : 14,
					// restrictedMinZoom
					// restrictedMinZoom
					// serverResolutions
					// restrictedMinZoom
					//  maxResolution and numZoomLevels
					/*zoomOffset: 4,
					maxResolution: 156543.0339/16,
					resolutions: [
						156543.0339/16,
						156543.0339/32,
						156543.0339/64,
						156543.0339/128,
						156543.0339/256,
						156543.0339/512,
						156543.0339/1024,
						156543.0339/2048,
						156543.0339/4096,
						156543.0339/8192,
						156543.0339/16384,
						156543.0339/65536
					],*/
					restrictedMinZoom:4,
					//zoomOffset: 4,
					/*serverResolutions: [
						156543.0339/16,
						156543.0339/32,
						156543.0339/64,
						156543.0339/128,
						156543.0339/256,
						156543.0339/512,
						156543.0339/1024,
						156543.0339/2048,
						156543.0339/4096,
						156543.0339/8192,
						156543.0339/16384,
						156543.0339/65536
					],*/
					//numZoomLevels : null,
					isBaseLayer : false,
					visibility : false, //FIXME
				}
			);
			var geogr = new OpenLayers.Layer.XYZ(
				"Grid",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=8&o=1",
				{
					sphericalMercator : true,
					minZoomLevel : 4,
					maxZoomLevel : 14,
					numZoomLevels : null,
					isBaseLayer : false,
					visibility : false, //FIXME
				}
			);
			var hills = new OpenLayers.Layer.OSM( //FIXME our own version?
				"Profile",
				"http://wanderreitkarte.de/hills/${z}/${x}/${y}.png",
				{
					attribution: 'H&ouml;hen: <a href="http://www.wanderreitkarte.de/">Nops Wanderreitkarte</a> mit <a href="http://www.wanderreitkarte.de/licence_de.php">CIAT-Daten</a>',//FIXME: wanderreitkarte Nop
					isBaseLayer : false,
					minZoomLevel : 8, // enforce?
					maxZoomLevel : 14,
					visibility : false, //FIXME
				}
			);

			var mapnik = new OpenLayers.Layer.OSM();

			var osmarender = new OpenLayers.Layer.OSM(
				"OpenStreetMap (Tiles@Home)",
				"http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png"
			);

			initMarkersLayer();

			map.addLayers([
				mapnik, osmarender,
				geo,
				hills,geosq,geogr,
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
				maximized: true
			});
			map.addControl(overview); // FIXME map type?

			function moveMarker(e) {
				var coords = map.getLonLatFromViewPortPx(e.xy);
				if (currentelement) {
					currentelement.move(coords);
				} else {
					coords.transform(map.getProjectionObject(), epsg4326); //FIXME?
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
			if (inilat < 90)
				point = new OpenLayers.LonLat(inilon, inilat);
			if (iniz >= 4 && iniz <= 14/*FIXME*/)
				zoom = iniz;
			map.setCenter(point.transform(epsg4326, map.getProjectionObject()), zoom);
			if (inimlat < 90) {
				var mpoint = new OpenLayers.LonLat(inimlon, inimlat);
				currentelement = createMarker(mpoint, 0);
				markerDrag(currentelement, null);
			}
			var op = 0.5;
			if (inio >= 0)
				op = inio;
			var opr = 1.0;
			if (inior >= 0)
				opr = inior;
			var user = 0;
			if (iniuser >= -1)
				user = iniuser;
			geosq.setOpacity(op);//FIXME
			hills.setOpacity(opr);//FIXME
			//map.setUser(user);//FIXME
		}


/* FIXME/TODO

text like "Loading Map (JavaScript Required)..."

min/max zoom

ini: type, user

osm+g
layer switcher

for small maps: grid lines + square

opacity control

extra-meta

permalink

overview map: map type?

host our own osm tiles (hills+mapnik, zoom level <= 14, approx 15GB?)?
overview map: level <= 9

*/

		AttachEvent(window,'load',loadmapO,false);

	// ]]>
	</script>
{/literal}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px\"/>
<p>
This feature is still in development. Please use with care and try to avoid high server load.
</p>
<p>
Diese Kartenansicht ist noch in einem frühen Entwicklungsstadium! Bitte nicht übermäßig nutzen um zu hohe Serverlast zu vermeiden.
</p>
</div>

<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{elseif $ext}action="javascript:void()"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if !$ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
{/if}

<div class="smallmap" id="map" style="width:600px; height:500px;border:1px solid blue"></div><!-- FIXME Loading map... -->
<!--div class="smallmap" id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div-->
<!--div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div-->

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Show gridsquare"   onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Submit image"      onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Search for images" onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Clear marker"      onclick="clearMarker();" />
{*<a id="maplink" href="#">Link to this map</a>*}
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
{*<br />
{dynamic}
<input type="radio" name="mtradio" value="coverage" onclick="map.setUser(0);" {if $iniuser == 0}checked{/if} />Coverage |
<input type="radio" name="mtradio" value="depth" onclick="map.setUser(-1);" {if $iniuser == -1}checked{/if} />Depth |
{if $userid}<input type="radio" name="mtradio" value="personal" onclick="map.setUser({$userid});" {if $iniuser == $userid}checked{/if} />Personal |{/if}
<input type="radio" name="mtradio" value="user" onclick="if(!map.trySetUserId(document.theForm.mtuser.value)){ldelim}document.theForm.mtradio[{if $userid}3{else}2{/if}].checked=false;document.theForm.mtradio[0].checked=true;map.setUser(0);{rdelim};"
{if $iniuser > 0 and $iniuser != $userid}checked{/if} />User:
<input type="text" size="5" name="mtuser"  value="{if $iniuser > 0}{$iniuser}{elseif $userid}{$userid}{/if}" />
{/dynamic}*}
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
