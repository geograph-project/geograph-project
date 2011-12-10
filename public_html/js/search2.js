/* 
 This code is Copyright 2011 Barry Hunter 
 and licenced for reuse under Creative Commons Attribution-Share Alike 3.0 licence.
 http://creativecommons.org/licenses/by-sa/3.0/ 

 Source: http://www.nearby.org.uk/geograph/playground/

 */

var timer = null;

$(function() {
	$('#searchq').keyup(function() {
		if (timer != null) {
			clearTimeout(timer);
		}
		timer = setTimeout(function() {
			runQuery(10);
			$('#results').css('width','');
			timer = null;
		},400);
	});
	$('#location').keyup(function() {
		if (timer != null) {
			clearTimeout(timer);
		}
		timer = setTimeout(function() {
			findPlace();
			timer = null;
		},400);
	}).bind('paste mouseup', function() {
		findPlace();
	});
});

var lastrun = '';

function runQuery(perpage) {
	var query = $('#searchq').attr('value');
	var location = $('#location').attr('value');
	
	if (query=='(anything)') query='';
	if (location=='(anywhere)') location='';
	
	if (query.length == 0) {
		if (location.length > 0) {
			return runQuery2(perpage,query,location);
		}
		return;
	}

	if (query.search(/\[[^\]]*$/) > -1) {
		//we have an open tag search
		var tag = query.match(/\[([^\]]*)$/);

		if (tag[1].length > 1) 
			_get_tags(tag[1],'tags/tags',function(data) {
				if (data && data.length > 0) {

					$("#autocomplete").css('display','block').html("<ul></ul>");

					var ul = $("#autocomplete ul");

					$.each(data, function(i,item){
						text = item.tag;
						if (item.prefix)
						text = item.prefix+':'+text;

						ul.append('<li><a href="javascript:void(useTag(\''+text.replace(/([\"\'])/g,'\\$1')+'\'))">'+text+'</a></li>');
					});
				}
			});

		return;
	} 


	$.ajax({
		url:  "/finder/keywords.json.php?q="+encodeURIComponent(query)+"&callback=?",
		dataType: 'jsonp',
		cache: true,
		success: function(data) {

			if (!data || data.length < 1) {
				showMessage("Nothing found matching '"+query+"'");
				return;
			}
			var search = true;
			var normalized = new Array(location);
			var stat = new Array();
			for(q=0;q<data.length;q++) {
				normalized.push(data[q].normalized);
				stat.push('<b>'+data[q].normalized+'</b>: '+data[q].docs+' images');
				if (data[q].docs < 1) {
					 search = false;
				}
			}
			normalized=normalized.join(' ');
			$('#stats').html(stat.join(', '));

			if (!search || lastrun == normalized) {
				return;
			}

			runQuery2(perpage,query,location,normalized);
		}
	});
}

function useTag(newtag) {
	var query = $('#searchq').attr('value');
	if (query.search(/\[[^\]]*\]?$/) > -1) {
		query = query.replace(/\[([^\]]*)\]?$/,'['+newtag+']');
		$('#searchq').attr('value',query);
		runQuery("15"); 
	}
	$('#autocomplete').css('display','none');
	$('#results').css('width','');
	$('#show_values').css('display','');
}

function runQuery2(perpage,query,location,normalized) {
	_get_textsearch(query ,function(data) {
		lastrun = normalized;

		if (!data || !data.items || data.items.length < 1) {
			showMessage("no images found");
		}
		pages = 1;

		if (data.nextURL) {
			//
		}
		$("#results").html("<p>Preview of search results:</p>");
		var imagecount = 0;
		$.each(data.items, function(i,item){
			$("#results").append('<div class="inner"><a href="http://www.geograph.org.uk/photo/'+item.guid+'" title="'+item.title+' by '+item.author+'" class="i">'+item.thumbTag+'</a></div>');
			imagecount = imagecount + 1;
		});

		if (map && data.syndicationURL) {
			if (layer) {
			       layer.setMap(null);
			}
			layer = new google.maps.KmlLayer(data.syndicationURL.replace(/.json/,'.kml'),{preserveViewport: true,map:map});
		}
		
		if (data.description && data.description.length > 0)
			$("#results").append('<p>Loaded '+imagecount+' of "<a href="'+data.link+'">'+data.description.replace(/\((\d+) in total/,'(<b>$1 in total</b>')+'</a>" images</p>');

	},location,perpage);
}

function findPlace() {
	var query = $('#location').attr('value');
	
	if (query=='(anywhere)') query='';
	
	if (query.length == 0)
		return;
	if (query.search(/^\w{1,2}\d{2,10}/) > -1) {
		var gridref = query.match(/^\w{1,2}\d{2,10}/);
		var grid=new GT_OSGB();
		var ok = false;
		if (grid.parseGridRef(gridref[0].toUpperCase())) {
			ok = true;
		} else {
			grid=new GT_Irish();
			ok = grid.parseGridRef(gridref[0].toUpperCase())
		}

		if (ok) {
			if (!map)
				openMap();

			if (query.length >4) {
				strinkMarkers();

				//convert to a wgs84 coordinate
				wgs84 = grid.getWGS84(true);

				var point = new google.maps.LatLng(wgs84.latitude,wgs84.longitude);

				newDraggableMarker({latLng: point,skipauto:true});
				//$('#location').attr('value',gr); //+' #('+name+')'
				runQuery("6");
				$('#results').css('width','300px');
				$('#autocomplete').css('display','none');

				setTimeout(function() {
					var bounds = map.getBounds();
					if (!bounds.contains(point)) {
						bounds.extend(point);
						map.fitBounds(bounds);
					} else if (map.getZoom() < 6) {
						map.setZoom(5+query.length);
						map.setCenter(point);
					} else if (map.getZoom() < 12 && query.length > 7) {
						map.setZoom(6+query.length);
						map.setCenter(point);
					} 
				}, 500);
			}

			if (query.length <=4) {
				drawSquare(grid,10000);
			} else if (query.length <=6) {
				drawSquare(grid,1000);
				return;
			} else if (query.length <=8) {
				drawSquare(grid,100);
				return;
			} else if (query.length <=10) {
				drawSquare(grid,10);
				return;
			} else {
				drawSquare(grid,1);
				return;
			}
		} else {
			showMessage("Does not appear to be a valid grid-reference '"+query+"'");
		}
	}

	_get_tags(query,'finder/places',function(data) {

		if (!data || !data.items || data.items.length < 1) {
			showMessage("No places found matching '"+query+"'");
			return;
		}

		$("#autocomplete").css('display','block').html("<ul></ul>");

		var ul = $("#autocomplete ul");

		$.each(data.items, function(i,item){
			ul.append('<li><tt>'+item.gr+'</tt> <a href="javascript:void(setLocationBoxGr(\''+item.gr+' '+item.name+'\'))" title="'+item.localities+'">'+item.name+'</a></li>');
		});

		$("#autocomplete").append('<p>'+data.query_info+'</p>');
		$("#autocomplete").append('<p>'+data.copyright+'</p>');
		$("#autocomplete").append('<div style="text-align:right"><a href="javascript:void($(\'#autocomplete\').css(\'display\',\'none\'))">hide</a></div>');

		if (!map)
			openMap();

		setTimeout(function() {
			strinkMarkers();

			showItems(data.items);
		},400);
	});
}

function drawSquare(grid,rounder) {
	var squareCoords = new Array();

	grid.eastings = Math.floor(grid.eastings/rounder)*rounder;
	grid.northings= Math.floor(grid.northings/rounder)*rounder;
	wgs84 = grid.getWGS84(true);squareCoords.push(new google.maps.LatLng(wgs84.latitude,wgs84.longitude));

	grid.eastings += rounder;
	wgs84 = grid.getWGS84(true);squareCoords.push(new google.maps.LatLng(wgs84.latitude,wgs84.longitude));

	grid.northings+= rounder;
	wgs84 = grid.getWGS84(true);squareCoords.push(new google.maps.LatLng(wgs84.latitude,wgs84.longitude));

	grid.eastings -= rounder;
	wgs84 = grid.getWGS84(true);squareCoords.push(new google.maps.LatLng(wgs84.latitude,wgs84.longitude));

	squareCoords.push(squareCoords[0]);

	if (square)
		square.setMap(null);
	square = null;

	square = new google.maps.Polygon({
		paths: squareCoords ,
		strokeColor: "#0000FF",
		strokeOpacity: 0.7,
		strokeWeight: 1,
		fillColor: "#0000FF",
		fillOpacity: 0.05
	});

	square.setMap(map);
}

function showValues() {
	$('#autocomplete').css('display','');
	$('#show_values').css('display','none');
}

var map = null;
var layer = null;
var marker = null;
var markers = new Array();
var square;

function openMap(userclick) {
	if (userclick && $('#location').attr('value')) {
		findPlace();
	}

	showMessage("Click on the map to place a marker - can also drag it");
	$("#close_map").css('display','');
	$("#open_map").css('display','none');
	$("#map_canvas").css('display','block').css('width','600px').css('height','500px');

	var latlng = new google.maps.LatLng(54.55,-3.88);
	var myOptions = {
		zoom: 5,
		center: latlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	markers = new Array();
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	google.maps.event.addListener(map, 'click', function(event) {
		newDraggableMarker(event);
	});

	var pano = map.getStreetView();

	google.maps.event.addListener(pano, 'visible_changed', function(event) {
		document.getElementById('sv_follow_div').style.display = pano.getVisible()?'':'none';
		if (pano.getVisible() && document.getElementById('sv_follow_checkbox').checked) {
			var point = pano.getPosition();
			newDraggableMarker({latLng: point});
		}
	});
	google.maps.event.addListener(pano, 'position_changed', function(event) {
		if (document.getElementById('sv_follow_checkbox').checked) {
			var point = pano.getPosition();
			newDraggableMarker({latLng: point});
		}
	});
}

function newDraggableMarker(event) {
	if (marker) {
		marker.setPosition(event.latLng);
	} else { 
		marker = new google.maps.Marker({
			map: map,
			position: event.latLng ,
			draggable: true
		});
		google.maps.event.addListener(marker, 'click', function() {
			if (map.getZoom() < 10) {
				map.setZoom(10);
			}
			map.panTo(event.latLng);
		});
		google.maps.event.addListener(marker, 'drag', function(event) {
			setLocationBoxLatLng(event.latLng);
		});
		google.maps.event.addListener(marker, 'dragend', function(event) {
			setLocationBoxLatLng(event.latLng);
			runQuery("6");
			$('#results').css('width','300px');
			$('#autocomplete').css('display','none');
			strinkMarkers();
		});
	}
	if (!event.skipauto) {
		setLocationBoxLatLng(event.latLng);
		runQuery("6");
		$('#results').css('width','300px');
		$('#autocomplete').css('display','none');
		strinkMarkers();
	}
}

function strinkMarkers() {
	if (markers.length > 0)
		for(q=0;q<markers.length;q++) {
			markers[q].setIcon('https://chart.googleapis.com/chart?chst=d_map_spin&chld=0.25%7C0%7CFF0000%7C000000');
		}
}

function closeMap() {
	$("#map_canvas").css('display','none');
	$("#close_map").css('display','none');
	$("#open_map").css('display','');
	$('#results').css('width','');
	map = marker = null;
}

function setLocationBoxLatLng(latlng) {
	//create a wgs84 coordinate
	wgs84=new GT_WGS84();
	wgs84.setDegrees(latlng.lat(), latlng.lng());

	if (wgs84.isIreland2()) {
		//convert to Irish
		var grid=wgs84.getIrish(true);

	} else if (wgs84.isGreatBritain()) {
		//convert to OSGB
		var grid=wgs84.getOSGB();
	}

	//get a grid reference with 4 digits of precision
	var gridref = grid.getGridRef(6).replace(/ /g,'');
	$('#location').attr('value',gridref);
}

function setLocationBoxGr(gridref) {
	$('#location').attr('value',gridref);
	runQuery();
	$('#autocomplete').css('display','none');
	$('#results').css('width','');
	$('#show_values').css('display','');
	
	strinkMarkers();
}


function showItems(items) {
	if (!map) {
		setTimeout(function() {
			showItems(items);
		},400);
	}

	var latlngbounds = new google.maps.LatLngBounds();

	$.each(items, function(i,item){

		var grid=new GT_OSGB();
		var ok = false;
		if (grid.parseGridRef(item.gr)) {
			ok = true;
		} else {
			grid=new GT_Irish();
			ok = grid.parseGridRef(item.gr)
		}

		if (ok) {
			//convert to a wgs84 coordinate
			wgs84 = grid.getWGS84(true);

			var point = new google.maps.LatLng(wgs84.latitude,wgs84.longitude);

			newMarker(point,item.name,item.gr)

			latlngbounds.extend(point);
		}
	});

	map.fitBounds( latlngbounds );
	if (map.getZoom() > 13) {
		map.setZoom(13);
	}
}

function newMarker(point,name,gr) {
	var newmarker = new google.maps.Marker({
		map: map,
		position: point, 
		title: name, 
		draggable: true,
		animation: null
	});
	google.maps.event.addListener(newmarker , 'click', function(event) {
		$('#location').attr('value',gr); //+' #('+name+')'
		runQuery("6");
		$('#autocomplete').css('display','none')
		$('#results').css('width','300px');

		newDraggableMarker(event);
	});
	google.maps.event.addListener(newmarker, 'dragend', function(event) {
		newmarker.setPosition(point); //put it back!

		newDraggableMarker(event); //make the main marker.

		strinkMarkers();
	});
	markers.push(newmarker);
}

function showMessage(message) {
	$('body').append('<div class="message">'+message+'</div>');
	setTimeout(function() {
		$('.message').remove();
	},2000);
}


/////////////

function _get_textsearch(query,callback,location,per) {
	var url = "/syndicator.php?text="+encodeURIComponent(query)+"&format=JSON&callback=?";
	if (location && location.length > 0) {
		url = url + "&location="+encodeURIComponent(location);
	}
	if (per && per.length > 0) {
		url = url + "&perpage="+parseInt(per,10);
	}
	_get_url(url,callback);
}

function _get_tags(query,method,callback) {
	if (!method)
		method = 'tags/tags';

	var url = "/"+method+".json.php?q="+encodeURIComponent(query)+"&callback=?";

	_get_url(url,callback);
}

function _get_url(url,callback) {
	$.ajax({
		url: url,
		dataType: 'jsonp',
		jsonpCallback: 'serveCallback',
		cache: true,
		success: function(data) {
			callback(data);
		}
	});
}