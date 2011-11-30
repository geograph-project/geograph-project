{if $inner}
{assign var="page_title" value="Grid Ref Finder"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Grid Ref Finder"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingG.js"|revision}"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;

		//the google map object
		var map;

		//the geocoder object
		var geocoder;
		var running = false;

		function showAddress(address) {
			if (!geocoder) {
				 geocoder = new GClientGeocoder();
			}
			if (geocoder) {
				//replace full uk postcodes by the sector version
				//address = address.replace(/\b([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9]?)([A-Z]{0,2})\b/i,'$1$2 $3');
				//uk postcodes now work!

				geocoder.getLatLng(address,function(point) {
					if (!point) {
						alert("Your entry '" + address + "' could not be geocoded, please try again");
					} else {
						lat = point.lat();
						lng = point.lng();
						ire = (lat > 51.2 && lat < 55.73 && lng > -12.2 && lng < -4.8);
						uk = (lat > 49 && lat < 62 && lng > -9.5 && lng < 2.3);

						if (!uk && !ire) {
							alert("Address could not be resolved to a British Isles location, please try again");
							return;
						}

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

		function loadmap() {
			if (GBrowserIsCompatible()) {
				map = new GMap2(document.getElementById("map"));
				map.addMapType(G_PHYSICAL_MAP);

				G_PHYSICAL_MAP.getMinimumResolution = function () { return 5 };
				G_NORMAL_MAP.getMinimumResolution = function () { return 5 };
				G_SATELLITE_MAP.getMinimumResolution = function () { return 5 };
				G_HYBRID_MAP.getMinimumResolution = function () { return 5 };

				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl(true));

				var point = new GLatLng(54.55,-3.88);
				var zoom = 5;

				newview = readCookie('GMapView');
				if (newview) {

					var pairs = newview.split("&");
					for (var i=0; i<pairs.length; i++) {
						// break each pair at the first "=" to obtain the argname and value
						var pos = pairs[i].indexOf("=");
						var argname = pairs[i].substring(0,pos).toLowerCase();
						var value = pairs[i].substring(pos+1).toLowerCase();

						if (argname == "ll") {
							var bits = value.split(',');
							point = new GLatLng(parseFloat(bits[0]),parseFloat(bits[1]));
						}
						if (argname == "z") {zoom = parseInt(value,10);}
						if (argname == "t") {
							if (value == "m") {mapType = G_NORMAL_MAP;}
							if (value == "k") {mapType = G_SATELLITE_MAP;}
							if (value == "h") {mapType = G_HYBRID_MAP;}
							if (value == "p") {mapType = G_PHYSICAL_MAP;}
							if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
						}
					}
				}

				newtype = readCookie('GMapType');
				if (newtype) {
					if (newtype == "m") {mapType = G_NORMAL_MAP;}
					if (newtype == "k") {mapType = G_SATELLITE_MAP;}
					if (newtype == "h") {mapType = G_HYBRID_MAP;}
					if (newtype == "p") {mapType = G_PHYSICAL_MAP;}
					if (newtype == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
					map.setCenter(point, zoom, mapType);
				} else {
					map.setCenter(point, zoom);
				}

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
				GEvent.addListener(map, "move", function() {
					checkBounds();
				});

				// The allowed region which the whole map must be within
				var allowedBounds = new GLatLngBounds(new GLatLng(49.4,-11.8), new GLatLng(61.8,4.1));

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
				GEvent.addListener(map, "moveend", saveView);
				GEvent.addListener(map, "zoomend", saveView);
				GEvent.addListener(map, "maptypechanged", saveMapType);
				map.savePosition();
			}
		}

		function saveView() {
			var ll = map.getCenter().toUrlValue(6);
			var z = map.getZoom();
			var t = map.getCurrentMapType().getUrlArg();
			createCookie('GMapView','ll='+ll+'&z='+z+'&t='+t,10);
		}

		function saveMapType() {
			var t = map.getCurrentMapType().getUrlArg();
			createCookie('GMapType',t,10);
		}

		AttachEvent(window,'load',loadmap,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);

			if (document.theForm.grid_reference.value.length > 4 && currentelement) {
				point = currentelement.getLatLng();
				map.setCenter(point,12);
			}

		}
		AttachEvent(window,'load',updateMapMarkers,false);
{/literal}

		{dynamic}
		{if $container}
			{literal}

			function resizeContainer() {
				var FramePageHeight =  document.body.offsetHeight + 10;
				window.parent.document.getElementById('{/literal}{$container|escape:'javascript'}{literal}').style.height=FramePageHeight+'px';
			}

			AttachEvent(window,'load',resizeContainer,false);
			{/literal}
		{/if}
		{/dynamic}

	</script>


<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">


<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>

<div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div>

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>
<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address:
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Find"/><small><small><br/>
	(Powered by the Google Maps API Geocoder)<br/>
	Change view: <a href="javascript:void(map.setCenter(new GLatLng(55.55,-3.88), 5));">Whole British Isles</a> &middot; <a href="javascript:void(map.returnToSavedPosition());">Initial View</a> &middot; <a href="javascript:void(map.setCenter(currentelement.getLatLng(), 12));">Center on Marker</a></small></small>
</div>
</form>

<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}