{include file="_std_begin.tpl"}

<div style="width:800px;position:relative;">
	<div id="mapLink" style="float:right"></div>
	<h2>Experimental Geograph Coverage Map</h2>
</div>

	<div id="map_message" style="width:800px; height:10px; position:relative;; left:0; margin-bottom:3px; padding:3px;"></div>
	<div id="map" style="width:800px; height:600px; position:relative;"></div>
	<div id="thumbs"></div>
	<br style="clear:both"/>

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
        <script src="http://{$static_host}/ol/nls-api.v2.js"></script>
        <script src="http://{$static_host}/ol/geograph-openlayers.v31.js"></script>
	
        <script src="http://maps.google.com/maps/api/js?v=3&amp;sensor=false"></script>

{literal}
<style>
div#thumbs div.thumb {
  float:left;
  width:120px;
  height:130px;
  margin:2px;
  text-align:center;
}
div#thumbs {
  width:800px;
}
</style>

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
var myriads = new Array();
var m;

var endpoint = "http://api.geograph.org.uk/api-facet.php";

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


	olmap.map.events.register('moveend', olmap.map, mapEvent);
	olmap.map.events.register('zoomend', olmap.map, mapEvent);
	olmap.layers['markers'].setVisibility(false);
        olmap.layers['markers'].events.register('visibilitychanged', olmap.layers['markers'], mapEvent);

	olmap.map.events.register('click', olmap.map, clickEvent);

}


function clickEvent(e) {

    var lonLat = olmap.map.getLonLatFromPixel(e.xy);

    if (olmap.map.getProjection() != "EPSG:4326") {
        lonLat.transform(olmap.map.getProjection(), "EPSG:4326");
    }

    var data = {
      a: 1,
      q: getTextQuery()+(myriads.length?' @myriad ('+myriads.join('|')+')':'')+(document.theForm.customised[1].checked?' @user user'+document.theForm.user_id.value:''),
      limit: 10,
      select: "title,grid_reference,realname,hash"
    };
    
    data.geo=roundNumber(lonLat.lat,6)+","+roundNumber(lonLat.lon,6)+",0";
    data.olbounds=sentBounds;
    data.sort="@geodist ASC"; data.rank=2; 

    var gridref = null;
    if (OpenLayers.Projection.Irish.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //Irish area, preceed lat,lon with Irish Grid Ref
        gridref = OpenLayers.Projection.Irish.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:29902"), 50);
    }
    else if (OpenLayers.Projection.OS.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //UK area, preceed lat,lon with UK Grid Ref
        gridref = OpenLayers.Projection.OS.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:27700"), 50);
    }

    $('#thumbs').html('<div style="height:260px">Loading thumbnails.... please wait.</div>');
    
    _call_cors_api(
      endpoint,
      data,
      'serveCallback',
      function(data) {
        if (data && data.matches) {
          $('#thumbs').empty();

          $.each(data.matches,function(index,value) {
            
            value.attrs.thumbnail = getGeographUrl(value.id, value.attrs.hash, 'small');
            
    value.html = '<div class="thumb">Dist: '+roundNumber(value.attrs['@geodist']/1000,1)+'km<br><a href="http://www.geograph.org.uk/photo/'+value.id+'" title="'+value.attrs.grid_reference+' : '+value.attrs.title+' by '+value.attrs.realname+'"><img src="'+value.attrs.thumbnail+'"/></a></div>';
            
            $('#thumbs').append(value.html);            
          });

	  if (data.total_found) {
	    gridref = encodeURIComponent(gridref.replace(/ /g,''));
            $('#thumbs').append('<div style="float:left;padding:5px;background-color:#eee;width:240px;text-align:center">'+data.matches.length+' of '+data.total_found+' images within map view. <br/><br/>'+
		'<b><a href="/near/'+gridref+'">More Images</a></b>, '+
		'<a href="/browser/#!/loc='+gridref+'/dist=2000/display=map_dots/pagesize=100">Image Browser</a><br/>'+
		'<a href="/gridref/'+gridref+'">Grid Square Page</a></div>');
          }
        }
      }
    );
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
    var gridref = null;
    if (OpenLayers.Projection.Irish.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //Irish area, preceed lat,lon with Irish Grid Ref
        gridref = OpenLayers.Projection.Irish.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:29902"), 50);
    }
    else if (OpenLayers.Projection.OS.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //UK area, preceed lat,lon with UK Grid Ref
        gridref = OpenLayers.Projection.OS.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:27700"), 50);
    }
    if (gridref) {
	$('#mapLink').html('Map Center: <a href="/gridref/'+encodeURIComponent(gridref)+'/links">'+gridref+'</a>');
    }


	if (!olmap.layers['coverage'].getVisibility()) {
		return;
	}

	if (shownall == false || olmap.map.getZoom() <= prevZoom) {
		var bounds = olmap.map.getExtent().transform(olmap.map.getProjection(),"EPSG:4326"); //getMyriadLetter expects wgs84

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

		if (true) {
			myriads = new Array();
			vgr = getMyriadLetter( new OpenLayers.LonLat(bounds.left,bounds.top) );
			if (vgr && vgr.length >0) myriads.push(vgr);
			vgr = getMyriadLetter( new OpenLayers.LonLat(bounds.left,bounds.bottom) );
			if (vgr && vgr.length >0) myriads.push(vgr);
			vgr = getMyriadLetter( new OpenLayers.LonLat(bounds.right,bounds.top) );
			if (vgr && vgr.length >0) myriads.push(vgr);
			vgr = getMyriadLetter( new OpenLayers.LonLat(bounds.right,bounds.bottom) );
			if (vgr && vgr.length >0) myriads.push(vgr);
			url = url + '&myriads='+myriads.join(',');
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

function getMyriadLetter(lonLat) {
    var gridref = null;
    if (OpenLayers.Projection.Irish.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //Irish area, preceed lat,lon with Irish Grid Ref
        gridref = OpenLayers.Projection.Irish.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:29902"), 0);
    }
    else if (OpenLayers.Projection.OS.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //UK area, preceed lat,lon with UK Grid Ref
        gridref = OpenLayers.Projection.OS.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:27700"), 0);
    }
    if (gridref) {
        var bits = gridref.split(/ /);
        return bits[0];
    } else 
        return '';
}

function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

//stolen from the browser!
function getTextQuery() {
    var raw = $('#q').attr('value');

    if (raw.length == 0) {
       return '';
    }

    //http: (urls) bombs out the field: syntax
    //$q = str_replace('http://','http ',$q);
    var query = raw.replace(/http:\/\//g,'http ');

    query = query.replace(/(-?)\b([a-z_]+):/g,'@$2 $1');
    query = query.replace(/@(year|month|day) /,'@taken$1 ');
    query = query.replace(/@gridref /,'@grid_reference ');
    query = query.replace(/@by /,'@realname ');
    query = query.replace(/@tag /,'@tags ');
    query = query.replace(/@placename /,'@place ');
    query = query.replace(/@category /,'@imageclass ');
    query = query.replace(/@text /,'@(title,comment,imageclass,tags) ');

    query = query.replace(/\b(\d{3})0s\b/g,'$1tt');
    query = query.replace(/\bOR\b/g,'|');

    //make excluded hyphenated words phrases
    query = query.replace(/(^|[^"\w]+)-(=?\w+)(-[-\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'-("'+(p1+p2).replace(/-/,' ')+'" | '+(p1+p2).replace(/-/,'')+')';
    });

    //make hyphenated words phrases
    query = query.replace(/(^|[^"\w]+)(=?\w+)(-[-\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'"'+(p1+p2).replace(/-/,' ')+'" | '+(p1+p2).replace(/-/,'');
    });

    //make excluded aposphies work (as a phrase)
    query = query.replace(/(^|[^"\w]+)-(=?\w+)(\'\w*[\'\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'-("'+(p1+p2).replace(/\'/,' ')+'" | '+(p1+p2).replace(/\'/,'')+')';
    });

    //make aposphies work (as a phrase)
    query = query.replace(/(^|[^"\w]+)(\w+)(\'\w*[\'\w]*\w)/,function(match,pre,p1,p2) {
        return pre+'"'+(p1+p2).replace(/\'/,' ')+'" | '+(p1+p2).replace(/\'/,'');
    });

    //change single quotes to double
    query = query.replace(/(^|\s)\b\'([\w ]+)\'\b(\s|$)/g, '$1"$2"$3');

    //fix placenames with / (the \b stops it replacing in "one two"/3
    query = query.replace(/\b\/\b/g,' ');

    if (false && query.length > 0 && query.indexOf('@') != 0) {//if first keyword is a field, no point setting ours. 
        var list = $('#searchin input:checked');
        var str = new Array();
        if (list.length > 0 && list.length <= 3) {
            list.each(function(index) {
              str.push($(this).val());
            });
            query = '@('+str.join(',')+') '+query;
        } else if (list.length > 3 && list.length < 7) {
            var list = $('#searchin input');
            list.each(function(index) {
              if (!$(this).attr('checked'))
                 str.push($(this).val());
            });
            query = '@!('+str.join(',')+') '+query;
        }

    }

    return query;
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
