var baseMaps = {};

////////////////////////
// PLEASE READ!
//
// If you copy any layers from this file, please get check with the provider and get your own 'API Key'. This file has Geographs own keys hardcoded!
// ... similally, if want to use any of the TileLayers from geograph.org.uk, please https://www.geograph.org.uk/contact.php
//
////////////////////////

	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
baseMaps["OpenStreetMap"] = L.tileLayer(osmUrl, {minZoom: 6, maxZoom: 21, attribution: osmAttrib});

        var cycleUrl='https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey=42a8aaad46fa4fd784104f2870221993';
        var cycleAttrib='&copy; OpenCycleMap, '+osmAttrib;
//baseMaps["OpenCycleMap"] = L.tileLayer(cycleUrl, {minZoom: 6, maxZoom: 21, attribution: cycleAttrib});

        var terrainUrl='https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=42a8aaad46fa4fd784104f2870221993';
        var terrainAttrib='Map &copy; ThunderForest, '+osmAttrib;
baseMaps["OSM Terrain"] = L.tileLayer(terrainUrl, {minZoom: 6, maxZoom: 21, attribution: terrainAttrib});

if (L.tileLayer.bing) {
        var BING_KEY = 'AhwwUjiHWfAqm-dQiAhV1tJO82v-v5mU6osoxU3t1XKx-AlPyKzfBhKpTY81MKtJ';
	var bingAttribution = 'Image courtesy of Ordnance Survey, via Bing <a style="white-space: nowrap" target="_blank" href="https://www.microsoft.com/maps/product/terms.html">Terms of Use</a>';
baseMaps["Ordnance Survey GB"] = L.tileLayer.bing({'bingMapsKey':BING_KEY,'minZoom':12,'maxZoom':17,'imagerySet':'OrdnanceSurvey', attribution:bingAttribution, 
		bounds: [[49.6, -12], [61.7, 3]] });
}

	var mbToken = 'pk.eyJ1IjoiZ2VvZ3JhcGgiLCJhIjoiY2lteXI3cmlpMDBmenY5bTF5dHFqMnh0NiJ9.sPXF2s1niWNNEfqGjs2HGw';
        var mbAttr = 'Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors, ' +
				'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery &copy; <a href="https://mapbox.com">Mapbox</a>',
			mbUrl = 'https://{s}.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=' + mbToken;

baseMaps["MapBox Grayscale"] = L.tileLayer(mbUrl, {id: 'geograph.plpdge8b', attribution: mbAttr});
//baseMaps["MapBox Streets"] = L.tileLayer(mbUrl, {id: 'geograph.plpdi1bk',   attribution: mbAttr}),
//baseMaps["MapBox Comic Sans"] = L.tileLayer(mbUrl, {id: 'geograph.plpdcipm',   attribution: mbAttr}),
baseMaps["MapBox Imagery"] = L.tileLayer(mbUrl, {id: 'geograph.plpdjb8m',   attribution: mbAttr});

	//https://leaflet-extras.github.io/leaflet-providers/preview/
baseMaps["Watercolour"] = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.png', {
		attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="https://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
		subdomains: 'abcd',
		minZoom: 1,
		maxZoom: 16
	});

baseMaps["ESRI Imagery"] = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
		attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
	});

//baseMaps["ESRI Topo"] = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
//		attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'
//	});


if (L.tileLayer.bing)
baseMaps["Bing Imagry"] = L.tileLayer.bing({'bingMapsKey':BING_KEY,'minZoom':7,'maxZoom':21,'imagerySet':'Aerial'});  //WithLabels


        var layerUrl='https://t0.geograph.org.uk/tile/tile-photomap.php?z={z}&x={x}&y={y}&match=&6=1&gbt=6';
        var layerAttrib='&copy; Geograph Project';
        var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
baseMaps["Geograph PhotoMap"] = L.tileLayer(layerUrl, {minZoom: 6, maxZoom: 18, attribution: layerAttrib, bounds: bounds, opacity: 0.8});

	////////////////////////////////////////////////

baseMaps['Historic OS - GB'] = L.tileLayer('https://nls-0.tileserver.com/nls/{z}/{x}/{y}.jpg',
                        {mapLetter: 'n', minZoom: 1, maxZoom:18 , attribution: 'Provided by <a href="https://geo.nls.uk/">NLS Geo</a>',
                                bounds: [[49.6, -12], [61.7, 3]] });

baseMaps['Historic OS - Ireland'] = L.tileLayer('https://geo.nls.uk/maps/ireland/gsgs4136/{z}/{x}/{y}.png',
                        {mapLetter: 'i', tms: true, minZoom: 5, maxZoom: 15, attribution: 'Provided by <a href="https://geo.nls.uk/">NLS Geo</a>',
                                bounds: [[51.371780, -10.810546], [55.422779, -5.262451]] });

	////////////////////////////////////////////////

var overlayMaps = {};

	if (L.britishGrid) {
		var gridOptions = {
        	        opacity: 0.3,
	                weight: 0.7,
        	        showSquareLabels: [100000,10000,100]
	        };
		overlayMaps["OSGB Grid"] = L.britishGrid(gridOptions);
		overlayMaps["Irish Grid"] = L.irishGrid(gridOptions);
	}

	var layerUrl='https://t0.geograph.org.uk/tile/tile-density.php?z={z}&x={x}&y={y}&match=&l=1&6=1';

overlayMaps["Coverage - Dots"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 9, maxZoom: 18, attribution: layerAttrib, bounds: bounds});

	if (L.geographCoverage) {
	        overlayMaps["Coverage - Close"] = L.geographCoverage();

	        var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
	        overlayMaps["Coverage - Coarse"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 7, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});

	        var layerUrl='https://t0.geograph.org.uk/tile/tile-score.php?z={z}&x={x}&y={y}';
	        overlayMaps["Coverage - Opportunities"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 7, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});

		overlayMaps["Coverage - Coarse"].on('add',function() {setTimeout(function () {  map.removeLayer(overlayMaps["Coverage - Opportunities"]); }, 100); } );
		overlayMaps["Coverage - Opportunities"].on('add',function() { setTimeout(function () { map.removeLayer(overlayMaps["Coverage - Coarse"]); }, 100); } );

	////////////////////////////////////////////////

		overlayMaps["(Personalize Coverage)"] = L.tileLayer('',{user_id: 0, minZoom:50}) //the container will be responsible for enabling this if needbe!
		.on('add',function(event) {
			var user_id = event.target.options.user_id;
			for(i in overlayMaps) {
				if (i && overlayMaps[i] && overlayMaps[i].options && typeof overlayMaps[i].options.user_id != "undefined") { // (use typeof becase it can be zero!) 
					overlayMaps[i].options.user_id = user_id;
					if (overlayMaps[i]._url) {
						overlayMaps[i].setUrl(overlayMaps[i]._url.replace(/(&user_id=\d+|$)/,'&user_id='+user_id));
					} else if (map.hasLayer(overlayMaps[i]) && typeof overlayMaps[i].Reset == 'function') {
                                                overlayMaps[i].Reset();
					}
				}
			}
			//need to catch the clicklayer! (can't JUST use eachLayer as some overlayMaps as may NOT be on the map!)
			if (L.GeographClickLayer)
			map.eachLayer(function(layer){
				if (layer instanceof L.GeographClickLayer)
					layer.options.user_id = user_id;
			});
		}).on('remove',function(event) {
			var user_id = 0;
			for(i in overlayMaps) {
                                if (i && overlayMaps[i] && overlayMaps[i].options && typeof overlayMaps[i].options.user_id != "undefined") { // (use typeof becase it can be zero!)
					//dont set user_id here, to avoid setting it on ourselves
                                        if (overlayMaps[i]._url && overlayMaps[i]._url.match(/user_id=/)) {
						//doesnt really matter if dont change options.user_id here
                                                overlayMaps[i].setUrl(overlayMaps[i]._url.replace(/(&user_id=\d+)/,''));
                                        } else if (typeof overlayMaps[i].Reset == 'function') {
	                                        overlayMaps[i].options.user_id = user_id;
						if (map.hasLayer(overlayMaps[i])) {
	                                                overlayMaps[i].Reset();
						}
                                        }
                                }
                        }
			if (L.GeographClickLayer)
			map.eachLayer(function(layer){
				if (layer instanceof L.GeographClickLayer)
					layer.options.user_id = user_id;
			});
		});
	}

	////////////////////////////////////////////////

	// overlay map
	var mbToken = 'pk.eyJ1IjoiZ2VvZ3JhcGgiLCJhIjoiY2lteXI3cmlpMDBmenY5bTF5dHFqMnh0NiJ9.sPXF2s1niWNNEfqGjs2HGw';
        var mbAttr = 'Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors, ' +
				'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery &copy; <a href="https://mapbox.com">Mapbox</a>',
	mbUrl = 'https://api.mapbox.com/styles/v1/{id}/tiles/256/{z}/{x}/{y}?access_token=' + mbToken + '&';

overlayMaps["PhotoMap Overlay"] = L.tileLayer(mbUrl, {id: 'geograph/cjju7ep8g3ypa2spdth1ibmih', attribution: mbAttr, bounds: bounds});

	////////////////////////////////////////////////


	//we dont call this ourselves (the parent page should call it, (so it can choose options, as as well add its own overlays etc) 
	//L.control.layers(baseMaps, overlayMaps, {collapsed: false}).addTo(map);
	
