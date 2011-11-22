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
            bottom: 0px 
        `$smarty.rdelim`
        #map `$smarty.ldelim`
            height: 512px;
        `$smarty.rdelim`
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
		var markers;

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
				map.removeOverlay(currentelement);
				currentelement = null;
				document.theForm.grid_reference.value = '';
				GEvent.trigger(map, "markergone");
			}
		}

		function showAddress(address) {
			if (!geocoder) {
				 geocoder = new GClientGeocoder();
			}
			if (geocoder) {
				geocoder.getLatLng(address,function(point) {
					if (!point) {
						alert("Your entry '" + address + "' could not be geocoded, please try again");
					} else {
						if (currentelement) {
							currentelement.setPoint(point);
							GEvent.trigger(currentelement,'drag');

						} else {
							currentelement = createMarker(point,null);
							map.addOverlay(currentelement);

							GEvent.trigger(currentelement,'drag');
						}
						map.setCenter(point, 12);
					}
				 });
			}
			return false;
		}

/*
OpacityControl.prototype.setOpacity = function() {
  this.layer.opacity = this.slide.left/56;
  if (this.map.getCurrentMapType() != this.maptype)
    return;
  //is there a less ugly way to repaint the map?
  //map.removeMapType(this.maptype);
  //map.addMapType(this.maptype);
  map.setMapType(G_NORMAL_MAP);
  map.setMapType(this.maptype);
  GEvent.trigger(this.layer, "opacitychanged");
}
*/

		/*function GetTileUrl_GeoM(txy, z) {
			return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z;
		}
		function GetTileUrl_GeoMO(txy, z) {
			return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&l=2&o=1";
		}*/
		function GetTileUrl_GeoMG(txy, z) {
			return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&l=8&o=1";
			//return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&l=4";
		}

		function GetTileUrl_Mapnik(a, z) {
		    return "http://tile.openstreetmap.org/" +
				z + "/" + a.x + "/" + a.y + ".png";
		}


		function GetTileUrl_TaH(a, z) {
		    return "http://tah.openstreetmap.org/Tiles/tile/" +
				z + "/" + a.x + "/" + a.y + ".png";
		}

		function GetTileUrl_TopB(a, z) {
		    //return "http://topo.openstreetmap.de/base/" +
		    return "http://base.wanderreitkarte.de/base/" +
				z + "/" + a.x + "/" + a.y + ".png";
		}

		function GetTileUrl_TopH(a, z) {
		    //return "http://hills-nc.openstreetmap.de/" +
		    return "http://wanderreitkarte.de/hills/" +
				z + "/" + a.x + "/" + a.y + ".png";
		}

		function GetTileUrl_Top(a, z) {
		    //return "http://topo.openstreetmap.de/topo/" +
		    return "http://topo.wanderreitkarte.de/topo/" +
				z + "/" + a.x + "/" + a.y + ".png";
		}

		function loadmapO() {
			var epsg4326 = new OpenLayers.Projection("EPSG:4326"); //FIXME rename to epsg4326
			var epsg900913 = new OpenLayers.Projection("EPSG:900913"); // FIXME use epsg900913 or map.getProjectionObject()?

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
					//new OpenLayers.Control.Permalink(),
					new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),//FIXME position
					//new OpenLayers.Control.Permalink('permalink'), //FIXME?
					//new OpenLayers.Control.MousePosition(),
					new OpenLayers.Control.Attribution(),
					//new OpenLayers.Control.OverviewMap(),// FIXME position/zoom level?
					//new OpenLayers.Control.KeyboardDefaults()
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
					//attribution: "Data CC-By-SA by <a href='http://openstreetmap.org/'>OpenStreetMap</a>",
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
			var ogeo = new OpenLayers.Layer.OSM(
				"OSM+Geo",
				"http://tile.openstreetmap.org/${z}/${x}/${y}.png"
			);
			var ogeosq = new OpenLayers.Layer.XYZ(
				"Squares",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=2&o=1&t=0",
				{
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
				}
			);
			ogeosq.setOpacity(0.5);//FIXME
			var ogeogr = new OpenLayers.Layer.XYZ(
				"Grid",
				"/tile.php?x=${x}&y=${y}&Z=${z}&l=8&o=1",
				{
					sphericalMercator : true,
					minZoomLevel : 4,
					maxZoomLevel : 14,
					numZoomLevels : null,
					isBaseLayer : false,
				}
			);
			var ogeoh = new OpenLayers.Layer.OSM( //FIXME our own version?
				"Profile",
				"http://wanderreitkarte.de/hills/${z}/${x}/${y}.png",
				{
					attribution: "Hoehen CIAT",//FIXME: wanderreitkarte Nop
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

			var SHADOW_Z_INDEX = 10;
			var MARKER_Z_INDEX = 11;
			var styleMap = new OpenLayers.StyleMap({
				// img/icons/cam-s.png == img/icons/view-s.png
				// img/icons/camicon.png  img/icons/viewicon.png
				externalGraphic:   "/img/icons/viewicon.png", //FIXME cam?
				backgroundGraphic: "/img/icons/view-s.png",
				backgroundXOffset: -10,
				backgroundYOffset: -34,
				backgroundWidth: 37,
				backgroundHeight: 34,
				graphicZIndex: MARKER_Z_INDEX,
				backgroundGraphicZIndex: SHADOW_Z_INDEX,
				//pointRadius: 20 //We use xxWitdh/xxHeight
				graphicWidth: 20,
				graphicHeight: 34,
				graphicXOffset: -10, // FIXME Offsets: +/- 1??
				graphicYOffset: -34,
			});
			var mtypelookup = {
				0: {externalGraphic:   "/img/icons/viewicon.png"},
				1: {externalGraphic:   "/img/icons/camicon.png"}, //FIXME shadow, ...
			};
			styleMap.addUniqueValueRules("default", "mtype", mtypelookup);
			markers = new OpenLayers.Layer.Vector(
				"Markers",
				{
					styleMap: styleMap,
					isBaseLayer: false,
					rendererOptions: {yOrdering: true},
					renderers: OpenLayers.Layer.Vector.prototype.renderers //FIXME?
				}
			);
			//vectorLayer.features[0].attributes.mtype=0;
			//vectorLayer.features[1].attributes.mtype=1;


			map.addLayers([
				mapnik, osmarender,
				geo,
				ogeo,ogeoh,ogeosq,ogeogr,
{/literal}
{if $google_maps_api_key}
{literal}
				gphy, gmap, gsat, ghyb,
{/literal}
{/if}
{literal}
				markers
			]);

			var overview =  new OpenLayers.Control.OverviewMap({
				maximized: true
			});
			map.addControl(overview);

			function createMarker(e) {
				var coords = map.getLonLatFromViewPortPx(e.xy);
				if (currentelement) {
					currentelement.move(coords);
				} else {
					currentelement = new OpenLayers.Feature.Vector(
						new OpenLayers.Geometry.Point(coords.lon, coords.lat), {mtype:0}
						//new OpenLayers.Geometry.Point(mpoint.lon, mpoint.lat), {mtype:1}
					);
					markers.addFeatures([currentelement]);//FIXME
				}
				coords.transform(map.getProjectionObject(), epsg4326);
				document.theForm.grid_reference.value = coords.lat+" "+coords.lon; //FIXME
			}
			function updateMarker(vector, pixel)
			{
				var lonlat = map.getLonLatFromPixel(pixel).transform(map.getProjectionObject(), epsg4326);
				document.theForm.grid_reference.value = lonlat.lat+" "+lonlat.lon; //FIXME
			}
			//GEvent.trigger(currentelement,'drag');
			//updateMapMarker(document.theForm.grid_reference,false,true);

			var dragFeature = new OpenLayers.Control.DragFeature(markers, {'onDrag': updateMarker});
			map.addControl(dragFeature);
			dragFeature.activate();
			var click = new OpenLayers.Control.Click({'trigger': createMarker});
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
				//currentelement = createMarker(new GLatLng(inimlat, inimlon),null);
				var mpoint = new OpenLayers.LonLat(inimlon, inimlat);
				mpoint.transform(epsg4326, map.getProjectionObject());
				currentelement = new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point(mpoint.lon, mpoint.lat), {mtype:0}
					//new OpenLayers.Geometry.Point(mpoint.lon, mpoint.lat), {mtype:1}
				);
				markers.addFeatures([currentelement]);//FIXME
				//GEvent.trigger(currentelement,'drag');
			}
		}

    //map.setCenter(point.transform(proj,proj2), zoom);
    //map.setCenter(point, zoom);

    //map.addControl(new OpenLayers.Control.LayerSwitcher());
    //map.addControl(new OpenLayers.Control.EditingToolbar(vector));
    //map.addControl(new OpenLayers.Control.ZoomPanel);
    //map.addControl(new OpenLayers.Control.Permalink());
    //map.addControl(new OpenLayers.Control.MousePosition());

/* FIXME/TODO

text like "Loading Map (JavaScript Required)..."

min/max zoom

ini: type

minimal zoom level
osm+g
layer switcher

for small maps: grid lines + square

markers /mappingO.js
 create markers
 drag marker
 markers<->form
 clear markers
 initial marker

opacity control

extra-meta

permalink

overview map: map type?

host our own osm tiles (hills+mapnik, zoom level <= 14, approx 15GB?)?

*/
/*  
    var bounds = new OpenLayers.Bounds();
    bounds.extend(new OpenLayers.LonLat(lonmin,latmin));
    bounds.extend(new OpenLayers.LonLat(lonmax,latmax));

var proj = new OpenLayers.Projection("EPSG:4326");
var point = new OpenLayers.LonLat(-71, 42);
map.setCenter(point.transform(proj, map.getProjectionObject()));

var bounds = new OpenLayers.Bounds(-74.047185, 40.679648, -73.907005, 40.882078)
bounds.transform(proj, map.getProjectionObject());
___________________________
var point1 = new OpenLayers.Geometry.Point(7, 48);
point1.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913")); 

var point2 = new OpenLayers.Geometry.Point(11, 54);
point2.transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913")); 

var bounds = new OpenLayers.Bounds();
bounds.extend(point1);
bounds.extend(point2);
bounds.toBBOX();






http://openlayers.org/dev/examples/graticule.html
http://wiki.openstreetmap.org/wiki/OpenLayers_Simple_Example
...
*/




		function loadmapG() {
			if (GBrowserIsCompatible()) {
				var op = 0.5;
				if (inio >= 0)
					op = inio;
				var opr = 1.0;
				if (inior >= 0)
					opr = inior;
				var user = 0;
				if (iniuser >= -1)
					user = iniuser;

				var copyright = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					'(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)');
				var copyrightCollection =
					new GCopyrightCollection('&copy; <a href="http://geo.hlipp.de">Geograph</a> and <a href="http://www.openstreetmap.org/">OSM</a> Contributors');
				copyrightCollection.addCopyright(copyright);
				var tilelayers = [new GTileLayer(copyrightCollection,4,13)];//FIXME 4 12?
				tilelayers[0].user = user;
				//tilelayers[0].getTileUrl = GetTileUrl_GeoM;
				tilelayers[0].getTileUrl = function(txy, z) {
					return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&t="+map.user;
				}
				tilelayers[0].isPng = function () { return true; };
				tilelayers[0].getOpacity = function () { return 1.0; };
				var proj = new GMercatorProjection(19);
				var geomapm = new GMapType(tilelayers, proj, "Geo", {tileSize: 256});

				var copyright1 = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					': http://www.openstreetmap.org/copyright');
				var copyright2 = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					': http://www.wanderreitkarte.de/licence_de.php');
				var copyright3 = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					': http://creativecommons.org/licenses/by-sa/2.0/');
				var copyrightCollectionOSMm = new GCopyrightCollection('(c) OSM (CC)');
				var copyrightCollectionTopoH = new GCopyrightCollection('Hoehen CIAT');
				//var copyrightCollectionOG = new GCopyrightCollection();
				var copyrightCollectionO = new GCopyrightCollection('Geograph Deutschland (CC)');
				copyrightCollectionOSMm.addCopyright(copyright1);
				copyrightCollectionTopoH.addCopyright(copyright2);
				//copyrightCollectionO.addCopyright(copyright);
				copyrightCollectionO.addCopyright(copyright3);
				var tilelayers_mapnikhg = new Array();
				tilelayers_mapnikhg[0] = new GTileLayer(copyrightCollectionOSMm, 4, 14);//0 18
				tilelayers_mapnikhg[0].isPng = function () { return true; };
				tilelayers_mapnikhg[0].getOpacity = function () { return 1.0; };
				tilelayers_mapnikhg[0].getTileUrl = GetTileUrl_Mapnik;
				tilelayers_mapnikhg[1] = new GTileLayer(copyrightCollectionTopoH, 9, 14);// 9 19
				tilelayers_mapnikhg[1].opacity = opr;
				tilelayers_mapnikhg[1].isPng = function () { return true; };
				tilelayers_mapnikhg[1].getOpacity = function () { return this.opacity; };
				tilelayers_mapnikhg[1].getTileUrl = GetTileUrl_TopH;
				tilelayers_mapnikhg[2] = new GTileLayer(copyrightCollectionO,4,14);
				tilelayers_mapnikhg[2].opacity = op;
				tilelayers_mapnikhg[2].user = user;
				//tilelayers_mapnikhg[2].getTileUrl = GetTileUrl_GeoMO;
				tilelayers_mapnikhg[2].getTileUrl = function(txy, z) {
					return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&l=2&o=1&t="+map.user;
				}
				tilelayers_mapnikhg[2].isPng = function () { return true; };
				tilelayers_mapnikhg[2].getOpacity = function () { return this.opacity; };
				tilelayers_mapnikhg[3] = new GTileLayer(copyrightCollectionO,4,14);
				tilelayers_mapnikhg[3].getTileUrl = GetTileUrl_GeoMG;
				tilelayers_mapnikhg[3].isPng = function () { return true; };
				tilelayers_mapnikhg[3].getOpacity = function () { return 1.0; };
				var mapnikhg_map = new GMapType(tilelayers_mapnikhg,
					proj, "OSM (Mapnik) + Profile",
					{ urlArg: 'mapnikhg', linkColor: '#000000', shortName: 'OSM+G', alt: 'OSM: Mapnik+Profile, Geo' });

				map = new GMap2(document.getElementById("map"));
				map.addMapType(G_PHYSICAL_MAP);
				map.addMapType(geomapm);
				map.addMapType(mapnikhg_map);

				map.user = user;
				map.geolayer = tilelayers_mapnikhg[2];
				map.relieflayer = tilelayers_mapnikhg[1];
				map.geomaplayer = tilelayers[0];

				G_NORMAL_MAP.gurlid = 'm';
				G_SATELLITE_MAP.gurlid = 'k';
				G_HYBRID_MAP.gurlid = 'h';
				G_PHYSICAL_MAP.gurlid = 'p';
				geomapm.gurlid = 'g';
				mapnikhg_map.gurlid = 'og';
				G_NORMAL_MAP.gmaxz = 21; // FIXME
				G_SATELLITE_MAP.gmaxz = 19;
				G_HYBRID_MAP.gmaxz = 21;
				G_PHYSICAL_MAP.gmaxz = 15;
				geomapm.gmaxz = 13;
				mapnikhg_map.gmaxz = 14;
				var maptypes = {
					'm' : G_NORMAL_MAP,
					'k' : G_SATELLITE_MAP,
					'h' : G_HYBRID_MAP,
					'p' : G_PHYSICAL_MAP,
					'g' : geomapm,
					'og': mapnikhg_map
				}

				G_PHYSICAL_MAP.getMinimumResolution = function () { return 4 };
				G_NORMAL_MAP.getMinimumResolution = function () { return 4 };
				G_SATELLITE_MAP.getMinimumResolution = function () { return 4 };
				G_HYBRID_MAP.getMinimumResolution = function () { return 4 };
				geomapm.getMinimumResolution = function () { return 4 };

				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl(true));
				map.addControl(new OpacityControl( tilelayers_mapnikhg[2], mapnikhg_map, 'Coverage: change opacity'));
				map.addControl(new OpacityControl( tilelayers_mapnikhg[1], mapnikhg_map, 'Relief: change opacity'),
				               new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(7,67)));

				var point = new GLatLng(lat0, lon0);
				var zoom = 5;
				var mt = geomapm;
				if (inilat < 90)
					point = new GLatLng(inilat, inilon);
				if (initype != '')
					mt = maptypes[initype];
				if (iniz >= 4 && iniz <= mt.gmaxz)
					zoom = iniz;
				map.setCenter(point, zoom, mt);

				map.enableDoubleClickZoom(); 
				map.enableContinuousZoom();
				map.enableScrollWheelZoom();

				if (inimlat < 90) {
					currentelement = createMarker(new GLatLng(inimlat, inimlon),null);
					map.addOverlay(currentelement);
					GEvent.trigger(currentelement,'drag');
				}
				map.setUser = function(u) {
					if (u < -1 || u == map.user)
						return;
					map.user = u;
					map.geolayer.user = u;
					map.geomaplayer.user = u;
					cmt = map.getCurrentMapType();
					if (cmt == geomapm || cmt == mapnikhg_map) {
						map.setMapType(G_NORMAL_MAP);
						map.setMapType(cmt);
					}
					GEvent.trigger(map, "userchanged");
				}
				map.trySetUserId = function(s) {
					u = parseInt(s, 10);
					if (u > 0) {
						map.setUser(u);
						return true;
					}
					return false;
				}
				function updateMapLink() {
					var ll = map.getCenter();
					var url = '?z=' + map.getZoom()
						+ '&t=' + map.getCurrentMapType().gurlid
						+ '&ll=' + ll.lat() + ',' + ll.lng();
					if (map.user != 0) {
						url += '&u=' + map.user;
					}
					if (map.geolayer.opacity != 0.5) {
						url += '&o=' + map.geolayer.opacity;
					}
					if (map.relieflayer.opacity != 1) {
						url += '&or=' + map.relieflayer.opacity;
					}
					if (currentelement) {
						ll = currentelement.getPoint();
						url += '&mll=' + ll.lat() + ',' + ll.lng();
					}
					var curlink = document.getElementById("maplink");
					curlink.setAttribute("href", url);
				}
				updateMapLink();
				GEvent.addListener(map, "maptypechanged", updateMapLink);
				GEvent.addListener(map, "userchanged", updateMapLink);
				GEvent.addListener(map, "moveend", updateMapLink);
				GEvent.addListener(map, "zoomend", updateMapLink);
				GEvent.addListener(tilelayers_mapnikhg[2], "opacitychanged", updateMapLink);
				GEvent.addListener(tilelayers_mapnikhg[1], "opacitychanged", updateMapLink);
				GEvent.addListener(map, "markergone", updateMapLink);

				GEvent.addListener(map, "click", function(marker, point) {
					if (marker) {
					} else if (currentelement) {
						currentelement.setPoint(point);
						GEvent.trigger(currentelement,'drag');
					} else {
						currentelement = createMarker(point,null);
						map.addOverlay(currentelement);
						GEvent.trigger(currentelement,'drag');
					}
					updateMapLink();
				});


				AttachEvent(window,'unload',GUnload,false);

				// Add a move listener to restrict the bounds range
				GEvent.addListener(map, "move", function() {
					checkBounds();
				});

				// The allowed region which the whole map must be within
				var allowedBounds = new GLatLngBounds(new GLatLng(latmin,lonmin), new GLatLng(latmax,lonmax));

				// If the map position is out of range, move it back
				function checkBounds() {
					// Perform the check and return if OK
					if (allowedBounds.contains(map.getCenter())) {
					  return;
					}
					// It`s not OK, so find the nearest allowed point and move there
					var C = map.getCenter();
					var X = C.lng();
					var Y = C.lat();

					var AmaxX = allowedBounds.getNorthEast().lng();
					var AmaxY = allowedBounds.getNorthEast().lat();
					var AminX = allowedBounds.getSouthWest().lng();
					var AminY = allowedBounds.getSouthWest().lat();

					if (X < AminX) {X = AminX;}
					if (X > AmaxX) {X = AmaxX;}
					if (Y < AminY) {Y = AminY;}
					if (Y > AmaxY) {Y = AmaxY;}

					map.setCenter(new GLatLng(Y,X));

					// This Javascript Function is based on code provided by the
					// Blackpool Community Church Javascript Team
					// http://www.commchurch.freeserve.co.uk/   
					// http://econym.googlepages.com/index.htm
				}
			}
		}

		AttachEvent(window,'load',loadmapO,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);
		}
		//AttachEvent(window,'load',updateMapMarkers,false);//FIXME
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
<a href="#" id="permalink">Permalink</a>
<div id="docs"></div>

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Show gridsquare"   onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Submit image"      onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Search for images" onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Clear marker"      onclick="clearMarker();" />
<a id="maplink" href="#">Link to this map</a>
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
<br />
{dynamic}
<input type="radio" name="mtradio" value="coverage" onclick="map.setUser(0);" {if $iniuser == 0}checked{/if} />Coverage |
<input type="radio" name="mtradio" value="depth" onclick="map.setUser(-1);" {if $iniuser == -1}checked{/if} />Depth |
{if $userid}<input type="radio" name="mtradio" value="personal" onclick="map.setUser({$userid});" {if $iniuser == $userid}checked{/if} />Personal |{/if}
<input type="radio" name="mtradio" value="user" onclick="if(!map.trySetUserId(document.theForm.mtuser.value)){ldelim}document.theForm.mtradio[{if $userid}3{else}2{/if}].checked=false;document.theForm.mtradio[0].checked=true;map.setUser(0);{rdelim};"
{if $iniuser > 0 and $iniuser != $userid}checked{/if} />User:
<input type="text" size="5" name="mtuser"  value="{if $iniuser > 0}{$iniuser}{elseif $userid}{$userid}{/if}" />
{/dynamic}
</div>
{/if}


</form>
<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address:</label>
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Find"/><small><small><br/>
	(Powered by the Google Maps API Geocoder)</small></small>
</div>
</form>

{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
