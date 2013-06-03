{assign var="page_title" value="Overview map :: Geo-Trips"}
{assign var="meta_description" value="A collection of square-bagging trips by members of the Geograph project, with photographs, descriptions and GPS tracks plotted on an Ordnance Survey map."}
{assign var="extra_css" value="/geotrips/geotrips.css"}
{assign var="olayersmap" value="1"}
{include file="_std_begin.tpl"}

  <!--RSS feed via Geograph-->
  <!--link rel="alternate" type="application/rss+xml" title="Geo-Trips RSS" href="/content/syndicator.php?scope[]=trip" /-->{* FIXME *}

{*if $google_maps_api_key}
<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.6&amp;sensor=false&amp;key={$google_maps_api_key}"></script>
{/if*}
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>

{literal}
<script type="text/javascript">
//<![CDATA[
	var map;
	var trkLayer,trk,trkFeature,trkString;
	var cont,contFeature,contString;
	var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
{/literal}
	var lat0 = {$lat0};
	var lon0 = {$lon0};
	var iniz = 6;//FIXME
	var lonmin = {$lonmin};
	var lonmax = {$lonmax};
	var latmin = {$latmin};
	var latmax = {$latmax};
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

		map.addLayers([
			mapnik,
			osmmapnik,
			trkLayer,
			markers
		]);

		var overview =  new OpenLayers.Control.OverviewMap({
			//maximized: true
		});
		map.addControl(overview);
		var point = new OpenLayers.LonLat(lon0, lat0);
		var mt = mapnik;
		map.setBaseLayer(mt);
		map.setCenter(point.transform(epsg4326, map.getProjectionObject()), iniz);

		var pos, content, icon, thumburl, thumbwidth, thumbheight;
		var size=new OpenLayers.Size(15,15);
		var offset=new OpenLayers.Pixel(-7,-7);
{/literal}
	{foreach item=trip from=$trips}
		{if $trip.title}
			{assign var="triptitle" value="`$trip.title`"}
		{else}
			{assign var="triptitle" value="`$trip.location` from `$trip.start`"}
		{/if}
			// Define marker
			pos=new OpenLayers.LonLat({$trip.latlon.1},{$trip.latlon.0});
			thumburl = '{$trip.gridimage->getThumbnail(213,160,3)|escape:"javascript"}';
			thumbwidth = {$trip.gridimage->last_width};
			thumbheight = {$trip.gridimage->last_height};
			content = makeHtml(
				'geotrip_show.php?trip={$trip.id}',
				'{$trip.user|escape:"html"|escape:"javascript"}',
				'/profile/{$trip.uid}',
				'{$triptitle|escape:"html"|escape:"javascript"}',
				'{$trip.location|escape:"html"|escape:"javascript"}',
				'{$trip.date|date_format:"%A, %e %B %Y"}',
				'{$trip.nicetype|escape:"html"|escape:"javascript"}',
				'{$trip.start|escape:"html"|escape:"javascript"}',
				thumburl, thumbwidth, thumbheight
			);
			icon=new OpenLayers.Icon('{$trip.type}.png',size,offset,null);
			addPopupMarker(pos, GeoPopup, content, true, true, icon);
		{if $trip.contfrom}
			// Link multi-day trips
			cont=new Array();
			pos=new OpenLayers.Geometry.Point({$trip.prevlatlon.1},{$trip.prevlatlon.0});
			pos.transform(epsg4326, map.getProjectionObject());
			cont.push(pos);
			pos=new OpenLayers.Geometry.Point({$trip.latlon.1},{$trip.latlon.0});
			pos.transform(epsg4326, map.getProjectionObject());
			cont.push(pos);
			contString=new OpenLayers.Geometry.LineString(cont);
			contFeature=new OpenLayers.Feature.Vector(contString,null,style_trk);
			trkLayer.addFeatures([contFeature]);
		{/if}
	{/foreach}
{literal}

	}

	function makeHtml(tripurl, realname, profileurl, title, triploc, tripdate, triptype, tripstart, thumburl, thumbwidth, thumbheight) {
		var html = '<h4 style="font-family:Arial,sans-serif;font-weight:bold;font-size:medium">'+title+'</h4>';
		html += '<div style="font-family:Arial,sans-serif;text-align:center;font-size:small">';
		html += '<p><a href="'+tripurl+'"  target="_blank"><img src="'+thumburl+'" width="'+thumbwidth+'" height="'+thumbheight+'" alt="'+triploc+'" /></a></p>';
		html += '<p><b>'+triploc+' &ndash; A '+triptype+' from '+tripstart+' by <a href="'+profileurl+'">'+realname+'</a><br /> '+tripdate+'</b></p>';
		html += '<p>Click image to see details of this trip.</p>';
		html += '</div>';
		return html;
	}

AttachEvent(window,'load',initmap,false);

//]]>
</script>
{/literal}

<h2>Geo-Trips overview map</h2>

<div class="panel maxi" style="max-width:800px">
	<p>
		The map below shows Geo-Trips submitted by members of the <a href="/">Geograph</a>
		project.  Each point on the map represents a day trip by one Geograph-er to cover a number of
		grid squares of the UTM Grid as shown on topographical maps.  The Geograph project aims
		to collect photographs and information for each grid square.
	</p><p>
		Pan around the map using the left mouse button, or use the arrows in the top left corner of the map.
		The +/- buttons on the map allow you to zoom in or out.  Double click zooms in on the spot.
		Each Geo-Trip is marked on the map by a round
		symbol.  Clicking the symbol gives details in a pop-up, and clicking the thumbnail in the pop-up
		takes you to the map page for the trip, with all the pictures and information shown on the map.
	</p>
{dynamic}{if $user->registered}
	<p class="inner hlt">
		If you are a <em>Geograph</em>-er and would like to put your own square-bagging
		expeditions on the map - on foot, by bike, in a car or by any other mode of transport -
		please use the <a href="geotrip_submit.php">Geo-Trip submission form</a>.
		If you upload a GPS track log in GPX format, the track will also be shown.
		You can also <a href="geotrip_edit.php">edit your existing Geo-Trips</a>.
	</p>
{/if}{/dynamic}
	<table class="ruled"><tr>
		<td><b>Legend:</b></td>
		<td><img src="walk.png" alt="" title="Fig.: Walk symbol" /> Walk</td><td></td>
		<td><img src="bike.png" alt="" title="Fig.: Bike symbol" /> Cycle ride</td><td></td>
		<td><img src="boat.png" alt="" title="Fig.: Boat symbol" /> Boat trip</td><td></td>
		<td><img src="rail.png" alt="" title="Fig.: Rail symbol" /> Train ride</td><td></td>
		<td><img src="road.png" alt="" title="Fig.: Road symbol" /> Drive</td><td></td>
		<td><img src="bus.png"  alt="" title="Fig.: Bus symbol" />  Scheduled public transport</td>
	</tr></table>
	<div id="map" class="inner" style="width:798px;height:650px"></div>
	<table class="ruled"><tr>
		<td><b>Legend:</b></td>
		<td><img src="walk.png" alt="" title="Fig.: Walk symbol" /> Walk</td><td></td>
		<td><img src="bike.png" alt="" title="Fig.: Bike symbol" /> Cycle ride</td><td></td>
		<td><img src="boat.png" alt="" title="Fig.: Boat symbol" /> Boat trip</td><td></td>
		<td><img src="rail.png" alt="" title="Fig.: Rail symbol" /> Train ride</td><td></td>
		<td><img src="road.png" alt="" title="Fig.: Road symbol" /> Drive</td><td></td>
		<td><img src="bus.png"  alt="" title="Fig.: Bus symbol" />  Scheduled public transport</td>
	</tr></table>
	<p>
		In the spirit if not the scope of Geo-Trips, here's <b>Thomas Nugent</b>'s
		<a href="http://www.geograph.org.uk/article/Luton-to-Glasgow-in-50-minutes">flight from Luton to Glasgow</a>,
		plotted on a Google Map, with tips for other flying Geograph-ers.
	</p>
</div>

<div class="panel maxi">
	<h3>Recently uploaded Geo-Trips</h3>
	<p>
		{*FIXME
		There is a <a href="/content/?scope[]=trip">full list of Geo-Trips</a> (updated once daily
		in the early morning) in the Collections area of Geograph, which can be filtered by keyword or author.  You can also
		subscribe to an
		<a href="/content/syndicator.php?scope[]=trip">RSS feed</a> with new Geo-Trips as they
		come in. *}
{if $alltrips}
		The list below shows all trips uploaded so far. {if $max < 0}See also the <a href="./">list of recent trips</a> which is updated more frequently.{/if}
{else}
		The list below includes all trips uploaded in the last {if $days==1}24 hours{else}{$days} days{/if}. See also the <a href="?max=-1">list of all trips</a>.
{/if}
	</p>

{foreach item=trip from=$trips}{if $trip.visible}
	{if $trip.title}
		{assign var="triptitle" value="`$trip.title`"}
	{else}
		{assign var="triptitle" value="`$trip.location` from `$trip.start`"}
	{/if}
	<div class="inner">
		<div class="inner flt_r" style="max-width:213px">
			<img src="{$trip.gridimage->getThumbnail(213,160,true)}" alt="" title="{$triptitle|escape:"html"}" />
			<br />
			<span style="font-size:0.6em">Image &copy;
				<a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}{*FIXME realname*}</a>
				and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a><img alt="external link" title="" src="/img/external.png" />
			</span>
		</div>
		<b>{$triptitle|escape:"html"}</b><br />
		<i>{$trip.location|escape:"html"}</i> &ndash; A {$trip.nicetype|escape:"html"} from {$trip.start|escape:"html"}<br />
		by <a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}</a>
		<div class="inner flt_r">{$trip.grid_reference}</div>
		<p title="{$trip.descr|escape:'html'}">
			{$trip.descr|escape:'html'|nl2br|truncate:500:"... (<u>more</u>)"|geographlinks}&nbsp;[<a href="geotrip_show.php?trip={$trip.id}">show</a>]
			{*FIXME edit if moderator or owner*}
		</p>
		<div class="row"></div>
	</div>
{/if}{/foreach}

</div>

{include file="_std_end.tpl"}
