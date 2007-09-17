{assign var="page_title" value="Grid Ref Finder"}
{include file="_std_begin.tpl"}


<script type="text/javascript" src="http://s0.{$http_host}/mapper/geotools2.js"></script>
<script type="text/javascript" src="http://s0.{$http_host}/mappingG.v{$javascript_version}.js"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var themarker;
		
		function loadmap() {
			if (GBrowserIsCompatible()) {
				var map = new GMap2(document.getElementById("map"));
				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl(true));
				
				var point = new GLatLng(54.55,-3.88);
				map.setCenter(point, 5);

				map.enableDoubleClickZoom(); 
				map.enableContinuousZoom();
				map.enableScrollWheelZoom();
		
				GEvent.addListener(map, "click", function(marker, point) {
					if (marker) {
					} else if (themarker) {
						themarker.setPoint(point);
						GEvent.trigger(themarker,'drag');
					
					} else {
						themarker = createMarker(point,null);
						map.addOverlay(themarker);
						
						GEvent.trigger(themarker,'drag');
					}
				});
		
				AttachEvent(window,'unload',GUnload,false);
			}
		}
		AttachEvent(window,'load',loadmap,false);
	
		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);
		}
		AttachEvent(window,'load',updateMapMarkers,false);
	</script>
{/literal}

<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form action="/submit.php" name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">


<p><label for="grid_reference"><b style="color:#0018F8">Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{if $grid_reference}{$grid_reference|escape:'html'}{/if}" size="14" onkeyup="updateMapMarker(this,false)"/>

<input type="submit" value="Step 2 &gt; &gt;"/></p>

<div id="map" style="width:600px; height:500px">Loading map...</div><br/>			
			


<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>

<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
			

{include file="_std_end.tpl"}
