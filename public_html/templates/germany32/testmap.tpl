{if $inner}
{assign var="page_title" value="Geograph-Karte"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Geograph-Karte"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingG.js"|revision}"></script>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var ri = -1;
		
		//the google map object
		var map;

		//the geocoder object
		var geocoder;
		var running = false;

		function UTMProj(ri) {
			if (ri == 4) {
				this.zone = 33;
				this.lonmin = 12;
				this.lonmax = 16;
				this.x0 = 300;
				this.xmin = 550;
				this.xmax = 850;
			} else if (ri == 5) {
				this.zone = 31;
				this.lonmin = 4;
				this.lonmax = 6;
				this.x0 = -700;
				this.xmin = 0;
				this.xmax = 50;
			} else {
				this.zone = 32;
				this.lonmin = 6;
				this.lonmax = 12;
				this.x0 = -200;
				this.xmin = 50;
				this.xmax = 550;
				ri = 3;
			}
			this.ri = ri;
			this.latmin = 47;
			this.latmax = 56;
			this.y0 = -5200;
			this.pixperkm=[ 0.5, 1, 2, 4, 8, 16, 32, 64 ];
			this.kmpertile=[ 512, 256, 128, 64, 32, 16, 8, 4 ];
			//this.yflip = [1331, 999, 999, 999];
			this.yflip = [1024, 1024, 1024, 1024, 1024, 1024, 1024, 1024];
			//this.xmin = 0;
			//this.xmax = 900;
			this.ymin = 0;
			this.ymax = 1000;
		}

		UTMProj.prototype=new GProjection();
		UTMProj.prototype.fromLatLngToPixel=function(ll, z) {
			//alert("ll: "+ll.lat()+"/"+ll.lng()+":"+z);
			var coord = GT_Math.wgs84_to_utm(ll.lat(), ll.lng(), this.zone);
			var e = coord[0];
			var n = coord[1];
			//alert("ll: "+ll.lat()+"/"+ll.lng()+":"+z+">"+e+"/"+n);
			var y = Math.round((this.yflip[z]-(n/1000+this.y0)) * this.pixperkm[z]);
			var x = Math.round(               (e/1000+this.x0)  * this.pixperkm[z]);
			//alert("ll: "+ll.lat()+"/"+ll.lng()+":"+z+">"+e+"/"+n+">"+x+"/"+y);
			//550 370
			return new GPoint(x,y);
		}
		UTMProj.prototype.fromPixelToLatLng=function(xy, z, unb) {
			//alert("xy: "+xy.x+"/"+xy.y+":"+z);
			var x = xy.x;
			var y = xy.y;
			var e = (              x/this.pixperkm[z] - this.x0) * 1000;
			var n = (this.yflip[z]-y/this.pixperkm[z] - this.y0) * 1000;
			var coord = GT_Math.utm_to_wgs84(e, n, this.zone);
			var lat = coord[0];
			var lon = coord[1];
			return new GLatLng(lat,lon,unb);
		}
		UTMProj.prototype.tileCheckRange=function(txy,z,ts) {
			//alert("cr: "+txy.x+"/"+txy.y+":"+z+":"+ts);
			//FIXME ts should be 256
			//FIXME server side check (load)
			//FIXME zone specific check!
			var y1 = Math.round(this.yflip[z]- txy.y * this.kmpertile[z] - this.kmpertile[z]);
			var x1 = Math.round(               txy.x * this.kmpertile[z]);
			//return true;//FIXME

			if (y1 + this.kmpertile[z] < this.ymin || y1 > this.ymax)
				return false;
			if (x1 + this.kmpertile[z] < this.xmin || x1 > this.xmax)
				return false;

			return true;
		}
		UTMProj.prototype.getWrapWidth=function(z) {
			return 1.0e30; //FIXME
		}

		// The allowed region which the whole map must be within
		var allowedBounds;
		function setBounds() {
			var proj = map.getCurrentMapType().getProjection();
			allowedBounds = new GLatLngBounds(new GLatLng(proj.latmin,proj.lonmin), new GLatLng(proj.latmax,proj.lonmax));
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
						//FIXME
						// map.getCurrentMapType() 
						// getMapTypes()
						// mtype.getProjection() 
						//var curmap;
						//var lon = point.lng();
						//if (proj5.lonmin < lon && lon <= proj5.lonmax)
						//	curmap = geomap5;
						//else if (proj4.lonmin < lon && lon <= proj4.lonmax)
						//	curmap = geomap4;
						//else
						//	curmap = geomap3;
						var curmap = map.getCurrentMapType();//null
						var maptypes = map.getMapTypes();
						var lon = point.lng();
						for (idx in maptypes) {
							var mtype = maptypes[idx];
							var proj = mtype.getProjection();
							if (proj.lonmin < lon && lon <= proj.lonmax) {
								curmap = mtype;
								break;
							}
						}

						map.setCenter(point, 2, curmap);
						setBounds();
					}
				 });
			}
			return false;
		}

		function GetTileUrl_Geo(txy, z, ri) {
			var pixperkm=[ 0.5, 1, 2, 4, 8, 16, 32, 64 ];
			var kmpertile=[ 512, 256, 128, 64, 32, 16, 8, 4 ];
			//var yflip = [1331, 999, 999, 999];
			var yflip = [1024, 1024, 1024, 1024, 1024, 1024, 1024, 1024];
			var ts = 256;
			var y1 = Math.round(yflip[z]- txy.y * kmpertile[z] - kmpertile[z]);
			var x1 = Math.round(          txy.x * kmpertile[z]);
			//return "/test/tile.php?x="+y1+"&amp;y="+x1;
			//return "/tile.php?x="+y1+"&y="+x1+"&z="+z;
			//return "test/tile.php?x="+y1+"&y="+x1+"&z="+z;
			//alert("ti: "+txy.x+"/"+txy.y+" > "+y1+"/"+x1);
			return "/tile.php?x="+x1+"&y="+y1+"&z="+(-z-1)+"&i="+ri;
			//return "test/tile/"+z+"/"+y1+"/"+x1+"/0.png";
		}

		function GetTileUrl_Geo3(txy, z) { return GetTileUrl_Geo(txy, z, 3); }
		function GetTileUrl_Geo4(txy, z) { return GetTileUrl_Geo(txy, z, 4); }
		function GetTileUrl_Geo5(txy, z) { return GetTileUrl_Geo(txy, z, 5); }

		function loadmap() {
			if (GBrowserIsCompatible()) {
				var copyright = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					'(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)');
				var copyrightCollection =
					new GCopyrightCollection('&copy; <a href="http://geo.hlipp.de">Geograph</a> and <a href="http://www.openstreetmap.org/">OSM</a> Contributors');
				copyrightCollection.addCopyright(copyright);
				var tilelayers3 = [new GTileLayer(copyrightCollection,0,7)];
				tilelayers3[0].getTileUrl = GetTileUrl_Geo3;
				tilelayers3[0].isPng = function () { return true; };
				tilelayers3[0].getOpacity = function () { return 1.0; };
				var tilelayers4 = [new GTileLayer(copyrightCollection,0,7)];
				tilelayers4[0].getTileUrl = GetTileUrl_Geo4;
				tilelayers4[0].isPng = function () { return true; };
				tilelayers4[0].getOpacity = function () { return 1.0; };
				var tilelayers5 = [new GTileLayer(copyrightCollection,0,7)];
				tilelayers5[0].getTileUrl = GetTileUrl_Geo5;
				tilelayers5[0].isPng = function () { return true; };
				tilelayers5[0].getOpacity = function () { return 1.0; };
				var proj3 = new UTMProj(3);
				var proj4 = new UTMProj(4);
				var proj5 = new UTMProj(5);

				//var geomap3 = new GMapType(tilelayers3, new UTMProj(3), "Z32", {tileSize: 256});
				//var geomap4 = new GMapType(tilelayers4, new UTMProj(4), "Z33", {tileSize: 256});
				//var geomap5 = new GMapType(tilelayers5, new UTMProj(5), "Z31", {tileSize: 256});
				var geomap3 = new GMapType(tilelayers3, proj3, "Z32", {tileSize: 256});
				var geomap4 = new GMapType(tilelayers4, proj4, "Z33", {tileSize: 256});
				var geomap5 = new GMapType(tilelayers5, proj5, "Z31", {tileSize: 256});
				var curmap = geomap3;


				map = new GMap2(document.getElementById("map"), {mapTypes:[geomap5, geomap3, geomap4]});
				//map = new GMap2(document.getElementById("map"), {mapTypes:[geomap3, geomap4, geomap5]});
				//map = new GMap2(document.getElementById("map"), {mapTypes:[geomap3/*, geomap4, geomap5*/]});
				//map.addMapType(G_PHYSICAL_MAP);

				//G_PHYSICAL_MAP.getMinimumResolution = function () { return 5 };
				//G_NORMAL_MAP.getMinimumResolution = function () { return 5 };
				//G_SATELLITE_MAP.getMinimumResolution = function () { return 5 };
				//G_HYBRID_MAP.getMinimumResolution = function () { return 5 };

				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl(true));
				
				//var point = new GLatLng(51, 10); //(54.55,-3.88); //FIXME
				var curproj = curmap.getProjection();
				var point = new GLatLng((curproj.latmin+curproj.latmax)*.5, (curproj.lonmin+curproj.lonmax)*.5);
				map.setCenter(point, 0, curmap);
				setBounds();

				map.enableDoubleClickZoom(); 
				map.enableContinuousZoom();
				map.enableScrollWheelZoom();
		
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
				});


				AttachEvent(window,'unload',GUnload,false);

				// Add a move listener to restrict the bounds range
				GEvent.addListener(map, "maptypechanged", function() {
					setBounds();
					checkBounds();
				});

				// Add a move listener to restrict the bounds range
				GEvent.addListener(map, "move", function() {
					checkBounds();
				});

				// The allowed region which the whole map must be within
				//var allowedBounds = new GLatLngBounds(new GLatLng(45,2), new GLatLng(57,18));//(new GLatLng(49.4,-11.8), new GLatLng(61.8,4.1));
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

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">


<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Nächster Schritt &gt; &gt;"/> <span id="dist_message"></span></div>

<div id="map" style="width:600px; height:500px;border:1px solid blue">Karte wird geladen... (JavaScript nötig)</div><br/>

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
