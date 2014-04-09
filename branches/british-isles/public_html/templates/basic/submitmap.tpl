{if $inner}
{assign var="page_title" value="Grid Ref Finder"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Grid Ref Finder"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/js/mappingG3.js"|revision}"></script>
<script type="text/javascript" src="{"/js/nls.tileserver.com-api.js"|revision}"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;

		//the google map object
		var map;
		var panorama;

		//the geocoder object
		var geocoder;
		var running = false;

		function showAddress(address) {
			if (!geocoder) {
				 geocoder = new google.maps.Geocoder();
			}
			if (geocoder) {
				//replace full uk postcodes by the sector version
				//address = address.replace(/\b([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9]?)([A-Z]{0,2})\b/i,'$1$2 $3');
				//uk postcodes now work!

				geocoder.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var point = results[0].geometry.location;

						lat = point.lat();
						lng = point.lng();
						ire = (lat > 51.2 && lat < 55.73 && lng > -12.2 && lng < -4.8);
						uk = (lat > 49 && lat < 62 && lng > -9.5 && lng < 2.3);

						if (!uk && !ire) {
							alert("Address could not be resolved to a British Isles location, please try again");
							return;
						}

						makeMarker(point)
						map.panTo(point);

					} else {
						alert('Geocode was not successful for the following reason: ' + status);
					}
				});
			}
			return false;
		}

		function loadmap() {
			var point = new google.maps.LatLng(54.55,-3.88);
			var zoom = 5;
			var newtype = readCookie('GMapType');
			var mapTypeId = google.maps.MapTypeId.TERRAIN;

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
						point = new google.maps.LatLng(parseFloat(bits[0]),parseFloat(bits[1]));
					}
					if (argname == "z") {zoom = parseInt(value,10);}
					if (argname == "t") {

					}
				}
			}

			mapTypeId = firstLetterToType(newtype);

			map = new google.maps.Map(
				document.getElementById('map'), {
				center: point,
				zoom: zoom,
				mapTypeId: mapTypeId,
				streetViewControl: true,
				mapTypeControlOptions: {
					mapTypeIds: mapTypeIds,
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
				}
			});

			setupNLSTiles(map);
			setupOSMTiles(map);

			google.maps.event.addListener(map, "click", function(event) {
				makeMarker(event.latLng);
			});

			panorama = map.getStreetView();
			google.maps.event.addListener(panorama, "position_changed", streetViewPosition);
			google.maps.event.addListener(panorama, "visible_changed", streetViewPosition);

			google.maps.event.addListener(map, "idle", saveView);
			google.maps.event.addListener(map, "maptypeid_changed", saveMapType);

			Attribution(map,mapTypeId);
		}

		function streetViewPosition() {
			if (!panorama.getVisible())
				return false;

			makeMarker(panorama.getPosition());
		}

		function makeMarker(position) {
			if (currentelement) {
				currentelement.setPosition(position);
			} else {
				currentelement = createMarker(position,null);
			}
			google.maps.event.trigger(currentelement,'drag');
		}
		function centerMarker() {
			map.panTo(currentelement.getPosition());
			if (map.getZoom() < 10) {
				map.setZoom(11);
			}
		}

		function saveView() {
			var ll = map.getCenter().toUrlValue(6);
			var z = map.getZoom();
			var t = map.getMapTypeId().substr(0,1);
			createCookie('GMapView','ll='+ll+'&z='+z+'&t='+t,10);
		}

		function saveMapType() {
			var t = map.getMapTypeId().substr(0,1);
			createCookie('GMapType',t,10);
		}

		function resetView() {
			map.setZoom(5);
			map.panTo(new google.maps.LatLng(55.55,-3.88));
		}

		AttachEvent(window,'load',loadmap,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);

			if (document.theForm.grid_reference.value.length > 4 && currentelement) {
				point = currentelement.getPosition();
				map.setCenter(point,12);
			}
		}
		AttachEvent(window,'load',updateMapMarkers,false);

function myPress(that,event) {
	var unicode=event.keyCode? event.keyCode : event.charCode;
	if (unicode == 13) { //enter
		showAddress(that.value);
		return false;
	}
	return true;
}

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


<div style="width:600px; text-align:center;">
<label for="addressInput">Enter Address/place/postcode:	<input type="text" size="42" id="addressInput" name="address" value="" onkeypress="return myPress(this,event)"/></label> <input type="button" value="Find" onclick="return showAddress(this.form.elements['address'].value);"/><br/>
<label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>
<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>

<div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div>

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>
<div style="width:600px; text-align:center;">
	<small><small>Change view: <a href="javascript:void(resetView());">Whole British Isles</a> &middot; <a href="javascript:void(centerMarker());">Center on Marker</a></small></small>
</div>

<script src="//maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>
{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
