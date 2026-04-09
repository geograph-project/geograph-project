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
	    background-image: url(https://s1.geograph.org.uk/templates/basic/img/logo.gif);
	    height: 74px;
	    width: 257px;
	    cursor: pointer;
	    cursor: hand;
	}
	#header h1 a {
	    display: none;
	}
{/literal}{dynamic}{if !$inner}{literal}
       @media screen and (min-height: 700px) {
               #header_block { display:block;}
               #map { position: absolute; top: 74px; }
               #message { top:74px; }
       }
{/literal}{/if}{/dynamic}{literal}

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

        <link rel="stylesheet" href="{"/js/leaflet-search-master/src/leaflet-search.css"|revision}" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/Leaflet.Photo/Leaflet.Photo.css" />

	<link rel="stylesheet" href="{"/js/Leaflet.GeographCoverage/Leaflet.GeographCoverage.css"|revision}" />

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

	<script src="{"/js/Leaflet.GeographCoverage/Leaflet.GeographCoverage.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/Leaflet.GeographPhotos.js?v=4"></script>

	<script src="https://www.geograph.org/leaflet/Leaflet.GeographCollections.js"></script>

        <script src="{"/js/Leaflet.GeographClickLayer.js"|revision}"></script>


{dynamic}{if $camera}
	<script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
        <script src="{"/js/Geograph.MediaDatabase.class.js"|revision}"></script>
	<script src="{"/js/submission_utils.js"|revision}"></script>
	<script src="{"/viewer/ExifRestorer.js"|revision}"></script>
	<script src="{"/js/Leaflet.GeographCameraButton.js"|revision}"></script>

    <script type="module">
        import {literal}{ setupSettingsListener }{/literal} from '{"/app/js/utils.js"|revision|regex_replace:"/^.*org\.uk/":""}';

        window.max_size = 8 * 1024 * 1024; //larger files will be downsized!
        window.uploadMaxDimension = 65536; // Default to effectively unlimited (will be updated by the settings listener!)

        setupSettingsListener();
    </script>
{/if}{/dynamic}

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

	<script src="{"/js/leaflet-search-master/src/leaflet-search.js"|revision}"></script>
	<script src="{"/js/Leaflet.GeographGeocoder.js"|revision}"></script>

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

function error_log(message, file, line) {
   $.ajax({
      url: '/stuff/record_error.php',
      data: {message:message, file:file, line:line},
      xhrFields: { withCredentials: true }
   });
}
window.onerror = error_log;


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
				var gridref = grid.getGridRef(5);
			} else if (zoom > 9) {
				var gridref = grid.getGridRef(3);
			} else {
				var gridref = grid.getGridRef(2);
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
		var wgs84=GT_WGS84.parseGridRef('{$gridref}'); //Its a factory method
		if (wgs84) mapOptions.center = L.latLng( wgs84.latitude, wgs84.longitude );
		if (wgs84) mapOptions.zoom = 13;
	{elseif $ireland}
		mapOptions.center = [53.416,-7.877];
		mapOptions.zoom = 7;
	{else}
		{literal}
                if (window.location.search && window.location.search.indexOf('locate')>-1) {
			// because we will be auto calling "locateControl.start()", there is no point initializing the map to UK view!
			// but dont we do still listen for locationerror, so can setup the map in that case!
			delete mapOptions.center;
			mapOptions.zoom = 13;
		}
		{/literal}
	{/if}

	{if $zoom}
		mapOptions.zoom = {$zoom};
	{/if}
{/dynamic}
	var static_host = '{$static_host}';
{literal}

	var map = L.map('map', mapOptions);
        var hash = new L.Hash(map);

	// If we are in 'locate' mode, we need to handle what happens if it fails
	if (!mapOptions.center) {

		// Add this guard immediately after creating the map object
		// ... because map starts non-centerd, accidental dragging of the map breaks it due to uncaught exception, this guards against that!
		map.on('mousedown dragstart', function(e) {
		    if (!map.getCenter()) {
		        // If no center is set, stop the event from bubbling
		        // to the internal Leaflet handlers like _onUp
		        L.DomEvent.stopPropagation(e);
		        return false;
		    }
		});

		map.once('locationerror', function(e) {
			console.warn("Location access denied or failed. Reverting to default view.");
			if (!map._loaded)
				map.setView([56.317, -2.769], 5); 
		});
	}

	addBaseLayer("OpenStreetMap"); //the default layer from Leaflet.base-layers.js, but will automatically use user prefernce too!

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
		//keep in sync, if layer is added/removed manually, or via user-preference
		overlayMaps["(Personalize Coverage)"].on('add', function() {
		    stateChangingButton.state('personal');
		});
		overlayMaps["(Personalize Coverage)"].on('remove', function() {
		    stateChangingButton.state('general');
		});
		{/literal}
		{if $filter}
			stateChangingButton.state('personal');
		{/if}

		stateChangingButton.addTo(map);

                if (L.GeographRecentUploads)
                        overlayMaps["Recent Submissions"] = L.geographRecentUploads();

		{literal}
		if (L.geographCameraButton && window.location.search && window.location.search.indexOf('camera')>-1 ) {
			$(function() {
				document.getElementById('cameraHelp').style.display='';
			});
			overlayMaps["Taken Photos"] = new L.FeatureGroup().addTo(map);
		        cameraButton = L.geographCameraButton({historyPoints: overlayMaps["Taken Photos"]}).addTo(map);
		}
		{/literal}
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
	//addOurControls may now have added "(Personalize Coverage)" to the map, so need to make sure any layers added after are setup to!
	if (overlayMaps["(Personalize Coverage)"] && map.hasLayer(overlayMaps["(Personalize Coverage)"]))
		clickOptions.user_id = overlayMaps["(Personalize Coverage)"].options.user_id;

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


L.Control.Peek = L.Control.extend({
    options: { position: 'topleft' },

    onAdd: function(map) {
        // Create the button container
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.innerHTML = '<a style="cursor:pointer;" title="Hold to Peek Out">&#127757;</a>';
        container.style.backgroundColor = 'white';
        container.style.width = '30px';
        container.style.height = '30px';
        container.style.textAlign = 'center';
        container.style.lineHeight = '30px';
        container.style.userSelect = 'none';
        container.style.webkitUserSelect = 'none';     // iOS Safari
        container.style.webkitTouchCallout = 'none';   // Disable the "save image" popup on iOS

        var self = this;
	const overlaysToHide = ['Photo Subjects', 'Photo Viewpoints', 'Coverage - Standard', 'Photo Thumbnails'];
        this._originalState = {};
        this._hiddenLayers = []; // Track layers we actually removed

	// --- THE FIX ---
	L.DomEvent.disableClickPropagation(container);
	L.DomEvent.disableScrollPropagation(container); 

	L.DomEvent.on(container, 'contextmenu', function(e) {
	    L.DomEvent.stop(e); // Prevents the right-click/long-press menu from appearing
	});
	// ---------------

        // MOUSE DOWN: Store state and zoom out
        L.DomEvent.on(container, 'mousedown touchstart', function(e) {
            L.DomEvent.stopPropagation(e);

	    if (!map._loaded) return; //getCenter fails if not loaded!            
	    if (self._isPeeking) return;
            self._isPeeking = true;

            // Save current view
            self._originalState = {
                center: map.getCenter(),
                zoom: map.getZoom()
            };

	    // Hide specific overlays if they are currently on the map
            self._hiddenLayers = [];
            overlaysToHide.forEach(name => {
                let layer = overlayMaps[name];
                if (layer && map.hasLayer(layer)) {
                    map.removeLayer(layer);
                    self._hiddenLayers.push(layer);
                }
            });
            // add a small marker to highlight the center
	    //self._marker = L.circleMarker(self._originalState.center, {color:'black',radius:2, opacity:0.6, interactive:false}).addTo(map);
	    self._viewfinder = L.rectangle(map.getBounds(), {color: "black", weight: 0.75, opacity:0.6, fill:false, interactive:false}).addTo(map);

            // Zoom out (e.g., current zoom minus 4 levels)
	    let currentMinZoom = map.getMinZoom(); // Default fallback
	    for (let name in baseMaps) {
		    if (map.hasLayer(baseMaps[name])) {
		        currentMinZoom = baseMaps[name].options.minZoom || currentMinZoom;
		        break; // Found the active base, stop looking
		    }
	    }
            map.setZoom(Math.max(currentMinZoom, self._originalState.zoom - 4), { animate: true });
        });

        // MOUSE UP: Restore state
        L.DomEvent.on(container, 'mouseup mouseleave touchend', function(e) {
            L.DomEvent.stopPropagation(e);
	    if (!map._loaded) return;
	    if (!self._isPeeking) return;
            if (!self._originalState.center) return;

	    // 2. Wait for the animation to finish before unlocking the button
	    map.once('moveend', function() {
        	// Restore layers only after we are back (smoother performance)
	        self._hiddenLayers.forEach(layer => map.addLayer(layer));

        	// RESET EVERYTHING AT THE END
		if (self._marker) self._marker.removeFrom(map); self._marker = null;
		if (self._viewfinder) self._viewfinder.removeFrom(map); self._viewfinder = null;
	        self._hiddenLayers = [];
        	self._originalState = {};
	        self._isPeeking = false; // The lock is finally released
	    });

            // 1. Restore View
            map.setView(self._originalState.center, self._originalState.zoom, { animate: true });
        });

        return container;
    }
});

map.addControl(new L.Control.Peek());

{/literal}</script>

<div id="sidebar">

<a href="/"><img src="https://s1.geograph.org.uk/templates/basic/img/logo.gif"></a>

{if !$inner}
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
{/if}

    <div style="text-align: center; margin-top: 15px;">
        <button type="button" class="btn-tour" onclick="startTour()">Interactive Tour of Features</button>
    </div>

    <h3>Gestures &amp; Icons</h3>
    <ul>
        <li><b>Search Icon:</b> Search for a place and recenter the map.
        <li><b>Single Tap:</b> Get the Grid Reference for that location.
        <li><b>Long Press:</b> Load images near that location.
{dynamic}
	{if $stats && $stats.images}
        <li><b>Personalize:</b> Enable "Personalize Coverage" to show only your own images.
        {/if}
{/dynamic}
        <li id="cameraHelp" style="display:none"><b>Camera Icon:</b> Take a photo instantly. It saves to your Downloads folder (same as the "Take Photo" page).
        <li><b>Focus Icon:</b> Change if the background or the overlay is more prominent.
        <li><b>Pin Icon:</b> Center the map on your current GPS location.
	<li>Upload a KML (not KMZ), or GPX file to display on the map. 1Mb file limit, should display points and line/shape features.
    </ul>

    <h3>Interacting with Photos</h3>
    <ul>
        <li><b>Tap Thumbnail:</b> Briefly view the photo and locate it on the map.
        <li><b>Long Press Thumbnail:</b> Open the full photo details page.
        <li><b>Clusters:</b> Numbers on the map indicate multiple photos. Zoom in to see them split into individual markers.
    </ul>

    <h3>Layers &amp; Performance</h3>
    <ul>
        <li><b>Experiment:</b> Try different base maps and overlays. Note: some only work for Great Britain or Ireland.
        <li><b>Zoom:</b> Some layers only appear at specific zoom levels.
        <li><b>Performance:</b> Reduce the number of active layers to keep the map fast.
    </ul>

    <h3>Coverage Colors</h3>

	<table class="help-table">
	    <tr>
	        <th>Status</th>
	        <th>Close (100m/1km)</th>
	        <th>Coarse (1km)</th>
	    </tr>
	    <tr>
	        <td>Recent (5yrs)</td>
	        <td><span class="swatch" style="color:#FF0000;">Red Text</span></td>
	        <td><span class="swatch" style="background-color:#FF0000;">Red Fill</span></td>
	    </tr>
	    <tr>
	        <td>Older Only</td>
	        <td><span class="swatch" style="color:#FF00FF;">Pink Text</span></td>
	        <td><span class="swatch" style="background-color:#FF8800;">Orange Fill</span></td>
	    </tr>
	    <tr>
	        <td>No Images</td>
	        <td><span class="swatch" style="color:gray;">Gray Text</span></td>
	        <td><span class="swatch" style="background-color:#75FF65; text-shadow: none; color: black;">Green Fill</span></td>
	    </tr>
	</table>

    <ul>
        <li><b>Blue Dots (Subjects):</b> Where the subject of the photo is located.
        <li><b>Purple Markers (Viewpoints):</b> Where the photographer was standing.
        <li><b>Red Lines:</b> Connects Viewpoint to Subject (visible when both layers are enabled).
    </ul>

    <ul>
        <li><b>Grid Lines:</b> Displays OSGB or Irish Grid. At close zoom, shows 100m "centisquare" lines.
        <li><b>Heatmap:</b> When zoomed out, colors shift from Yellow to Red based on the density of recent photos.
    </ul>

    <h3>Specialized Layers</h3>
    <ul>
        <li><b>Opportunities:</b> Experimental layer. Lighter (Yellow) means more opportunities; Darker (Red) means the area is well-covered.
        <li><b>Recent Submissions:</b> (Contributors only) Shows images from the last 3 days, including those pending moderation. Toggle the layer to refresh in real-time.
        <li><b>Taken Photos:</b> Displays dots where you took photos during your current session.
        <li><b>Geology (BGS):</b> For a key to the BGS layers, use the "Other Maps" link at the top to visit the official Geology of Britain Viewer.
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
