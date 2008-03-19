{dynamic}
{assign var="page_title" value="$gridrefraw :: Links"}
{include file="_std_begin.tpl"}

{if $errormsg}
	<p>{$errormsg}</p>
{else}


<dl style="float:right; margin:0px; position:relative">
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $square->reference_index eq 1}OSGB36{else}Irish{/if}: {getamap gridref=$gridrefraw text=$gridrefraw} [{$square->precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>
</dl>

<h2><img src="/img/geotag_32.png" width="32" height="32" align="absmiddle"> Links for {$gridrefraw} <sup>[{$square->imagecount} images]</sup></h2>
<hr style="margin-top:5px"/>

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative;font-size:0.8em; float:right; z-index:10">
	{$rastermap->getImageTag($gridrefraw)}
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	{$rastermap->getScriptTag()}	
	</div>
{/if}



<ul>
	<li><a href="/submit.php?gridreference={$gridrefraw}"><b>submit your own picture for {$gridref}</b></a></li>
	<li><a href="/gridref/{$gridrefraw}">View <b>browse page</b> for {$gridref}</a> 
		{if $square->imagecount > 2}
				(<a href="/gridref/{$gridref}?by=1">view breakdown</a>)
			{/if}</li>
	{if $square->imagecount > 2}
		<li><a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1" title="View images in a Slide Show" class="nowrap">View <b>slide show</b> for the {$square->imagecount} images</a></li>
	{/if}
	{if $enable_forums}
		<li>
		{if $discuss}
			There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
			<a href="/discuss/index.php?gridref={$gridref}"><b>discussion</b> about {$gridref}</a> (preview on the left)
		{else}
			{if $user->registered} 
				<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a <b>discussion</b> about {$gridref}</a>
			{else}
				<a href="/login.php">login</a> to start a <b>discussion</b> about {$gridref}</a>
			{/if}
		{/if}</li>
	{/if}

	
	<li><a title="Download GPX" href="/gpx.php?gridref={$gridref}">Download a <b>GPX coverage</b> file around {$gridref}</a></li>
	{if strlen($gridrefraw) < 5}
		<li><a title="First Geographs within {$gridrefraw}" href="/search.php?first={$gridrefraw}">Find <b>first geographs for hectad</b> {$gridrefraw}</a></li>
	{/if}
	<li>or <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}"><b>search</b> for nearby images</a></li>

</ul>
	{if $title}
		{assign var="urltitle" value=$title|escape:'url'}
	{else}
		{assign var="urltitle" value=$gridrefraw}
	{/if}

<b>Maps</b>:
<ul style="margin-top:0px; margin-bottom:0px">
	<li><a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}">Geograph <b>map</b></a>, <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}&amp;depth=1"><b>Depth</b> Version</a>{if $square->reference_index == 1}, <a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}"><b>Draggable</b></a><sup style="color:red">New!</sup>{/if} </li>
	<li><a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">View a <b>printable check sheet</b> for {if strlen($gridrefraw) < 5}{$gridrefraw}{else}{$gridref}{/if}</a></li>







	<li>{getamap gridref=$gridrefraw text="Get-a-Map&trade;"}</li>
	{if $square->reference_index eq 1}
		<li>{external href="http://www.magic.gov.uk/website/magic/opener.htm?startTopic=magicall&chosenLayers=moncIndex&xygridref=`$square->nateastings`,`$square->natnorthings`&startscale=10000" text="magic.gov.uk"}</li> 
		<li>{external href="http://www.streetmap.co.uk/newmap.srf?x=`$square->nateastings`&amp;y=`$square->natnorthings`&amp;z=3&amp;sv=`$square->nateastings`,`$square->natnorthings`&amp;st=OSGrid&amp;lu=N&amp;tl=[$urltitle]+from+geograph.org.uk&amp;ar=y&amp;bi=background=http://$http_host/templates/basic/img/background.gif&amp;mapp=newmap.srf&amp;searchp=newsearch.srf" text="streetmap.co.uk"}</li> 
		<li>{external href="http://www.multimap.com/maps/?title=[`$urltitle`]+on+geograph.org.uk#t=l&amp;map=$lat,$long|14|4&amp;dp=841&amp;loc=GB:$lat:$long:14|$gridref|$gridref" text="multimap.com"} {external href="http://www.multimap.com/map/browse.cgi?GridE=`$square->nateastings`&amp;GridN=`$square->natnorthings`&amp;scale=25000&amp;title=[`$urltitle`]+on+geograph.org.uk" text="(old)"}</li>
	{else}
		<li>{external href="http://www.multimap.com/p/browse.cgi?scale=25000&amp;lon=`$long`&amp;lat=`$lat`&amp;GridE=`$long`&amp;GridN=`$lat`" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}</li>
	{/if}</li>


	{if $id}
		<li><a title="Open in Google Earth" href="http://{$http_host}/photo/{$id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external title="Open in Google Maps" href="http://maps.google.co.uk/maps?q=http://`$http_host`/photo/`$id`.kml" text="Google Maps"}</li>
		<li>{external href="http://maps.live.com/default.aspx?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;mapurl=http://`$http_host`/photo/`$id`.kml&amp;encType=1" text="maps.live.com" title="detailed aerial photography from maps.live.com"}</li>
	{else}
		<li><a title="Open in Google Earth" href="http://www.nearby.org.uk/googleEarth.kml.php?lat={$lat}&amp;long={$long}&amp;zoom=11" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external title="Open in Google Maps" href="http://maps.google.co.uk/maps?q=http%3A%2F%2Fwww.nearby.org.uk%2FgoogleEarth.kml.php%3Flat%3D`$lat`%26long%3D`$long`%26zoom%3D11&zoom=11" text="Google Maps"}</li>
		<li>{external href="http://maps.live.com/default.aspx?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1" text="maps.live.com" title="detailed aerial photography from maps.live.com"}</li>
	{/if}
</ul>

{if $overview}
<div style="clear:both; float:right; width:{$overview_width+30}px; position:relative">

<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
{if $marker}
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="http://{$static_host}/templates/basic/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
{/if}
</div>
</div>
</div>
{/if}

<p><b>What's nearby?</b> </p>
<div style="position:relative; float:left; width:40%;">
<ul style="margin-top:0px">
	{if $image_taken}
		{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;MONTH=%m&amp;YEAR=%Y"}
		<li>{external href="http://www.weatheronline.co.uk/cgi-bin/geotarget?LAT=`$lat`&amp;LON=`$long``$imagetakenurl`" text="weatheronline.co.uk" title="weather at the time this photo was taken from weatheronline.co.uk"}</li>
	{/if}
	{if $square->reference_index eq 1}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="Geocaches" title="Geocaches from geocaching.com"}</li>
			<li>{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$gridrefraw`" text="Trigpoints"}</li>
			<li>{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}</li>
		</ul>
		</div>
		<div style="position:relative; float:left; width:40%;">
		<ul style="margin-top:0px">
			<li>{external title="find local features and maps with wikimedia" href="http://tools.wikimedia.de/~magnus/geo/geohack.php?params=`$lat`_`$nl`_`$long`_`$el`_region:GB_scale:25000" text="more from wikimedia"}</li>
			<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`" text="more from nearby.org.uk"}</li>
	{else}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="Geocaches" title="Geocaches from geocaching.com"}</li>
			<li>{external href="http://www.trigpointing-ireland.org.uk/gridref.php?g=`$square->grid_reference`" text="trigpoints" title="Trigpoints from trigpointing-ireland.org.uk"}</li>
			<li>{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}</li>
		</ul>
		</div>
		<div style="position:relative; float:left; width:40%;">
		<ul style="margin-top:0px">
			<li>{external title="find local features and maps with wikimedia" href="http://tools.wikimedia.de/~magnus/geo/geohack.php?params=`$lat`_`$nl`_`$long`_`$el`_region:GB_scale:25000" text="more from wikimedia"}</li>
			<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`+OSI" text="more from nearby.org.uk"}</li>
	{/if}
</ul>
</div>
<br style="clear:both"/>
<br/>
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}

{/if}
{include file="_std_end.tpl"}
{/dynamic}
