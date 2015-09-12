{assign var="page_title" value="Übersichtskarte :: Geo-Trips"}
{assign var="meta_description" value="Eine Zusammenstellung von Touren von Teilnehmern des Geograph-Projekts mit Fotos, Beschreibungen und GPS-Tracks, dargestellt auf einer Landkarte."}
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
			"Mapnik (Statisch + OSM)",
			"/tile/osm/${z}/${x}/${y}.png",
			0, 18, OpenLayers.Util.Geograph.MISSING_TILE_URL_BLUE /*FIXME*/,
			{
				attribution: '&copy; <a href="http://www.openstreetmap.org/">OSM</a>-User (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
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
			{assign var="triptitle" value="`$trip.location` vom Ausgangspunkt `$trip.start`"}
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
				'{$trip.date|date_format:"%A, %e. %B %Y"}',
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
		html += '<p><b>'+triploc+' &ndash; '+triptype+' vom Ausgangspunkt '+tripstart+' von <a href="'+profileurl+'">'+realname+'</a><br /> '+tripdate+'</b></p>';
		html += '<p>Anklicken des Fotos zeigt Tour-Details.</p>';
		html += '</div>';
		return html;
	}

AttachEvent(window,'load',initmap,false);

//]]>
</script>
{/literal}

<h2>Geo-Trips-Übersichtskarte</h2>

<div class="panel maxi" style="max-width:800px">
	<p>
		Die untenstehende Karte zeigt von den Teilnehmern des <a href="/">Geograph-Projekts</a> hochgeladene Geo-Trips.
		Jeder Kreis auf der Karte steht für eine Tagestour, die ein Geographer unternommen hat, um die auf
		topographischen Karten verzeichneten Planquadrate des UTM-Gitters fotografisch zu dokumentieren.
		Das Geograph-Projekt verfolgt das Ziel, für jedes dieser Planquadrate Fotos und Informationen zu sammeln.
	</p><p><small>
		Die Karte kann durch Anklicken der Pfeile oder durch Mausbewegung bei gedrückter linker Maustaste verschoben werden.
		Zoomen kann man durch Anklicken der "+"/"-"-Symbole auf der linken Seite, durch Doppelklick oder mit Hilfe des
		Scrollrads der Maus. Anklicken der "+"-Symbole auf der rechten Seite öffnet eine Auswahl alternativer Karten oder
		eine Übersichtskarte.
		Klickt man auf einen der Kreise, so öffnet sich ein Fenster, das Details und ein Foto des zugehörigen Geo-Trips enthält.
		Anklicken dieses Fotos öffnet eine detaillierte Beschreibung der Tour sowie eine Landkarte, auf der Fotos und weitere
		Informationen eingetragen sind.
	</small></p>
{dynamic}{if $user->registered}
	<p class="inner hlt">
		Teilnehmer des <em>Geograph</em>-Projekts können eigene Touren (zu Fuß, auf dem Rad, im Auto
		oder auf andere Weise unternommen) über das <a href="geotrip_submit.php">Geo-Trip-Einreichformular</a>
		hochladen. Dieses erlaubt es auch, GPS-Tracks im GPX-Format hochzuladen.
		Bereits hochgeladene Touren können auch <a href="geotrip_edit.php">nachträglich geändert</a> werden.
	</p>
{/if}{/dynamic}
	<table class="ruled"><tr>
		<td><b>Legende:</b></td>
		<td><img src="walk.png" alt="" title="Abb.: Wanderungs-Symbol" /> Zu Fuß</td><td></td>
		<td><img src="bike.png" alt="" title="Abb.: Fahrrad-Symbol" /> Fahrrad</td><td></td>
		<td><img src="boat.png" alt="" title="Abb.: Boot-Symbol" /> Boot</td><td></td>
		<td><img src="rail.png" alt="" title="Abb.: Zug-Symbol" /> Zug</td><td></td>
		<td><img src="road.png" alt="" title="Abb.: Straßen-Symbol" /> Auto</td><td></td>
		<td><img src="bus.png"  alt="" title="Abb.: Bus-Symbol" /> Öffentliche Verkehrsmittel</td>
	</tr></table>
	<div id="map" class="inner" style="width:798px;height:650px"></div>
	<table class="ruled"><tr>
		<td><b>Legende:</b></td>
		<td><img src="walk.png" alt="" title="Abb.: Wanderungs-Symbol" /> Zu Fuß</td><td></td>
		<td><img src="bike.png" alt="" title="Abb.: Fahrrad-Symbol" /> Fahrrad</td><td></td>
		<td><img src="boat.png" alt="" title="Abb.: Boot-Symbol" /> Boot</td><td></td>
		<td><img src="rail.png" alt="" title="Abb.: Zug-Symbol" /> Zug</td><td></td>
		<td><img src="road.png" alt="" title="Abb.: Straßen-Symbol" /> Auto</td><td></td>
		<td><img src="bus.png"  alt="" title="Abb.: Bus-Symbol" /> Öffentliche Verkehrsmittel</td>
	</tr></table>
</div>

<div class="panel maxi">
	<h3>Kürzlich hochgeladene Geo-Trips</h3>
	<p>
		{*FIXME
		There is a <a href="/content/?scope[]=trip">full list of Geo-Trips</a> (updated once daily
		in the early morning) in the Collections area of Geograph, which can be filtered by keyword or author.  You can also
		subscribe to an
		<a href="/content/syndicator.php?scope[]=trip">RSS feed</a> with new Geo-Trips as they
		come in. *}
{if $alltrips}
		Die untenstehende Liste zeigt alle bisher hochgeladenen Touren. {if $max < 0}Siehe auch die <a href="./">Liste aktueller Touren</a>, die häufiger aktualisiert wird.{/if}
{else}
		Die untenstehende Liste enthält alle in den letzten {if $days==1}24 Stunden{else}{$days} Tagen{/if} hochgeladenen Touren. Siehe auch die <a href="?max=-1">Liste aller Touren</a>.
{/if}
	</p>

{foreach item=trip from=$trips}{if $trip.visible}
	{if $trip.title}
		{assign var="triptitle" value="`$trip.title`"}
	{else}
		{assign var="triptitle" value="`$trip.location` vom Ausgangspunkt `$trip.start`"}
	{/if}
	<div class="inner">
		<div class="inner flt_r" style="max-width:213px">
			<img src="{$trip.gridimage->getThumbnail(213,160,true)}" alt="" title="{$triptitle|escape:"html"}" />
			<br />
			<span style="font-size:0.6em">Bild &copy;
				<a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}{*FIXME realname*}</a>
				und lizenziert unter einer <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative-Commons-Lizenz</a><img alt="external link" title="" src="/img/external.png" />
			</span>
		</div>
		<b>{$triptitle|escape:"html"}</b><br />
		<i>{$trip.location|escape:"html"}</i> &ndash; {$trip.nicetype|escape:"html"} vom Ausgangspunkt {$trip.start|escape:"html"}<br />
		von <a href="/profile/{$trip.uid}">{$trip.user|escape:"html"}</a>
		<div class="inner flt_r">{$trip.grid_reference}</div>
		<p title="{$trip.descr|escape:'html'}">
			{$trip.descr|escape:'html'|nl2br|truncate:500:"... (<u>more</u>)"|geographlinks}&nbsp;[<a href="geotrip_show.php?trip={$trip.id}">Details</a>]
			{*FIXME edit if moderator or owner*}
		</p>
		<div class="row"></div>
	</div>
{/if}{/foreach}

</div>

{include file="_std_end.tpl"}
