{assign var="page_title" value="Cluster Maps"}
{include file="_std_begin.tpl"}


{if $google_maps_api_key}
	<div style="float:right;position:relative"><a title="Geograph Google Earth Clusters" href="http://gokml.net/2kf.kml" class="xml-kml">KML</a> {external href="http://gokml.net/2kf.kml" text="Download the Google Earth Version"}</div> 
		
	<h2>Geograph Map Clusters</h2>

	<form action="" onsubmit="return updateFilters(this);" name="theForm" style="background-color:#eeeeee;">
	<div style="padding-left:10px;border-bottom:1px solid silver"><b style="margin-bottom:1px">Apply Optional Filters</b></div>
	<div style="float:left;position:relative;padding:10px;">
	Title Keyword:<br/> <input type="text" name="q" value="" id="q"/><br/>
	<small>Example: <tt>river</tt> (single keyword only)</small>
	</div>

	<div style="float:left;position:relative;padding:10px;">
	User ID:<br/> <input type="text" name="user_id" value="" id="user_id" size="3"/><br/>
	<small>Example: <tt>{dynamic}{$user->user_id|default:123}{/dynamic}</tt></small>
	</div>

	<div style="float:left;position:relative;padding:10px;">
	<br/>
	<input type="submit" value="Update Map"/>
	</div>

	<div style="float:left;position:relative;padding:10px;border-left:1px solid silver">Display: <br/>
	<input type="radio" name="clouds" onclick="setClouds(false)" id="clouds_0" checked/> <label for="clouds_0"> Squares</label> /
	<input type="radio" name="clouds" onclick="setClouds(true)" id="clouds_1"/> <label for="clouds_1"> Circles</label><br/><br/>
	</div>
	<br style="clear:both"/>
	</form>
	
	<div style="clear:both;text-align:right;position:relative;font-family:monospace" id="countDiv"></div>
	<div id="mapWrapper">
	<div id="map" style="width:100%; height:600px; position:relative;"></div>
	<br style="clear:both"/>
	</div>
	<form><input type="button" value="Enable Photo Display" onclick="enablePhotos(this)"/> - Displays photos as you drag the map <sup style="color:red">Experimental</sup>
	{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;
	var gc;
	var filterUrl = '';
	var clouds = false;
	var categorycrc = 0;
	var myHtml = '';
	var myLatLng = '';
	
	function onLoad() {
		map = new GMap2(document.getElementById("map"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GLargeMapControl());
		map.addControl(new GMapTypeControl());
		map.addControl(new GScaleControl());
	
	//
		var mapType = G_PHYSICAL_MAP;
		var newZoom = 6;
		var center = new GLatLng(54.55,-3.88);
		
		var filter = false;
		
		if (location.hash.length) {
			// If there are any parameters at the end of the URL, they will be in location.search
			// looking something like  "#ll=50,-3&z=10&t=h"

			// skip the first character, we are not interested in the "#"
			var query = location.hash.substring(1);

			var pairs = query.split("&");
			for (var i=0; i<pairs.length; i++) {
				// break each pair at the first "=" to obtain the argname and value
				var pos = pairs[i].indexOf("=");
				var argname = pairs[i].substring(0,pos).toLowerCase();
				var value = pairs[i].substring(pos+1).toLowerCase();

				if (argname == "ll") {
					var bits = value.split(',');
					center = new GLatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {newZoom = parseInt(value);}
				if (argname == "t") {
					if (value == "m") {mapType = G_NORMAL_MAP;}
					if (value == "k") {mapType = G_SATELLITE_MAP;}
					if (value == "h") {mapType = G_HYBRID_MAP;}
					if (value == "p") {mapType = G_PHYSICAL_MAP;}
					if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
				if (argname == "q") {document.theForm.elements['q'].value = decodeURI(value); filter = true;}
				if (argname == "u") {document.theForm.elements['user_id'].value = decodeURI(value); filter = true;}
				if (argname == "c") {document.theForm.elements['imageclass'].value = decodeURI(value); filter = true;}
				if (argname == "c2") {categorycrc = decodeURI(value); filter = true;}
				if (argname == "r") {
					if (value == "c") {document.theForm.elements['clouds'][1].checked = true; clouds = true}
				}
			}
		}

		map.setCenter(center, newZoom, mapType);

		GEvent.addListener(map, "moveend", makeHash);
		GEvent.addListener(map, "zoomend", makeHash);
		GEvent.addListener(map, "maptypechanged", makeHash);
	
	
	// 
		gc = new gcGrid(map, "{/literal}{$geocubes_api_key}{literal}");

		gc.setOption(GC_OP_DEBUG, 1);
		gc.setOption(GC_OP_CLUSTERCOUNT, 1);

		if (clouds) {
			gc.setRendering(GC_RND_CLOUDS);
		}

		gc.setIcon(GC_IC_CLUSTERMOUSEOVER, 0);
		gc.setVar(GC_VR_COUNTDESCR, 0);

		gc.setCallback(GC_CB_ONCREATECLUSTER, function (cl, latNE, lngNE, latSW, lngSW) {

			var div = cl.getClusterCountDIV();
			div.style.marginTop = 0;
			div.style.fontSize = "20px";
			div.style.fontWeight = "bold";

			if (cl.count > 1000) {
				div.style.fontSize = "16px";
				cl.setImage('http://www.geocubes.com/bla/cube1.png');
			} else if (cl.count > 100) {
				div.style.fontSize = "14px";
				cl.setImage('http://www.geocubes.com/bla/cube2.png');
			} else {
				div.style.fontSize = "12px";
				cl.setImage('http://www.geocubes.com/bla/cube3.png');
			}
		});

		gc.setCallback(GC_CB_ONCREATECLOUD, function (cl, latNE, NE, SW, weight) {

			var div = cl.getClusterCountDIV();

			if (cl.count >= 10000) {
				div.style.fontSize = "14px";
				cl.setImage('http://{/literal}{$static_host}{literal}/img/bubble_4.png');
			} else if (cl.count >= 1000) {
				div.style.fontSize = "13px";
				cl.setImage('http://{/literal}{$static_host}{literal}/img/bubble_3.png');
			} else if (cl.count >= 100) {
				div.style.fontSize = "12px";
				cl.setImage('http://{/literal}{$static_host}{literal}/img/bubble_2.png');
			} else if (cl.count >= 0) {
				div.style.fontSize = "11px";
				cl.setImage('http://{/literal}{$static_host}{literal}/img/bubble_1.png');
			}
		});

		gc.setCallback(GC_CB_POINTCLICK, function (marker, point_id, freetext, opt_field1, opt_field2) {
			myHtml = "<iframe src='/frame.php?id=" + point_id + "' width='500' height='300'><br/><b><a href='/photo/" + point_id + "' target='_blank'>" + freetext + "</a></b> <a href='/profile/" + opt_field1 + "' target='_blank'>User Profile</a>";
			map.openInfoWindowHtml(myLatLng = marker.getLatLng(),myHtml);
			GEvent.addListener(map.getInfoWindow(), "closeclick", function() { myHtml = ''; });
		});

		gc.setCallback(GC_CB_ONLOADSTART, function () {
			document.getElementById('countDiv').innerHTML = "loading photos...";
		});
		gc.setCallback(GC_CB_ONLOADEND, function () {
			document.getElementById('countDiv').innerHTML = gc.getTotalCount() + " photos in current map";
			if (myHtml != '') {
				map.openInfoWindowHtml(myLatLng,myHtml);
				GEvent.addListener(map.getInfoWindow(), "closeclick", function() { myHtml = ''; });
			}
		});

		if (filter) {
			updateFilters(document.theForm);
		}

		gc.enableRenderGrid();

	}
	
	function setClouds(result) {
		clouds = result;
		if (clouds) {
			gc.setRendering(GC_RND_CLOUDS);
		} else {
			gc.setRendering(GC_RND_CUBES);
		}
		gc.enableRenderGrid();
		makeHash();
	}
	
	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getCurrentMapType().getUrlArg();
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t+filterUrl+(clouds?'&r=c':'');
	}

	function updateFilters(f) {

		gc.releaseFilters();
		var render = false;
		filterUrl = '';

		if (f.q.value != '') {
			gc.textFilter (f.q.value);
			filterUrl = filterUrl + "&q=" + encodeURIComponent(f.q.value);
		} else {
			gc.textFilterRelease();
		}

		if (f.user_id.value != '') {
			gc.andFilter (GC_FD1, GC_EQ, parseInt(f.user_id.value,10));
			filterUrl = filterUrl + "&u=" + parseInt(f.user_id.value,10);
		}

		if (categorycrc != 0) {
			gc.andFilter (GC_FD1, GC_EQ, parseInt(categorycrc,10));
			filterUrl = filterUrl + "&c2=" + parseInt(categorycrc,10);
		}

		if (filterUrl.length > 0) {
			gc.renderFilter();
		}

		makeHash();
		return false;
	}

	AttachEvent(window,'load',onLoad,false);
	
	
	var photoList = new Object();
	var photoQueue = new Object();
	var photoTimer = null;
	var floatname;
	
	function enablePhotos(that) {
		var mapDiv = document.getElementById("map");
		var mapWrapperDiv = document.getElementById("mapWrapper");

		mapDiv.style.width = (mapDiv.clientWidth-130)+"px";
		floatname = (mapDiv.style.styleFloat === undefined) ? 'cssFloat' : 'styleFloat';
		mapDiv.style[floatname] = "left";
		map.checkResize();
		that.form.style.display = 'none';
		
		gc.setCallback(GC_CB_ONCREATEPOINT, function (marker, point_id, freetext, opt_field1, opt_field2) {
			photoQueue[point_id] = {marker:marker,distance:map.getCenter().distanceFrom(marker.getLatLng()) };
			
			if (photoTimer != null) {
				clearTimeout(photoTimer)
			}
			photoTimer = setTimeout("processPhotoQueue()",100);
		});
		
	}
	
	function processPhotoQueue() {
		var mapDiv = document.getElementById("map");
		var mapWrapperDiv = document.getElementById("mapWrapper");
		
		//create a sorted array
		var tmpArray = new Array();
		for (var id in photoQueue) {
			tmpArray[tmpArray.length] = id;
		} 
		tmpArray.sort(function(a,b) {
			return photoQueue[a].distance-photoQueue[b].distance;
		});
		
		var added=0;
		var lastdistance = -600;
		//loop though the queue, and add a few never seen before images
		for(var q=0;q<tmpArray.length;q++) {
			id = tmpArray[q];
			if (typeof photoList[id] == 'undefined') {
				if (photoQueue[id].distance - lastdistance > 600) {
					var newdiv = document.createElement('div');
					newdiv.setAttribute('id','p'+id);
					newdiv.style[floatname] = 'left';
					newdiv.style.width = '126px';
					newdiv.style.height = '126px';
					newdiv.style.paddingTop = '3px';
					newdiv.style.margin = '1px';
					newdiv.style.textAlign = 'center';
					newdiv.style.color = 'silver';
					newdiv.style.backgroundColor = 'black';
					newdiv.setAttribute('onmouseover','onPhotoOver('+id+')');
					newdiv.setAttribute('onmouseout','onPhotoOut('+id+')');
					newdiv.innerHTML = "Loading...<br/> #"+id;
					insertAfter(newdiv,mapDiv);
					
					GDownloadUrl("/api/photo/"+id,function(doc,status) {
						if (status == 200) {
							var xmlDoc = GXml.parse(doc);
							var statuses = xmlDoc.documentElement.getElementsByTagName("status");

							if (!statuses || statuses[0].getAttribute("state") != 'ok') {
								return; //stops the inner function only. 
							}

							var titles = xmlDoc.documentElement.getElementsByTagName("title");
							var realnames = xmlDoc.documentElement.getElementsByTagName("user");
							var thumbnails = xmlDoc.documentElement.getElementsByTagName("thumbnail");

							i = 0;
							var title = GXml.value(titles[i]);
							var realname = GXml.value(realnames[i]);
							var src = GXml.value(thumbnails[i]);
							//todo, extract width height, from <img src="....0019_b7c03099.jpg" width="320" height="240"/> and scale to 120px
							
							var match = /\/(\d{6,})_/.exec(src);
							if (match && match.length && match.length > 1 && match[1]) {
								var id = parseInt(match[1],10);

								var thediv = document.getElementById('p'+id);
								thediv.innerHTML = '<a href="/photo/'+id+'" title="'+title+' by '+realname+'" target="_blank"><img src="'+src+'"/></a>'; 
							}
						}
					});

					photoList[id] = photoQueue[id];

					added=added+1;
					if (added == 4) {
						break;
					}
					lastdistance = photoQueue[id].distance;
				}
			} else {
				//need to update its marker reference (GeoCubes calls clearOverlays each time)
				photoList[id].marker = photoQueue[id].marker;
			}
		}
		
		//loop though the displayed list and remove any no longer in the viewport (because they not in the queue) 
		for (var id in photoList) {
			if (typeof photoQueue[id] == 'undefined') {
				var olddiv = document.getElementById('p'+id);
				mapWrapperDiv.removeChild(olddiv);
				delete photoList[id];
			}
		}
		
		//clear the queue so can start over
		photoQueue = new Object();
		photoTimer = null;
	}
	
	//create function, it expects 2 values.
	function insertAfter(newElement,targetElement) {
		//target is what you want it to go after. Look for this elements parent.
		var parent = targetElement.parentNode;
	 
		//if the parents lastchild is the targetElement...
		if(parent.lastchild == targetElement) {
			//add the newElement after the target element.
			parent.appendChild(newElement);
		} else {
			// else the target has siblings, insert the new element between the target and it's next sibling.
			parent.insertBefore(newElement, targetElement.nextSibling);
		}
	}
	
	function onPhotoOver(id) {
		if (typeof photoList[id] != 'undefined')
			photoList[id].marker.setImage('http://{/literal}{$static_host}{literal}/img/highlight_gc_point.png');
	}
	function onPhotoOut(id) {
		if (typeof photoList[id] != 'undefined')
			photoList[id].marker.setImage("http://api.geocubes.com/images/default_gc_point.png");
	}
		
	//]]>
	</script>
	{/literal}
	
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}&amp;sensor=false" type="text/javascript"></script>
	<script src="http://api.geocubes.com/api/geocubes.js?v=1&amp;r=1" type="text/javascript"></script>

{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_std_end.tpl"}
