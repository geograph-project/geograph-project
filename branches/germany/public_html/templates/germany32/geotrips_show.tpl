{if $trip.title}
{assign var="triptitle" value="`$trip.title`"}
{else}
{assign var="triptitle" value="`$trip.location` from `$trip.start`"}
{/if}
{assign var="page_title" value="`$triptitle` :: Geo-Trips"}
{assign var="meta_description" value="A `$trip.nicetype` near `$trip.location`, starting from `$trip.start`, with pictures and plotted on a map."}
{assign var="extra_css" value="/geotrips/geotrips.css"}
{assign var="olayersmap" value="1"}
{include file="_std_begin.tpl"}

{if $google_maps_api_key}
<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.6&amp;sensor=false&amp;key={$google_maps_api_key}"></script>
{/if}
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>

{literal}
<script type="text/javascript">
//<![CDATA[
	var map;
	var trkLayer,trk,trkFeature,trkString;                             // track
	var vdir,vdirFeature,vdirString;                                   // view directions
	var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
	var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
{/literal}
	var lat0 = {$trip.latcen};
	var lon0 = {$trip.loncen};
	var lonmin = {$lonmin};
	var lonmax = {$lonmax};
	var latmin = {$latmin};
	var latmax = {$latmax};
	var triplatmin = {$trip.latmin};
	var triplonmin = {$trip.lonmin};
	var triplatmax = {$trip.latmax};
	var triplonmax = {$trip.lonmax};
{literal}
	function initmap() {
		initOL();
		initIconLayer();
		trkLayer = new OpenLayers.Layer.Vector(
			"Lines",
			{
				isBaseLayer: false,
				displayInLayerSwitcher: false
			}
		);
		var point1 = new OpenLayers.Geometry.Point(lonmin, latmin);
		var point2 = new OpenLayers.Geometry.Point(lonmax, latmax);
		point1.transform(epsg4326, epsg900913);
		point2.transform(epsg4326, epsg900913);

		var bounds = new OpenLayers.Bounds();
		bounds.extend(point1);
		bounds.extend(point2);

		point1 = new OpenLayers.Geometry.Point(triplonmin, triplatmin);
		point2 = new OpenLayers.Geometry.Point(triplonmax, triplatmax);
		point1.transform(epsg4326, epsg900913);
		point2.transform(epsg4326, epsg900913);

		var tripbounds = new OpenLayers.Bounds();
		tripbounds.extend(point1);
		tripbounds.extend(point2);

		var layerswitcher = new OpenLayers.Control.LayerSwitcher({'ascending':false});

		map = new OpenLayers.Map({
			div: "map",
			projection: epsg900913,
			displayProjection: epsg4326,
			units: "m",
			numZoomLevels: 18,
			restrictedExtent: bounds,
			controls : [
				new OpenLayers.Control.Navigation(),
				new OpenLayers.Control.PanZoomBar(),
				layerswitcher,
				new OpenLayers.Control.ScaleLine({ 'geodesic' : true }),
				new OpenLayers.Control.Attribution()
			]
		});
{/literal}
{if $google_maps_api_key}
{literal}
		var gphy = new OpenLayers.Layer.Google(
			"Google Physical",
			{type: google.maps.MapTypeId.TERRAIN, numZoomLevels: 16}
		);

		var gmap = new OpenLayers.Layer.Google(
			"Google Streets",
			{numZoomLevels: 20}
		);

		var ghyb = new OpenLayers.Layer.Google(
			"Google Hybrid",
			{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20}
		);

		var gsat = new OpenLayers.Layer.Google(
			"Google Satellite",
			{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
		);

		gphy.hasHills = true;
		gsat.hasHills = true;
		ghyb.hasHills = true;
		gphy.gmaxz = gphy.numZoomLevels-1;
		gmap.gmaxz = gmap.numZoomLevels-1;
		gsat.gmaxz = gsat.numZoomLevels-1;
		ghyb.gmaxz = ghyb.numZoomLevels-1;
{/literal}
{/if}
{literal}
		var geogr = new OpenLayers.Layer.XYrZ(
			"Geograph: Grid",
			"/tile.php?x=${x}&y=${y}&Z=${z}&l=8&o=1",
			4, 14, OpenLayers.Util.Geograph.MISSING_TILE_URL,
			{
				attribution: '&copy; <a href="/">Geograph</a> (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
				sphericalMercator : true,
				isBaseLayer : false,
				visibility : false
			}
		);
		var mapnik = new OpenLayers.Layer.XYrZ(
			"Mapnik (Static + OSM)",
			"/tile/osm/${z}/${x}/${y}.png",
			0, 18, OpenLayers.Util.Geograph.MISSING_TILE_URL_BLUE /*FIXME*/,
			{
				attribution: '&copy; <a href="http://www.openstreetmap.org/">OSM</a> contributors (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
				sphericalMercator : true
			},
			16, "http://tile.openstreetmap.org/${z}/${x}/${y}.png"
		);
		var osmmapnik = new OpenLayers.Layer.OSM(
			null,
			null,
			{ numZoomLevels: 19 }
		);
		var hills = new OpenLayers.Layer.XYrZ(
			"Relief",
			"/tile/hills/${z}/${x}/${y}.png",
			4, 15, OpenLayers.Util.Geograph.MISSING_TILE_URL,
			{
				attribution: 'Relief: <a href="http://srtm.csi.cgiar.org/">CIAT data</a>',
				sphericalMercator : true,
				isBaseLayer : false,
				visibility : false
			}
		);
		hills.savedVisibility = false;
		var topobase = new OpenLayers.Layer.XYrZ(
			"Nop's Wanderreitkarte",
			//[ "http://base.wanderreitkarte.de/base/${z}/${x}/${y}.png", "http://base2.wanderreitkarte.de/base/${z}/${x}/${y}.png"],
			[ "http://topo.wanderreitkarte.de/topo/${z}/${x}/${y}.png", "http://topo2.wanderreitkarte.de/topo/${z}/${x}/${y}.png"], // topo3, topo4
			4, 16, OpenLayers.Util.Geograph.MISSING_TILE_URL,
			{
				attribution: '&copy; <a href="http://www.wanderreitkarte.de/">Nop\'s Wanderreitkarte</a> (<a href="http://www.wanderreitkarte.de/licence_en.php">CC, CIAT</a>)',
				sphericalMercator : true,
				isBaseLayer : true
			}
		);
		topobase.hasHills = true;
		var cycle = new OpenLayers.Layer.OSM(
			"Cycle Map",
			"http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
			{
				attribution: '&copy; <a href="http://opencyclemap.org/">OpenCycleMap</a> (<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
				numZoomLevels: 19
			}
		);
		cycle.hasHills = true;

		mapnik.gmaxz = mapnik.maxZoomLevel;//mapnik.numZoomLevels-1;
		osmmapnik.gmaxz = osmmapnik.numZoomLevels-1;
		topobase.gmaxz = topobase.maxZoomLevel;
		cycle.gmaxz = cycle.numZoomLevels-1;

		map.events.register("changebaselayer", map, function(e) {
			var redrawlayerswitcher = false;
			/* Don't show relief if already shown in base layer */
			if (('hasHills' in e.layer) && e.layer.hasHills) {
				if (!map.hillBase) {
					hills.savedVisibility = hills.getVisibility();
					hills.setVisibility(false);
					hills.displayInLayerSwitcher = false;
					redrawlayerswitcher = true;
					map.hillBase = true;
				}
			} else if (map.hillBase) {
				if (hills.savedVisibility)
					hills.setVisibility(true);
				hills.displayInLayerSwitcher = true;
				redrawlayerswitcher = true;
				map.hillBase = false;
			}
			if (redrawlayerswitcher) {
				layerswitcher.layerStates = [];
				layerswitcher.redraw();
			}
			if (e.layer instanceof OpenLayers.Layer.XYrZ) {
				var z = map.zoom;
				if (z > e.layer.maxZoomLevel)
					map.setCenter(map.center, e.layer.maxZoomLevel);
				else if (z < e.layer.minZoomLevel)
					map.setCenter(map.center, e.layer.minZoomLevel);
			}
		});
		map.events.register("zoomend", map, function(e) {
			if (map.baseLayer instanceof OpenLayers.Layer.XYrZ) {
				var z = map.zoom;
				if (z > map.baseLayer.maxZoomLevel)
					map.setCenter(map.center, map.baseLayer.maxZoomLevel);
				else if (z < map.baseLayer.minZoomLevel)
					map.setCenter(map.center, map.baseLayer.minZoomLevel);
			}
		});

		map.addLayers([
			mapnik,
			osmmapnik,
			topobase,
			cycle,
{/literal}
{if $google_maps_api_key}
{literal}
			gphy, gmap, gsat, ghyb,
{/literal}
{/if}
{literal}
			hills,
			geogr,
			trkLayer,
			markers
		]);

		var overview =  new OpenLayers.Control.OverviewMap({
			//maximized: true
		});
		map.addControl(overview);
		var point = new OpenLayers.LonLat(lon0, lat0);
		var mt = mapnik; //FIXME
		var mtHasHills = ('hasHills' in mt) && mt.hasHills;
		map.hillBase = mtHasHills;
		hills.setOpacity(0.75);//FIXME
		map.setBaseLayer(mt);
		map.setCenter(point.transform(epsg4326, map.getProjectionObject())/*, iniz*/);
		map.zoomToExtent(tripbounds);

{/literal}
{if $trip.track}
		// Define track
		trk=new Array();
		{foreach item=trkpt from=$trip.track}
			trk.push((new OpenLayers.Geometry.Point({$trkpt.1},{$trkpt.0})).transform(epsg4326, map.getProjectionObject()));
		{/foreach}
		trkString=new OpenLayers.Geometry.LineString(trk);
		trkFeature=new OpenLayers.Feature.Vector(trkString,null,style_trk);
		trkLayer.addFeatures([trkFeature]);
{/if}
		var pos, content, icon, thumburl, thumbwidth, thumbheight;
		var size=new OpenLayers.Size(9,9);
		var offset=new OpenLayers.Pixel(-4,-9);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre //FIXME
	{foreach item=image from=$images}
			// Define camera marker
			pos=new OpenLayers.LonLat({$image.viewpoint.1},{$image.viewpoint.0});
			thumburl = '{$image.gridimage->getThumbnail(213,160,3)|escape:"javascript"}';
			thumbwidth = {$image.gridimage->last_width};
			thumbheight = {$image.gridimage->last_height};
			content = makeHtml(
				'/photo/{$image.gridimage_id}',
				'{$image.realname|escape:"html"|escape:"javascript"}',
				'{$image.grid_reference|escape:"html"|escape:"javascript"}',
				'{$image.title|escape:"html"|escape:"javascript"}',
				'{$image.title2|escape:"html"|escape:"javascript"}',
				'{$image.comment|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				'{$image.comment2|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				thumburl, thumbwidth, thumbheight
			);
			icon=new OpenLayers.Icon('walk.png',size,offset,null);
			addPopupMarker(pos, GeoPopup, content, true, true, icon);
			// Define view direction
			vdir=new Array();
			pos=new OpenLayers.Geometry.Point({$image.viewpoint.1},{$image.viewpoint.0});
			pos.transform(epsg4326, map.getProjectionObject());
			vdir.push(pos);
			pos=new OpenLayers.Geometry.Point({$image.subject.1},{$image.subject.0});
			pos.transform(epsg4326, map.getProjectionObject());
			vdir.push(pos);
			vdirString=new OpenLayers.Geometry.LineString(vdir);
			vdirFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
			trkLayer.addFeatures([vdirFeature]);
	{/foreach}
{literal}

	}

	function makeHtml(photourl, realname, gridref, title1, title2, comment1, comment2, thumburl, thumbwidth, thumbheight) {
		var title = title2 === '' ? title1 : (title1 === '' ? title2 : title1 + ' (' + title2 + ')');
		title = gridref + ' : ' + title;
		var comment = comment2 === '' ? comment1 : (comment1 === '' ? comment2 : comment1 + '</p><hr style="width:3em"/><p style="font-weight:bold">' + comment2);
		var html = '<h4 style="font-family:Arial,sans-serif;font-weight:bold;font-size:medium">'+title+'</h4>';
		html += '<div style="font-family:Arial,sans-serif;text-align:center;font-size:small">';
		html += '<p><a href="'+photourl+'" target="_blank"><img src="'+thumburl+'" width="'+thumbwidth+'" height="'+thumbheight+'" alt="'+title+'"/></a></p>';
		if (comment !== '') {
			html += '<p style="font-weight:bold">' + comment + '</p>';
		}
		html += '<p>&copy; Copyright <i>'+realname+'</i> and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a></p>';
		html += '<p><a href="'+photourl+'" target="_blank">View photo page</a></p>';
		html += '</div>';
		return html;
	}

AttachEvent(window,'load',initmap,false);

//]]>
</script>
{/literal}

<h2><a href="./">Geo-Trips</a> :: {$triptitle|escape:"html"}</h2>

<div class="panel maxi">
	<h3>{$trip.location|escape:"html"}</h3>
	<h4>A {$trip.nicetype|escape:"html"} from {$trip.start|escape:"html"}</h4>
	<h4>{$trip.date|date_format:"%A, %e %B %Y"}</h4>{*FIXME H4?*}
	<h4>by <a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}</a></h4>{*FIXME H4?*}
	<p style="text-align:center">{*FIXME title2*}
	{foreach item=idx from=$selectedimages}<a href="/photo/{$images.$idx.gridimage_id}" title="{$images.$idx.title|escape:"html"}"
	><img alt="{$images.$idx.title|escape:"html"}" class="inner" src="{$images.$idx.gridimage->getThumbnail(213,160,true)}" /></a>&nbsp;{/foreach}
	</p>

{if $trip.contfrom || $trip.nextpart}
	<table class="ruled" style="margin:auto"></tr>
	{if $trip.contfrom}<td class="hlt" style="width:120px;text-align:center"><a href="geotrip_show.php?trip={$trip.contfrom}">preceding leg</a></td>{else}<td></td>{/if}
	<td style="margin:20px;text-align:center"><b>This trip is part of a series.</b></td>
	{if $trip.nextpart}<td class="hlt" style="width:120px;text-align:center"><a href="geotrip_show.php?trip={$trip.nextpart}">next leg</a></td>{else}<td></td>{/if}
	</tr></table>
{/if}

	<p>{$trip.descr|escape:"html"|nl2br|geographlinks}</p>
	<div class="inner flt_r">[<a href="/geotrips/">overview map</a>]</div>
	<div><p><small>
{if $trip.track}
		On the map below, the grey line is the GPS track from this trip.
{/if}
		Click the blue circles to see a photograph
		taken from that spot and read further information about the location.  The blue lines indicate
		the direction of view.  There is also a
		<a href="/search.php?i={$trip.search}&amp;displayclass=slide">slideshow</a>
		<img alt="external link" title="" src="/img/external.png" /> of this trip.
	</small></p></div>
	<div class="row"></div>
	<div id="map" class="inner" style="width:798px;height:650px"></div>
	<p style="font-size:.65em">
		All images &copy;
		<a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}</a
		>{foreach item=realname from=$realnames},
		<a href="/profile/{$trip.uid}?a={$realname|escape:"url"}">{$realname|escape:"html"}</a>
		{/foreach}
		and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a>
		<img alt="external link" title="" src="http://geo.hlipp.de/img/external.png" />.
	</p>
</div>

{include file="_std_end.tpl"}
