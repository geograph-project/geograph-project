{assign var="page_title" value="Blog"}
{assign var="rss_url" value="/blog/feed.rss"}
{include file="_std_begin.tpl"}

{if $geo}
<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<small style="color:red">Marker only shows gridsquare, see description for exact location<small><br/><br/></small></small>
	<div style="width:400px; height:320px;" id="mapCanvas"></div>
</div>
{/if}

<h2>Geograph Blog</h2>

<div style="float:right; padding-right:30px;"><a title="geoRSS Feed for Geograph Blog Entries" href="/blog/feed.rss" class="xml-rss">RSS</a> {if $geo}<a title="KML Feed for Geograph Blog Entries" href="/blog/kml-nl.php" class="xml-kml">KML</a>{/if}</div>

{dynamic}
{if $user->registered}


	<ul style="margin:0px;"><li><a href="/blog/edit.php?id=new">Add your own Entry</a></li></ul>



{/if}
{/dynamic}



<br style="clear:both"/>


{if $list}


{foreach from=$list item=item}

	<div class="interestBox">
		<div style="float:right;position:relative">
			{$item.published}
		</div>
		<a href="/blog/entry.php?id={$item.blog_id}"><b>{$item.title|escape:'html'}</b></a>,
		<small>by <a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a></small>
	</div>
	<blockquote>
		<p>{$item.content|truncate:80|escape:'html'}</p>
		<a href="/blog/entry.php?id={$item.blog_id}"><i>Read more...</i></a>
		{if $user->user_id == $item.user_id || $isadmin}
				<a href="/blog/edit.php?id={$item.blog_id}">edit</a>
		{/if}
	</blockquote>






{/foreach}

{else}
  <p>There are no listed entries.</p>
{/if}


{dynamic}
{if $user->registered}
<br/><br/><br/><br/><br/><br/>
<div class="interestBox">
	<ul style="margin:0px;"><li><a href="/blog/edit.php?id=new">Add your own entry</a></li></ul>

</div>

{/if}
{/dynamic}


{if $geo && $google_maps_api_key}
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

		var xml = new GGeoXml("http://{$http_host}/blog/feed.kml");
		{literal}
		map.addOverlay(xml);
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

