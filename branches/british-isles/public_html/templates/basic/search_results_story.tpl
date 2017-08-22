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

		<p data-id="{$image->gridimage_id}" data-position="{$image->viewpoint_eastings},{$image->viewpoint_northings},{$image->nateastings},{$image->natnorthings}">

		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true,$src)}</a><br>

		<strong>{$image->title|escape:'html'}</strong> by <a title="view user profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a>

		{if $image->comment}
			<small title="{$image->comment|escape:'html'}">{$image->comment|escape:'html'|truncate:100:"... (<u>more</u>)"|geographlinks}</small>
		{/if}
		</p>
	{/if}
{/foreach}{literal}

	</div>
  </div>
</div>

  <p class="mapwidth"><small></small></p>
  

<script src="https://osopenspacepro.ordnancesurvey.co.uk/osmapapi/openspace.js?key=A493C3EB96133019E0405F0ACA6056E3&debug=true" type="text/javascript"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script src="//cdn.jsdelivr.net/jquery.scrollto/2.1.2/jquery.scrollTo.min.js"></script>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;                             // track
  var vdir,vdirFeature,vdirString;                                   // view directions
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
  var points = [];
  var moveTimer = null;
  function initmap() {
    osMap=new OpenSpace.Map('map',{products: ["OV0", "OV1", "OV2", "MSR", "MS", "250KR", "250K", "50KR", "50K", "25KR", "25K", "VMLR", "VML"], controls:[],centreInfoWindow:false});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
   
   osMap.setCenter(new OpenSpace.MapPoint(350000,630000),1);
   bounds = new OpenLayers.Bounds();
 
    trkLayer=osMap.getVectorLayer();
   
{/literal}{foreach from=$engine->results item=image}
	{if $image->viewpoint_eastings && $image->nateastings}
   
      // Define camera marker
      pos=new OpenSpace.MapPoint({$image->viewpoint_eastings},{$image->viewpoint_northings});
      size=new OpenLayers.Size(9,9);
      offset=new OpenLayers.Pixel(-4,-9);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre
      infoWindowAnchor=new OpenLayers.Pixel(4,4);
      icon=new OpenSpace.Icon('/geotrips/walk.png',size,offset,null,infoWindowAnchor);
      popUpSize=new OpenLayers.Size(300,320);
      var marker = osMap.createMarker(pos,icon,null,popUpSize);
      marker.events.register('click', marker, function(evt) {literal}{{/literal}
          scrollIntoView({$image->gridimage_id});
      {literal}}{/literal});
      points.push([{$image->viewpoint_eastings},{$image->viewpoint_northings},{$image->gridimage_id}]);
	bounds.extend(new OpenLayers.LonLat({$image->viewpoint_eastings},{$image->viewpoint_northings}));
	bounds.extend(new OpenLayers.LonLat({$image->nateastings},{$image->natnorthings}));

      // Define view direction
      vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point({$image->viewpoint_eastings},{$image->viewpoint_northings}));
      {if $image->nateastings && ($image->nateastings!=$image->viewpoint_eastings || $image->natnorthings!=$image->viewpoint_northings)}
      		vdir.push(new OpenLayers.Geometry.Point({$image->nateastings},{$image->natnorthings}));
      {else}
	        var ea={$image->nateastings} +Math.round(100.0*Math.sin({$image->view_direction}*Math.PI/180.0));
	        var no={$image->natnorthings}+Math.round(100.0*Math.cos({$image->view_direction}*Math.PI/180.0));
	        vdir.push(new OpenLayers.Geometry.Point(ea,no));
      {/if}     
      vdirString=new OpenLayers.Geometry.LineString(vdir);
      vdirFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([vdirFeature]);

	{/if}
{/foreach}{literal}

    osMap.zoomToExtent(bounds,false);


    osMap.events.register('move', osMap, function(evt) {
      if (moveTimer) {
        clearTimeout(moveTimer);
      }
	if (!document.getElementById('enableScroll').checked)
		return;
      moveTimer = setTimeout(function() {
      var point = osMap.getCenter();
      var east = point.getEasting();
      var north = point.getNorthing();
      var distance;
      var idx = -1;
      for(i=0;i<points.length;i++) {
        var d = Math.pow(east  - points[i][0],2) +
                Math.pow(north - points[i][1],2); //no point bothering with sqrt, as just want shortest.
        if (idx == -1 || d < distance) {
          distance = d;
          idx = i;
        }
      }
      if (idx > 0) {
        scrollIntoView(points[idx][2]);
      }
      },100);
    });


  }

  AttachEvent(window,'load',initmap,false);



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

			var bits = element.data('position').split(/,/);
			if (bits.length > 1) {
				var pos = new OpenSpace.MapPoint(bits[0],bits[1]);
				var zoom = (moveTimer)?null:10;
				if (document.getElementById('enableScroll').checked)
					osMap.setCenter(pos,zoom,false);
				newHighlightMarker(bits);
			}
		}
	}
});

function newHighlightMarker(bits) {
	if (highlightMarker) {
		osMap.removeMarker(highlightMarker);
	        trkLayer.removeFeatures([highlightFeature]);
	}

      var pos = new OpenSpace.MapPoint(bits[0],bits[1]);
      size=new OpenLayers.Size(35,35);
      offset=new OpenLayers.Pixel(-17,-21);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre
      infoWindowAnchor=new OpenLayers.Pixel(17,17);
      icon=new OpenSpace.Icon('/geotrips/walk_focus_big_dark.png',size,offset,null,infoWindowAnchor);
      highlightMarker = osMap.createMarker(pos,icon);

     if (bits.length==2)
	return;

      // Define view direction
      var vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point(bits[0],bits[1]));
      vdir.push(new OpenLayers.Geometry.Point(bits[2],bits[3]));
      var vdirString=new OpenLayers.Geometry.LineString(vdir);
      var style_vdir={strokeColor:"#880088",strokeOpacity:0.3,strokeWidth:9.};
      highlightFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([highlightFeature]);

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

			var bits = $(this).data('position').split(/,/);
			if (bits.length > 1) {
				pos = new OpenSpace.MapPoint(bits[0],bits[1]);
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
