{assign var="page_title" value="Blog :: $title"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.unable,.unable A  {
	color:gray;
}
</style>{/literal}
<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="/blog/">Blog</a> :: {$title|escape:"html"}</h2>

{if $gridsquare_id}
<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	<div style="width:500px; height:450px;" id="mapCanvas">Loading map...</div>
	<div style="color:red; background-color:white">Marker only shows gridsquare</div><br/>
</div>
{/if}
<p>By <a title="View profile" href="/profile/{$user_id}">{$realname|escape:'html'}</a></p>

<p style="font-size:1.2em">{$content|escape:"html"}</p>

<hr/>
<dl class="picinfo">



<dt>When</dt>
 <dd style="font-size:1.2em">{$published|date_format:"%a, %e %b %Y at %H:%M"}</dd>

{if $gridsquare_id}
<dt>Grid Square</dt>
 <dd><a href="/location.php?gridref={$grid_reference}"><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/></a> <a title="Grid Reference {$grid_reference}" href="/gridref/{$grid_reference}">{$grid_reference}</a></dd>
{/if}




{if $image}
<dt>Chosen Photo</dt>
 <dd><div class="img-shadow">
		<a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160)}</a>
		 <div style="font-size:0.7em">
			  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
			  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
			  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		</div>
	</div></dd>
{/if}

</dl>

{if $user->user_id == $user_id || $isadmin}
	<p style="clear:both"><a href="/blog/edit.php?id={$blog_id}">edit</a></p>
{/if}

<br style="clear:both"/>


{if $lat && $google_maps_api_key}
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
		var center = new GLatLng({$lat},{$long});
		{literal}
		map.setCenter(center, 10,G_PHYSICAL_MAP);

		var themarker = new GMarker(center,{clickable: false});
		map.addOverlay(themarker);
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
{/literal}{/if}

{include file="_std_end.tpl"}

