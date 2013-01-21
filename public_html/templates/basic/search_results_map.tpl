{include file="_search_begin.tpl"}

{if $engine->resultCount}

	<div id="map" style="width:800px; height:600px; position:relative;"></div>
	{if $engine->results}
        <link rel="stylesheet" href="http://{$static_host}/ol/theme/default/style.css" type="text/css">
        <link rel="stylesheet" href="http://{$static_host}/ol/theme/default/google.css" type="text/css">
        <link rel="stylesheet" href="http://{$static_host}/ol/style.v4.css" type="text/css">        

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<script src="http://{$static_host}/ol/grid-projections.js"></script>
        <script src="http://{$static_host}/ol/OpenLayers.js"></script>
	<script src="http://{$static_host}/ol/OlEpsg27700Projection.js"></script>
	<script src="http://{$static_host}/ol/OlEpsg29902Projection.js"></script>
        <script src="http://{$static_host}/ol/km-graticule.js"></script>
        <script src="http://{$static_host}/ol/osgb-layer.v7.js"></script>
        <script src="http://{$static_host}/ol/nls-api.v1.js"></script>
        <script src="http://{$static_host}/ol/geograph-openlayers.v17.js"></script>
	
        <script src="http://maps.google.com/maps/api/js?v=3&amp;sensor=false"></script>

{literal}
<script type="text/javascript">
//<![CDATA[

function loadMap() {
	loadMapInner(); //this does most things, EXCEPT center the map, and doesnt add any interaction. 

//	var centre = new OpenLayers.LonLat(436000, 157000).transform("EPSG:27700", olmap.map.getProjection());
//	olmap.map.setCenter(centre, 1);


	olmap.bounds = new OpenLayers.Bounds();

{/literal}{foreach from=$engine->results item=image}
        olmap.bounds.extend(new OpenLayers.LonLat({$image->wgs84_long}, {$image->wgs84_lat}));
{/foreach}{literal}


	//we using wgs84 as input here... 
	//olmap.bounds = olmap.bounds.transform(new OpenLayers.Projection("EPSG:4326"),olmap.map.getProjection());

	olmap.map.zoomToExtent(olmap.bounds.clone().transform(new OpenLayers.Projection("EPSG:4326"),olmap.map.getProjection()));
	


    var lonLat = olmap.bounds.getCenterLonLat();    
    if (OpenLayers.Projection.Irish.isValidLonLat(lonLat.lon, lonLat.lat)) {
        olmap.map.setBaseLayer(olmap.layers['google_physical']);

	//we repeat this, because ireland might be out of GB grid extents. 
        olmap.map.zoomToExtent(olmap.bounds.clone().transform(new OpenLayers.Projection("EPSG:4326"),olmap.map.getProjection()));

        //todo, disable the GB grid?
        controls = olmap.map.getControlsByClass("OpenLayers.Control.OSGraticule");
        for(q=0;q<controls.length;q++)
            if (controls[q].layerName == "OSGB Grid") 
                controls[q].gratLayer.setVisibility(false);
    } else {
        //todo, diable the Irish Grid?
    }


        var iconSize = new OpenLayers.Size(36, 36);
        var iconOffset = new OpenLayers.Pixel(-19, -19);
	
{/literal}{foreach from=$engine->results item=image}
        var markerIcon = new OpenLayers.Icon("{$image->getThumbnail(120,120,true)}", iconSize, iconOffset, null);
	var markerPoint = new OpenLayers.LonLat({$image->wgs84_long}, {$image->wgs84_lat}).transform("EPSG:4326", olmap.map.getProjection());
	olmap.images[{$image->gridimage_id}] = createMarker({$image->gridimage_id}, markerPoint, markerIcon, "{$image->title|escape:'javascript'}","{$image->realname|escape:'javascript'}");

	olmap.layers['markers'].addMarker(olmap.images[{$image->gridimage_id}]);
{/foreach}


                {if $markers}
                        {foreach from=$markers item=marker}
                                var marker = new OpenLayers.Marker(new OpenLayers.LonLat({$marker.2}, {$marker.1}).transform("EPSG:4326", olmap.map.getProjection()) );
				olmap.layers['markers'].addMarker(marker);
                        {/foreach}
                {/if}

{literal}


//this is the global layer, we dont need it here...
//	olmap.map.events.register('moveend', olmap.map, mapEvent);
//	olmap.map.events.register('zoomend', olmap.map, mapEvent);
//      olmap.layers['markers'].events.register('visibilitychanged', olmap.layers['markers'], mapEvent);

}

//function closure to make creating markers easier... 
function createMarker(uniqueId, markerPoint, markerIcon, title, realname) {
	var marker = new OpenLayers.Marker(markerPoint, markerIcon);

        marker.events.register('mousedown', marker, function(evt) {
                marker.popup = new OpenLayers.Popup.FramedCloud('pop'+uniqueId,
                   markerPoint,
                   new OpenLayers.Size(300,200),
                   '<center><b>'+title+'</b> <br/>by <b>'+realname+'</b><br/><a href="http://www.geograph.org.uk/photo/'+uniqueId+'" target="_blank"><img src="'+markerIcon.url+'"/></a></center>',
		   markerIcon,
                   true);
                olmap.map.addPopup(marker.popup);

                OpenLayers.Event.stop(evt);
        });

	return marker;
}



AttachEvent(window,'load',loadMap,false);
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
	
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
