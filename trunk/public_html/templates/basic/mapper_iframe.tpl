{assign var="page_title" value="Geograph Mapper"}

{include file="_basic_begin.tpl"}


<script src="{"/mapper/OpenLayers.js"|revision}" type="text/javascript"></script>
<script src="{"/mapper/geotools2.js"|revision}"></script>
<script src="{"/mapper/mapper.js"|revision}"></script>

<script type="text/javascript">

var lon = {$e};
var lat = {$n};
var tileurl = "http://{$http_host}/tile.php";
var ttileurl = "http://{$tile_host}/tile.php";

var zoom = 0;
var map, osposition, ml;

var maxOpacity = 0.9;
var minOpacity = 0.1;

var glayer,player;

{literal}

function changeOpacity(byOpacity) {
	var newOpacity = (parseFloat(glayer.opacity) + byOpacity).toFixed(1);
	newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
	glayer.setOpacity(newOpacity);
	if (player) {
		var newOpacity = (parseFloat(player.opacity) + byOpacity).toFixed(1);
		newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
		player.setOpacity(newOpacity);
	}
}

function loadMap() {
	map = new OpenLayers.Map('mapbox', {controls:[], maxExtent: new OpenLayers.Bounds(0, 0, 700000, 1300000), resolutions: [40000/250,10000/250,4000/250,2000/250], units: 'meters', projection: "EPSG:27700"});
	
	//Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.
	var oslayer = new OpenLayers.Layer.WMS("OSGB Landranger", tileurl+"?l=o", {}, {projection: "EPSG:27700", buffer:0});
	oslayer.tileSize = new OpenLayers.Size(250,250);
	oslayer.getURL = geographURL;
	
	//Photographs and coverages are available under a seperate Creative Commons Licence, but NO spidering - see Terms.
	glayer = new OpenLayers.Layer.WMS("Gridsquare Coverage", ttileurl+"?l=g", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [40000/250,10000/250,4000/250,2000/250], buffer:0{/literal}{if $centi}, visibility:false, opacity: 1{else}, opacity: 0.3{/if}{literal}});	
	glayer.tileSize = new OpenLayers.Size(250,250);	
	glayer.getURL = geographURL;
	
	player = new OpenLayers.Layer.WMS("Centisquare Coverage", ttileurl+"?l=p", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [4000/250,2000/250], buffer:0{/literal}{if $centi}, opacity: 1{else}, visibility:false, opacity: 0.9{/if}{literal}});	
	player.tileSize = new OpenLayers.Size(250,250);	
	player.getURL = geographURL;
	
	map.addLayers([oslayer,glayer,player]); 
	
	ll = new OpenLayers.LonLat(lon, lat);
	map.setCenter(ll, {/literal}{$z}{literal});

	// Disable the scroll wheel - zooming breaks the map
	var mousecontrol = new OpenLayers.Control.MouseDefaults();
	mousecontrol.onWheelEvent = mousecontrol.defaultDblClick = function (){ };
	mousecontrol.defaultClick = mouseDefaultClick;
	mousecontrol.defaultMouseDown = mouseDefaultMouseDown;
	map.addControl( mousecontrol );
	
	var panzoom = new OpenLayers.Control.PanZoom();
	//panzoom.draw = drawNoZoom;
	map.addControl( panzoom );
	
	osposition = new OpenLayers.Control.MousePosition({element: document.getElementById('maplocation'), numdigits: 4, update: 1});
	osposition.redraw = showGridRef;
	map.addControl( osposition );
	
	switcher = new OpenLayers.Control.LayerSwitcher();
	map.addControl( switcher );
	
	
	//map.addControl(new OpenLayers.Control.Permalink());
	map.addControl(new OpenLayers.Control.Permalink('permalink'));
	
}

AttachEvent(window,'load',loadMap,false);

</script>{/literal}

{dynamic}{if $user->registered}
<div style="float:left; font-size:0.9em; color:gray;">If parts of the map stop displaying, then <a href="/mapper/captcha.php?token={$token}" style="color:gray;" target="_top">visit this page to continue</a></div>{/if}

<div style="text-align:right; width:660px; font-size:0.7em;margin-bottom:3px" class="nowrap">Change Overlay Opacity: [<a title="increase opacity" href="javascript: changeOpacity(0.1);">+</a>] [<a title="decrease opacity" href="javascript: changeOpacity(-0.1);">-</a>]</div>{/dynamic}

<div id="mapcontainer">
	<div style="position:absolute">
		<div id="mapbox"></div>

		<div id="mapfooter">
			&nbsp; &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616. 
			<span id="maplocation">loading...</span>
		</div>
	</div>
</div>

<div style="float:left; font-size:0.9em; color:gray;">
	<a href="#" id="permalink" target="_top">Link to this Location</a>
</div>
<div style="width:660px; text-align:right; font-size:0.9em;">Jump to Grid Reference: <input type="text" size="8" id="coordin" /><input type="button" onclick="parseLocation()" value="Go" /></div>

<div style="width:660px; border-top:1px solid lightgrey; text-align:center; padding-top:3px;margin-top:3px">
	Click map to view photo(s) in <input type="radio" name="clickto" {if !$centi}checked{/if} id="clickto"/>Grid-Square / <input type="radio" {if $centi}checked{/if} name="clickto"/>Centi-Square 

</div>

<div class="interestBox" style="padding:0px; margin-top:6px">
<h3 style="text-align:center;">Draggable Geograph Map of Great Britain <sup>(beta)</sup></h3>
</div>

<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
Photographs and coverages are available under a seperate <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.<br/>
<br/>
For the purposes of the Creative Commons Licence this page should be considered a "Collective Work", and as such is not available in its entirety for reuse.
</div>

</body>
</html>
