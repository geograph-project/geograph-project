{if $engine->resultCount}
{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">

<div style="float:right;position:relative; font-size:0.9em">
<form action="/search.php" method="get" style="display:inline">
<div>
Display: 
<input type="hidden" name="i" value="{$i}"/>
{if $engine->currentPage > 1}<input type="hidden" name="page" value="{$engine->currentPage}"/>{/if}
<select name="displayclass" size="1" onchange="this.form.submit()" style="font-size:0.9em"> 
	{html_options options=$displayclasses selected=$engine->criteria->displayclass}
</select>
{if $legacy}<input type="hidden" name="legacy" value="1"/>{/if}
<noscript>
<input type="submit" value="Update"/>
</noscript></div>
</form>

</div>
{/if}

<h2>Search Results</h2>
<br style="clear:both"/>

{if !$google_maps_api_key}
	<div class="interestBox">
	<p>This page is no longer able to display a map - please use a different display method.</p>
	<p>However you may be able to display a map on {if $engine->currentPage > 1}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`/`$engine->currentPage`.kml" text="Google Maps"}.{else}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`.kml" text="Google Maps"}.{/if}</p>
	</div>

{elseif $engine->resultCount}

	<div id="map" style="width:400px; height:400px; position:relative; float:left;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;

	function onLoad() {
		map = new GMap2(document.getElementById("map"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl(true));
		map.addControl(new GScaleControl());
		var mapType = G_NORMAL_MAP;

		var bounds = new GLatLngBounds();

		{/literal}{foreach from=$engine->results item=image}
			bounds.extend(new GLatLng({$image->wgs84_lat}, {$image->wgs84_long}));
		{/foreach}{literal}

		var newZoom = map.getBoundsZoomLevel(bounds);
		var center = bounds.getCenter();
		
		if (location.hash.length) {
			// If there are any parameters at the end of the URL, they will be in location.search
			// looking something like  "#ll=50,-3&z=10&t=h"

			// skip the first character, we are not interested in the "#"
			var query = location.hash.substring(1);

			var pairs = query.split("&");
			for (var i=0; i<pairs.length; i++) {
				// break each pair at the first "=" to obtain the argname and value
				var pos = pairs[i].indexOf("=");
				var argname = pairs[i].substring(0,pos).toLowerCase();
				var value = pairs[i].substring(pos+1).toLowerCase();

				if (argname == "ll") {
					var bits = value.split(',');
					center = new GLatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {newZoom = parseInt(value);}
				if (argname == "t") {
					if (value == "m") {mapType = G_NORMAL_MAP;}
					if (value == "k") {mapType = G_SATELLITE_MAP;}
					if (value == "h") {mapType = G_HYBRID_MAP;}
					if (value == "p") {mapType = G_PHYSICAL_MAP;}
					if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
			}
		}

		map.setCenter(center, newZoom,mapType);


		{/literal}
		var xml = new GGeoXml("http://{$http_host}/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.kml");
		map.addOverlay(xml);

		{if $markers} 
			{foreach from=$markers item=marker}
				map.addOverlay(createMarker(new GLatLng({$marker.1},{$marker.2}),'{$marker.0}'));
			{/foreach}
		{/if}{literal}

		GEvent.addListener(map, "moveend", makeHash);
		GEvent.addListener(map, "zoomend", makeHash);
		GEvent.addListener(map, "maptypechanged", makeHash);
	}

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getCurrentMapType().getUrlArg();
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t;
	}

	function createMarker(point,myHtml) {
		var marker = new GMarker(point, {draggable: true});

		GEvent.addListener(marker, "click", function() {
			map.openInfoWindowHtml(point, myHtml);
		});
		GEvent.addListener(marker, "dragend", function() {
			marker.setPoint(point);
		});

		return marker;
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}{/if}
	
	
	<div style="float:left; position:relative; width:400px; text-align:center; padding:7px">
		{assign value=$engine->results.0 var=image}
		<div >
			<a title="view full size image" href="/photo/{$image->gridimage_id}">
			{$image->getFull()|regex_replace:'/"(\d+)"/e':'"\"".($1/2)."\""'}
			</a><div class="caption"><b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> <span class="nowrap">for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></span></div>
		</div>
		{if $image->excerpt}
			<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->excerpt}</div>
			{elseif $image->imageclass}<small>Category: {$image->imageclass}</small>
		{/if}
	
	</div>
	
	
	<div class="interestBox" style="clear:both;margin-top:15px">
		{if $engine->criteria->searchclass != 'Special'}<div style="float:right">
			[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]</div>{/if}
	
		<b><a href="/search.php?i={$i}&amp;displayclass=cooliris">View results on a 3D Wall</a></b> |
		<a href="/search.php?i={$i}&amp;displayclass=gmap">Larger Map</a> |
		<a href="/search.php?i={$i}&amp;displayclass=full">Full Results</a> |
		{if $engine->numberOfPages > 1} 
			<b><a href="/search.php?i={$i}&amp;page=2&amp;displayclass=excerpt">Next Page of results &gt; &gt;</a></b>
		{/if}
	</div>
	{if $engine->islimited && $engine->resultCount != $engine->numberofimages}
		<div style="float:right">
			Image <b>1 to {$engine->numberofimages}</b> of {$engine->resultCount}
		</div>
	{/if}
	
	<p><big>Your search{if !$engine->criteria->groupby} for images{/if}<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
	{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
		<acronym title="to keep server load under control, we delay calculating the total">many</acronym> {if $engine->criteria->groupby}groups{else}images{/if}
	{elseif $engine->islimited}
		<b>{$engine->resultCount|number_format}</b> {if $engine->criteria->groupby}groups{else}images{/if}
	{else}
		the following
	{/if}:</big></p>
	
	{if $suggestions || $related} 
		<div style="float:right;position:relative;padding:8px; border-left:2px solid gray;width:250px;">
			<div style="width:1px;float:left;height:375px"></div>
			
		{if $suggestions} 
			<b>Alternative suggestions:</b>
			<ul>
			{foreach from=$suggestions item=row}
				<li><b><a href="{if $row.link}{$row.link}{else}/search.php?i={$i}&amp;text={$row.query|escape:'url'}&amp;gridref={$row.gr}&amp;redo=1{/if}">{$row.query}{if $row.name} <i>near</i> {$row.name}{/if}</a></b>? {if $row.localities}<small style="font-size:0.7em">({$row.localities})</small>{/if}</li>
			{/foreach}
			</ul>
		{/if}
		
		{if $related}
			<b>Related Collections</b>
			<ul style="padding:0 0 0 1em;">
				{foreach from=$related item=item}
					<li><a href="{$item.url}">{$item.title|escape:'html'|regex_replace:"/(`$engine->criteria->searchtext`)/i":'<b>$1</b>'}</a>
					{if $item.images}[{$item.images|thousends}]{/if}
					<div style="font-size:0.7em;color:gray;margin-left:2px;">
					By <a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>.</div>
					</li>
				{/foreach}
			</ul>
			<a href="/content/?q={$engine->criteria->searchtext}&amp;scope=all&amp;order=relevance">more...</a>
		{/if}
		
		</div>
	{/if}
	
	
	<div>
		{foreach from=$engine->results item=image}
		{searchbreak image=$image}
		  <div style="float:left;position:relative; width:130px; height:130px" onmouseover="this.style.background='gray';showMyInfoDiv('image{$image->gridimage_id}',true);" onmouseout="this.style.background='';showMyInfoDiv('image{$image->gridimage_id}',false);">
		  <div align="center">
		  <a title="" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
		  
		  <div style="position:relative">
		  	<div class="interestBox" style="position:absolute;top:0;left:-120px;display:none;z-index:10000;padding:4px;width:260px" id="image{$image->gridimage_id}_info">
		  		{$image->dist_string}{if $image->count} - {$image->count|thousends} images in group<br/>{/if}
				<div class="caption"><b><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></b> <span class="nowrap">for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></span></div>
				
				{if $image->excerpt}
					<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->excerpt}</div>
					{elseif $image->imageclass}<small>Category: {$image->imageclass}</small>
				{/if}
		  	</div>
		  </div>
		  
		  </div>
		{foreachelse}
		 	{if $engine->resultCount}
		 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
		 	{/if}
		{/foreach}
		
		{literal}<script type="text/javascript"><!--
			
		function showMyInfoDiv(which,show) {
			document.getElementById(which+'_info').style.display=show?'':'none';
		}
		
		//--></script>{/literal}
		
		<br style="clear:both"/>
	</div>
	
	
	
	
	
	
	
	
	
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}


	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString("&amp;displayclass=excerpt")})
	
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
	
	
	{if $engine->criteria->searchclass != 'Special'}
	[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>	
	
	
	<div style="text-align:right">{if $engine->islimited && (!$engine->fullText || $engine->criteria->sphinx.compatible)}<a title="Breakdown for images{$engine->criteria->searchdesc|escape:"html"}" href="/statistics/breakdown.php?i={$i}">Statistics</a> {/if}<a title="Google Earth Or Google Maps Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}">Results as KML</a> <a title="geoRSS Feed for images{$engine->criteria->searchdesc|escape:"html"}" href="/feed/results/{$i}{if $engine->currentPage > 1}/{$engine->currentPage}{/if}.rss" class="xml-rss">RSS</a></div>
	

</div>
	
	{include file="_std_end.tpl"}
{else}
	{include file="_search_begin.tpl"}
	{include file="_search_noresults.tpl"}
	{include file="_search_end.tpl"}
{/if}

