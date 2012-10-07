{include file="_std_begin.tpl"}


	<div id="map_message" style="width:800px; height:10px; position:relative;"></div>
	<div id="map" style="width:800px; height:600px; position:relative;"></div>
        <link rel="stylesheet" href="/ol/theme/default/style.css" type="text/css">
        <link rel="stylesheet" href="/ol/theme/default/google.css" type="text/css">
        <link rel="stylesheet" href="/ol/style.v4.css" type="text/css">        

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<script src="/ol/grid-projections.js"></script>
        <script src="/ol/OpenLayers.js"></script>
	<script src="/ol/OlEpsg27700Projection.js"></script>
	<script src="/ol/OlEpsg29902Projection.js"></script>
        <script src="/ol/km-graticule.js"></script>
        <script src="/ol/osgb-layer.v7.js"></script>
        <script src="/ol/nls-api.v1.js"></script>
        <script src="/ol/geograph-openlayers.v16.js"></script>
	
        <script src="http://maps.google.com/maps/api/js?v=3&amp;sensor=false"></script>

<script type="text/javascript">{literal}
//<![CDATA[

var labels = [];
var circles = [];

//is the a fetch in progress?
var running = false;

//these are for zoomin optimization (if prev zoom had all markers then no need to load them again for zooming in)
var prevZoom = -1;
var shownall = false;
var sentBounds = '';
var m;

function loadMap() {
	loadMapInner(); //this does most things, EXCEPT center the map, and doesnt add any interaction. 

	if (!olmap.map.getCenter()) { //it might of been set via a permalink
		var centre = new OpenLayers.LonLat(436000, 157000).transform("EPSG:27700", olmap.map.getProjection());
		olmap.map.setCenter(centre, 7);
	}

	olmap.layers['coverage'] = new OpenLayers.Layer.Vector('Coverage');
	olmap.map.addLayer(olmap.layers['coverage']);

	//our coverage layer
	olmap.map.events.register('moveend',olmap.map, updateCoverage);
	olmap.map.events.register('zoomend',olmap.map, updateCoverage);

	updateCoverage();


//this is the global layer, we dont need it here...
//	olmap.map.events.register('moveend', olmap.map, mapEvent);
//	olmap.map.events.register('zoomend', olmap.map, mapEvent);
//      olmap.layers['markers'].events.register('visibilitychanged', olmap.layers['markers'], mapEvent);
}

function updateCoverage(event) {
	if (running) {
		if (request)
			request.abort();
		running = false;
	}

	if (shownall == false || olmap.map.getZoom() <= prevZoom) {

		sentBounds = olmap.map.getExtent().transform(olmap.map.getProjection(),"EPSG:4326").toString();
		
		m = document.getElementById("map_message");
		m.innerHTML = "Requesting Results...";
		
		request = $.getJSON("http://www.geograph.org.uk/stuff/squares.json.php?callback=?&olbounds="+sentBounds,function (data) {

			if (data.error && data.error.length > 0) {
				m.innerHTML = data.error;
				running = false;
				prevZoom = olmap.map.getZoom();
				return;
			}

			m.innerHTML = "Parsing Results..";
			//flag all current markers as old
			for (i in labels) 
				if (labels[i] != null) {
					labels[i].old = true;
				}

		        var loaded = 0;


			var labelStyle = {
			        fontColor: "#000000",
			        fontFamily: 'arial',
			        fontWeight: 'bold',
			        labelAlign: "cm"
			};
                        var circleStyle = {
		                graphicName: 'circle',
		                strokeColor: '#707',
		                strokeWidth: 1,
		                fillColor: '#FF0000',
		                pointRadius: 12
                        };
                        var circleTStyle = {
		                graphicName: 'circle',
		                strokeColor: '#707',
		                strokeWidth: 1,
		                fillColor: '#FF00FF',
		                pointRadius: 12
                        };


			m.innerHTML = "Adding "+(data.markers.length)+" Markers...";
			for (var i = 0; i < data.markers.length; i++) {
				id = data.markers[i].gr;
				if (labels[id] && labels[id] != null) {
			            labels[id].old = false;
                                } else {
  					labelPos = new OpenLayers.Geometry.Point(parseFloat(data.markers[i].lng),parseFloat(data.markers[i].lat)).transform(new OpenLayers.Projection("EPSG:4326"),olmap.map.getProjection());

			                style = {
			                    label:  data.markers[i].c.toString()
			                };
			                OpenLayers.Util.extend(style, labelStyle);
					olmap.layers['coverage'].addFeatures([
						circles[id] = new OpenLayers.Feature.Vector(labelPos.clone(), null, data.markers[i].r?circleStyle:circleTStyle),
						labels[id] = new OpenLayers.Feature.Vector(labelPos, null, style)
					]);

			        }
		                loaded=loaded+1;
		        }

			m.innerHTML = "Removing Old Markers...";
			for (i in labels) 
				if (labels[i] != null) 
					if (labels[i].old == true) {
						olmap.layers['coverage'].removeFeatures([labels[i],circles[i]]);
						labels[i] = null;		
						circles[i] = null;		
					}

			
			//print out a message
			var count = '331917';
			
			if (count && count.length > 0) {
				if (data.markers.length == count) {
					m.innerHTML = "Finished, showing "+data.markers.length+" markers.";
					shownall = true;
				} else {
					m.innerHTML = "Finished, showing "+data.markers.length+" of "+count+" markers.";
					shownall = false;
				}
			} else {
				m.innerHTML = "Finished, showing "+data.markers.length+" of unknown markers.";
				shownall = true;
			}
			running = false;
		});

	}
	prevZoom = olmap.map.getZoom();
}

AttachEvent(window,'load',loadMap,false);
//]]>
{/literal}</script>

{include file="_std_end.tpl"}
