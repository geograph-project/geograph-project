{include file="_std_begin.tpl"}

<div style="width:800px;position:relative;">
	<div id="mapLink" style="float:right"></div>
	<h2>Experimental Geograph Coverage Map</h2>
</div>

	<div id="map_message" style="width:800px; height:10px; position:relative;; left:0; margin-bottom:3px; padding:3px;"></div>
	<div id="map" style="width:800px; height:600px; position:relative;"></div>

	<br style="clear:both"/>

        <link rel="stylesheet" href="{$static_host}/ol/theme/default/style.css" type="text/css">
        <link rel="stylesheet" href="{$static_host}/ol/theme/default/google.css" type="text/css">
        <link rel="stylesheet" href="{"/ol/style.css"|revision}" type="text/css">        

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<script src="{$static_host}/ol/grid-projections.js"></script>
        <script src="{$static_host}/ol/OpenLayers.js"></script>
	<script src="{$static_host}/ol/OlEpsg27700Projection.js"></script>
	<script src="{$static_host}/ol/OlEpsg29902Projection.js"></script>
        <script src="{$static_host}/ol/km-graticule.js"></script>
        <script src="{"/ol/osgb-layer.js"|revision}"></script>
        <script src="{"/ol/nls-api.js"|revision}"></script>
        <script src="{"/ol/geograph-openlayers.js"|revision}"></script>
	
        <script src="https://maps.google.com/maps/api/js?v=3"></script>

{literal}

<script type="text/javascript">
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

var endpoint = "https://api.geograph.org.uk/api-facet.php";

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
	olmap.layers['coverage'].events.register('visibilitychanged', olmap.layers['coverage'], updateCoverage); //pvf

	updateCoverage();
}

function updateCoverage(event) {
	if (running) {
		if (request)
			request.abort();
		running = false;
	}
	var lonLat = olmap.map.getCenter();

    if (olmap.map.getProjection() != "EPSG:4326") {
        lonLat.transform(olmap.map.getProjection(), "EPSG:4326");
    }

	//pvf Warning: openlayers generates zoomend and moveend events whenever zoom level is changed                                                
        if (prevZoom > 4 && olmap.map.getZoom() <= 4) { //pvf                                                                                        
                olmap.layers['coverage'].setVisibility(false);                                                                                       
        }                                                                                                                                            
        else if (prevZoom <= 4 && olmap.map.getZoom() > 4) {                                                                                         
                olmap.layers['coverage'].setVisibility(true);                                                                                        
                prevZoom = olmap.map.getZoom(); //force if below to be true                                                                          
        } //pvf                                                                                                                                      

	if (!olmap.layers['coverage'].getVisibility()) {
                prevZoom = olmap.map.getZoom(); //pvf track zoom level even if invisible
		return;
	}

	if (shownall == false || olmap.map.getZoom() <= prevZoom) {
		var bounds = olmap.map.getExtent().transform(olmap.map.getProjection(),"EPSG:4326");

		sentBounds = bounds.toString();
		if (document.theForm.resolution[1].checked) {
			url = "http://www.geograph.org.uk/stuff/squares-centi.json.php?olbounds="+sentBounds;
			var labelSize = 8;

			if (txt = getTextQuery()) {
				url = url + '&q='+encodeURIComponent(txt);
			}
		} else {
			url = "http://www.geograph.org.uk/stuff/squares.json.php?olbounds="+sentBounds;
			var labelSize = 12;
		}

		if (document.theForm.customised[1].checked)
			url = url + '&user_id='+document.theForm.user_id.value;


		m = document.getElementById("map_message");
		m.innerHTML = "Requesting Results...";

		request = $.getJSON(url, function (data) {

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
			        labelAlign: "cm",
				fontSize: labelSize
			};
                        var circleStyle = {
		                graphicName: 'circle',
		                strokeColor: '#707',
		                strokeWidth: 1,
		                fillColor: '#FF0000',
		                pointRadius: labelSize
                        };
                        var circleTStyle = {
		                graphicName: 'circle',
		                strokeColor: '#707',
		                strokeWidth: 1,
		                fillColor: '#FF00FF',
		                pointRadius: labelSize
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
					/*
					circles[id].events.register('mousedown', circles[id], function(evt) {
						window.open('/gridref/'+data.markers[i].gr);
				                OpenLayers.Event.stop(evt);
				        });
					*/
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

			
			
			if (data.count && data.count.length > 0) {
				if (data.markers.length == data.count) {
					m.innerHTML = "Finished, showing "+data.markers.length+" markers.";
					shownall = true;
				} else {
					m.innerHTML = "Finished, showing "+data.markers.length+" of "+data.count+" markers.";
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

function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

function getTextQuery() {
    return $('#q').attr('value');
}


AttachEvent(window,'load',loadMap,false);

function checkboxUpdate() {
	for (i in labels)
                if (labels[i] != null) {
                         olmap.layers['coverage'].removeFeatures([labels[i],circles[i]]);
                         labels[i] = null;
                         circles[i] = null;
                }

	shownall = false; //just to force update
	updateCoverage();

	document.getElementById("tpointSpan").style.display = (document.theForm.customised[0].checked && document.theForm.resolution[0].checked)?'':'none';
	document.getElementById("keywordSpan").style.display = (document.theForm.resolution[1].checked)?'':'none';
}

//]]>
{/literal}</script>

 {dynamic}{if $user->registered}
	<hr/>
    <form name="theForm">
	<b>Options</b>: 
	<input type=radio name="customised" value="global" onclick="checkboxUpdate()" checked>Everyone / 
	<input type=radio name="customised" value="user" onclick="checkboxUpdate()">Personalized (only your images)
	<input type=hidden name=user_id value="{$user->user_id}">

	&nbsp;&middot;&nbsp;

	<input type=radio name="resolution" value="square" onclick="checkboxUpdate()" checked>GridSquare / 
	<input type=radio name="resolution" value="centisquare" onclick="checkboxUpdate()">CentiSquare
	<br/>
	<span id="keywordSpan" style="display:none">
		Keyword Filter: <input type="search" name="q" id="q"><input type=button value="update" onclick="checkboxUpdate()"> (uses the syntax from the Browser)
	</span>
    </form>
 {/if}{/dynamic}

	<hr/>	
	<b>Key</b>: 
		<span style="background-color:#FF0000;color:black;padding:2px;border-radius:5px;border:1px solid black;">Square with recent images</span> /
		<span style="background-color:#FF00FF;color:black;padding:2px;border-radius:5px;border:1px solid black;">No images in last 5 years</span>
		<span id="tpointSpan">(available for tpoint)</span>

{include file="_std_end.tpl"}
