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

<h2><img src="http://{$static_host}/img/geotag_32.png" width="32" height="32" align="absmiddle"> Links for {$gridrefraw} <sup>[{$square->imagecount} images]</sup></h2>
<hr style="margin-top:5px"/>

{if $rastermap->enabled}
	<div class="rastermap" style="width:{$rastermap->width}px;position:relative;font-size:0.8em; float:right; z-index:10">
	{$rastermap->getImageTag($gridrefraw)}
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	{$rastermap->getScriptTag()}	
	</div>
{/if}

<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px; margin-bottom:200px">
	<table border="0" cellspacing="0" cellpadding="2">
	<tr><td><a href="/location.php?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}">NW</a></td>
	<td align="center"><a href="/location.php?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}">N</a></td>
	<td><a href="/location.php?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}">NE</a></td></tr>
	<tr><td><a href="/location.php?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}">W</a></td>
	<td><b>Go</b></td>
	<td align="right"><a href="/location.php?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}">E</a></td></tr>
	<tr><td><a href="/location.php?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}">SW</a></td>
	<td align="center"><a href="/location.php?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}">S</a></td>
	<td align="right"><a href="/location.php?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}">SE</a></td></tr>
	</table>
</div>

<small><b>Links within Geograph for gridsquare {$gridref}:</b></small>

<ul style="list-style-type:none; padding-left:5px">
	<li><img src="http://{$static_host}/img/links/20/submit.png" width="20" height="20" alt="submit icon" align="absmiddle"/> <a href="/submit.php?gridreference={$gridrefraw}"><b>submit your own picture</b></a></li>
	<li><img src="http://{$static_host}/img/links/20/browse.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/gridref/{$gridrefraw}">View the <b>browse page</b></a> 
		{if $square->imagecount > 2}
				(<img src="http://{$static_host}/img/links/20/grid.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/>  <a href="/gridref/{$gridref}?by=1">or a <b>breakdown</b></a>)
			{/if}</li>
	{if $square->imagecount > 2}
		<li><img src="http://{$static_host}/img/links/20/slideshow.png" width="20" height="20" alt="slideshow icon" align="absmiddle"/> <a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1" title="View images in a Slide Show" class="nowrap">View <b>slide show</b> for the {$square->imagecount} images in this square</a></li>
	{/if}
	{if $viewpoint_count}
		<li><img src="http://{$static_host}/img/links/20/lookout.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/gridref/{$gridref}?takenfrom">view <b>{$viewpoint_count} image{if $viewpoint_count != 1}s{/if} taken <i>from</i> {$gridref}</b></a></li>
	{/if}
	{if $enable_forums}
		<li><img src="http://{$static_host}/img/links/20/discuss.png" width="20" height="20" alt="discussion icon" align="absmiddle"/> 
		{if $discuss}
			There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
			<a href="/discuss/index.php?gridref={$gridref}"><b>discussion</b> about {$gridref}</a> (preview on the left)
		{else}
			{if $user->registered} 
				<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a <b>discussion</b> about {$gridref}</a>
			{else}
				<a href="/login.php">login</a> to start a <b>discussion</b> about this square</a>
			{/if}
		{/if}</li>
	{/if}
	<li><img src="http://{$static_host}/img/links/20/gpx.png" width="20" height="20" alt="gpx icon" align="absmiddle"/> <a title="Download GPX" href="/gpx.php?gridref={$gridref}">Download a <b>GPX coverage</b> file around this area</a></li>
	{if strlen($gridrefraw) < 5}
		<li><img src="http://{$static_host}/img/links/20/hectad.png" width="20" height="20" alt="hectad icon" align="absmiddle"/> <a title="First Geographs within {$gridrefraw}" href="/search.php?first={$gridrefraw}">Find <b>first geographs for hectad</b> {$gridrefraw}</a></li>
	{/if}
	<li><img src="http://{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/> <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}"><b>search</b> for images near {$gridref}</a></li>
	<li><img src="http://{$static_host}/img/links/20/no-photos.png" width="20" height="20" alt="no photos icon" align="absmiddle"/> <a title="Empty Squares" href="/squares.php?gridref={$gridref}&amp;type=without">View list of nearby <b>squares without images</b></a> or <a title="Few Squares" href="/squares.php?gridref={$gridref}&amp;type=few">without many images</a><br/><br/></li>
	
	{if $gridref6}
		<li><img src="http://{$static_host}/img/links/20/centi.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/> <a href="/gridref/{$gridref}?viewcenti={$gridref6}">image(s) <b>taken in {$gridref6}</b></b></a> / <span class="nowrap"><a href="/gridref/{$gridref}?centi={$gridref6}">of <b>subjects in {$gridref6}</b></a> (if any)</span><br/><br/></li>
	{/if}
	
	<li style="list-style-type:none"><img src="http://{$static_host}/img/links/20/map.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}">Geograph <b>map</b></a>, <img src="http://{$static_host}/img/links/20/depth.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}&amp;depth=1"><b>Depth</b> Version</a>{if $square->reference_index == 1}, <img src="http://{$static_host}/img/links/20/mapper.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}"><b>Draggable</b></a><sup style="color:red">New!</sup>{/if} </li>
	<li style="list-style-type:none"><img src="http://{$static_host}/img/links/20/checksheet.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">View a <b>printable check sheet</b> for {if strlen($gridrefraw) < 5}{$gridrefraw}{else}{$gridref}{/if}</a></li>

</ul>
<hr style="margin-top:5px"/>
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
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
{/if}
</div>
</div>
</div>
{/if}

	{if $title}
		{assign var="urltitle" value=$title|escape:'url'}
	{else}
		{assign var="urltitle" value=$gridrefraw}
	{/if}

<div style="position:relative; float:left; width:40%;">

<b>Mapping Websites</b>:
<ul style="margin-top:0px; margin-bottom:0px">
	<li>{getamap gridref=$gridrefraw text="Get-a-Map&trade;"}</li>
	{if $square->reference_index eq 1}
		<li>{external href="http://www.magic.gov.uk/website/magic/opener.htm?startTopic=maggb&xygridref=`$square->nateastings`,`$square->natnorthings`&startscale=10000" text="magic.gov.uk"}{if $gridref6} ({external href="http://www.magic.gov.uk/website/magic/opener.htm?startTopic=maggb&xygridref=`$square->nateastings`,`$square->natnorthings`&startscale=5000" text="closer"}){/if}</li> 
		<li>{external href="http://www.streetmap.co.uk/newmap.srf?x=`$square->nateastings`&amp;y=`$square->natnorthings`&amp;z=3&amp;sv=`$square->nateastings`,`$square->natnorthings`&amp;st=OSGrid&amp;lu=N&amp;tl=[$urltitle]+from+geograph.org.uk&amp;ar=y&amp;bi=background=http://$http_host/templates/basic/img/background.gif&amp;mapp=newmap.srf&amp;searchp=newsearch.srf" text="streetmap.co.uk"}</li> 
		<li>{external href="http://www.multimap.com/maps/?title=[`$urltitle`]+on+geograph.org.uk#t=l&amp;map=$lat,$long|14|4&amp;dp=841&amp;loc=GB:$lat:$long:14|$gridref|$gridref" text="multimap.com"} {external href="http://www.multimap.com/map/browse.cgi?GridE=`$square->nateastings`&amp;GridN=`$square->natnorthings`&amp;scale=25000&amp;title=[`$urltitle`]+on+geograph.org.uk" text="(old)"}</li>
	{else}
		<li>{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}</li>
	{/if}</li>


	{if $id}
		<li><a title="Open in Google Earth" href="http://{$http_host}/photo/{$id}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external title="Open in Google Maps" href="http://maps.google.co.uk/maps?q=http://`$http_host`/photo/`$id`.kml" text="Google Maps"}</li>
		<li>{external href="http://maps.live.com/default.aspx?v=2&amp;mkt=en-us&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1&amp;mapurl=http://`$http_host`/photo/`$id`.kml" text="maps.live.com" title="detailed aerial photography from maps.live.com"}</li>
	{else}
		<li><a title="Open in Google Earth" href="http://www.nearby.org.uk/googleEarth.kml.php?lat={$lat}&amp;long={$long}&amp;zoom=11" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external title="Open in Google Maps" href="http://maps.google.co.uk/maps?q=http%3A%2F%2Fwww.nearby.org.uk%2FgoogleEarth.kml.php%3Flat%3D`$lat`%26long%3D`$long`%26zoom%3D11&zoom=11" text="Google Maps"}</li>
		<li>{external href="http://maps.live.com/default.aspx?v=2&amp;mkt=en-us&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1" text="maps.live.com" title="detailed aerial photography from maps.live.com"}</li>
	{/if}

</ul>
</div>
<div style="position:relative; float:left; width:40%;">
<b>What's nearby?</b>
<ul style="margin-top:0px">
	{if $image_taken}
		{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;MONTH=%m&amp;YEAR=%Y"}
		<li>{external href="http://www.weatheronline.co.uk/cgi-bin/geotarget?LAT=`$lat`&amp;LON=`$long``$imagetakenurl`" text="weatheronline.co.uk" title="weather at the time this photo was taken from weatheronline.co.uk"}</li>
	{/if}
	{if $square->reference_index eq 1}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="Geocaches" title="Geocaches from geocaching.com"}</li>
			<li>{external title="Trigpoints from trigpointinguk.com" href="http://www.trigpointinguk.com/trigtools/find.php?t=`$gridrefraw`" text="Trigpoints"}</li>
			<li>{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}<br/><br/></li>

			<li>{external title="find local features and maps with wikimedia" href="http://stable.toolserver.org/geohack/geohack.php?params=`$lat`_`$nl`_`$long`_`$el`_region:GB_scale:25000" text="<b>more</b> from wikimedia"}<br/><br/></li>
			<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`" text="<b>more</b> from nearby.org.uk"}</li>
	{else}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="Geocaches" title="Geocaches from geocaching.com"}</li>
			<li>{external href="http://www.trigpointing-ireland.org.uk/gridref.php?g=`$square->grid_reference`" text="trigpoints" title="Trigpoints from trigpointing-ireland.org.uk"}</li>
			<li>{external href="http://geourl.org/near?lat=`$lat`&amp;long=`$long`" text="geourl.org" title="search for webpages near this location"}<br/><br/></li>

			<li>{external title="find local features and maps with wikimedia" href="http://stable.toolserver.org/geohack/geohack.php?params=`$lat_abs`_`$nl`_`$long_abs`_`$el`_region:GB_scale:25000" text="<b>more</b> from wikimedia"}<br/><br/></li>
			<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`+OSI" text="<b>more</b> from nearby.org.uk"}</li>
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
