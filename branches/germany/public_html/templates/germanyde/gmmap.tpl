{if $inner}
{assign var="page_title" value="Geograph Mercator-Karte"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Geograph Mercator-Karte"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingG.js"|revision}"></script>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
		
		//the google map object
		var map;

		//the geocoder object
		var geocoder;
		var running = false;

{/literal}
		var lat0 = {$lat0};
		var lon0 = {$lon0};
		var latmin = {$latmin};
		var latmax = {$latmax};
		var lonmin = {$lonmin};
		var lonmax = {$lonmax};
		var iniz = {$iniz};
		var initype = '{$initype}';
		var inio = {$inio};
		var inior = {$inior};
		var inilat = {$inilat};
		var inilon = {$inilon};
		var inimlat = {$inimlat};
		var inimlon = {$inimlon};
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
						alert("Die Eingabe '" + address + "' konnte nicht bearbeitet werden, bitte nochmals versuchen");
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
 * Based on Opacity GControl by Klokan Petr Pridal (based on XSlider of Mike Williams)
 */
 
function OpacityControl( layer, maptype, title ) {
  this.layer = layer;
  this.maptype = maptype;
  this.title = title;
}
OpacityControl.prototype = new GControl();

// This function positions the slider to match the specified opacity
OpacityControl.prototype.setSlider = function(pos) {
  var left = Math.round((58*pos));
  this.slide.left = left;
  this.knob.style.left = left+"px";
  this.knob.style.top = "0px"; // correction001
}

// This function reads the slider and sets the overlay opacity level
OpacityControl.prototype.setOpacity = function() {
  this.layer.opacity = this.slide.left/58;
  if (this.map.getCurrentMapType() != this.maptype)
    return;
  //is there a less ugly way to repaint the map?
  //map.removeMapType(this.maptype);
  //map.addMapType(this.maptype);
  map.setMapType(G_NORMAL_MAP);
  map.setMapType(this.maptype);
  GEvent.trigger(this.layer, "opacitychanged");
}

// This gets called by the API when addControl(new OpacityControl())
OpacityControl.prototype.initialize = function(map) {
  var that=this;
  this.map = map;
  //this.layer = layer;

  // Is this MSIE, if so we need to use AlphaImageLoader
  var agent = navigator.userAgent.toLowerCase();
  if ((agent.indexOf("msie") > -1) && (agent.indexOf("opera") < 1)){this.ie = true} else {this.ie = false}

  // create the background graphic as a <div> containing an image
  var container = document.createElement("div");
  container.style.width="70px";
  container.style.height="21px";

  // Handle transparent PNG files in MSIE
  if (this.ie) {
    var loader = "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='/img/opacity-slider.png', sizingMethod='crop');";
    container.innerHTML = '<div style="height:21px; width:70px; ' +loader+ '" ></div>';
  } else {
    container.innerHTML = '<div style="height:21px; width:70px; background-image:url(/img/opacity-slider.png)" ></div>';
  }

  // create the knob as a GDraggableObject
  // Handle transparent PNG files in MSIE
  if (this.ie) {
    var loader = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='/img/opacity-slider.png', sizingMethod='crop');";
    this.knob = document.createElement("div"); 
    this.knob.title = this.title;
    this.knob.style.height="21px";
    this.knob.style.width="13px";
    this.knob.style.overflow="hidden";
    this.knob_img = document.createElement("div"); 
    this.knob_img.style.height="21px";
    this.knob_img.style.width="83px";
    this.knob_img.style.filter=loader;
    this.knob_img.style.position="relative";
    this.knob_img.style.left="-70px";
    this.knob.appendChild(this.knob_img);
  } else {
    this.knob = document.createElement("div"); 
    this.knob.title = this.title;
    this.knob.style.height="21px";
    this.knob.style.width="13px";
    this.knob.style.backgroundImage="url(/img/opacity-slider.png)";
    this.knob.style.backgroundPosition="-70px 0px";
  }
  container.appendChild(this.knob);
  this.slide=new GDraggableObject(this.knob, {container:container});
  this.slide.setDraggableCursor('pointer');
  this.slide.setDraggingCursor('pointer');
  this.container = container;

  // attach the control to the map
  map.getContainer().appendChild(container);

  // init slider
  this.setSlider( this.layer.opacity );

  // Listen for the slider being moved and set the opacity
  GEvent.addListener(this.slide, "dragend", function() {that.setOpacity()});
  GEvent.addListener(this.map, "maptypechanged", function() {
    if (that.map.getCurrentMapType() != that.maptype) {
      that.container.style.display="none";
    } else {
      that.container.style.display="block";
      that.setSlider( that.layer.opacity );
    }
  } );

  return container;
}

// Set the default position for the control
OpacityControl.prototype.getDefaultPosition = function() {
  return new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(7, 47));
}

		function GetTileUrl_GeoM(txy, z) {
			return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z;
		}
		function GetTileUrl_GeoMO(txy, z) {
			return "/tile.php?x="+txy.x+"&y="+txy.y+"&Z="+z+"&l=2&o=1";
		}
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

		function loadmap() {
			if (GBrowserIsCompatible()) {
				var op = 0.5;
				if (inio >= 0)
					op = inio;
				var opr = 1.0;
				if (inior >= 0)
					opr = inior;

				var copyright = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					'(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)');
				var copyrightCollection =
					new GCopyrightCollection('&copy; <a href="http://geo.hlipp.de">Geograph</a> and <a href="http://www.openstreetmap.org/">OSM</a> Contributors');
				copyrightCollection.addCopyright(copyright);
				var tilelayers = [new GTileLayer(copyrightCollection,4,13)];//FIXME 4 12?
				tilelayers[0].getTileUrl = GetTileUrl_GeoM;
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
				tilelayers_mapnikhg[2].getTileUrl = GetTileUrl_GeoMO;
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

				map.geolayer = tilelayers_mapnikhg[2];
				map.relieflayer = tilelayers_mapnikhg[1];

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
				map.addControl(new OpacityControl( tilelayers_mapnikhg[2], mapnikhg_map, 'Abdeckung: Transparenz aendern'));
				map.addControl(new OpacityControl( tilelayers_mapnikhg[1], mapnikhg_map, 'Profil: Transparenz aendern'),
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
				function updateMapLink() {
					var ll = map.getCenter();
					var url = '?z=' + map.getZoom()
						+ '&t=' + map.getCurrentMapType().gurlid
						+ '&ll=' + ll.lat() + ',' + ll.lng();
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

		AttachEvent(window,'load',loadmap,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);
		}
		AttachEvent(window,'load',updateMapMarkers,false);
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

<p>Bitte Karte anklicken um einen verschiebbaren Marker zu erzeugen...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{elseif $ext}action="javascript:void()"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if !$ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Nächster Schritt &gt; &gt;"/> <span id="dist_message"></span></div>
{/if}

<div id="map" style="width:600px; height:500px;border:1px solid blue">Karte wird geladen... (JavaScript nötig)</div>

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Planquadrat zeigen" onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Bild einreichen"    onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Bilder suchen"      onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Marker löschen"     onclick="clearMarker();" />
<a id="maplink" href="#">Link zur Karte</a>
</div>
{/if}

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>
<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Adresse eingeben:
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Suchen"/><small><small><br/>
	(über Google Maps API Geocoder)</small></small>
</div>
</form>

{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
