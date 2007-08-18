{assign var="page_title" value="Geograph :: Great Britain Map"}
{include file="_std_begin.tpl"}
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/mapper.v{$javascript_version}.css" media="screen" />

<script src="http://s0.{$http_host}/mapper/OpenLayers.js" type="text/javascript"></script>
<script src="http://s0.{$http_host}/mapper/geotools2.js"></script>
<script src="http://s0.{$http_host}/mapper/mapper.v{$javascript_version}.js"></script>

<script type="text/javascript">

var lon = {$e};
var lat = {$n};
var tileurl = "http://{$http_host}/tile.php";

var zoom = 0;
var map, osposition, ml;

var maxOpacity = 0.9;
var minOpacity = 0.1;

var glayer;

{literal}

function changeOpacity(byOpacity) {
	var newOpacity = (parseFloat(glayer.opacity) + byOpacity).toFixed(1);
	newOpacity = Math.min(maxOpacity, Math.max(minOpacity, newOpacity));
	glayer.setOpacity(newOpacity);
}

function loadMap() {
	map = new OpenLayers.Map('mapbox', {controls:[], maxExtent: new OpenLayers.Bounds(0, 0, 700000, 1300000), maxResolution: 4000/250, units: 'meters', projection: "EPSG:27700"});
	
	var oslayer = new OpenLayers.Layer.WMS("OSGB Landranger", tileurl+"?l=o", {}, {projection: "EPSG:27700", buffer:0});
	oslayer.tileSize = new OpenLayers.Size(250,250);
	oslayer.getURL = geographURL;
	
	glayer = new OpenLayers.Layer.WMS("Geograph Coverage", tileurl+"?l=g", {transparent: 'true'}, {projection: "EPSG:27700", isBaseLayer:false, opacity: 0.3, buffer:0});	
	glayer.tileSize = new OpenLayers.Size(250,250);	
	glayer.getURL = geographURL;
	
	map.addLayers([oslayer,glayer]);
	
	ll = new OpenLayers.LonLat(lon, lat);
	map.setCenter(ll, 0);

	// Disable the scroll wheel - zooming breaks the map
	var mousecontrol = new OpenLayers.Control.MouseDefaults();
	mousecontrol.onWheelEvent = mousecontrol.defaultDblClick = function (){ };
	mousecontrol.defaultClick = mouseDefaultClick;
	map.addControl( mousecontrol );
	
	var panzoom = new OpenLayers.Control.PanZoom();
	panzoom.draw = drawNoZoom;
	map.addControl( panzoom );
	
	osposition = new OpenLayers.Control.MousePosition({element: document.getElementById('maplocation'), numdigits: 4, update: 1});
	osposition.redraw = showGridRef;
	map.addControl( osposition );
	
	switcher = new OpenLayers.Control.LayerSwitcher();
	map.addControl( switcher );
}

AttachEvent(window,'load',loadMap,false);

</script>{/literal}

<div style="float:right; position:relative"><input type="text" length="8" id="coordin" /><input type="button" onclick="parseLocation()" value="Go" /></div>

<h3>Draggable Geograph Map of Great Britain</h3>

<div id="mapcontainer">
	<div style="position:absolute">
		<div id="mapbox"></div>

		<div id="mapfooter">
			&nbsp; &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616. 
			<span id="maplocation">loading...</span>
		</div>
	</div>
</div>
<div style="width:660px; text-align:center; font-size:0.9em;">Change Overlay Opacity: [<a title="increase opacity" href="javascript: changeOpacity(0.1);">+</a>] [<a title="decrease opacity" href="javascript: changeOpacity(-0.1);">-</a>]</div>

<br/><br/>

<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
Photographs and coverages are available under a seperate <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.<br/>
<br/>
For the purposes of the Creative Commons Licence this page should be considered a "Collective Work", and as such is not available in its entirety for reuse.
</div>

<br/><br/>
<div class="interestBox">If you are getting messages to "Login to continue viewing maps", then <a href="/mapper/captcha.php">click here</a></div>

{include file="_std_end.tpl"}
