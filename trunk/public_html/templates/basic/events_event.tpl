{assign var="page_title" value="Event :: $title"}
{include file="_std_begin.tpl"}

<div style="float:right; position:relative; padding:5px; border:1px solid gray; ">
	Marker only shows gridsquare, see description for exact location<br/><br/>
	<div style="width:500px; height:500px;" id="mapCanvas">Loading map...</div>
</div>

<h2><a href="/events/">Events</a> :: {$title|escape:"html"}</h2>


<dl class="picinfo">

<dt>Description</dt>
 <dd style="font-size:1.2em">{$description|escape:"html"}</dd>


<dt>When</dt>
 <dd style="font-size:1.2em">{$event_time|date_format:"%a, %e %b %Y"} <small>({$days} days from now)</small></dd>

<dt>Grid Square</dt>
 <dd><a title="Grid Reference {$grid_reference}" href="/gridref/{$grid_reference}">{$grid_reference}</a></dd>

{if $url}
<dt>Link</dt>
 <dd style="font-size:1.2em">{external href=$url text="more info"}</dd>
{/if}

<dt>Event Lister</dt>
 <dd><a title="View profile" href="/profile/{$user_id}">{$realname|escape:'html'}</a></dd>



{if $image}
<dt>Choosen Photo</dt>
 <dd><div class="img-shadow">
		<a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
		 <div style="font-size:0.7em">
			  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
			  by <a title="view user profile" href="/profile/{$image->user_id}">{$image->realname}</a>
			  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		</div>
	</div></dd>
{/if}

</dl>

<br style="clear:both"/>

{if $user->user_id == $user_id || $isadmin}
	<td><a href="/events/edit.php?id={$geoevent_id}">edit</a></td>
{/if}







{if $lat}
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

