{assign var="page_title" value="Geograph :: Great Britain Map"}
{include file="_std_begin.tpl"}
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/mapper.v{$javascript_version}.css" media="screen" />

<script src="/mapper/OpenLayers.v{$javascript_version}.js" type="text/javascript"></script>
<script src="/mapper/geotools2.v{$javascript_version}.js"></script>
<script src="/mapper/mapper.v{$javascript_version}.js"></script>

<script type="text/javascript">

var lon = {$e};
var lat = {$n};

var zoom = 0;
var map, osposition, ml;

{literal}

function loadMap() {
	map = new OpenLayers.Map('mapbox', {controls:[], maxExtent: new OpenLayers.Bounds(0, 0, 700000, 1300000), maxResolution: 2000/125, units: 'meters', projection: "EPSG:27700"});
	var mmlayer = new OpenLayers.Layer.WMS("Geograph GB", "http://geograph.mobile/tile.php", {}, {projection: "EPSG:27700"});
	
	mmlayer.tileSize = new OpenLayers.Size(125,125);
	
	mmlayer.getURL = geographURL;

	map.addLayer(mmlayer);

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
	
	osposition = new OpenLayers.Control.MousePosition({element: document.getElementById('maplocation'), numdigits: 3, update: 1});
	osposition.redraw = showGridRef;
	map.addControl( osposition );
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

<br/><br/>

<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
Photographs and coverages are available under a seperate <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.

</div>


{include file="_std_end.tpl"}
