{include file="_std_begin.tpl"}

<h2>Geograph Coverage Map (v4) {dynamic}{if $realname} for {$realname|escape:'html'}{/if}{/dynamic}</h2>

	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.css" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.css?v=2" />

	<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.css?v=2" />

	<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.GeographClickLayer.css?v=2" />

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.css" />

	<div style="position:relative; width:800px; height:600px">
		<div id="map" style="width:800px; height:600px"></div>
		<div id="message" style="z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8"></div>
		<div id="gridref" style="z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;"></div>
	</div>

	<script src="https://www.geograph.org/leaflet/Leaflet.Photo/examples/lib/reqwest.min.js"></script>

	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>
        <script src="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.js"></script>

	<script src="{"/js/leaflet-bing-layer.min.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.js" charset="utf-8"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.js?v=4"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographPhotos.js"></script>

	<script src="https://www.geograph.org/leaflet/Leaflet.GeographCollections.js"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographClickLayer.js?v=2"></script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.js"></script>
	<script src="https://www.geograph.org/leaflet/Leaflet.GeographGeocoder.js"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>{literal}

	var mapOptions =  {
                center: [54.4266, -3.1557], zoom: 13,
                minZoom: 5, maxZoom: 18
        };
	var clickOptions = {};

{/literal}
{dynamic}
	{if $gridref}
		 var wgs84=new GT_WGS84();
                 wgs84 = wgs84.parseGridRef('{$gridref}'); //technically a factory method

                 if (wgs84)
                          mapOptions.center = L.latLng( wgs84.latitude, wgs84.longitude );
	{/if}

	var map = L.map('map', mapOptions);
        var hash = new L.Hash(map);

	L.control.locate().addTo(map);

        map.addLayer(baseMaps["OpenStreetMap"]); //todo, make this configure like in mappingLeaflet.js
        map.addLayer(overlayMaps["OSGB Grid"]);
        map.addLayer(overlayMaps["Irish Grid"]);

	{if $dots}
	        map.addLayer(overlayMaps["Coverage - Dots"]);
	{else}
	        map.addLayer(overlayMaps["Coverage - Close"]);
	        map.addLayer(overlayMaps["Coverage - Coarse"]);
	{/if}

	{if $stats && $stats.images}
		overlayMaps["(Personalize Coverage)"].options.user_id = {$stats.user_id};
                overlayMaps["(Personalize Coverage)"].options.minZoom = 5;
		{if !$ownfilter}
			delete overlayMaps["Coverage - Opportunities"];
		{/if}
		{if $filter}
			overlayMaps["(Personalize Coverage)"].addTo(map); //this sets options.user_id on all layers
			clickOptions.user_id = {$stats.user_id}; //but clicklayer doesnt exist yet, so need to set options from start
			if (map.getZoom() >= 13)
				setTimeout('overlayMaps["Coverage - Close"].Reset();',100); //TODO some race conditon, means not it doesnt get called automatically :(
		{/if}
	{else}
		 delete overlayMaps["Coverage - Opportunities"];
		 delete overlayMaps["(Personalize Coverage)"];
	{/if}
{/dynamic}
{literal}
	
	L.control.layers(baseMaps, overlayMaps).addTo(map).expand();

	map.on('baselayerchange', function(e) {
		var color = (e.name.indexOf('Imagery') > -1)?"#fff":"#00f";
		var opacity = (e.name.indexOf('Imagery') > -1)?0.8:0.3;
		for(i in overlayMaps) {
			if (i.indexOf('Grid') > 0) {
				overlayMaps[i].options.color = color;
				overlayMaps[i].setOpacity(opacity);
				overlayMaps[i]._reset();
			}
		}
	});

	map.addControl(L.geographGeocoder());

	map.addLayer(L.geographClickLayer(clickOptions));

{/literal}</script>



<h3>Tips</h3>
<ul class=tips>
	<li>Use the <b>Search icon</b> (also top left) to search for a place and recenter the map</li>
	<li>Use the <b>Pin icon</b> (top left) to attempt to center the map on your current location</li>

{dynamic}
	{if $stats && $stats.images}
		<li>Enable (Personalize Coverage) in the layer switcher (top right of the map) to just count <b>your images</b></li>
        {/if}
{/dynamic}

	<li>Try experimenting with the <b>various map layers</b> (top right!) - there are a wide range of base maps, as well as overlay alternatives.<ul>
		<li>May need to zoom in or out to use some layers, as well as some only working for Great Britain or Ireland </li>
		<li>Reduce the number of layers to improve performance!</li>
		</ul></li>

	<li><b>Click the map</b> to view some nearby images, or can enable the 'Photo Thumbnails' layer. (click thumbs to view larger)</li>
	<li>The <b>Photo Thumbnails</b> layer, automatically clusters images, to reduce overlap. The number show is a hint of the size of the cluster, but there can be significately more photos, which will load automatically when zoom in!
</ul>

<h3>Coverage Colours</h3>
<ul class=tips>
	<li><b>Dots</b>: A blue dot presents one or more photos - dot plotted at photo subject position (only images with 6fig+ grid-reference plotted!)
		<ul>
			<li>Note: when zoom out, this layer will change to show coverage by square, darker = more photos. Zoom out further and it shows by 10km (hectad) squares. 
			(because becomes too many individual dots to plot, and can't see patterns at these scale anyway)</li>
		</ul></li>
	<li style="padding:3px;"><b>Close</b>: <span style="opacity:0.8">
		<span style="background-color:#FF0000;padding:3px;">Square with recent Images</span> /
		<span style="background-color:#FF00FF;padding:3px;">No Images in last 5 years</span> /
		<span style="background-color:gray;padding:3px;">No Geograph Images</span>
		</span></li>
	<li style="padding:3px;"><b>Course</b>: <span style="opacity:0.6">
		<span style="background-color:#FF0000;padding:3px;">Recent Geographs (last 5 years)</span>
		<span style="background-color:#ECCE40;padding:3px;">Only older Geographs</span>
	 	<span style="background-color:#75FF65;padding:3px;">No Geograph Images</span>
		</span></li>
	<li><b>Opportunities</b>: Lighter (yellow) - more opportunties for points, up to, darker (red) less opportunties, as already lots of photos in square. 
		Experimental coverage layer to see if concept works. Exact specififications of layer subject to change or withdrawl. 
</ul>

<h3>Other suggestions/requests?</h3>
	<p>Let us know!</p>

<style>{literal}

ul.tips li {
	margin-bottom: 5px;
}

{/literal}</style>

{include file="_std_end.tpl"}
