{assign var="page_title" value="Events"}
{assign var="rss_url" value="/events/feed.rss"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<small style="color:red">Marker only shows grid square, see description for exact location<small><br/><br/></small></small>
	<div style="width:400px; height:320px;" id="mapCanvas"></div>
</div>

<h2>Geograph Events</h2>

<div style="float:right; padding-right:30px;"><a title="geoRSS Feed for Geograph Events" href="/events/feed.rss" class="xml-rss">RSS</a> <a title="KML Feed for Geograph Events" href="/events/kml-nl.php" class="xml-kml">KML</a></div>

{dynamic}
{if $user->registered}
	
<div class="interestBox" style="width:300px">
	<ul style="margin:0px;"><li><a href="/events/edit.php?id=new">Announce your own event</a></li></ul>

</div>

{/if}
{/dynamic}

<p>Use this section to find out about upcoming events, organised by Geograph members for Geograph members! Listed below are events, as well as their location plotted on a national map. If you are able to attend then let the organiser know by adding your name on the event page.</p>

<p>Use the RSS <small>({external href="http://en.wikipedia.org/wiki/RSS_(protocol)" text="Wikipedia article"})</small> or KML <small class="nowrap">(for {external href="http://earth.google.com/" text="Google Earth"} amongst others)</small> links above to keep track of new events in your favorite program.</p>

<p><small>If you are thinking of organising your own event (or want to attend a meet-up) then you may be interested in this {external href="http://www.nearby.org.uk/google/meet-me-at.php?group=1" text="meet-me-at map"} for finding possible areas of interest (this map also shows previous gatherings).</small></p>



<br style="clear:both"/>


{if $list}

<table class="report sortable" id="events">
<thead><tr>
	<td>Title &amp; more info</td>
	<td style="width:150px" sorted="asc">Date/Time</td>
	<td>Location</td>
	<td>Attendees</td>
	<td>Organiser</td>
</tr></thead>
<tbody>

{foreach from=$list item=item}
	{if $item.future == 1}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}" id="row{$item.geoevent_id}">
	{else}
		<tr style="color:gray; font-size:0.8em" id="row{$item.geoevent_id}">
	{/if}
		<td sortvalue="{$item.title|escape:"html"|default:'Untitled'}"><b><a href="/events/event.php?id={$item.geoevent_id}" title="{$item.description|escape:"html"|default:''}">{$item.title|escape:"html"|default:'Untitled'}</a></b></td>
		<td sortvalue="{$item.event_time}" class="nowrap"><b>{$item.event_time|date_format:"%a, %e %b %Y"}</b></td>
		<td sortvalue="{$item.grid_reference}"><a href="/location.php?gridref={$item.grid_reference}"><img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/></a> <a href="/gridref/{$item.grid_reference}">{$item.grid_reference}</a></td>
		{if $item.type == 'other'}
			<td>unknown</td>
		{else}
			<td>{$item.attendees}</td>
		{/if}
		<td sortvalue="{$item.realname|escape:"html"}"><a href="/profile/{$item.user_id}">{$item.realname|escape:"html"}</a></td>
		<td><a href="/events/event.php?id={$item.geoevent_id}" title="{$item.description|escape:"html"|default:''}">event page</a>
		{if $user->user_id == $item.user_id || $isadmin}
			<a href="/events/edit.php?id={$item.geoevent_id}">edit</a>
		{/if}
		</td>
		</tr>
	
{/foreach}
</tbody>
</table>
{else}
  <p>There are no listed events.</p>
{/if}


{dynamic}
{if $user->registered}
<br/><br/>	
<div class="interestBox">
	<ul style="margin:0px;"><li><a href="/events/edit.php?id=new">Announce your own event</a></li></ul>

</div>

{/if}
{/dynamic}


{if $future && $google_maps_api_key}
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	
	{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;

	function onLoad() {
		map = new GMap2(document.getElementById("mapCanvas"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl(true));
		{/literal}
		
		var bounds = new GLatLngBounds();
		
		{foreach from=$list item=item}
			{if $item.future == 1}
				bounds.extend(new GLatLng({$item.wgs84_lat}, {$item.wgs84_long}));
			{/if}
		{/foreach}
		{if $future == 1}
			//bounds doesnt seem to like one point via extends
			bounds.extend(new GLatLng({$item.wgs84_lat}+1, {$item.wgs84_long}+1));
			bounds.extend(new GLatLng({$item.wgs84_lat}-1, {$item.wgs84_long}-1));
		{/if}

		var newZoom = map.getBoundsZoomLevel(bounds);
		if (newZoom > 10)
			newZoom = 10;
		var center = bounds.getCenter();
		map.setCenter(center, newZoom,G_PHYSICAL_MAP);
		
		var xml = new GGeoXml("http://{$http_host}/events/feed.kml");
		{literal}
		map.addOverlay(xml);
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

