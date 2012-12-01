{if $user_id && $realname}
{assign var="rss_url" value="/blog/syndicator.php?u=`$user_id`"}
{assign var="page_title" value="`$realname`s Blog"}
{else}
{assign var="rss_url" value="/blog/feed.rss"}
{assign var="page_title" value="Blog"}
{/if}
{include file="_std_begin.tpl"}
<style>{literal}
div.tags {
	line-height:1.5em;
	font-size:0.85em;
}
span.tag, a.tag {
	padding:2px;
	border-radius:3px;
	background-color:lightgray;
	font-family:monospace;
	text-decoration:none;
	color:brown;
	white-space:nowrap;
}

.listing-sidebar {
	line-height:1.4em;
	width:200px;
	float:right;
	background-color:#eee;
}
.listing-sidebar table {
	font-size:0.9em;
}
.listing-sidebar .cell {
	background-color:cyan;
}
.listing-sidebar .cell a {
	text-decoration:none;
}

.entry {
	position:relative;
	width:233px;
	float:left;
	border-left:2px solid #eee;
	padding-left:5px;
	margin-left:5px;
	margin-bottom:20px;
	height:28em;
	overflow:hidden;
}
.entry h4 {
	background-color:#eee;
	margin-top:0px;
	font-size:1.2em;
	margin-bottom:0;
	border-bottom: 1px solid silver;
	margin-bottom:2px;
	padding:2px;
}
.entry h4 a {
	text-decoration:none;
}
.entry .iamge {
	float:left;
	padding-right:8px;
	padding-bottom:2px;
}
.entry .iamge img {
	box-shadow: 3px 3px 5px #888888;
}
.entry div.date {
	text-align:right;
	margin-bottom:3px;
	color:gray
}
.entry div.textual {
	font-size:0.8em;
	padding-left:3px;
	overflow:none;
	font-family:'Comic Sans MS',Georgia,Verdana,Arial,serif
}
.entry div.footer {
	margin-top:8px;
	border-top:1px solid gray;
	color:gray;
}
.entry div.footer a {
	color:#9372E8;
}

{/literal}</style>
{if $geo}
<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<small style="color:red">Marker only shows grid square, see description for exact location<small><br/><br/></small></small>
	<div style="width:400px; height:320px;" id="mapCanvas"></div>
</div>
{/if}

<div style="float:right; padding-right:30px;"><a title="geoRSS Feed for Geograph Blog Entries" href="{$rss_url}" class="xml-rss">RSS</a> {if $geo}<a title="KML Feed for Geograph Blog Entries" href="/blog/kml-nl.php" class="xml-kml">KML</a>{/if}</div>

<h2>Blog Entries by Geograph Users

{dynamic}
{if $user->registered}
	<small>(<a href="/blog/edit.php?id=new">Post your own Entry</a>)</small>
{/if}
{/dynamic}</h2>

{if $user_id && $realname}
	<p style="clear:both"><b>{if $when}{$when}{else}Recent{/if} Posts by <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></b></p>
{elseif $when}
	<p style="clear:both"><b>{$when} Posts</b></p>
{/if}

{if $tags || $archive}
	<div style="clear:both" class="interestBox wordnet listing-sidebar">

	{if $archive}
		<b>Post Archive</b>:
		<table border="0" cellspacing="1" cellpadding="1">
		{assign var="last" value="0"}
		{foreach from=$archive item=item}
			{if $last != $item.year}
				{if $last}
					</tr>
				{/if}
				<tr>
					<td>{$item.year}</td>
					{if $item.month > 01}
						{section name="loop" start=1 loop=$item.month}
							<td width="10" height="10">&nbsp;</td>
						{/section}
					{/if}
				{assign var="last" value=$item.year}
			{elseif $item.month-1 > $lastmonth}
				{section name="loop" start=$lastmonth loop=$item.month-1}
					<td width="10" height="10">&nbsp;</td>
				{/section}
			{/if}
			{if $when == "`$item.year`-`$item.month`"}
				<td class="cell" width="10" height="10" align="center"><b>{$item.c}</b></td>
			{else}
				<td class="cell" width="10" height="10" align="center"><a href="?when={$item.year}-{$item.month}{if $user_id && $realname}&amp;u={$user_id}{/if}" title="{$item.year}/{$item.month}">{$item.c}</a></td>
			{/if}
			{assign var="lastmonth" value=$item.month}
		{/foreach}
		{if $last}
			</tr>
		{/if}
		</table>

	{/if}

	{if $tags}
		<b>Tag listing</b>:<br/>
		<div class="tags">
		{foreach from=$tags key=tag item=count name=foo}
			{if $tag eq $thetag}
				<span class="tag"><b>{$tag|escape:'html'}</b> [<a href="/blog/">remove filter</a>]</span>
			{else}
				<a class="tag" title="{$count} entries" {if $count > 10} style="font-weight:bold"{/if} href="/blog/?tag={$tag|escape:'url'}{if $user_id && $realname}&amp;u={$user_id}{/if}" rel="nofollow" class="tag">{$tag|escape:'html'}</a>
			{/if}
		{/foreach}
		</div>
	{/if}
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
	</div>
{/if}


{if $list}


{foreach from=$list item=item}

	<div class="entry" style="{if $item.approved < 1}background-color:pink{/if}">
		<h4><a href="/blog/{$item.blog_id}">{$item.title|escape:'html'}</a></h4>
		<div class="date">{$item.created}</div>


		{if $item.image}
			<div class="iamge"><a title="{$item.image->title|escape:'html'} by {$item.image->realname} - click to view full size image" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a></div>
		{/if}

		<div class="textual">{$item.content|truncate:500|escape:'html'|regex_replace:'/\[\[\[(\d+)\]\]\]/':'<a href="/photo/\1">Photo</a>'|GeographLinks:false}</div>
		{if $item.tags}
			<div class="tags">
			<span class="tag">{$item.tags|escape:'html'|lower|replace:',':'</span> <span class="tag">'}</span>
			</div>
		{/if}

		<div class="footer">
		Posted by <a title="View profile" href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a> <a href="?u={$item.user_id}">+</a> <span class="nowrap">on {$item.published|date_format:"%a, %e %b"}</span>
		<a href="/blog/{$item.blog_id}"><b>Read More...</b></a>

			{if $user->user_id == $item.user_id || $isadmin}<br/><br/>
					[<a href="/blog/edit.php?id={$item.blog_id}">edit</a>]
			{/if}
			{if $isadmin}
				{if $item.approved eq 1}
					[<a href="/blog/?id={$item.blog_id}&amp;approve=0">disapprove</a>]
				{else}
					[<a href="/blog/?id={$item.blog_id}&amp;approve=1">approve</a>]
					[<a href="/blog/?id={$item.blog_id}&amp;approve=-1">delete</a>]
				{/if}
			{elseif $item.approved < 1}
				[Not Approved]
			{/if}

		</div>
	</div>

{/foreach}
<br style="clear:both"/>

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

