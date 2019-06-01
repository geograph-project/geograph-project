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
baseMaps["OpenStreetMap"] = L.tileLayer(osmUrl, {minZoom: 5, maxZoom: 21, attribution: osmAttrib});

        var cycleUrl='https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey=42a8aaad46fa4fd784104f2870221993';
        var cycleAttrib='&copy; OpenCycleMap, '+osmAttrib;
baseMaps["OpenCycleMap"] = L.tileLayer(cycleUrl, {minZoom: 5, maxZoom: 21, attribution: cycleAttrib});

        var terrainUrl='https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=42a8aaad46fa4fd784104f2870221993';
        var terrainAttrib='Map &copy; ThunderForest, '+osmAttrib;
baseMaps["OSM Terrain"] = L.tileLayer(terrainUrl, {minZoom: 5, maxZoom: 21, attribution: terrainAttrib});

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
baseMaps["Bing Imagery"] = L.tileLayer.bing({'bingMapsKey':BING_KEY,'minZoom':7,'maxZoom':21,'imagerySet':'Aerial'});  //WithLabels


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
		overlayMaps["OS National Grid"] = L.layerGroup([L.britishGrid(gridOptions), L.irishGrid(gridOptions)]);

		gridOptions['weight'] = 0.4;
		gridOptions['showAxisLabels'] = [];
		gridOptions['showSquareLabels'] = [];
		gridOptions['density'] = 10; //draws gridlines at 10x density of normal.
		gridOptions['minZoom'] = 8; //below this will be very dense (could use skipZoom, but dont want any below 8 anyway)
		gridOptions['maxZoom'] = 16; //above this, minInterval means will be 100, same as main grid, so pointless rendering
		gridOptions['skipZoom'] = [10,13]; //at these two zoom levels the grid is too dense

		overlayMaps["Extra Dense Grid"] = L.layerGroup([L.britishGrid(gridOptions), L.irishGrid(gridOptions)]);
	}

        var wmsLayer = L.tileLayer.wms('https://map.bgs.ac.uk/arcgis/services/BGS_Detailed_Geology/MapServer/WMSServer?', {
          layers: 'BGS.50k.Bedrock', transparent: true, format: 'image/png', opacity: 0.6, minZoom: 13, 
           attribution: "Contains British Geological Survey materials &copy; UKRI 2019",
	  bounds: bounds
        });

        var wmsLayer2 = L.tileLayer.wms('http://ogc.bgs.ac.uk/cgi-bin/BGS_Bedrock_and_Superficial_Geology/wms?', {
          layers: 'BGS_EN_Bedrock_and_Superficial_Geology', transparent: true, format: 'image/png', opacity: 0.7, maxZoom: 12, 
           attribution: "Contains British Geological Survey materials &copy; UKRI 2019",
	  bounds: bounds
        });
	
overlayMaps["BGS Bedrock Geology"] = L.layerGroup([wmsLayer, wmsLayer2]);

	////////////////////////////////////////////////

	var layerUrl='https://t0.geograph.org.uk/tile/tile-density.php?z={z}&x={x}&y={y}&match=&l=1&6=1';

overlayMaps["Photo Subjects"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 19, attribution: layerAttrib, bounds: bounds});


//... going out of way to make the transition seamless as possible. (so layer doesnt disappear before reappearing)
L.TileLayer2 = L.TileLayer.extend({
        _refreshTileUrl: function(tile, url) {
                //use a image in background, so that only replace the actual tile, once image is loaded in cache!
                var img = new Image();
                img.onload = function() {
                        L.Util.requestAnimFrame(function() {
                                 tile.el.src = url;
                        });
                }
                img.src = url;
        },
        refresh: function() {
                //prevent _tileOnLoad/_tileReady re-triggering a opacity animation
                var wasAnimated = this._map._fadeAnimated;
                this._map._fadeAnimated = false;

                for (var key in this._tiles) {
                        tile = this._tiles[key];
                        if (tile.current && tile.active) {
                                var newsrc = this.getTileUrl(tile.coords);
                                if (tile.el.src != newsrc) { //just to make sure it really did update!
                                        this._refreshTileUrl(tile,newsrc);
                                }
                        }
                }

                if (wasAnimated)
                        setTimeout(function() { map._fadeAnimated = wasAnimated; }, 5000);
        }
});
L.tileLayer2 = function(url, options) {
    return new L.TileLayer2(url, options);
}

        function j() { //L.template can use callback function. 
                return (map.getZoom()>16 && overlayMaps["Photo Subjects"] && map.hasLayer(overlayMaps["Photo Subjects"]))?1:0;
        }
        var layerUrl='https://t0.geograph.org.uk/tile/tile-viewpoint2.php?z={z}&x={x}&y={y}&match=&l=1&6=1&j={j}';

overlayMaps["Photo Viewpoints"] = L.tileLayer2(layerUrl, {j:j, user_id: 0, minZoom: 11, maxZoom: 18, attribution: layerAttrib, bounds: bounds, opacity:0.9});

	//this is just being nice, forcing the Viewpoint layer to redraw
	/// for now using a custom .refresh() method, but hope to have it merged into native leaflet at some point!
        function syncViewLine() {
                if (map.getZoom()>16 && overlayMaps["Photo Viewpoints"] && map.hasLayer(overlayMaps["Photo Viewpoints"])) {
			overlayMaps["Photo Viewpoints"].refresh();
                }
        }
	overlayMaps["Photo Subjects"].on('remove',syncViewLine).on('add',syncViewLine);



	////////////////////////////////////////////////

	if (L.geographCoverage) {
	        overlayMaps["Coverage - Close"] = L.geographCoverage();

	        var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
	        overlayMaps["Coverage - Coarse"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});

	        var layerUrl='https://t0.geograph.org.uk/tile/tile-score.php?z={z}&x={x}&y={y}';
	        overlayMaps["Coverage - Opportunities"] = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 7, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});

		overlayMaps["Coverage - Coarse"].on('add',       function() { setTimeout(function () { if (overlayMaps["Coverage - Opportunities"])  map.removeLayer(overlayMaps["Coverage - Opportunities"]); }, 100); } );
		overlayMaps["Coverage - Opportunities"].on('add',function() { setTimeout(function () { if (overlayMaps["Coverage - Coarse"])         map.removeLayer(overlayMaps["Coverage - Coarse"]);        }, 100); } );

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

	if (L.geographPhotos) {
	        overlayMaps["Photo Thumbnails"] = L.geographPhotos();
	}

	if (L.geographCollections) {
	        overlayMaps["Collections"] = L.geographCollections();
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

var layerswitcher;
var filelayer;

function addOurControls(map) {
	
	//the parent map will now have modified the baseMaps/overlayMaps lists!
	layerswitcher = L.control.layers(baseMaps, overlayMaps).addTo(map);

	if (L.britishGrid) {
		//not strictly a control, this is just setting event to mutate the Grid layers depending on baselayer
		map.on('baselayerchange', function(e) {
			var color = (e.name.indexOf('Imagery') > -1 || e.name.indexOf('PhotoMap') > -1)?"#fff":"#00f";
			var opacity = (e.name.indexOf('Imagery') > -1 || e.name.indexOf('PhotoMap') > -1)?0.8:0.3;
			for(i in overlayMaps) {
				if (i.indexOf('Grid') > 0) {
					if (typeof overlayMaps[i].eachLayer == 'function') {
						overlayMaps[i].eachLayer(function(layer) { 
							layer.options.color = color;
	                                	        layer.setOpacity(opacity);
		                                        layer._reset();
						});
					} else {
						overlayMaps[i].options.color = color;
						overlayMaps[i].setOpacity(opacity);
						overlayMaps[i]._reset();
					}
				}
			}
			document.getElementById('map').style.backgroundColor = (e.name.indexOf('Imagery') > -1)?'gray':'white';
		});
	}

	if (L.geographGeocoder)
		map.addControl(L.geographGeocoder());

	if (L.control.locate)
		L.control.locate().addTo(map);

	if (L.Control.fileLayerLoad) {
		filelayer = L.Control.fileLayerLoad().addTo(map);

	        filelayer.loader.on('data:loaded', function (event) {
        	    // event.layer gives you access to the layers you just uploaded!

	            // Add to map layer switcher
        	    layerswitcher.addOverlay(event.layer, event.filename);
	        });
	}
}
