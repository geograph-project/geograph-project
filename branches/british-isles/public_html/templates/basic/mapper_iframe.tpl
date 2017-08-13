{assign var="page_title" value="Geograph Mapper"}

{include file="_basic_begin.tpl"}


<script src="{"/mapper/OpenLayers.js"|revision}" type="text/javascript"></script>
<script src="{"/mapper/geotools2.js"|revision}"></script>
<script src="{"/mapper/mapper.js"|revision}"></script>

<script type="text/javascript">

var lon = {$e};
var lat = {$n};
var tileurl = "http://{$http_host}/tile.php";
var ttileurl = "{$tile_host}/tile.php";

var zoom = 0;
var map, osposition, ml;

var maxOpacity = 0.9;
var minOpacity = 0.1;

var oslayer,glayer,rlayer,player,slayer;

{literal}

function changeOpacity(byOpacity) {
	var newOpacity = (parseFloat(glayer.opacity) + byOpacity).toFixed(1);
	newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
	glayer.setOpacity(newOpacity);
	if (rlayer) {
		var newOpacity = (parseFloat(rlayer.opacity) + byOpacity).toFixed(1);
		newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
		rlayer.setOpacity(newOpacity);
	}
	if (player) {
		var newOpacity = (parseFloat(player.opacity) + byOpacity).toFixed(1);
		newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
		player.setOpacity(newOpacity);
	}
	if (slayer) {
		var newOpacity = (parseFloat(slayer.opacity) + byOpacity).toFixed(1);
		newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
		slayer.setOpacity(newOpacity);
	}
}

function fadeBackground() {
	if (typeof oslayer.opacity == 'undefined') {
		oslayer.opacity = 1;
	}
	var newOpacity = (parseFloat(oslayer.opacity) == 1)?0.3:1;
	oslayer.setOpacity(newOpacity);
}

function loadMap() {
	map = new OpenLayers.Map('mapbox', {controls:[], maxExtent: new OpenLayers.Bounds(0, 0, 700000, 1300000), resolutions: [40000/250,10000/250,4000/250,2000/250], units: 'meters', projection: "EPSG:27700"});

	//Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.
	oslayer = new OpenLayers.Layer.WMS("OSGB Landranger", tileurl+"?l=o", {}, {projection: "EPSG:27700", buffer:0{/literal}{if $scenic}, opacity: 0.3{/if}{literal}});
	oslayer.tileSize = new OpenLayers.Size(250,250);
	oslayer.getURL = geographURL;

	//Photographs and coverages are available under a seperate Creative Commons Licence, but NO spidering - see Terms.
	glayer = new OpenLayers.Layer.WMS("Gridsquare Coverage", ttileurl+"?l=g", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [40000/250,10000/250,4000/250,2000/250], buffer:0{/literal}{if $centi || $scenic || $recent}, visibility:false, opacity: 1{else}, opacity: 0.3{/if}{literal}});
	glayer.tileSize = new OpenLayers.Size(250,250);
	glayer.getURL = geographURL;

	rlayer = new OpenLayers.Layer.WMS("TPoint Availability", ttileurl+"?l=r", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [40000/250,10000/250,4000/250,2000/250], buffer:0{/literal}{if $recent}, opacity: 1{else}, visibility:false, opacity: 0.9{/if}{literal}});
	rlayer.tileSize = new OpenLayers.Size(250,250);
	rlayer.getURL = geographURL;

	player = new OpenLayers.Layer.WMS("Centisquare Coverage", ttileurl+"?l=p", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [4000/250,2000/250], buffer:0{/literal}{if $centi}, opacity: 1{else}, visibility:false, opacity: 0.9{/if}{literal}});
	player.tileSize = new OpenLayers.Size(250,250);
	player.getURL = geographURL;

	slayer = new OpenLayers.Layer.WMS("ScenicOrNot Data", ttileurl+"?l=s", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, resolutions: [40000/250,10000/250,4000/250], buffer:0{/literal}{if $scenic}, opacity: 1{else}, visibility:false, opacity: 0.9{/if}{literal}});
	slayer.tileSize = new OpenLayers.Size(250,250);
	slayer.getURL = geographURL;

	map.addLayers([oslayer,glayer,rlayer,player,slayer]);

	ll = new OpenLayers.LonLat(lon, lat);
	map.setCenter(ll, {/literal}{$z}{literal});

	// Disable the scroll wheel - zooming breaks the map
	var mousecontrol = new OpenLayers.Control.MouseDefaults();
	mousecontrol.onWheelEvent = mousecontrol.defaultDblClick = function (){ };
	mousecontrol.defaultClick = mouseDefaultClick;
	mousecontrol.defaultMouseDown = mouseDefaultMouseDown;
	map.addControl( mousecontrol );

	var panzoom = new OpenLayers.Control.PanZoomBar();
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
<div style="float:left; font-size:0.9em; color:gray;">Getting 'Quota Exceeded'? <a href="/mapper/captcha.php?token={$token}" style="color:gray;" target="_top">refresh your quota</a></div>{/if}{/dynamic}

<div style="text-align:right; width:660px; font-size:0.7em;margin-bottom:3px" class="nowrap">Change Overlay Opacity: [<a title="increase opacity" href="javascript: changeOpacity(0.1);">+</a>] [<a title="decrease opacity" href="javascript: changeOpacity(-0.1);">-</a>], [<a href="javascript: fadeBackground();">Toggle background</a>]</div>

<div id="mapcontainer">
	<div style="position:absolute">
		<div id="mapbox" style="background-color:lightgrey"></div>

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
	Click map to view photo(s) in <input type="radio" name="clickto" {if !$centi}checked{/if} id="clickto"/>Grid Square / <input type="radio" {if $centi}checked{/if} name="clickto"/>CentiSquare
</div>

<div class="interestBox" style="padding:0px; margin-top:6px">
<h3 style="text-align:center;">Draggable Geograph Map of Great Britain <sup>(beta)</sup></h3>
</div>

<div class="copyright">&middot; Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey.<br/> All Rights Reserved. Educational licence 100045616 &middot;<br/>
&middot; Photographs and coverages are available under a separate <a href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a> &middot;<br/>
&middot; {external href="http://scenic.mysociety.org/faq" text="ScenicOrNot"} dataset &copy; mySociety and licenced for reuse under this <a href="http://creativecommons.org/licenses/by-nc/3.0/" class="nowrap">Creative Commons Licence</a> &middot;<br/><br/>
For the purposes of the Creative Commons Licence this page should be considered a "Collective Work", and as such is not available in its entirety for reuse.
</div>

</body>
</html>

