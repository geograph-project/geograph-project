<html>
<head>
<title>Map :: Geograph Britain and Ireland</title>
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

<meta name="theme-color" content="#000066" />

<link rel="shortcut icon" type="image/x-icon" href="https://s1.geograph.org.uk/favicon.ico"/>
<link rel="canonical" href="https://www.geograph.org.uk/mapper/combined.php"/>

{literal}<style>
	html, body, #map { margin:0; padding:0; width:100%; height: 100% }
	#map {max-width:1024px; max-height:800px }
	#message {
		z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:0.8em;font-family:sans-serif;opacity:0.7
	}
	#gridref {
		z-index:10000;position:fixed;bottom:0;left:0;background-color:#F5F5DC;font-size:1em;font-family:sans-serif;opacity:0.9;padding:1px;
	}
	.leaflet-control-locate a span, .easy-button-container button span {
		line-height:30px;
	}
	#header_block {
	    display:none;
	    position:absolute;
	    background: #000066;
	    margin: 0px;
	    width:100%;
	}
	#header h1 {
	    margin: 0px;
	    background-image: url(https://s1.geograph.org.uk/templates/basic/img/xmaslogo.gif);
	    height: 74px;
	    width: 257px;
	    cursor: pointer;
	    cursor: hand;
	}
	#header h1 a {
	    display: none;
	}
	@media screen and (min-height: 700px) {
		#header_block {	display:block;}
		#map { position: absolute; top: 74px; }
		#message { top:74px; }
	}

	.leaflet-sidebar .close {
	    z-index: 100000 !important;
	}
	.leaflet-sidebar ul {
		padding:6px;
	}

	#sidebar {
		background-color:#e4e4fc;
	}
::-webkit-scrollbar {
    -webkit-appearance: none;
    width: 7px;
}
::-webkit-scrollbar-thumb {
    border-radius: 4px;
    background-color: rgba(0,0,0,.5);
    box-shadow: 0 0 1px rgba(255,255,255,.5);
}

@media print {
       .no_print {
               display:none;
       }
       .leaflet-control-container .leaflet-top {
               display:none;
       }
       #message {
               display:none;
       }
}

ul.tips li {
        margin-bottom: 5px;
}

.applyUnsharp {
	filter: url(#unsharpy);
}
.applyHighContrast {
	filter: url(#highcontrast);
}

svg.svgFilter {
	display:none;
}

.applyContrast {
	filter: brightness(90%) contrast(130%);
}

.tabHolder a {
    text-decoration: none;
    background-color: #eee;
    padding: 5px;
    border: 1px solid #ccc;
    --border-bottom: none;
}
.tabHolder a.tabSelected {
    background-color: #e4e4fc;
    border-bottom: 1px solid #e4e4fc;
}

 </style>{/literal}

	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="{"/js/mappingLeaflet.css"|revision}" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.css" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.css" />

	<link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.css?v=2" />

	<link rel="stylesheet" href="{"/js/Leaflet.GeographClickLayer.css"|revision}" />

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.67.0/dist/L.Control.Locate.min.css" />

	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
	<link rel="stylesheet" href="https://www.geograph.org/leaflet/leaflet-sidebar-master/src/L.Control.Sidebar.css" />


	<script src="https://www.geograph.org/leaflet/Leaflet.Photo/examples/lib/reqwest.min.js"></script>

	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>
        <script src="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.js"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<!--script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.js" charset="utf-8"></script-->
	<script src="https://www.geograph.org/leaflet/L.Control.Locate.js"></script> <!-- fork at https://github.com/barryhunter/leaflet-locatecontrol/blob/gh-pages/ -->

        <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
        <script src="https://www.geograph.org/leaflet/leaflet-sidebar-master/src/L.Control.Sidebar.js"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

        <script src="https://www.geograph.org/leaflet/Leaflet.GeographCoverage.js?v=4"></script>

	<script src="https://www.geograph.org/leaflet/Leaflet.GeographPhotos.js?v=4"></script>

	<script src="https://www.geograph.org/leaflet/Leaflet.GeographCollections.js"></script>

        <script src="{"/js/Leaflet.GeographClickLayer.js"|revision}"></script>

<script>
     {if $os_api_key}
              var OSAPIKey = "{$os_api_key}";
     {else}
              var OSAPIKey = null;
     {/if}
</script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="{"/js/jquery.storage.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.js"></script>
	<script src="https://www.geograph.org/leaflet/Leaflet.GeographGeocoder.js"></script>

	<script src="https://unpkg.com/togeojson@0.16.0/togeojson.js"></script>
	<script src="https://unpkg.com/leaflet-filelayer@1.2.0/src/leaflet.filelayer.js"></script>

{dynamic}
       {if $stats && $stats.images}
               <script src="{"/js/Leaflet.GeographRecentUploads.js"|revision}"></script>
       {/if}
{/dynamic}

</head>
<body>
<div id="header_block" onclick="document.location='/';">
  <div id="header">
    <h1><a title="Geograph home page" href="/">GeoGraph - photograph every grid square</a></h1>
  </div>
</div>

	<div id="map"></div>
	<div id="message">Loading Geograph Map ...</div>
	<div id="gridref"></div>

<script>{literal}
	function linkToMap(url) {
		var center = map.getCenter();
		var zoom = map.getZoom();

		if (url.indexOf('$gridref') > -1 || url.indexOf('$layers') > -1) {
                        //create a wgs84 coordinate
                        wgs84=new GT_WGS84();
                        wgs84.setDegrees(center.lat, center.lng);
			if (wgs84.isIreland2()) {
				//convert to Irish
				var grid=wgs84.getIrish(true);
			} else if (wgs84.isGreatBritain()) {
				//convert to OSGB
				var grid=wgs84.getOSGB();
			}
			if (zoom > 14) {
				var gridref = grid.getGridRef(10);
			} else if (zoom > 9) {
				var gridref = grid.getGridRef(6);
			} else {
				var gridref = grid.getGridRef(4);
			}
			url = url.replace(/\$gridref/g,gridref.replace(/ /g,''));
		}
		if (url.indexOf('$layers') > -1) {
			if (wgs84.isGreatBritain()) {
				zoom = Math.floor(zoom * 0.4); //os maps use a different scale :(
				var layers = 'FTFB000000000000FT';
			} else {
				var layers = 'FFT000000000000BFT';
			}
			url = url.replace(/\$layers/g,layers);
		}

		url = url.replace(/\$lat/g,center.lat);
		url = url.replace(/\$long/g,center.lng);
		url = url.replace(/\$zoom/g,zoom);

		if (overlayMaps && overlayMaps["(Personalize Coverage)"] && map.hasLayer(overlayMaps["(Personalize Coverage)"])) {
			if (url.indexOf('?') > -1) {
				url = url + "&mine=1";
			} else if (url.indexOf('/browser/') == 0) {
				var user_id = overlayMaps["(Personalize Coverage)"].options.user_id;
				url = url + "/user+%22user"+user_id+"%22";
			}
		}

		var newWin = window.open(url,'_blank');

		if(!newWin || newWin.closed || typeof newWin.closed=='undefined') {
			location.href = url;
		}
	}

	var mapOptions =  {
                center: [56.317, -2.769], zoom: 5,
                minZoom: 5, maxZoom: 18,
		zoomControl: false
        };
	var clickOptions = {};
	var wgs84;
{/literal}
{dynamic}
	{if $gridref}
		wgs84=new GT_WGS84();
                wgs84 = wgs84.parseGridRef(gridref = '{$gridref}'); //technically a factory method

		{literal}
                if (wgs84) {
                        mapOptions.center = L.latLng( wgs84.latitude, wgs84.longitude );
			mapOptions.zoom = 13;
		}
		{/literal}
	{elseif $ireland}
		mapOptions.center = [53.416,-7.877];
		mapOptions.zoom = 7;
	{/if}

	{if $zoom}
		mapOptions.zoom = {$zoom};
	{/if}
{/dynamic}
	var static_host = '{$static_host}';
{literal}

	var map = L.map('map', mapOptions);
        var hash = new L.Hash(map);


	if ($.localStorage && $.localStorage('LeafletBaseMap')) {
		basemap = $.localStorage('LeafletBaseMap');
		if (baseMaps[basemap])
			map.addLayer(baseMaps[basemap]);
		else
			map.addLayer(baseMaps["OpenStreetMap"]);
	} else {
		map.addLayer(baseMaps["OpenStreetMap"]);
	}
	if ($.localStorage) {
		map.on('baselayerchange', function(e) {
			$.localStorage('LeafletBaseMap', e.name);
		});
	}

	var sidebar;
	setTimeout(function() {
	        sidebar = L.control.sidebar('sidebar', {
		    closeButton: true,
	            position: 'left'
		});
	        map.addControl(sidebar);
	},500);

        //setTimeout(function () {
        //    sidebar.show();
        //}, 500);


        if (L.geographGeocoder && !geocoder)
                map.addControl(geocoder = L.geographGeocoder());

        if (mapOptions && typeof mapOptions.zoomControl !== 'undefined' && !mapOptions.zoomControl) //default version turned off, so add it now, AFTER search
                 map.addControl(L.control.zoom());


        L.easyButton('fa-info', function(btn, map){
            sidebar.show();
        }).addTo( map );

        map.addLayer(baseMaps["OpenStreetMap"]); //todo, make this configure like in mappingLeaflet.js
	map.addLayer(overlayMaps["OS National Grid"]);

	if (baseMaps["Geograph PhotoMap"]) delete baseMaps["Geograph PhotoMap"];
	if (baseMaps["Watercolour"]) delete baseMaps["Watercolour"];
	if (overlayMaps["PhotoMap Overlay"]) delete overlayMaps["PhotoMap Overlay"];

{/literal}
{dynamic}
	{if $views}
                map.addLayer(overlayMaps["Photo Viewpoints"]);
        {elseif $dots}
	        map.addLayer(overlayMaps["Photo Subjects"]);
	{else}
	        map.addLayer(overlayMaps["Coverage - Standard"]);
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
			if (map.getZoom() >= 13 && coverageClose && coverageClose.options)
				setTimeout('coverageClose.Reset();',100); //TODO some race conditon, means not it doesnt get called automatically :(
		{/if}

		{literal}
		var stateChangingButton = L.easyButton({
		    states: [{
		            stateName: 'general',        // name the state
		            icon:      'fa-user-o',               // and define its properties
		            title:     'Non Personalized - Click to personalized map',      // like its title
			    onClick: function(btn, map) {       // and its callback
				overlayMaps["(Personalize Coverage)"].addTo(map);
		                btn.state('personal');    // change state on click!
		            }
		        }, {
		            stateName: 'personal',
		            icon:      'fa-user',
		            title:     'Personalized (just your images) - click to disable',
		            onClick: function(btn, map) {
				overlayMaps["(Personalize Coverage)"].removeFrom(map);
		                btn.state('general');
		            }
		    }]
		});
		{/literal}
		{if $filter}
			stateChangingButton.state('personal');
		{/if}

		stateChangingButton.addTo(map);

                if (L.GeographRecentUploads)
                        overlayMaps["Recent Uploads"] = L.geographRecentUploads();

	{else}
		 delete overlayMaps["(Personalize Coverage)"];
	{/if}
{/dynamic}
{literal}

	var initialValue = null;

	function setAllOpacity(list, fudge) {
            if (!initialValue) {
		initialValue = {}
		for(i in baseMaps)
			if (baseMaps[i].options && baseMaps[i].options.opacity)
				initialValue[i] = baseMaps[i].options.opacity
		for(i in overlayMaps)
			if (overlayMaps[i].options && overlayMaps[i].options.opacity)
				initialValue[i] = overlayMaps[i].options.opacity
	    }

	    for(i in list) {
		var defaultValue = initialValue[i] || 1;
		var value = defaultValue*fudge;
	        if (value > 1) value=1;
	        if (value < 0) value=0;

                if (list[i].setOpacity) {
			list[i].setOpacity(value);
		} else if (list[i].options && list[i].options.opacity) {
			list[i].options.opacity = value;
                }
            }
          }

		var opacityButton = L.easyButton({
		    states: [{
		            stateName: 'general',
		            icon:      'fa-adjust',
		            title:     'Nominal Display - click to highlight overlays',
			    onClick: function(btn, map) {
		                setAllOpacity(baseMaps,0.6);
		                setAllOpacity(overlayMaps,1.1);
		                btn.state('over');    // change state on click!
		            }
		        }, {
		            stateName: 'over',
		            icon:      'fa-adjust fa-rotate-90',
		            title:     'Overlay Highlighted - click to highlight base layer',
		            onClick: function(btn, map) {
		                setAllOpacity(baseMaps,1.1);
		                setAllOpacity(overlayMaps,0.6);
		                btn.state('base');
		            }
		        }, {
		            stateName: 'base',
		            icon:      'fa-adjust fa-rotate-270',
		            title:     'Base layer Highlighted - click to return to nominal',
		            onClick: function(btn, map) {
		                setAllOpacity(baseMaps,1);
		                setAllOpacity(overlayMaps,1);
		                btn.state('general');
		            }
		    }]
		});

		opacityButton.addTo(map);


	//needs calling AFTER updating overlayMaps
        addOurControls(map);

	clickOptions['touch'] = true;
	clickOptions['domain'] = 'https://www.geograph.org.uk';
	clickOptions['limit'] = 6;

	map.addLayer(L.geographClickLayer(clickOptions));


	if (wgs84 && gridref && gridref.length > 6)
		L.marker(mapOptions.center).addTo(map);

	$(function() {
		$('input[name=enhance]').click(function() {
			$('input[name=enhance]').each(function() {
				$('div.leaflet-tile-pane').removeClass(this.value);
			});
			$('div.leaflet-tile-pane').addClass(this.value);
		});

		$('.tabHolder a').on('click',function() {
			var layeron = $(this).data('layer');
			$('.tabHolder a.tabSelected').each(function() {
				var layeroff = $(this).data('layer');
				if (overlayMaps[layeroff] && layeron != '(Personalize Coverage)') {
					overlayMaps[layeroff].removeFrom(map);
					$(this).removeClass('tabSelected');
				}
			});
			if (overlayMaps[layeron]) {
				overlayMaps[layeron].addTo(map);
				$(this).addClass('tabSelected');
				if (layeron == 'Photo Viewpoints' && map.getZoom() < 11)
					map.setZoom(11);
			}
		});
	});

	var guiders = null;

	$(function() {
	jQuery.cachedScript = function(url, success) {

	  return jQuery.ajax({
	    dataType: "script",
	    cache: true,
	    url: url,
	    success: success
	  });
	};
	if (window.location.hash && window.location.hash.length > 1 && window.location.hash.indexOf('tour') > -1)
		startTour();
	});

	function startTour() {
	    if (!guiders) {
	        guiders = 'started'; //just to prevent repeats while still loading...

	        $('head').append('<link rel="stylesheet" href="//s1.geograph.org.uk/guider/guiders-1.2.8.css" type="text/css" />');

	        $.cachedScript('//s1.geograph.org.uk/guider/guiders-1.2.8.js', function() {
	            $.cachedScript('/guider/mapper_guider.js?t={/literal}{dynamic}{$g_time}{/dynamic}{literal}', function() {
	                setTimeout('startTour()',100);
	            });
	        });
	        return false;
	    }
	    $(function() {
	        guiders.show('g_welcome');
	    });
	    return false;
	}

{/literal}</script>

<div id="sidebar">

<a href="/"><img src="https://s1.geograph.org.uk/templates/basic/img/xmaslogo.gif"></a>

<h3>Quick Mode</h3>
<div class="tabHolder">
	<a class="tab{if !$filter}Selected{/if}" data-layer="Coverage - Standard">Coverage</a> 
	{if $stats && $stats.images}
	<a class="tab{if $filter}Selected{/if}" data-layer="(Personalize Coverage)">Personalized</a> 
	{/if}
	<a class="tab" data-layer="Photo Subjects">Subjects</a> 
	<a class="tab" data-layer="Photo Viewpoints">Viewpoints</a> 
	<a class="tab" data-layer="Photo Thumbnails">Thumbnails</a>
</div>

<h3>Location &amp; Map Links</h3>
<ul class="tips">
	<li><a href="#" onclick="linkToMap('/browser/#!/loc=$gridref/dist=2000/display=map_dots')">Image Browser Map</a></li>
	<li><a href="#" onclick="linkToMap('/mapbrowse.php?zoom=$zoom&lat=$lat&lon=$long')">Coverage Map V1</a></li>
	<li><a href="#" onclick="linkToMap('/mapper/?zoom=$zoom&lat=$lat&lon=$long')">Coverage Map V2 (GB only)</a></li>
	<li><a href="#" onclick="linkToMap('/mapsheet.php?zoom=$zoom&lat=$lat&lon=$long')">Printable Checksheet</a></li>
{dynamic}
        {if $stats && $stats.images}
	<li><a href="#" onclick="linkToMap('/mapsheet.php?zoom=$zoom&lat=$lat&lon=$long&mine=1')">Printable Checksheet (personalized)</a></li>
	{/if}
{/dynamic}
	<li><a href="#" onclick="linkToMap('/gridref/$gridref/links')">Location Links Page</a></li>
	<li><a href="#" onclick="linkToMap('/gridref/$gridref')">GridSquare Page</a></li>
	<li><a href="#" onclick="linkToMap('/submit.php?gridreference=$gridref')">Submit a photo in square</a></li>
	<li><a href="#" onclick="linkToMap('https://www.nearby.org.uk/coord.cgi?p=$gridref')">nearby.org.uk Links Page</a></li>
	<li><a href="#" onclick="linkToMap('https://www.geograph.org/leaflet/all.php#$zoom/$lat/$long')">All Projects Map</a></li>
	<li><a href="#" onclick="linkToMap('http://mapapps.bgs.ac.uk/geologyofbritain/home.html?lat=$lat&long=$long')">Geology of Britain Viewer (GB Only)</a></li>
</ul>

<h3>Tips</h3>
<ul class=tips>
	<li><b>Tap the map briefly</b> to get the Grid-Reference for that location</li>
	<li><b>Hold-down</b> to load images near that location<ul>
		<li>In the popup tap photo briefly to locate the photo on map
		<li>Hold-down on photo thumbnail, to load the full photo page
		</ul></li>

	<li>Use the <b>Pin icon</b> (top left) to attempt to center the map on your current location</li>
	<li>Use the <b>Search icon</b> (also top left) to search for a place and recenter the map</li>

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
	<li><a href="javascript:void(startTour())">Interactive Tour of the main features</a></li>
</ul>

<h3>Coverage Colours</h3>
<ul class=tips>
        <li><b>OS National Grid</b>: Displays both OSGB and Irish Grid over the top of the map</li>

        <li><b>Extra Dense Grid</b>: At some scales is able to display extra detailed grid lines, particully useful to be able to see centisquare (100m square) grid.</li>

	<li><b>Photo Subjects</b>: A blue dot presents one or more photos - dot plotted at photo <b>Subject</b> position (only images with 6fig+ grid-reference plotted!)
		<ul>
			<li>Note: when zoom out, this layer will change to show coverage by square, darker = more photos. Zoom out further and it shows by 10km (hectad) squares.
			(because becomes too many individual dots to plot, and can't see patterns at these scale anyway)</li>
		</ul></li>

	<li><b>Photo Viewpoints</b><sup style=color:red>NEW!</sup>: A purple marker - one per photo, showing where the photo was taken <b>from</b>, pointing in the approximate direction of view
		<ul>
			<li>If have <b>both</b> Viewpoints and Subjects layers enabled, at close zoom will draw red lines joining each purple to blue dots
			<li>Disable one or other layer to remove the lines, keeping just one set of dots
		</ul></li>

	<li><b>Coverage - Standard</b>: Shows coverage by squares, optionally personalized to just your images. Split into different versions at different resolution:<ul>

		<li style="padding:3px;"><b>Close</b>: Shows number of photos in square (either 1km or 100m squares), and coloured by what Geograph image(s) in the square<br>
		<span style="opacity:0.92; font-family:'Trebuchet MS','Comic Sans MS',Georgia,Verdana,Arial,serif; text-shadow:1px 1px 1px black; font-size:16px;">
                <span style="color:#FF0000;padding:3px;">Square with recent Images</span><br>
                <span style="color:#FF00FF;padding:3px;">No Images in last 5 years</span><br>
                <span style="color:gray;padding:3px;">No Geograph Images</span>
		</span></li>

		<li style="padding:3px;"><b>Coarse</b>: Coloured by what Geograph(s) are in the 1km square. <br>
		<span style="opacity:0.7">
		<span style="background-color:#FF0000;padding:3px;">Recent Geographs (last 5 years)</span><br>
		<span style="background-color:#FF8800;padding:3px;">Only older Geographs</span><br>
		<span style="background-color:#75FF65;padding:3px;">No Geograph Images</span>
		</span></li>

                <li>When zoom out, changes to hectad (10km square) grid resolution, and is coloured yellow->red on the number of squares with recent (last 5 years) Geographs</li>

	</ul></li>

	<li><b>Coverage - Large Squares</b>: Duplicates the <i>Coverage - Coarse</i> layer to provide coloured squares at a closer zoom level</li>

	<li><b>Coverage - Opportunities</b>: Lighter (yellow) - more opportunties for points, up to, darker (red) less opportunties, as already lots of photos in square.
		Experimental coverage layer to see if concept works. Exact specififications of layer subject to change or withdrawl.

	<li><b>Recent Uploads</b><sup style=color:red>NEW!</sup> (for contributors only!): Shows images submitted in last 3 days. Regardless of moderation (so can see still pending images on map to follow coverage), so this layer should see new images before visible on other layers.
                Note: Can turn the layer off, and when turn it back on, it will immiidately check for new images, so can use this to follow submissions in real time.

	<li>We don't have a key of the <b>BGS layer(s)</b>, but use the link (in [Other Maps...] at top!) to the offical Geology of Britain Viewer, which provides some functions to explain the colouring</li>
</ul>

<h3>Image Enhancement</h3>
<div class="no_print">
<input type=radio name="enhance" value="applyNone" checked id="enableNone"><label for=enableNone>Original / No Enhancement</label><br>
<input type=radio name="enhance" value="applyUnsharp" id=enableUnsharp><label for=enableUnsharp>Apply 'unsharp' filter to imagery layers</label> - may help with clarity<br>
<input type=radio name="enhance" value="applyContrast" id=enableContrast><label for=enableContrast>Apply 'contrast enhance' filter to imagery layers</label> - may also help with clarity<br>
<input type=radio name="enhance" value="applyHighContrast" id=enableHighContrast><label for=enableHighContrast>Apply 'high contrast' filter to imagery layers</label> - extreme version<br>
</div>

<svg class="svgFilter">
    <defs>
        <filter id="unsharpy">
            <feGaussianBlur  result="blurOut" in="SourceGraphic" stdDeviation="5"/>
            <feComposite operator="arithmetic" k1="0" k2="1.3" k3="-0.3" k4="0"
            in="SourceGraphic" in2="blurOut" />
        </filter>

	<filter id="highcontrast">
		<feComponentTransfer>
			<feFuncR type="gamma" exponent="3.0"/><feFuncG type="gamma" exponent="3.0"/><feFuncB type="gamma" exponent="3.0"/>
		</feComponentTransfer>
	</filter>
    </defs>
</svg>

<h3>Other suggestions/requests?</h3>
	<p>Let us know!</p>

</div>

</body>
</html>
