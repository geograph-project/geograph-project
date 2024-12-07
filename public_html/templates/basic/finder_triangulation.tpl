{assign var="page_title" value="Search"}
{include file="_std_begin.tpl"}
{dynamic}

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}

<form method="get" action="/search.php" onsubmit="return submitSearch(this)">
	<div style="position:relative;" class="interestBox">
		<div style="position:relative;float:left;width:400px">
			<label for="searchq" style="line-height:1.8em"><b>Search For</b>:</label> <a href="/article/Searching-on-Geograph" class="about" title="More details about Keyword Searching">About</a><br/>
			<input id="qqq" type="text" name="q" value="{$searchtext|escape:"html"}" placeholder="(anything)" size="30" style="font-size:1.3em" />
		</div>
		<div style="position:relative;float:left;width:400px">
			<label for="searchlocation" style="line-height:1.8em">and a <b>Placename, Postcode, Grid Reference</b>:</label> <span id="placeMessage"></span> <br/>
			<input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"}" placeholder="(anywhere)" size="30" style="font-size:1.3em"/>&nbsp;&nbsp;&nbsp;
		</div>
		<br style="clear:both">
		<input id="searchgo" type="submit" name="go" value="Search..." style="font-size:1.3em"/>

	</div>
</form>

{/dynamic}

<div id="message">Search images above</div>

<div id="output"></div>

   <br/><br/>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>
	<script src="{"/mapper/geotools2.js"|revision}"></script>

	<script src="https://d3js.org/d3.v7.min.js"></script>
 <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>


<script>{literal}

var images = {}; //will contain loaded images! (they key is the image id)
var ids = []; //will contain a simple array of image ids. Good for loops, but also will be used to find the image ie, from the idx in delaunay map

var image = null; //the current image
var map;
var delaunay;

//renders a single image
function renderImage(imageIdx) {
	let image = images[ids[imageIdx]];
	let neighbors = delaunay.neighbors(imageIdx);

	 $('#output').empty();

	//todo, if the point is at the 'boundary' of the current map, should run a new search to 'extend' the map
	//maybe get the convexthull, and then find it the point (or its neigbours!) are 'on' the boundary. 
	//perhaps can be done directy with indeges bt not sure how work!) 

	neighbors.forEach(function(nIdx) {
		let nImage = images[ids[nIdx]];

		let $a = $('<a>').attr('href','javascript:renderImage('+nIdx+')').attr('title',image.title+' by '+image.realname);
		let $img = $('<img>').attr('src',nImage.thumbnail);

		$('#output').append($a.append($img));
	});

	$('#output').append('<hr>');


	let $a = $('<a>').attr('href','/photo/'+image.gridimage_id).text(image.title);
	let $a2 = $('<a>').attr('href','/profile/'+image.user_id).text(image.realname);

	let $img = $('<img>').attr('src',image.img).attr('width',image.width).attr('height',image.height);
	$('#output').append($img);
	$('#output').append('<br>');
	$('#output').append($a);
	$('#output').append(' by ');
	$('#output').append($a2);
}
	

function updateInternalMap() {

	ids = Object.keys(images); //recreate it every time!

	if (ids.length < 2) {
		$('#message').html('Only '+ids.length+' images loaded. Not enough to function');
		return;
	}

	$('#message').html(ids.length+' images loaded');

	let points = []; //simply array of points - passed direct to Delaunay triangualtor
        let keys = {}; //assoc array of locations (to find duplicates!) 
        let bounds = L.latLngBounds();
	for(i=0;i<ids.length;i++) {
		let image = images[ids[i]];
		bounds.extend([image.lat, image.lng]);		

		//delaunay doesnt do so well with points in same place, so shuffle them!
		var key = image.lat.toFixed(6)+' '+image.lng.toFixed(6);
		if (keys[key]) {
			keys[key]=keys[key]+1;
			image.lat += (Math.random()-0.5)/100000;
			image.lng += (Math.random()-0.5)/100000;
		} else {
			keys[key]=1;
		}
		points.push([image.lat, image.lng]);
	}	

	delaunay = d3.Delaunay.from(points);

//////////////////////

	//quick way to get middle of the points!
	let center = bounds.getCenter();
	let bestDist = Infinity;
	let bestIdx = null;
        for(i=0;i<ids.length;i++) {
		let image = images[ids[i]];
		let dist = center.distanceTo([image.lat, image.lng]);
		if (dist < bestDist) {
			bestDist = dist;
			bestIdx = i;
		}
	}

	//should never fail
	renderImage(bestIdx);
	
//////////////////////
// this map putput is jsut for debug purposes!

	if (!map) {
		$("#output").after('<div id=mapid style="width:500px;height:500px"></div>');

	        map = L.map('mapid');//.setView([51.505, -0.09], 13);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		    }).addTo(map);
	}
	map.fitBounds(bounds,{maxZoom:15});

    var polygons = delaunay.trianglePolygons();
    polygons.forEach(function(polygon) {
      if (polygon) {
        L.polygon(polygon).addTo(map);
      }
    });

//////////////////////
}


///////////////////////////////////////////////////////////////////////

var endpoint = "https://api.geograph.org.uk/api-facetql.php";

//function _call_cors_api(endpoint,data,uniquename,success,error) {

var perpage = 12;
var distance = 5000;

/////////////////////////////////////////////////////////

var eastings = null;
var northings = null;

function submitSearch(form, skip_pop) {
	if (history.pushState && !skip_pop) {
		var data = $(form).serialize();
                history.pushState({data:data}, '', "?"+data);
        }

  var query = getTextQuery(); //form.elements['q'].value;
  var location = form.elements['location'].value;
  var geo = null; 
  var loctext = '';

 /////////////
 // is a location centered search

//TODO, if there isnt a location, we should do a pre-query, to find a 'central' image, as a starting point!
// myabe SELECT lat,lng FROM ... ORDER BY sequence ASC LIMIT 1!


  if (location && location.length > 5) {
     if (gridref = location.toUpperCase().match(/(^|\/)\s*(\w{1,2}\d{2,10})/)) {
  
	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref[2])) {
		ok = true;

	        //get more detailed location from easting/norhting, returned from gazetter, and check right square.
		if (eastings && northings &&  //saved from the gazetter autocomplete!
				grid.eastings%1000 == 0 && grid.northings%1000 == 0 && // and that its a four figure!
				Math.floor(eastings/1000) == Math.floor(grid.eastings/1000) && // as a quick sanity check, check right square!
				Math.floor(northings/1000) == Math.floor(grid.northings/1000)) {
			grid.eastings = eastings;
			grid.northings = northings;
		}
	} else {
		grid=new GT_Irish();
		ok = grid.parseGridRef(gridref[2])
	}
        if (ok) {
		//convert to a wgs84 coordinate
		wgs84 = grid.getWGS84(true);

            geo=parseFloat(wgs84.latitude).toFixed(6)+","+parseFloat(wgs84.longitude).toFixed(6)+","+distance;
        }
     }
  }

  /////////////
  // finally general search results 

  fetchImages(query,geo);

  /////////////

  //so the form doesnt actully submit
  return false;
}

/////////////////////////////////////////////////////////

function fetchImages(query,geo,order) {

  var geoprefix = "wgs84_"; //set to 'v' to use viewpoint. Todo would be to filter to only images with a photographer location!

geoprefix = 'v';

  var data = {
     select: "id,title,grid_reference,realname,hash,user_id,takenday,"+geoprefix+"lat,"+geoprefix+"long,original,width,height,format",
     match: query,
     limit: perpage,
  };

  if (geo) {
     data.geo = geo;
  }
  if (geoprefix == 'v')
     data.where = "vgrlen>0";

    if (page && page > 1) {
      data.offset=((page-1)*data.limit);
    } else {
      var page = 1;
    }

  if (!query && geo && typeof order === 'undefined')
     order = 'distance';

  if (order) {
    switch(order) {//defaults to relevence!
      case 'taken_down':  data.order="takendays DESC"; data.option='ranker=none';  break;
      case 'taken_up':  data.order="takendays ASC"; data.option='ranker=none';  break;
      case 'submitted_down':  data.order="id DESC"; data.option='ranker=none';  break;
      case 'submitted_up':  data.order="id ASC"; data.option='ranker=none';  break;
      case 'spread':  data.order="sequence ASC"; data.option='ranker=none';  break;
      case 'hash':  data.order="hash ASC"; data.option='ranker=none';  break;
      case 'score':  data.order="score DESC"; data.option='ranker=none';  break;
      case 'distance':  data.order="geodist ASC"; data.option='ranker=none';  break;
      case 'larger':  if (!data.match || data.match.length < 2) data.match = '@status Geograph'; //cheeky, but need something!
		data.match = data.match + ' MAYBE @larger 1024';  break; //as long as left on relvence sorting this should work!
      case 'random':  data.order="RAND()";  break;
    }
  }

  $("#message").text('Loading ['+query+']...');

  _call_cors_api(
    endpoint,
    data,
    'serveCallback',
    function(data) {
     if (data && data.rows) {
        //todo, if rows < 50 then increase the distance?

        $("#message").html("Processing Images...");

        $.each(data.rows,function(index,value) {
          if (images[value.id])
            return;
          value.gridimage_id = value.id;
          value.thumbnail = getGeographUrl(value.id, value.hash, 'small');
          value.img = getGeographUrl(value.id, value.hash, 'full');
	  value.lat = rad2deg(value[geoprefix+'lat'])          
	  value.lng = rad2deg(value[geoprefix+'long'])          
          images[value.id] = value; //assoc array, to deduplcate!
        });

	updateInternalMap();

    } else {
        $("#message").html("No Results Found");
    }
  });
}

///////////////////////////////////////////////////////////////////////

	function setLocationBox(value,wgs84,skipautoload) {
		 $("#searchlocation").val(value);
	}

$(function () {
	$("#searchlocation").autocomplete({
		minLength: 2,
                search: function(event, ui) {
                        if (this.value.search(/^\s*\w{1,2}\d{2,10}\s*$/) > -1) {
				ok = getWgs84FromGrid(this.value);
		                if (ok) {
					setLocationBox(this.value,ok);
				} else {
					$("#message").html("Does not appear to be a valid grid-reference '"+this.value+"'");
                                        $("#placeMessage").show().html("Does not appear to be a valid grid-reference '"+this.value+"'");
                                        setTimeout('$("#placeMessage").hide()',3500);
				}
                                $( "#location" ).autocomplete( "close" );
                                return false;
                        }
                },
                source: function( request, response ) {
			$.ajax('/finder/places.json.php?q='+encodeURIComponent(request.term), {
				success: function(data) {
					if (!data || !data.items || data.items.length < 1) {
						$("#message").html("No places found matching '"+request.term+"'");
			                        $("#placeMessage").show().html("No places found matching '"+request.term+"'");
				                setTimeout('$("#placeMessage").hide()',3500);
					        return;
					}
		                        var results = [];
					$.each(data.items, function(i,item){
				                results.push({value:item.gr+' '+item.name,label:item.name,gr:item.gr,title:item.localities});
					});
					results.push({value:'',label:'',title:data.query_info});
					results.push({value:'',label:'',title:data.copyright});
					response(results);
				}
			});
		},
                select: function(event,ui) {
			if (ui.item && ui.item.e && ui.item.n) {
			        eastings = ui.item.e;
			        northings = ui.item.n;
			}
                        setLocationBox(ui.item.value,false,false);
                        return false;
                }
	})
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#location").val()+')','gi');
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small> " + (item.gr||'') + "<br>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
			.appendTo( ul );
	};  
});

/////////////////////////////////////////////

function rad2deg (angle) {
    // Converts the radian number to the equivalent number in degrees  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/rad2deg
    // +   original by: Enrique Gonzalez
    // +      improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: rad2deg(3.141592653589793);
    // *     returns 1: 180
    return angle * 57.29577951308232; // angle / Math.PI * 180
}

function getGeographUrl(gridimage_id, hash, size) { 

	yz=zeroFill(Math.floor(gridimage_id/1000000),2); 
	ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2); 
	cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
	abcdef=zeroFill(gridimage_id,6); 

	if (yz == '00') {
		fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash; 
	} else {
		fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash; 
	}
	
	switch(size) { 
		case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break; 
		case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break; 
		case 'small': 
		default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg"; 
	}
}

function zeroFill(number, width) {
	width -= number.toString().length;
	if (width > 0) {
		return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
	}
	return number + "";
}

function refreshImage(source) {
     //calling the ombed api should in theory cause the small thumbnail get created
     $.getJSON("https://api.geograph.org.uk/api/oembed?url="+encodeURIComponent(source.src)+"&output=json&callback=_",function(data) {
         source.onerror = null;
         source.src = source.src;
     });
}

function space_date(datestr) {
    if (datestr && datestr.length == 8)
       return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
    return datestr;
}


function usage_log(action,param,value) {
	//todo, use BeaconAPI ?
   $.ajax({
      url: '/stuff/record_usage.php',
      data: {action:action, param:param, value:value},
      xhrFields: { withCredentials: true }
   });
}

// function to allow using cors if possible
function _call_cors_api(endpoint,data,uniquename,success,error) {
  crossDomain = true; //todo/tofix!
  if (uniquename && crossDomain && !jQuery.support.cors) {
    //use a normal JSONP request - works accorss domain
    endpoint += (endpoint.indexOf('?')>-1?'&':'?')+"callback=?&";
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'jsonp',
      jsonpCallback: uniquename,
      cache: true,
      success: success,
      error: error
    });
  } else {
    //works as a json requrest - either same domain, or a browser with cors support
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'json',
      cache: true,
      success: success,
      error: error
    });
  }
}

	
	function _fullsize(thumbnail) {
		return thumbnail.replace(/_\d+x\d+\.jpg$/,'.jpg').replace(/s[1-9]\.geograph/,'s0.geograph');
	}


        function urlplus(input) {
                return encodeURIComponent(input).replace(/%20/g,'+').replace(/%2F/g,'/');
        }
        function htmlentities(input) {
                return $('<div />').text(input).html()
        }

function getTextQuery() {
    var raw = $('#qqq').attr('value');

    if (raw.length == 0) {
       return '';
    }

    //http: (urls) bombs out the field: syntax
    //$q = str_replace('http://','http ',$q);
    var query = raw.replace(/(https?):\/\//g,'$1 ');

    //remove any colons in tags - will mess up field: syntax
    query  =  query.replace(/\[([^\]]+)[:]([^\]]+)\]/g,'[$1~~~$2]');

    query = query.replace(/(-?)\b([a-z_]+):/g,'@$2 $1');
    query = query.replace(/@(year|month|day) /,'@taken$1 ');
    query = query.replace(/@gridref /,'@grid_reference ');
    query = query.replace(/@by /,'@realname ');
    query = query.replace(/@name /,'@realname ');
    query = query.replace(/@tag /,'@tags ');
    query = query.replace(/@subject /,'@subjects ');
    query = query.replace(/@type /,'@types ');
    query = query.replace(/@context /,'@contexts ');
    query = query.replace(/@placename /,'@place ');
    query = query.replace(/@category /,'@imageclass ');
    query = query.replace(/@text /,'@(title,comment,imageclass,tags,subjects) ');
    query = query.replace(/@user /,'@user user');
    
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

    //seperate out tags!
    if (m = query.match(/(-?)\[([^\]]+)\]/g)) {
       for(i=0;i<m.length;i++) {
          var value = m[i];
          query = query.replace(value,'');
          var bits = value.replace(/[\[\]-]+/g,'').split('~~~');
          var prefix = '*';
          if (bits.length > 1) {
             if (bits[0] == 'subject' || bits[0] == 'type' || bits[0] == 'context' || bits[0] == 'bucket') {
                 prefix = bits[0]+'s';
                 value = bits[1];
             } else if (bits[0] == 'top') {
                 prefix = 'contexts';
                 value = bits[1];
             } else {
                 prefix = 'tags';
                 value = bits[0]+' '+bits[1];
             }
          } 
          query = query +' @'+prefix+' '+((value.indexOf('-')==0)?'-':'') + '"_SEP_ '+value.replace(/[\[\]-]+/g,'') + ' _SEP_"';
       }
    }

    if ($('#searchin').length && query.length > 0 && query.indexOf('@') != 0) {//if first keyword is a field, no point setting ours. 
        var list = $('#searchin input:checked');
        var searchintotal = $('#searchin input').length;
        var str = new Array();
        if (list.length > 0 && list.length <= 3) {
            list.each(function(index) {
              str.push($(this).val());
            });
            query = '@('+str.join(',')+') '+query;
        } else if (list.length > 3 && list.length < searchintotal) {
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

</script>{/literal}

{include file="_std_end.tpl"}
