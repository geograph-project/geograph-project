{assign var="page_title" value="Events"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


<div style="float:right; position:relative; padding:10px; border:1px solid gray; ">
	<div style="width:300px; height:300px;" id="mapCanvas"></div>
</div>

<h2>Geograph Events</h2>


{dynamic}
{if $user->registered}
	
<div class="interestBox" style="width:300px">
	<ul style="margin:0px;"><li><a href="/events/edit.php?id=new">Announce your own Event</a></li></ul>

</div>

{/if}
{/dynamic}

<p>Use this section to find out about upcoming events, organised by Geograph members for Geograph members! Listed below are events, as well as their location plotted on a national map. If you are able to attend then let the organiser know by clicking the link below</p>

<p>If you are thinking of organising your own event (or want to attend an meetup) then you may be interested in this {external href="http://www.nearby.org.uk/google/meet-me-at.php?group=1" text="meet-me-at map"} for finding possible areas of interest</p>


<br style="clear:both"/>


{if $list}

<p>Upcoming Events...</p>
<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Date/Time</td>
	<td>Where</td>
	<td>Title</td>
	<td style="width:150px">by</td>
</tr></thead>
<tbody>

{foreach from=$list item=item}
	{if $list.future == 0}
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}" id="row{$item.geoevent_id}">
		<td sortvalue="{$item.event_time}" class="nowrap">{$item.event_time|date_format:"%a, %e %b %Y"}</td>
		<td sortvalue="{$item.grid_reference}"><a href="/gridref/{$item.grid_reference}">{$item.grid_reference}</a></td>
		<td><a href="/events/event.php?id={$item.geoevent_id}">{$item.title|default:'Untitled'}</a></td>
		<td sortvalue="{$item.realname}"><a href="/profile/{$item.user_id}">{$item.realname}</a></td>
		</tr>
	{/if}
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
	<ul style="margin:0px;"><li><a href="/events/edit.php?id=new">Announce your own Event</a></li></ul>

</div>

{/if}
{/dynamic}


{if $item}
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
			bounds.extend(new GLatLng({$item.wgs84_lat}, {$item.wgs84_long}));
		{/foreach}
		{if count($list) == 1}
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

