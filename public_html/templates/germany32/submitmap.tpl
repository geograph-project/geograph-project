{if $inner}
{assign var="page_title" value="Grid Ref Finder"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Grid Ref Finder"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingG3.js"|revision}"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
		var nolineslayer = true;
		
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
{literal}

		function showAddress(address) {
			if (!geocoder) {
				 geocoder = new google.maps.Geocoder();
			}
			if (geocoder) {
				geocoder.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var point = results[0].geometry.location;

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
			var point = new google.maps.LatLng(lat0,lon0);
			var zoom = 5;
			var mapTypeId = google.maps.MapTypeId.HYBRID;

			map = new google.maps.Map(
				document.getElementById('map'), {
				center: point,
				zoom: zoom,
				mapTypeId: mapTypeId,
				streetViewControl: false, //true,
				mapTypeControlOptions: {
					mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, google.maps.MapTypeId.TERRAIN],
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
				}
			});
			map.setTilt(0);

			google.maps.event.addListener(map, "click", function(event) {
				makeMarker(event.latLng);
			});
		}

		function makeMarker(position) {
			if (currentelement) {
				currentelement.setPosition(position);
			} else {
				currentelement = createMarker(position,null);
			}
			google.maps.event.trigger(currentelement,'drag');
		}

		AttachEvent(window,'load',loadmap,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);
		}
		AttachEvent(window,'load',updateMapMarkers,false);
	// ]]>
	</script>
{/literal}

<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">


<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="16" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>

<div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div>		

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>
<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address:</label>
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Find"/><small><small><br/>
	(Powered by the Google Maps API Geocoder)</small></small>
</div>
</form>

<script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;key={$google_maps_api_key}" type="text/javascript"></script>
{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
