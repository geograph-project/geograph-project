{dynamic}
{assign var="page_title" value="$gridrefraw :: Links"}
{include file="_std_begin.tpl"}

{if $getamap}
	<div class="interestBox">
		<b>Note: Get-a-Map is currently unavailable, you have been taken to our location links page so can open alternative mapping. </b> read more: <a href="http://www.getamap.ordnancesurveyleisure.co.uk/">www.getamap.ordnancesurveyleisure.co.uk</a>
	</div>
{/if}

{if $errormsg}
	<p>{$errormsg}</p>
{else}

{if $gridref} 
<dl style="float:right; margin:0px; position:relative">
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $square->reference_index eq 1}OSGB36{else}Irish{/if}: {getamap gridref=$gridrefraw text=$gridrefraw} [{$square->precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>
</dl>

<h2><img src="{$static_host}/img/geotag_32.png" width="32" height="32" align="absmiddle"> Links for {$gridrefraw|escape:'html'} <sup>[{$square->imagecount} images]</sup></h2>
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

<small><b>Links within Geograph for grid square {$gridref}:</b></small>

<ul style="list-style-type:none; padding-left:5px">
	<li><img src="{$static_host}/img/links/20/submit.png" width="20" height="20" alt="submit icon" align="absmiddle"/> <a href="{if $user->submission_method == 'submit2'}/submit2.php#gridref={$gridrefraw|escape:'url'}{else}/submit.php?gridreference={$gridrefraw|escape:'url'}{/if}"><b>Submit your own picture</b></a></li>
	<li><img src="{$static_host}/img/links/20/browse.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/gridref/{$gridrefraw|escape:'url'}">View the <b>browse page</b></a> 
		{if $square->imagecount > 2}
				(<img src="{$static_host}/img/links/20/grid.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/>  <a href="/gridref/{$gridref}?by=1">or a <b>breakdown</b></a>)
			{/if}</li>
	{if $square->imagecount > 2}
		<li><img src="{$static_host}/img/links/20/slideshow.png" width="20" height="20" alt="slideshow icon" align="absmiddle"/> <a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1" title="View images in a Slide Show" class="nowrap">View <b>slide show</b> for the {$square->imagecount} images in this square</a></li>
	{/if}
	{if $viewpoint_count}
		<li><img src="{$static_host}/img/links/20/lookout.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/gridref/{$gridref}?takenfrom">view <b>{$viewpoint_count} image{if $viewpoint_count != 1}s{/if} taken <i>from</i> {$gridref}</b></a></li>
	{/if}
	{if $enable_forums}
		<li><img src="{$static_host}/img/links/20/discuss.png" width="20" height="20" alt="discussion icon" align="absmiddle"/> 
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
	<li><img src="{$static_host}/img/links/20/gpx.png" width="20" height="20" alt="gpx icon" align="absmiddle"/> <a title="Download GPX" href="/gpx.php?gridref={$gridref}">Download a <b>GPX coverage</b> file around this area</a></li>
	{if strlen($gridrefraw) < 5}
		<li><img src="{$static_host}/img/links/20/hectad.png" width="20" height="20" alt="hectad icon" align="absmiddle"/> <a title="First Geographs within {$gridrefraw|escape:'html'}" href="/search.php?first={$gridrefraw|escape:'url'}">Find <b>First Geographs for hectad</b> {$gridrefraw|escape:'html'}</a></li>
	{/if}
	<li><img src="{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/> <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}"><b>Search</b> for images near {$gridref}</a>
	{if $gridref6}
		(<b><a href="/search.php?q={$gridref6}">near {$gridref6}</a></b>)
	{/if}	
	</li>
	<li><img src="{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/> <a title="search for nearby images to {$gridref}" href="/browser/#!/grid_reference+%22{$gridref}%22">Open <b>Browser</b> for images in {$gridref}</a></a>
	<li><img src="{$static_host}/img/links/20/place.png" width="20" height="20" alt="places icon" align="absmiddle"/> <a href="/finder/places.php?q={$gridref}"><b>Places near {$gridref}</b></a></li>
	<li><img src="{$static_host}/img/links/20/no-photos.png" width="20" height="20" alt="no photos icon" align="absmiddle"/> <a title="Empty Squares" href="/squares.php?gridref={$gridref}&amp;type=without">View list of nearby <b>squares without images</b></a> or <a title="Few Squares" href="/squares.php?gridref={$gridref}&amp;type=few">without many images</a></li>
	<li><img src="{$static_host}/img/links/20/checksheet.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">View a <b>printable check sheet</b> for {if strlen($gridrefraw) < 5}{$gridrefraw|escape:'html'}{else}{$gridref}{/if}</a><br/><br/></li>
	
	{if $gridref6}
		<li><img src="{$static_host}/img/links/20/centi.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/> <a href="/gridref/{$gridref}?viewcenti={$gridref6}">image(s) <b>taken in {$gridref6}</b></b></a> / <span class="nowrap"><a href="/gridref/{$gridref}?centi={$gridref6}">of <b>subjects in {$gridref6}</b></a> (if any)</span><br/><br/></li>
	{/if}

	<li style="list-style-type:none">Coverage Maps: <img src="{$static_host}/img/links/20/map.png" width="20" height="20" alt="map icon" align="absmiddle"/> <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}"><b>Coverage</b></a>, {if $hectad && $hectad_row}<img src="{$static_host}/img/links/20/mosaic.png" width="20" height="20" alt="mosaic icon" align="absmiddle"/> <a title="View Mosaic for {$hectad_row.hectad}, completed {$hectad_row.last_submitted}" href="/maplarge.php?t={$hectad_row.largemap_token}" style="background-color:yellow">Photo Mosaic</a>, {/if}
	<img src="{$static_host}/img/links/20/depth.png" width="20" height="20" alt="depth icon" align="absmiddle"/> <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}&amp;depth=1"><b>Depth</b></a>, <img src="{$static_host}/img/links/20/maprecent.png" width="20" height="20" alt="recent icon" align="absmiddle"/> <a href="/mapbrowse.php?t={$map_token}&amp;gridref_from={$gridref}&amp;recent=1"><b>Recent Only</b></a></li>
	
	<li style="list-style-type:none">Draggable Maps: 
		<img src="{$static_host}/img/links/20/mapper.png" width="20" height="20" alt="draggable icon" align="absmiddle"/> 
		<a href="/mapper/combined.php#13/{$lat}/{$long}"><b>Coverage Map</b></a><sup style="color:red">Updated!</sup>
	</li>
	
	<li style="list-style-type:none">Interactive Map: <img src="{$static_host}/img/links/20/clusters.png" width="20" height="20" alt="clusters icon" align="absmiddle"/> <a href="/browser/#!/grid_reference+%22{$gridref}%22/display=map_dots/pagesize=100"><b>Browser Map</b></a><br/><br/></li>

	<li style="list-style-type:none"><a href="/gridref/{$hectad}">Page for <b>Hectad {$hectad}</b></a></li>


</ul>

<form method="get" action="/search.php">
	<input type="hidden" name="form" value="location"/>
	<div class="interestBox" style="margin-top:5px; margin-bottom:10px">
	<b>Search local images</b>:  
	<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
	<input type="submit" value="Search"/>
	<input type="hidden" name="location" value="{$gridref}"/>
	<input type="radio" name="distance" value="1" checked id="d1"/><label for="d1">In {$gridref} only</label> /
	<input type="radio" name="distance" value="3" id="d3"/><label for="d3">inc. surrounding squares</label>
	<input type="hidden" name="do" value="1"/>
	</div> 
</form>	

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
<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></div>
{/if}
</div>
</div>
</div>
{/if}



	{if $title}
		{assign var="urltitle" value=$title|escape:'url'}
	{else}
		{assign var="urltitle" value=$gridrefraw|escape:'url'}
	{/if}

<div style="position:relative; float:left; width:40%;">

<b>Mapping Websites</b>:
<ul style="margin-bottom:0px">
	{if $square->reference_index eq 1}
		<li>{getamap gridref=$gridrefraw text="Geograph Map Popup"}</li>
		<li>{external href="http://www.old-maps.co.uk/maps.html?txtXCoord=`$square->nateastings`&amp;txtYCoord=`$square->natnorthings`" text="old-maps.co.uk"}</li>
		<li>{external href="http://maps.nls.uk/geo/find/#zoom=13&lat=$lat&lon=$long&layers=102" text="maps.nls.uk"}</li>
		<li>{external href="http://www.nearby.org.uk/magic-opener.php?startTopic=maggb&amp;xygridref=`$square->nateastings`,`$square->natnorthings`&amp;startscale=10000" text="magic.defra.gov.uk"}{if $gridref6} ({external href="http://www.nearby.org.uk/magic-opener.php?startTopic=maggb&xygridref=`$square->nateastings`,`$square->natnorthings`&startscale=5000" text="closer"}){/if}</li> 
		<li>{external href="http://www.streetmap.co.uk/newmap.srf?x=`$square->nateastings`&amp;y=`$square->natnorthings`&amp;z=3&amp;sv=`$square->nateastings`,`$square->natnorthings`&amp;st=OSGrid&amp;lu=N&amp;tl=[$urltitle]+from+geograph.org.uk&amp;ar=y&amp;bi=background=$self_host/templates/basic/img/background.gif&amp;mapp=newmap.srf&amp;searchp=newsearch.srf" text="streetmap.co.uk"}</li> 
		
		<li>{external href="http://wtp2.appspot.com/wheresthepath.htm?lat=$lat&amp;lon=$long" text="Where's the path?"}</li>
		
	{/if}
	<li>{external href="http://www.openstreetmap.org/?mlat=$lat&amp;mlon=$long&amp;zoom=14" text="OpenStreetMap.org"} ({external href="http://www.openstreetmap.org/?mlat=$lat&amp;mlon=$long&amp;zoom=14&amp;layers=C" text="Cycling"})</li>


	<li>{external title="Open in Google Maps" href="http://www.google.co.uk/maps/place/`$lat`,`$long`/`$lat`,`$long`,11z/" text="Google Maps"}</li>
	{if $id}
		<li><a title="Open in Google Earth" href="{$self_host}/photo/{$id|escape:'url'}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external href="https://www.bing.com/maps?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1&amp;mapurl=`$self_host`/photo/`$id`.kml" text="Bing Maps" title="detailed aerial photography from bing.com"}</li>
		<li>{external href="http://gokml.net/maps?q=`$self_host`/photo/`$id`.kml" text="ClassyGMaps" title="Google Maps view via ClassyGMaps"}</li>
	{else}
		<li><a title="Open in Google Earth" href="http://www.nearby.org.uk/googleEarth.kml.php?lat={$lat}&amp;long={$long}&amp;p={$gridrefraw}" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> (Google Earth)</li>
		<li>{external href="https://www.bing.com/maps?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1" text="Bing Maps" title="detailed aerial photography from bing.com"}</li>
		<li>{external href="http://gokml.net/maps?q=`$lat`,`$long`&amp;ll=`$lat`,`$long`&amp;z=11" text="ClassyGMaps" title="Google Maps view via ClassyGMaps"}</li>
	{/if}

</ul>
</div>
<div style="position:relative; float:left; width:40%;">
{if $square->reference_index eq 1}
	<b>Historic databases</b>
	<ul>
		<li>{external href="http://www.pastscape.org.uk/SearchResults.aspx?rational=m&mapist=os&&mapigrn=`$square->natnorthings`&mapigre=`$square->nateastings`&mapisa=1000&sort=2&recordsperpage=10" text="pastscape.org.uk" title="historic environment database via pastscape.org.uk"} (England Only)</li>
		<li>{external href="https://canmore.org.uk/site/search/result?LOCAT_XY_RADIUS_M=3000&LOCAT_X_COORD=`$square->nateastings`&LOCAT_Y_COORD=`$square->natnorthings`" text="canmore.org.uk" title="historic environment database via canmore.org.uk"}<br/>
		or {external href="http://pastmap.org.uk/?zoom=8&lonlat=lon=`$square->nateastings`,lat=`$square->natnorthings`" text="pastmap.org.uk"} (Scotland Only)</li>
		<li>{external href="http://map.coflein.gov.uk/index.php?action=do_advanced&ngr=`$gridrefraw`&radiusm=3000&submit=Search" text="map.coflein.gov.uk" title="historic environment database via map.coflein.gov.uk"} (Wales Only)</li>
	</ul>
{/if}


<b>What's nearby?</b>
<ul>
	{if $image_taken}
		{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;MONTH=%m&amp;YEAR=%Y"}
		<li>{external href="http://www.weatheronline.co.uk/cgi-bin/geotarget?LAT=`$lat`&amp;LON=`$long``$imagetakenurl`" text="weatheronline.co.uk" title="weather at the time this photo was taken from weatheronline.co.uk"}</li>
	{/if}
	{if $dblock}
		<li>{external href="http://webarchive.nationalarchives.gov.uk/20120320232950/http://www.bbc.co.uk/history/domesday/dblock/`$dblock`" text="bbc.co.uk/domesday" title="Domesday Reloaded via BBC History"} 
			<small>(<a href="/finder/dblock.php?gridref={$gridref|escape:'url'}">Geograph D-Block Viewer</a>)</small>
		</li>
	{/if}

	{if $square->reference_index eq 1}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="geocaching.com" title="Geocaches from geocaching.com"}</li>
			<li>{external title="Trigpoints from trigpointing.uk" href="http://trigpointing.uk/trigtools/find.php?t=`$gridrefraw`" text="trigpointing.uk"}</li>
	{else}
			<li>{external href="http://www.geocaching.com/seek/nearest.aspx?lat=`$lat`&amp;lon=`$long`" text="Geocaches" title="Geocaches from geocaching.com"}</li>
			<li>{external href="http://www.trigpointing-ireland.org.uk/gridref.php?g=`$square->grid_reference`" text="trigpoints" title="Trigpoints from trigpointing-ireland.org.uk"}</li>
	{/if}
</ul>
</div>
<br style="clear:both"/>
<hr/>
<b>Even more links</b>: &nbsp;
	{if $square->reference_index eq 1}
		via {external title="find local features and maps with wikimedia" href="http://tools.wmflabs.org/os/coor_g/?params=`$gridrefraw`_region%3AGB_scale%3A25000" text="wikimedia geohack"} &nbsp; &middot; &nbsp;
		via {external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`" text="nearby.org.uk"} 
	{else}
		via {external title="find local features and maps with wikimedia" href="http://tools.wmflabs.org/os/coor_g/?params=`$lat_abs`_`$nl`_`$long_abs`_`$el`_type:city_region:IE" text="wikimedia geohack"} &nbsp; &middot; &nbsp;
		via {external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`+OSI" text="nearby.org.uk"}
	{/if}

{/if}
<hr/>
<form action="/location.php" method="get">
<div>
	<h3>Jump to another location</h3>
	<label for="gridref">Enter grid reference (e.g. SY9582)</label>
	<input id="gridref" type="text" name="gridref" value="{$gridrefraw|escape:'html'}" size="8"/>
	<input type="submit" name="setref" value="Show &gt;"/>

	
	<br/>
	<i>or</i><br/>

	<label for="gridsquare">Choose grid reference</label>
	<select id="gridsquare" name="gridsquare">
		{html_options options=$prefixes selected=$gridsquare}
	</select>
	<label for="eastings">E</label>
	<select id="eastings" name="eastings">
		{html_options options=$kmlist selected=$eastings}
	</select>
	<label for="northings">N</label>
	<select id="northings" name="northings">
		{html_options options=$kmlist selected=$northings}
	</select>

	<input type="submit" name="setpos" value="Show &gt;"/>
</div>
</form>



<br/>
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}

{/if}
{include file="_std_end.tpl"}
{/dynamic}
