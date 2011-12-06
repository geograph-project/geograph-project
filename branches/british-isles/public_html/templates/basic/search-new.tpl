{assign var="page_title" value="Search"}
{include file="_std_begin.tpl"}
{dynamic}

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}

<form method="get" action="/search.php">
	<div class="tabHolder" style="text-align:right">
		<span class="tabSelected">Simple search</span>
		<a href="/search.php?form=text" class="tab">Advanced search</a>
		{if $user->registered}
			<a href="/search.php?form=first" class="tab">First Geographs</a>
			<a href="/search.php?form=check" class="tab">Check submissions</a>
		{/if}
		<a href="/finder/" class="tab">more...</a>
	</div>
	<div class="interestBox">
		 <table cellspacing=0 cellpadding=0 border=0>
		  <tr>
		   <td>Search for: (keywords)<br/>
			<input type="text" id="searchq" name="q"  value="{$searchtext|escape:"html"|default:"(anything)"}" style="font-size:1.2em" size="30" onfocus="if (this.value=='(anything)') this.value=''" onblur="if (this.value=='') this.value='(anything)'"/><br/>
			(<a href="/article/Searching-on-Geograph">Help</a>)
			<div style="position:relative">
			  <div id="autocomplete">
			  </div>
			</div>
		   </td>
		   <td>near: (location)<br/>
			<input type="text" id="location" name="location" value="{$searchlocation|escape:"html"|default:"(anywhere)"}" style="font-size:1.2em" size="30" onfocus="if (this.value=='(anywhere)') this.value=''" onblur="if (this.value=='') this.value='(anywhere)'"/><br/>
			<a href="javascript:void(openMap(true))" id="open_map">Open Map</a> <a href="javascript:void(closeMap())" id="close_map" style="display:none">CloseMap</a> <a href="javascript:void(showValues())" id="show_values" style="display:none">Show Places List</a>
			<div style="position:relative">
			  <div id="map_canvas">
			  </div>
			  <div id="sv_follow_div">
				  <input type="checkbox" id="sv_follow_checkbox"/> <label for="sv_follow_checkbox">Update position as move around in Street-View</a>
			  </div>
			</div>
		   </td>
		   <td>
			<input type="submit" name="go" value="Search" style="font-size:1.2em"/>
		   </td>
		  </tr>
		 </table>
	</div>
</form>

{/dynamic}


<div id="results">
   <p>Enter your search above to find images. Can enter keywords to match against the image, and/or a location. Start typing a placename, or enter a postcode/grid-reference in the Location box. </p>
</div>
<div id="stats">

</div>

<ul style="margin-left:0;padding:0 0 0 1em;">

<li>Here are a couple of example searches:<br/>
<div style="float:left; margin-top:3px;  width:60%; position:relative">
	<ul style="margin-left:0;padding:0 0 0 1em;font-size:0.8em">
	{foreach from=$featured key=id item=row}
	<li><a href="/search.php?i={$row.id|escape:url}">{$row.searchdesc|regex_replace:'/^, /':''|escape:html}</a></li>
	{/foreach}
	<li><a href="/explore/searches.php" title="Show Featured Searches"><i><b>more examples...</b></i></a></li>
	</ul>
</div>
<div style="float:left; margin-top:3px;  width:40%; position:relative">
	<ul style="font-size:0.8em">
	{foreach from=$imageclasslist key=id item=name}
	<li><a href="/search.php?imageclass={$id|escape:url}" title="Show images classed as {$id|escape:html}">{$name|escape:html}</a></li>
	{/foreach}
	<li><a href="/statistics/breakdown.php?by=class" title="Show Image Categories"><i><b>more categories...</b></i></a></li>

	</ul>
</div><br style="clear:both;"/><br/>
</li>

{dynamic}
{if $user->registered}
	{if $recentsearchs}
	<li>And a list of your recent searches:
	<ul style="margin-left:-10px; margin-top:3px; padding:0 0 0 0em; list-style-type:none">
	{foreach from=$recentsearchs key=id item=obj}
	<li>{if $obj.favorite == 'Y'}<a href="/search.php?i={$id}&amp;fav=0" title="remove favorite flag"><img src="http://{$static_host}/img/star-on.png" width="14" height="14" alt="remove favorite flag" onmouseover="this.src='http://{$static_host}/img/star-light.png'" onmouseout="this.src='http://{$static_host}/img/star-on.png'"></a> <b>{else}<a href="/search.php?i={$id}&amp;fav=1" title="make favorite - starred items stay near top"><img src="http://{$static_host}/img/star-light.png" width="14" height="14" alt="make favorite" onmouseover="this.src='http://{$static_host}/img/star-on.png'" onmouseout="this.src='http://{$static_host}/img/star-light.png'"></a> {/if}{if $obj.searchclass == 'Special'}<i>{/if}<a href="/search.php?i={$id}" title="Re-Run search for images{$obj.searchdesc|escape:"html"}{if $obj.use_timestamp != '0000-00-00 00:00:00'}, last used {$obj.use_timestamp}{/if} (Display: {$obj.displayclass})">{$obj.searchdesc|escape:"html"|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'</a><small>$1</small>'}</a>{if !is_null($obj.count)} [{$obj.count}]{/if}{if $obj.searchclass == 'Special'}</i>{/if}{if $obj.favorite == 'Y'}</b>{/if} {if $obj.edit}<a href="/refine.php?i={$id}" style="color:red">Edit</a>{/if}</li>
	{/foreach}
	{if !$more && !$all}
	<li><a href="/search.php?more=1" title="View More of your recent searches" rel="nofollow"><i>view more...</i></a></li>
	{/if}
	</ul><br/>
	</li>
	{/if}
	<div
	<div id="hidemarked">
		 <small>Marked Images <input type=button value="expand" onclick="show_tree('marked')"/></small>
	</div>
	<div style="position:relative; padding:10px; background-color:#eeeeee;display:none" id="showmarked">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	<small>Marked Images <span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	</small><small style="font-size:0.6em">TIP: Add images to your list by using the [Mark] buttons on the "full + links" and "thumbnails + links"<br/> search results display formats, and the full image page.<br/><br/>
	<span style="color:red">Note: The Marked list is stored in a <b>temporary</b> cookie in your browser, and limited to about 500 images.<br/>
	You can use the 'View as Search Results' to save your current list to the server permanently.</small></div>
	<br/>
	<script>
		AttachEvent(window,'load',showMarkedImages,false);
	</script>
{else}
	<li><i><a href="/login.php">Login</a> to see your recent and favorite searches.</i><br/></li>
{/if}
{/dynamic}
</ul>
<div class="interestBox">
<ul class="lessIndent" style="margin-top:5px">

<li>If you are unable to find your location in our search above try {getamap} and return here to enter the <acronym style="border-bottom: red dotted 1pt; text-decoration: none;" title="look for something like 'Grid reference at centre - NO 255 075 GB Grid">grid reference</acronym>.<br/><br/></li>

<li><b>If you have a WGS84 latitude &amp; longitude coordinate</b>
		(e.g. from a GPS receiver, or from multimap site), then see our
		<a href="/latlong.php">Lat/Long to Grid Reference Convertor</a><br/><br/></li>


<li>A <a title="Photograph Listing" href="/sitemap/geograph.html">complete listing of all photographs</a> is available.<br/><br/></li>

<li>You may prefer to browse images on a <a title="Geograph Map Browser" href="/mapbrowse.php">map of the British Isles</a>.<br/><br/></li>


<li>Or you can browse a <a title="choose a photograph" href="browse.php">particular grid square</a>.<br/><br/></li>

{if $enable_forums}
<li>Registered users can also <a href="/finder/discussions.php">search the forum</a>.</li>
{/if}
</ul>
</div>

   <br/><br/>


<script type="text/javascript"
    src="http://maps.googleapis.com/maps/api/js?sensor=false">
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script src="{"/mapper/geotools2.js"|revision}"></script>
<script src="{"/js/search2.js"|revision}"></script>

{literal}
<style>
#results div.inner {
	float:left;
	height:130px;
	width:130px;
	padding:2px;
	text-align:center;
}
#results p {
	clear:both;
}
#stats {
	font-size:0.8em;
}
#autocomplete {
	position:absolute;top:0;left:0;width:300px;display:none;
	z-index:1000;

	background-color:silver;
}
.message {
	position:absolute;
	background-color:yellow;
	border:1px solid black;
	padding:10px;
	left:200px;
	top:200px;
	width:400px;
	z-index:1000px;
	filter:alpha(opacity=90);
	opacity:0.9;
}

#map_canvas {
	position:absolute;top:0;left:0;display:none;
	z-index:1000;
	background-color:white;
}
#sv_follow_div {
	position:absolute;top:500px;left:0;display:none;width:600px
}
</style>
{/literal}

{include file="_std_end.tpl"}
