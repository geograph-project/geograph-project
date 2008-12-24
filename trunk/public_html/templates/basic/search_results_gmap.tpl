{include file="_search_begin.tpl"}

{if !$google_maps_api_key}
	<p>this page is no longer able to display a map - please use a different display method</p>
{elseif $engine->resultCount}

	<div id="map" style="width:100%; height:500px; position:relative;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;

	function onLoad() {
		map = new GMap2(document.getElementById("map"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());
		{/literal}
		
		var bounds = new GLatLngBounds();
		
		{foreach from=$engine->results item=image}
			bounds.extend(new GLatLng({$image->wgs84_lat}, {$image->wgs84_long}));
		{/foreach}

		var newZoom = map.getBoundsZoomLevel(bounds);
		var center = bounds.getCenter();
		map.setCenter(center, newZoom);
		
		var xml = new GGeoXml("http://{$http_host}/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.kml");
		map.addOverlay(xml);
		
		{if $markers} 
			{foreach from=$markers item=marker}
				map.addOverlay(createMarker(new GLatLng({$marker.1},{$marker.2}),'{$marker.0}'));
			{/foreach}
		{/if}
		{literal}
		
	}

	 function createMarker(point,myHtml) {

		var marker = new GMarker(point, {draggable: true});

		GEvent.addListener(marker, "click", function() {
			map.openInfoWindowHtml(point, myHtml);
		});
		GEvent.addListener(marker, "dragend", function() {
			marker.setPoint(point);
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
	
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
