<html>
<head>
<title>Submit Image</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link href="{"/js/select2-3.3.2/select2.css"|revision}" rel="stylesheet"/>
<script src="{"/js/select2-3.3.2/select2.js"|revision}"></script>
<script type="text/javascript" src="https://s1.geograph.org.uk/mapper/geotools2.v7300.js"></script>
<script src="{"/js/jquery.storage.js"|revision}"></script>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <link rel="stylesheet" type="text/css" href="{"/js/mappingLeaflet.css"|revision}" />

        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.0/proj4.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

        <script type="text/javascript" src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>
        <script type="text/javascript" src="{"/js/mappingLeaflet.js"|revision}"></script>


        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.67.0/dist/L.Control.Locate.min.css" />
	<link rel="stylesheet" href="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.css" />

	<script src="https://www.geograph.org/leaflet/L.Control.Locate.js"></script>
	<script src="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.min.js"></script>

	<script src="{"/js/Leaflet.GeographRecentUploads.js"|revision}"></script>

<script src="{"/js/to-title-case.js"|revision}"></script>
<script type="text/javascript" src="https://m.geograph.org.uk/s/exif-js/exif.js"></script>

<style>{literal}

html {
  box-sizing: border-box;
}
*, *:before, *:after {
  box-sizing: inherit;
}
img, #previewImage2 {
	image-orientation: none;
}
#preview {
	text-align:center;
}
.tabs {
	white-space:nowrap;
	overflow:hidden;
}
.tabs a {
	display:inline-block;
	width:20vw;
	text-decoration:none;
	padding:10px;
	color:black;
	background-color:grey;
	border-left:1px solid black;
	color:white;
}
.tabs a.selected {
	background-color:#e4e4fc;
	color:black;
}
.tabs a span {
	display:none;
}
 @media screen and (min-width: 400px) {
	.tabs a span {
		display:inline-block;
	}
}

form { 
	background-color:#e4e4fc;
	min-height:calc( 100vh - 89px );
}

@media screen and (min-width: 600px) {
	form {
		padding:8px;
	}
}

.tab2, .tab3, .tab4, .tab5 {
	display:none;
}

.tab1 input, .tab3 input, .tab4 input[type=text] {
	width:100%;
}
.tab1 select, .tab4 select {
	width:100%;
}

.tab3 textarea {
	width:100%;
	height:400px;
	max-height:80vh;
}
.tab3 #placenames {
	text-align:right;
}
.tab3 #placenames a {
	padding:3px;
	margin-right:10px;
	text-decoration:none;
	white-space:nowrap;
}

.tab2 input[type=text] {
        width:50%;
	max-width:120px;
	background-color:silver;
}
.tab2 input.active {
	background-color:white;
}
.tab2 label.gr {
        padding:2px;
}
.tab2 label.active {
        background-color:yellow;
}

.tab2 select {
	max-width: calc( 100vw - 190px );
}
.tab2 #map {
	width:100%;
	height: calc( 100vh - 189px );
	background-color:white;
}

.tab2 #mapInfo {
	position:absolute;
	z-index:100000;
	bottom:30px;
	left:30px;
	right:30px;
	height:150px;
	background-color:white;
	opacity:0.9;
	text-align:center;
	padding:2px;
}
a.done {
	color:lightgreen;
}
#recentTags {
	line-height:3em;
}
#recentTags a {
	padding:5px;
	border-radius:3px;
	background-color:silver;
	text-decoration:none;
	margin-right:10px;
	white-space:nowrap;
}
</style>

<script>
$.ajaxSetup({
  cache: true
});


	function selectTab(idx) {
		for(q=1;q<=5;q++)
			$("div.tab"+q).toggle(idx==q);
		$('div.tabs a').removeClass('selected').eq(idx-1).addClass('selected');
		if (idx == 2) {
			if (map)
				map.invalidateSize();
			else
				loadmap();
		}

		$('div.tabs a').removeClass('done');
		var form = document.forms['theForm'];
		if (form.elements['jpeg_exif'].value.length > 2) {
			if (form.elements['jpeg_exif'].files && form.elements['jpeg_exif'].files[0]) {
				var file = form.elements['jpeg_exif'].files[0];
				if (file.size > 10000 && file.size <= 8192000 && file.type && file.type == "image/jpeg")
					$('div.tabs a').eq(0).addClass('done');
			} else {
				$('div.tabs a').eq(0).addClass('done');
			}
		}
		if (form.elements['grid_reference'].value.length > 4) $('div.tabs a').eq(1).addClass('done');
		if (form.elements['title'].value.length > 1) $('div.tabs a').eq(2).addClass('done');
		if (form.elements['contexts[]'].value) $('div.tabs a').eq(3).addClass('done');

		if (idx == 3 && eastings1 && form.elements['grid_reference'].value.match(/^[A-Z]{2}/)) {
			$('#placenames').html('<a href="#" onclick="loadplacenames()">Load Placenames</a>');
		}

		return false;
	}

function unloadMess() {
        return "**************************\n\nYou have unsaved changes.\n\n**************************\n";
}
function setupMess() {
        //this is unreliable with AttachEvent
        window.onbeforeunload=unloadMess;
}

function cancelMess() {
        window.onbeforeunload=null;
}


			function updateAttribDivs() {
				isself = document.theForm.pattrib[0].checked;
				
				$('#dt_self').get(0).style.fontWeight = isself?'bold':'';
				$('#dd_self').get(0).style.display = isself?'':'none';
				$('#dt_other').get(0).style.fontWeight = isself?'':'bold';
				$('#dd_other').get(0).style.display = isself?'none':'';
			}
			//AttachEvent(window,'load',updateAttribDivs,false);


function checkMultiFormSubmission() {
	var form = document.forms['theForm'];


	if (form.elements['jpeg_exif'].value.length < 2) {
		selectTab(1);		
		alert("Please select an image to submit");
		return false;
	}
        if (form.elements['jpeg_exif'].files && form.elements['jpeg_exif'].files[0]) {
            var file = form.elements['jpeg_exif'].files[0];
            if (file && file.size && file.size > 8192000) {
                alert('File appears to be '+file.size+' bytes, which is too big for final submission. Please downsize image to be under 8 Megabytes');
		return false;
            }
            if (file && file.type && file.type != "image/jpeg") {
                alert('File appears to not be a JPEG image. We only accept .jpg files');
		return false;
            }
        }


	if (form.elements['grid_reference'].value.length < 5) {
		selectTab(2);
		alert("Please enter a Subject Location. (subject is required, camera location is optional)");
		return false;
	}

	if (form.elements['title'].value.length < 2) {
		selectTab(3);		
		alert("Please enter a image title");
		return false;
	}

	////////////////////////

	var options = form.elements['contexts[]'].options;
	var count = 0;
	var rawText = $.localStorage('submit.contexts');
	var existing = {};
	if (rawText && rawText.length > 3) {
		$.each(rawText.split(/\n/), function(index,value) {
			var bits = value.split('|');
			existing[bits[1]] = bits[0];
		});
	}
	for(q = 0;q<options.length;q++)
		if (options[q].selected) {
			existing[options[q].value] = Date.now();
			count++;
		}
	if (count > 0) {
		var lines = [];
		$.each(existing,function(key,value) {
			lines.push(value+'|'+key);
		});
		$.localStorage('submit.contexts',lines.join('\n'));
	} else {
		selectTab(4);		
		alert("Please select at least one Geograpical Context");
		return false;
	}

	/////////////////////////

	var rawText = $.localStorage('submit.tags');
	var existing = {};
	if (rawText && rawText.length > 3) {
		$.each(rawText.split(/\n/), function(index,value) {
			var bits = value.split('|');
			existing[bits[1]] = bits[0];
		});
	}
	var tags = $('#tags').val();
	if (tags && tags.length > 1) {
		$.each(tags.toLowerCase().split(/;/), function(index,value) {
			existing[value] = Date.now();
		});
		var lines = [];
		$.each(existing,function(key,value) {
			lines.push(value+'|'+key);
		});
		$.localStorage('submit.tags',lines.join('\n'));
	}

	/////////////////////////

	cancelMess();
	return true;
}

/******************************************************************************
 MAP */

	var map = null;
	var issubmit = false; //we do it manually. 
	var geocoder = null;
	var disableAutoUpdate = false;

{/literal}
{dynamic}
	var static_host = '{$static_host}';

	{if $os_api_key}
		var OSAPIKey = "{$os_api_key}";
	{else}
		var OSAPIKey = null;
	{/if}
{/dynamic}
{literal}

function loadmap() {
	setupBaseMap(map);

	if (location.search.length>2 && location.search.indexOf('gridref=')) {
		if (match = location.search.match(/gridref=([A-Z]{1,2} ?\d{2,5} ?\d{2,5})/)) {
			disableAutoUpdate = true; //we just centering the map, not setting an exact location!
			centerMap(match[1]);
		}
	}

	L.geotagPhoto.crosshair({
		crosshairHTML: '<img alt="Center of the map; crosshair location" title="Crosshair" src="https://unpkg.com/leaflet-geotag-photo@0.5.1/images/crosshair.svg" width="100px" />'
	}).addTo(map).on('input', function (event) { //really jsut called when the map is recentered!
	   var point = this.getCrosshairLatLng(); //really just getting center of the map!
           if (point && point.lat && !disableAutoUpdate)
		   setLatLong(point.lat, point.lng);
	});

	map.on('mousedown',function() {
		disableAutoUpdate = false;
	});

	L.DomEvent.on(map._container, 'touchstart', function() {
                disableAutoUpdate = false;
        });

	map.on('dblclick',function(event) {
		console.log(event,event.latlng);

		//first SWAP the active.
		disableAutoUpdate = true;
		$('.tab2 input[type=text]').toggleClass('active');
		$('.tab2 label.gr').toggleClass('active');
		
		//then recenter the map (which feeds back to the new location box!) 
		disableAutoUpdate = false;
		map.panTo(event.latlng);
	});

	setupMess();
}

$(function() {
	$('.tab2 input[type=text]').focus(function() {
		$('.tab2 input[type=text]').removeClass('active');
		$('.tab2 label.gr').removeClass('active');
		$(this).addClass('active');
		$(this).prev().addClass('active');
		if (this.value) {
			centerMap(this.value);
		}
	}).on('change input keyup paste',function() {
		console.log(this.value);
		disableAutoUpdate = true;
		if (this.value)
			centerMap(this.value);
		updateMapMarker(this,false);
		$('#mapInfo').hide();
	});
	$('.tab2 label').each(function() {
		var attr = $(this).attr('for');
		if (attr) {
			var $ele = $('input[type=text][name='+attr+']');
			if ($ele.length == 1) {
				$(this).click(function(event) {
					event.preventDefault(); //prevent it actully focusing the element!
					$('.tab2 input[type=text]').removeClass('active');
					$('.tab2 label.gr').removeClass('active');
					$ele.addClass('active');
					$ele.prev().addClass('active');
					if ($ele.val())
						centerMap($ele.val());
				})
			}
		}
	});
});

function centerMap(gridref) {
	gridref = gridref.trim().toUpperCase().replace(/ /g,'');
	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref)) {
	        ok = true;
	} else {
	        grid=new GT_Irish();
	        ok = grid.parseGridRef(gridref)
	}

        if (ok && gridref.length > 4) {
	        if (gridref.length <= 6 && grid.eastings%1000 == 0 && grid.northings%1000 == 0) {
		        grid.eastings = grid.eastings + 500;
		        grid.northings = grid.northings + 500;
	        } else if (gridref.length <= 8 && grid.eastings%100 == 0 && grid.northings%100 == 0) {
		        grid.eastings = grid.eastings + 50;
		        grid.northings = grid.northings + 50;
	        } else if (gridref.length <= 10 && grid.eastings%10 == 0 && grid.northings%10 == 0) {
	                grid.eastings = grid.eastings + 5;
	                grid.northings = grid.northings + 5;
		}

	        //convert to a wgs84 coordinate
	        wgs84 = grid.getWGS84(true);

		if (!map)
			loadmap();
		var point = new L.LatLng(wgs84.latitude,wgs84.longitude);
		var z = map.getZoom();

		if (!z || z < 13)
			map.setView(point,15);
		else
			map.setView(point);
	}
}

function setLatLong(lat,long,element,source) {
	//console.log('setLatLong',lat,long,element,source);
	if (!lat || !long) {
		return;
	}
	if (!map)
                loadmap();


	  if (map && element) {  //only call if specifying a element. If no element, it probably just a map drag!
                var z = map.getZoom();
		if (!z || z < 13) {
			map.setView([lat,long],15);
		} else {
			map.panTo([lat,long]);
		} 
	  }

          wgs84=new GT_WGS84();
          wgs84.setDegrees(lat, long);

          var grid = false
          if (wgs84.isIreland2()) {
                grid=wgs84.getIrish(true);
          } else if (wgs84.isGreatBritain()) {
                grid=wgs84.getOSGB();
          }
          if (grid) {
		gridref = grid.getGridRef(5);//.replace(/ /g,'');

		if (!element) {
			if ($('#photographer_gridref').hasClass('active'))
				element = 'photographer_gridref';
			else if ($('#grid_reference').hasClass('active'))
				element = 'grid_reference';
		}

		if (element) {
	                document.forms['theForm'].elements[element].value = gridref;

			if (element == 'photographer_gridref' && !marker2) { //updateMapMarker WILL create subject marker, but not photographer marker?
				createPMarker([lat,long]);
			}

			updateMapMarker(document.forms['theForm'].elements[element],false);
		}
		if (source) {
			$('#exiflocation').text("Location from "+source+": "+gridref).show();
		}
		$('#mapInfo').hide();
          }
}

function getLocation() {
	$.geolocation.get({success: function(position) {

		setLatLong(position.coords.latitude, position.coords.longitude, 'photographer_gridref','GPS');

	}, fail:function() {
		alert('Unable to load location');
	}});
}


var size=6;
var last='';
function copyPosition(form) {
	var input = form.elements['photographer_gridref'].value;

	var grid = new GT_OSGB();
	if (!grid.parseGridRef(input)) {
		grid = new GT_Irish();
		if (!grid.parseGridRef(input)) {
			return;
		}
	}

	if (last != input)
		size = 3;	
	last = input;

	form.elements['grid_reference'].value = grid.getGridRef(size);//.replace(/ /g,'');

	size = size + 1;
	if (size ==6) size = 3;
}
function openMap(input) {
	var grid = new GT_OSGB();
	if (!grid.parseGridRef(input)) {
		grid = new GT_Irish();
		if (!grid.parseGridRef(input)) {
			alert("Does not appear to be a valid grid reference");
			return;
		}
	}
	window.open('http://www.geograph.org.uk/showmap.php?gridref='+encodeURIComponent(input),'_blank');
}

function checkGridref(that) {
	//todo!

}


/******************************************************************************
 ONLINE CHECKS */ 

function successCallback() {
	$('#submissionAvailable').show();
	$('#submissionUnavailable').hide();
}
function failedCallback(message) {
	if (message)
		$('#submissionMessage').html(message);
	$('#submissionAvailable').hide();
	$('#submissionUnavailable').show();
	$('#submissionUnavailable input').show();
}

function checkOnline() {
	if (!jQuery.support.cors) {
		alert("sorry your browser is not supported");
		return;
	}

	$.ajax({
		url: "/stuff/online.json.php",
		dataType: 'json',
		xhrFields: { withCredentials: true },
		cache: false,
		success: function(data){
			if (data === "ok") {
				successCallback();
			} else if (data === "no") {
				failedCallback('Need to <a href="/login.php" target="_blank">login</a>. Once logged in, return here, and try again.');
			}
				},
				error: function() {
			failedCallback('unable to contact server');
		}
	});
	setTimeout(function() {
		failedCallback('Check again that the server is still available...');
	},30000);
}



/******************************************************************************
 PLACENAMES */ 

function loadplacenames() {
        var url = "https://www.geograph.org.uk/stuff/os_open_names.json.php";
        $.ajax({
                url: url,
		data: {e:eastings1,n:northings1},
                dataType: 'json',
                cache: true,
                success: function(data) {
			var $ele = $('#placenames').empty();
			if (data && data.rows) {
				$.each(data.rows, function(index,value) {
					var $link = $('<a href="#"/>');
					$link.text(value['name1'] || value['name2']).attr('title',value['local_type']);
					$ele.append($link);
				});
				$ele.find('a').click(useplacename);
			}
		}
	});
}

function useplacename() {
	if (!inputElement && document.forms['theForm'].elements['title'].value == '') {
		inputElement = 'title';
	}
	if (inputElement) {
		var element = document.forms['theForm'].elements[inputElement];
		if (element.value == '')
			element.value = $(this).text();
		else
			element.value = element.value + ' ' + $(this).text();
	}
	return false;
}

var inputElement = null;
$(function() {
	$('.tab3 input, .tab3 textarea').focus(function() {
		inputElement = this.name;
	});
});

/******************************************************************************
 CONTEXTS */ 

$(function() {
	var $ele = $('select#contexts');

	var rawText = $.localStorage('submit.contexts');
	if (rawText && rawText.length > 3) {
	        var existing = {};
		var $optgroup = $('<optgroup/>').attr('label', 'Recently Used').appendTo($ele);
		$.each(rawText.split(/\n/), function(index,value) {
			var bits = value.split('|');
			existing[bits[1]] = parseInt(bits[0],10);
		});
		var keys = Object.keys(existing); //list of contexts
		keys.sort(function(a, b) {
		    a = existing[a];
		    b = existing[b];
		    return a < b ? 1 : (a > b ? -1 : 0); //descending sort
		});
		var count=0;
		$.each(keys,function(index,value) {
			if(count < 6) {
				$('<option/>').attr('value', value).text(value).appendTo($optgroup);
				count++;
			}
		});
	}

        var url = "https://www.geograph.org.uk/tags/primary.json.php";
        $.ajax({
                url: url,
                dataType: 'json',
                cache: true,
                success: function(data) {
			var $optgroup;
			var last = '';
			for(q=0;q<data.length;q++) {
				if (data[q].grouping != last) {
					$optgroup = $('<optgroup/>').attr('label', data[q].grouping).appendTo($ele);
					last = data[q].grouping;
				}
				$('<option/>').attr('value', data[q].tag).text(data[q].tag).appendTo($optgroup);
			}

		}

	});

	var rawText = $.localStorage('submit.tags');
	if (rawText && rawText.length > 3) {
		var existing = {};
		var $tags = $('#recentTags').append('Recent (click to use): ');
		$.each(rawText.split(/\n/), function(index,value) {
			var bits = value.split('|');
			existing[bits[1]] = bits[0];
		});
		var keys = Object.keys(existing); //list of tags
		keys.sort(function(a, b) {
		    a = existing[a];
		    b = existing[b];
		    return a < b ? 1 : (a > b ? -1 : 0); //descending sort
		});
		var count=0;
		$.each(keys,function(index,value) {
			if(count < 16) {
				$('<a/>').attr('href','#').text(value.capitalizeTag()).click(useTag).appendTo($tags);
				count++;
			}
		});
	}
});

function useTag() {
	var $ele = $('#tags');
	if ($ele.val().length>1) {
		$ele.val($ele.val()+';'+$(this).text()).trigger('change');
	} else {
		$ele.val($(this).text()).trigger('change');
	}
	var $parent = $(this).parent();
	$(this).remove();
	if ($parent.find('a').length==0)
		$parent.empty();
}

/******************************************************************************
 TAGS */

String.prototype.capitalizeTag = function () {
	var bits = this.split(":",2);
	if (bits.length == 2) {
		return bits[0].toLowerCase()+':'+bits[1].toTitleCase();
	} else {
		return this.toTitleCase();
	}
}

var sentFirst = false;
var defaultMode = false;

$(function() {
	$('#tags').select2({
		multiple: true,
		separator: ';',
		placeholder: 'enter or search for tags here',
		closeOnSelect: false,
		tokenSeparators: [';',','],
		ajax: {
			quietMillis: 200,
			url: "https://www.geograph.org.uk/tags/tags.json.php",
			cache: true,
			jsonpCallback: 'tagsFunc',
			dataType: 'jsonp',
			data: function (term, page) {
				var mode =$("input[name=selector]:checked").val();
				var data = {mode: mode, term: term};
				if (mode == 'nearby' && $("input[name=grid_reference]").length > 0) {
					data.gr = $("input[name=grid_reference]").val();
				} else if (mode == 'nearby' && $("input[name=photographer_gridref]").length > 0) {
					data.gr = $("input[name=photographer_gridref]").val();
				} else if (mode == 'selfrecent') {
					if (term.length > 0 && !$('.experimental').prop('checked')) {
						//if entered a term, fall back to 'Your Tags - Ranked'
						data.mode = 'ranked';
						data.mine = 1;
						data.page = page;
					} else {
						data.term = ''; //send a empty string to help with caching
					}
				} else {
					data.page = page;
				}
				return data;
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				var more = (data.length == 60 && (page*60) < 1000);
				var results = [];
				$.each(data, function(){
					results.push({id: this, text: this.capitalizeTag() });
				});
				return {results: results, more: more};
			}
		},
		createSearchChoice: function (term) {
			var mode =$("input[name=selector]:checked").val()
			if (mode == 'subject' || mode == 'top' || mode == 'bucket' || mode == 'categories')
				return false;
			return {id: term, text: term};
		},
		//formatCreateNew: function (term) { return "\"" + term + "\" (create as new tag)"; },
		initSelection: function (element, callback) {
			var data = [];
			$(element.val().split(/;/)).each(function () {
				data.push({id: this, text: this.capitalizeTag() });
			});
			callback(data);
		}

	}).on('change', function (e) {
		//console.log(e.val,e.added,e.removed);
		if (e.added) {

			//bodge to prevent the event firing when emptying the box (Select2.updateResults does if (equal(term, lastTerm)) return;
			$.data($('.select2-container'), "select2-last-term", '');

			//empty the search box
			$('.select2-input').val('');
		}
	});


	$("input[name=selector]").click(function() {
		var txt = $('.select2-input').val();
		$('#tags').select2('close');
		$('#tags').select2('open');
		if (txt.length > 0) {
			$('.select2-input').val(txt);
		}
		var mode =$("input[name=selector]:checked").val();
		$('.select2-input').prop('disabled',(mode == 'suggestions' || mode == 'prospective' || mode == 'automatic'));
	});

	//fix for firefox to allow the search box to be clicked to focus (works with just the z-index bodge on other browsers)
	$(".select2-search-field input").bind('click',function(e) {
		$(this).focus();
	});

});


/******************************************************************************
 WORK WITH <input type=file> */
 
 
//http://blogs.microsoft.co.il/ranw/2015/01/07/reading-gps-data-using-exif-js/
function toDecimal(number) {
       return number[0].numerator + number[1].numerator /
           (60 * number[1].denominator) + number[2].numerator / (3600 * number[2].denominator);
}    

$(function() {
	document.getElementById("jpeg_exif").onchange = function(e) {
            var file = e.target.files[0];
	    if (file && file.size && file.size > 8192000) {
		$('#jpeg_exif').after('<div class=toobig><b>File appears to be '+file.size+' bytes, which is too big for final submission</b>. Please downsize the image to be under 8 Megabytes</div>');
	    } else {
		$('.toobig').remove();
	    }
	    if (file && file.type && file.type != "image/jpeg") {
		$('#jpeg_exif').after('<div class=nonjpeg>File appears to not be a JPEG image. We only accept .jpg files</div>');
            } else if (file && file.name) {
                if (file && file.size && file.size < 10000) {
			$('#jpeg_exif').after('<div class=toobig>File appears to be '+file.size+' bytes, which is rather small. Please check selected right image.</div>');
		}
		$('.nonjpeg').remove();
                EXIF.getData(file, function() {
		////////////////////////			

			var dateraw = EXIF.getTag(this, 'DateTimeOriginal') || EXIF.getTag(this, 'DateTimeDigitized') || EXIF.getTag(this, 'DateTime');
			if (dateraw) {
				$('input#imagetaken').val(dateraw.substr(0,10).replace(/:/g,'-'));
			}

		////////////////////////			

			var long = EXIF.getTag(this, 'GPSLongitude');		
			var lat = EXIF.getTag(this, 'GPSLatitude');
			if (long&&lat) {
				long = toDecimal(long);
				lat = toDecimal(lat);

				if (EXIF.getTag(this, 'GPSLongitudeRef') == 'W') long = long * -1;
				if (EXIF.getTag(this, 'GPSLatitudeRef') == 'S') lat = lat * -1;

				//console.log('F',long,lat);

				setLatLong(lat, long, 'photographer_gridref','EXIF');


			}

		////////////////////////
                });

		/////////////////////////
			//https://stackoverflow.com/questions/12368910/html-display-image-after-selecting-filename
		    var reader = new FileReader();

	            reader.onload = function (e) {
			$('#preview').show();
			setupMess();

			$('#previewImage').on('load',function() {
				//need to do this 'async' to get the actual size
				var size = $('#previewImage').prop('naturalWidth')+"px "+$('#previewImage').prop('naturalHeight')+"px";
				$('#previewImage2').css({backgroundSize:size});			
			});

		        $('#previewImage').css({maxWidth:'100%',maxHeight:'60vh'})
	                    .attr('src', e.target.result);

			//want the background to be the natural size, not resized
			var size = $('#previewImage').prop('naturalWidth')+"px "+$('#previewImage').prop('naturalHeight')+"px";

			// https://stackoverflow.com/questions/17090571/is-there-a-way-to-set-background-image-as-a-base64-encoded-image
			$('#previewImage2').css({width:'100%', height:'400px', boxShadow:'0 0 8px 8px silver inset', borderRadius:'20px',
				backgroundImage:"url('"+e.target.result.replace(/[\r\n]/g, "")+"')",
				backgroundSize:size, backgroundRepeat:'no-repeat', backgroundPosition:'center'});
		    };

	            reader.readAsDataURL(file);
		/////////////////////////
            }
        }
});

{/literal}
</script>
</head>
<body style="background-color:white">

<div style="background-color:#000066">
	<a target="_top" href="/"><img src="{$static_host}/templates/basic/img/logo.gif" height="50"></a>
</div>

<div class=tabs>
	<a href=# class=selected onclick="return selectTab(1);">1<span>-Image</span>
	</a><a href=# onclick="return selectTab(2);">2<span>-Map</span>
	</a><a href=# onclick="return selectTab(3);">3<span>-Describe</span>
	</a><a href=# onclick="return selectTab(4);">4<span>-Additional</span>
	</a><a href=# onclick="return selectTab(5);">5<span>-Final</span></a>
</div>


<form action="/api-submit.php?mobile=1" name="theForm" method="post" enctype="multipart/form-data">

<div class="tab1">

	{dynamic}
		{if $id}
			<b>Submission Successful</b><br>
			ID: <a href="/photo/{$id}" target=_blank style="background-color: lightgreen;">{$id}</a>
			<hr>
			Submit another image below... (or <a href="/">return to homepage</a>)
			<hr>
		{/if}
	{/dynamic}

	<label for="jpeg_exif">Select image:</label><br>

		<input id="jpeg_exif" name="jpeg_exif" type="file" size="60" style="background-color:white" accept="image/jpeg"/>
		<input type="hidden" name="MAX_FILE_SIZE" value="8192000"/><br><br>

		<div id="preview" style="display:none">
			Full image preview:<br>
			<img src="" id="previewImage"/><br>
			Actual size preview:<br>
			<div id="previewImage2"/></div>
			(may only display a part of image, so can check image is in focus etc)
		</div>

		<label for=largestsize>Size to release: (pixels)</label>
		<select name="largestsize">
                <option value="640"{if $user->upload_size == 640} selected{/if}>640 x 640 (the minimum size)</option>
                <option value="800"{if $user->upload_size == 800} selected{/if}>800 x 800</option>
                <option value="1024"{if $user->upload_size == 1024 || !$user->upload_size} selected{/if}>1024 x 1024</option>
                <option value="1600"{if $user->upload_size == 1600} selected{/if}>1600 x 1600</aoption>
                <option value="65536"{if $user->upload_size > 65530} selected{/if}>As uploaded</option>
	        </select>

		<p id="note">NOTE: Currently the image is uploaded at FULL resolution, and later downsized (not resized on device first!) - so beware of data charges!</p>


		<hr>
		<a href="?redir=false">Choose different Submission Process</a>

		<div style="float:right;position:relative"><a href="/submit.php?redir=false">v1</a> / <a href="/submit2.php">v2</a> / <b>mobile</b> / <a href="/submit-multi.php">multi</a> / <a href="/help/submit">more...</a></div>

</div>

<div class="tab2">
		<br>
		<span class=nowrap><label for=photographer_gridref class="gr active">Camera</label>:
			<input type="text" name="photographer_gridref" id="photographer_gridref" value="" size="12" maxlength="14" onblur="checkGridref(this)" placeholder="(Camera Location)" class="active"/></span>
		&nbsp;
		<span class=nowrap><label for=grid_reference class=gr>Subject</label>:
			<input type="text" name="grid_reference" value="" id="grid_reference" size="12" maxlength="14" onblur="checkGridref(this)" placeholder="(Subject Location)"/></span>

		
		<div><input type="checkbox" name="use6fig" value="1"/> <label for="use6fig">Only use 6 figures (<span class="nowrap"><a title="Explanation" href="https://www.geograph.org.uk/help/map_precision" target="_blank">Explanation</a><img style="padding-left:2px;" alt="New Window" title="opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/></span>)</label></div> 
		
					<select id="view_direction" name="view_direction">
							<option value="-1" style="color:gray">View Direction</option>
							<option value="0">NORTH            : 000 deg (348 > 011)</option>
							<option value="22" style="color:gray">North-northeast  : 022 deg (011 > 033)</option>
							<option value="45">Northeast        : 045 deg (033 > 056)</option>
							<option value="67" style="color:gray">East-northeast   : 067 deg (056 > 078)</option>
							<option value="90">EAST             : 090 deg (078 > 101)</option>
							<option value="112" style="color:gray">East-southeast   : 112 deg (101 > 123)</option>
							<option value="135">Southeast        : 135 deg (123 > 146)</option>
							<option value="157" style="color:gray">South-southeast  : 157 deg (146 > 168)</option>
							<option value="180">SOUTH            : 180 deg (168 > 191)</option>
							<option value="202" style="color:gray">South-southwest  : 202 deg (191 > 213)</option>
							<option value="225">Southwest        : 225 deg (213 > 236)</option>
							<option value="247" style="color:gray">West-southwest   : 247 deg (236 > 258)</option>
							<option value="270">WEST             : 270 deg (258 > 281)</option>
							<option value="292" style="color:gray">West-northwest   : 292 deg (281 > 303)</option>
							<option value="315">Northwest        : 315 deg (303 > 326)</option>
							<option value="337" style="color:gray">North-northwest  : 337 deg (326 > 348)</option>
							<option value="00">NORTH            : 000 deg (348 > 011)</option>
					</select><span id="dist_message" style="padding-left:10px"></span>
		

		<div id="map"></div>

		<div id="mapInfo">
			If don't have location from the image, use the Locate icon above to get a map.<br><br>
			Drag the map so the cross-hairs mark the Camera/Photographer Location.<br><br>
			Tap/click the grid-reference box above the map to toggle between positioning Camera/Subject (current highlighted in White)
		</div>

</div>

<div class="tab3">

		<label for=title>Title:</label>
		<input type="text" name="title" id="title" value="" size="50" maxlength="128" placeholder="Title (REQUIRED)"/>
		<div id="placenames"></div>

		<label for=comment>Description:</label>
		<textarea name="comment" id=comment cols="60" rows="5" wrap="soft" placeholder="description (optional)"></textarea>

</div>

<div class="tab4">
		<br>
		<div><label>Taken Date:</label>
			<input type="date" name="imagetaken" id="imagetaken" value="" size="10" maxlength="10" min="1700-01-01" max="{$smarty.now|date_format:'%Y-%m-%d'}"/>
			<input type=button value=today onclick="this.form.elements['imagetaken'].value = '{$smarty.now|date_format:'%Y-%m-%d'}'"> 
		</div>  
		<hr>

		<div><label for=contexts>Geographical Contexts: (Select Multiple)</label>
			<select name="contexts[]" id="contexts" size=10 multiple=multiple></select></div>
			(<a href="/tags/primary.php" text="More examples" target="_blank">further details and examples</a>)
		<br>
		<hr>

		<div><label for=tags>Tags (and/or Subject):</label>
		<input type="text" name="tags" id="tags" value="" size="60" placeholder="tags (optional)" style="width:100%"></div>
		<div id="recentTags"></div>
		<blockquote>
			<span class="experimental">
			<input type="radio" name="selector" accesskey="1" value="alpha" id="sel_alpha"/> <label for="sel_alpha">All Tags - Alphabetical</label><br/></span>
			<input type="radio" name="selector" accesskey="2" value="ranked" id="sel_ranked" checked/> <label for="sel_ranked">All Tags<span class="experimental"> - Ranked</span></label><br/>
			<input type="radio" name="selector" accesskey="3" value="selfrecent" id="sel_selfrecent"/> <label for="sel_selfrecent">Your Tags<span class="experimental"> - Recently Used</span></label><br/>
			<span class="experimental">
			<input type="radio" name="selector" accesskey="e" value="selfimages" id="sel_selfimages"/> <label for="sel_selfimages">Your Tags - Most Used</label><br/>
			<input type="radio" name="selector" accesskey="4" value="selfalpha" id="sel_selfalpha"/> <label for="sel_selfalpha">Your Tags - Alphabetical</label><br/></span>
			<input type="radio" name="selector" accesskey="5" value="nearby" id="sel_nearby"/> <label for="sel_nearby">Nearby Tags</label><br/>
			<input type="radio" name="selector" accesskey="s" value="subject" id="sel_subject"/> <label for="sel_subject">Subject List</label><br/>
		</blockquote>
		<br><br>
</div>


<div class="tab5">

	
	<div class="termsbox" style="margin:0">
	<div class="interestBox">
	<p>As part of the licence it's important that the '<i>Original Author</i>' or Photographer is correctly attributed, use this section to apply the appropriate credit to the photographer.</p>
	
	<dl>
		<dt id="dt_self"><input type="radio" name="pattrib" value="self" id="pattrib_self" checked="checked" onclick="updateAttribDivs()"/><label for="pattrib_self">I am the photographer</label></dt>
		<dd id="dd_self" style="border:1px solid gray; padding:5px;margin-left:0px;">Use this option when you as the '<i>Geograph Account Holder</i>', also took the photo.</dd>
		<div style="padding:10px"><i>- or -</i></div>
		<dt id="dt_other"><input type="radio" name="pattrib" value="other" id="pattrib_other" onclick="updateAttribDivs()" /><label for="pattrib_other">I am not the photographer, and need to assign the appropriate credit to this image</label></dt>
		<dd id="dd_other" style="border:1px solid gray; padding:5px;margin-left:0px;display:none">By selecting the above option you certify that you as the '<i>Geograph Account Holder</i>',<br/> act as an authorised '<i>Licensor</i>' (<span><a title="What does this mean?" href="/help/what_is_a_licensor" target="_blank">What does this mean?</a><img style="padding-left:2px;" alt="New Window" title="opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/></span>) for the photographer named below:
		<br/><br/>
		Photographer Name: <input type="text" name="pattrib_name" value="" size="40" style="width:100%"/>
		<br/><br/>
		Note: It's vitally important to be sure you are a valid '<i>Licensor</i>' on behalf of the '<i>Original Author</i>' mentioned here. 
		<br/><br/>
		This option should <b>not</b> be used to re-publish the work of others already published under a Creative Commons Licence, either on Geograph or elsewhere; such content is not appropriate for Geograph.
		</dd>
	</dl>
	<!--div style="text-align:right"><input type="checkbox" name="pattrib_default" value="on" id="pattrib_default" />Make this my new default from now on</div-->
		
	</div>
	</div>


		
	<div id="sd6" class="sd">

		<p>
		Because we are an open project we want to ensure our content is licensed
		as openly as possible and so we ask that all images are released under a <b>Attribution-Share Alike</b> <span><a title="Learn more about Creative Commons" href="https://creativecommons.org" target="_blank">Creative Commons</a><img style="padding-left:2px;" alt="External link" title="External link - opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/></span>
		licence, including accompanying metadata.</p>

		<p><span><a title="View licence" href="https://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Here is the Commons Deed outlining the licence terms</a><img style="padding-left:2px;" alt="External link" title="External link - opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/></span></p>
	
		<div id="submissionAvailable" style="display:none;padding:10px;">
			<p>If you agree with these terms, click "I agree" and your image will be stored in the grid square.<br/><br/>
			<input style="background-color:pink; width:120px" type="button" name="abandon" value="I DO NOT AGREE" onclick="return confirm('Are you sure? The current upload will be discarded!');"/> 
			&nbsp;&middot;&nbsp;
			<input style="background-color:lightgreen; width:120px" type="submit" name="finalise" value="I AGREE &gt;" onclick="{literal}if (checkMultiFormSubmission()) {autoDisable(this); return true} else {return false;}{/literal}"/> <br><br>

			(You appear to be online (and logged into geograph.org.uk), so use the Agree button above to submit image)
		</p>
		</div>
		<div id="submissionUnavailable" onmouseover="checkOnline(this)"> 
			<div id="submissionMessage">Dont know if can contact server yet. Click the button to see if have a functional connection...</div>
			<input type="button" value="Check Online" onclick="checkOnline(this)">
		</div>
		<br/><br/>
	</div>




</div>

<input type=hidden name="method" value="mobile">
</form>

</body>
</html>

