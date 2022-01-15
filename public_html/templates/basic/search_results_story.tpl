{include file="_search_begin.tpl"}

{if $engine->resultCount}

<style>{literal}
.mapwidth {
        width:800px;
        clear:both;
}
#map {
        width:700px;
        height:800px;
        float:left;
        background-color:white;
	color:black;
}
#scroller {
        position:relative;
        float:left;
        height:800px;
        width:300px;
        overflow-y:scroll;
        overflow-x:hidden;
}

#scroller div {
        padding-bottom:500px;
        text-align:center;
}

#scroller p {
        border:1px solid transparent;
}
#scroller p.selected {
        --background-color:#eee;
        border:1px solid DarkOrchid;
}

#map a {
	color:inherit;
}
</style>

  <div class="mapwidth"> 
	Click the blue circles to see a photograph
	taken from that spot and read further information about the location.  The blue lines indicate
the direction of view. 
	( <i> <input type=checkbox id="enableScroll" checked> <label for="enableScroll">Auto-sync scrolling and map dragging</label></i> )

  </div>

<div style="width:1020px">
  <div id="map" style="width:700px;height:800px;"></div>
  <div id="scroller">
	<div>
	<p>&darr; Scroll down here &darr;</p>



{/literal}{foreach from=$engine->results item=image}
	{if $image->viewpoint_eastings && $image->nateastings}

		<p data-id="{$image->gridimage_id}">

		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true,$src)}</a><br>

		<strong>{$image->title|escape:'html'}</strong> by <a title="view user profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a>

		{if $image->comment}
			<small title="{$image->comment|escape:'html'}">{$image->comment|escape:'html'|truncate:100:"... (<u>more</u>)"|geographlinks}</small>
		{/if}
		</p>
	{/if}
{/foreach}

	</div>
  </div>
</div>

  <p class="mapwidth"><small></small></p>
  


        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

        <script type="text/javascript" src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>
        <script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>
        <script src="{"/mapper/geotools2.js"|revision}"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
	<script src="{"/js/jquery.storage.js"|revision}"></script>
<script src="//cdn.jsdelivr.net/jquery.scrollto/2.1.2/jquery.scrollTo.min.js"></script>

<script>
 var OSAPIKey = '{$os_api_key}';
</script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

<script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '{$static_host}';
  var points = [];
  var moveTimer = null;
  
{literal}
	function loadmap() {

	        var mapOptions =  {
	              //  center: [54.4266, -3.1557], zoom: 13,
        	//        minZoom: 5, maxZoom: 21
	        };
	        var bounds = L.latLngBounds();

	        map = L.map('map', mapOptions);
	        //var hash = new L.Hash(map);

        	//////////////////////////////////////////////////////

		if ($.localStorage && $.localStorage('LeafletBaseMap')) {
			basemap = $.localStorage('LeafletBaseMap');
			if (baseMaps[basemap] && basemap != "Ordnance Survey GB" && (
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
				))
				map.addLayer(baseMaps[basemap]);
			else
				map.addLayer(baseMaps["OpenStreetMap"]);
		} else if (baseMaps['Modern OS - GB']) { // && ri=1
			map.addLayer(baseMaps['Modern OS - GB']);
		} else {
			map.addLayer(baseMaps["OpenStreetMap"]);
		}
		if ($.localStorage) {
			map.on('baselayerchange', function(e) {
		  		$.localStorage('LeafletBaseMap', e.name);
			});
		}

		map.addLayer(overlayMaps["OS National Grid"]);

		addOurControls(map)

		//////////////////////////////////////////////////////
{/literal}{foreach from=$engine->results item=image}
	{if $image->viewpoint_eastings && $image->nateastings}
		wgs84 = en2ll('{$image->grid_reference}',{$image->viewpoint_eastings},{$image->viewpoint_northings});
		wgs842= en2ll('{$image->grid_reference}',{$image->nateastings},{$image->natnorthings});
		createMarker([wgs84.latitude,wgs84.longitude], 'walk', {$image->gridimage_id}, [wgs842.latitude,wgs842.longitude]);
		points[{$image->gridimage_id}] = [wgs84.latitude,wgs84.longitude, wgs842.latitude,wgs842.longitude];
		bounds.extend([wgs84.latitude,wgs84.longitude]);
	//	bounds.extend([wgs842.latitude,wgs842.longitude]);

	{/if}
{/foreach}{literal}

		map.fitBounds(bounds, {padding:[30,30], maxZoom: 14});
		map.setMaxBounds(bounds.pad(2.5));

    map.on('drag', function(evt) {
      if (moveTimer) {
        clearTimeout(moveTimer);
      }
	if (!document.getElementById('enableScroll').checked)
		return;
      moveTimer = setTimeout(function() {
      var point = map.getCenter();
      var distance;
      var idx = -1;
      for(i in points) {
        var d = Math.pow(point.lat - points[i][0],2) +
                Math.pow(point.lng - points[i][1],2); //no point bothering with sqrt, as just want shortest.
        if (idx == -1 || d < distance) {
          distance = d;
          idx = i;
        }
      }
      if (idx > 0) {
        scrollIntoView(idx);
      }
      },100);
    });


  } //loadmap

  AttachEvent(window,'load',loadmap,false);

	function en2ll(gridref,eastings,northings) {
		if (gridref.length%2 == 0)
			var grid=new GT_OSGB();
		else
			var grid=new GT_Irish();
		grid.eastings = eastings;
		grid.northings = northings;
		
		return grid.getWGS84(true);
	}


         var icons = [];
         function createMarker(point,icon,gridimage_id,point2) {
                if (!icons[icon]) {
                        icons[icon] = L.icon({
                            iconUrl: static_host+"/geotrips/"+icon+".png",
                            iconSize:     [9, 9], // size of the icon
                            iconAnchor:   [5, 5], // point of the icon which will correspond to marker's location
                            popupAnchor:  [0, -5] // point from which the popup should open relative to the iconAnchor
                        });
                }
                var marker = L.marker(point, {icon: icons[icon], draggable: false}).addTo(map);

		if (gridimage_id)
		      marker.on('click', function(evt) {
		          scrollIntoView(gridimage_id);
		      });

		if (point2)
                        L.polyline([point, point2],{
                        color: "#0000ff",
                        weight: 2,
                        opacity: 1
                        }).addTo(map);

                return marker;
        }





var performScroll = true;
var highlightMarker = null;
var highlightFeature = null;
$('#scroller').scroll(function() {
        if (!performScroll) { //hacky way to avoid code initialted event
		performScroll = true; //do it next time!
		return;
	}

	var elements = $(this).find('p');
	var offset =  elements.first().offset().top;
	var position = $(this).scrollTop();

	var element = 1;
	if (position > 20) //bodge, to make sure can always select the first image!
	elements.each(function(index) {
	     //for some unknown reason, .position() doesnt seem to work, so use .offset() instead,
	     //but need remove the offset of the first element to get the actual position WITHIN the scrolling div

	     if (($(this).offset().top - offset - position) < 250)
                 element = index;
	});

	if (element > -1) {
		element = $(elements.get(element));
		if (!element.hasClass('selected')) {
			elements.removeClass('selected');
			element.addClass('selected');

			var id = element.data('id')
			var bits = points[id];
			if (bits.length > 1) {
				if (document.getElementById('enableScroll').checked) {
					//calling getBounds directly here exceeds the call stack!, so launder it via setTimeout
					setTimeout("var pos = ["+bits.slice(0,2).join(',')+"];"+
						"if (!map.getBounds().pad(-0.25).contains(pos)) "+
						"	map.panTo(pos);", 50);
				}
				newHighlightMarker(bits);
			}
		}
	}
});

function newHighlightMarker(bits) {

	if (highlightMarker) {
		highlightMarker.removeFrom(map);
		if (highlightFeature)
			highlightFeature.removeFrom(map);
	}

	var icon = 'walk_focus_big_dark';
                if (!icons[icon]) {
                        icons[icon] = L.icon({
                            iconUrl: static_host+"/geotrips/"+icon+".png",
                            iconSize:     [35, 35], // size of the icon
                            iconAnchor:   [17, 17], // point of the icon which will correspond to marker's location
                        });
                }

       highlightMarker = createMarker([bits[0],bits[1]], icon);

       if (bits.length==2)
		return;

      // Define view direction

       highlightFeature = L.polyline([
				[bits[0],bits[1]],
				[bits[2],bits[3]]
                        ],{
                        color: "#800080",
                        weight: 9,
                        opacity: 0.3
                        }).addTo(map);
}

function scrollIntoView(gridimage_id) {

	var elements = $('#scroller').find('p');

	elements.each(function(index) {
             if ($(this).data('id') == gridimage_id) {
                        elements.removeClass('selected');
                        $(this).addClass('selected');
                    performScroll = false;
                    //$('#scroller').scrollTop($(this).position().top-200);
                    $('#scroller').scrollTo($(this),0,{offset:-200});

			var id = $(this).data('id');
			var bits = points[id];
			if (bits.length > 1) {
				newHighlightMarker(bits);
			}
             }
	});
}
</script>
{/literal}


      {if $src == 'data-src'}
                <script src="{"/js/lazynew.js"|revision}" type="text/javascript"></script>
      {/if}


	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*floor(y)+900-floor(x)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}


	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}

