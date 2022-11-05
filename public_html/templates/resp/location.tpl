{dynamic}
{assign var="page_title" value="$gridrefraw :: Links"}
{include file="_std_begin.tpl"}

<style>
{literal}
*{
	box-sizing:border-box;
}
{/literal}
</style>

<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px">
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
<h2>Links for <a href="/gridref/{$gridrefraw|escape:'url'}">{if $gridref6}{$gridref6}{else}{$gridref}{/if}</a></h2>
<p><b><a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=full&amp;orderby=submitted&amp;do=1" title="Show a search of images in this square">{$square->imagecount} images</a></b></p>
<p>near to ##########, ########</p>
<p>
	{if $place}
	<div style="font-size:0.8em;border-bottom:1px solid silver;margin-bottom:2px">{place place=$place}</div>
	{/if}
  </p>



{if $getamap}
	<div class="interestBox">
		<b>Note: Get-a-Map is currently unavailable, you have been taken to our location links page so can open alternative mapping. </b> read more: <a href="http://www.getamap.ordnancesurveyleisure.co.uk/">www.getamap.ordnancesurveyleisure.co.uk</a>
	</div>
{/if}



<div class="threecolsetup">
 
 {if $rastermap->enabled}
  <div class="threecolumn">
    <h3>Location map</h3>
	<div style="width:{$rastermap->width}px; font-size:0.8em; margin:auto">
	{$rastermap->getImageTag($gridrefraw)}
	<div style="color:gray"><small>{$rastermap->getFootNote()}</small></div>
	{$rastermap->getScriptTag()}	
	</div>
  </div>
	{/if}
  
  {if $overview}  
  <div class="threecolumn">
    <h3>Coverage map</h3>
		<ul>
    <li><a href="/mapper/combined.php#13/{$lat}/{$long}">Coverage Map</a></li>
    <li><a href="/browser/#!/grid_reference+%22{$gridref}%22/display=map_dots/pagesize=100">Browser Map</a></li>
    </ul>
<div style="clear:both; margin:auto; width:{$overview_width+30}px">

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
<ul>
<li><a title="Download GPX file" href="/gpx.php?gridref={$gridref}" class="xml-gpx">GPX</a> <a title="Download GPX" href="/gpx.php?gridref={$gridref}">Download a GPX coverage file around this area</a></li>
{if $id}
		<li><a title="Download KML file" href="{$self_host}/photo/{$id|escape:'url'}.kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> <a title="Download KML file" href="{$self_host}/photo/{$id|escape:'url'}.kml" type="application/vnd.google-earth.kml+xml">Download a KML file for this photo</a> (eg for Google Earth Pro)</li>
		{else}
		<li><a title="Download KML file" href="http://www.nearby.org.uk/googleEarth.kml.php?lat={$lat}&amp;long={$long}&amp;p={$gridrefraw}" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a> <a title="Download KML file" href="http://www.nearby.org.uk/googleEarth.kml.php?lat={$lat}&amp;long={$long}&amp;p={$gridrefraw}" type="application/vnd.google-earth.kml+xml">Download a KML coverage file around this area</a> (eg for Google Earth Pro)</li>
		{/if}
</ul>
</div>
{/if}

  <div class="threecolumn">
    <h3>Coordinate Information</h3>
    <p>{if $square->reference_index eq 1}OSGB36{else}Irish{/if}: {getamap gridref=$gridrefraw text=$gridrefraw} [{$square->precision} m precision]</p>
    <p>Easting/Northing: {$square->nateastings}, {$square->natnorthings} {if $square->reference_index eq 2}OSI{/if} [meters]</p>
    <p>WGS84: <abbr class="latitude" title="{$lat|string_format:"%.6f"}">{$latdm}</abbr> <abbr class="longitude" title="{$long|string_format:"%.6f"}">{$longdm}</abbr></p>
    <p>Lat/Long (decimal): {$lat|string_format:"%.6f"}, {$long|string_format:"%.6f"}</p>

	<h3>Jump to another location</h3>
<form action="/location.php" method="get">
<p>
	<label for="gridref">Enter grid reference (e.g. SY9582):</label><br>
	<input id="gridref" type="text" name="gridref" value="{$gridrefraw|escape:'html'}" size="8"/>
	<input type="submit" name="setref" value="Show &gt;"/>
</p>
	<p><i>or</i><p>
<p>
	<label for="gridsquare">Choose grid reference:</label><br>
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
  </p>
</form>
</div>

  
  <div class="threecolumn">
  	<h3>Geograph links</h3>
  	<h4>Search local images</h4>   
  	<form method="get" action="/search.php">
		<input type="hidden" name="form" value="location"/>
		<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
    <br>
		<input type="hidden" name="location" value="{$gridref}"/>
		<input type="radio" name="distance" value="1" checked id="d1"/><label for="d1">In {$gridref} only</label>
		<input type="radio" name="distance" value="3" id="d3"/><label for="d3">inc. surrounding squares</label>
		<input type="hidden" name="do" value="1"/>
		</form>	
    
    <h4>This gridsquare</h4>
    <ul>
    <li><img src="{$static_host}/img/links/20/submit.png" width="20" height="20" alt="submit icon" align="absmiddle"/><a href="{if $user->submission_method == 'submit2'}/submit2.php#gridref={$gridrefraw|escape:'url'}{else}/submit.php?gridreference={$gridrefraw|escape:'url'}{/if}">Submit your own picture for {$gridref}</a></li>
    <li><img src="{$static_host}/img/links/20/browse.png" width="20" height="20" alt="browse icon" align="absmiddle"/><a href="/gridref/{$gridrefraw|escape:'url'}">View the Grid Square page</a></li>
    {if $square->imagecount > 2}<li><img src="{$static_host}/img/links/20/grid.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/> <a href="/gridref/{$gridref}?by=1">View a breakdown of images in {$gridref}</a></li>{/if}
    {if $square->imagecount > 2}<li><img src="{$static_host}/img/links/20/slideshow.png" width="20" height="20" alt="slideshow icon" align="absmiddle"/><a href="/search.php?gridref={$gridref}&amp;distance=1&amp;displayclass=slide&amp;orderby=submitted&amp;do=1" title="View images in a Slide Show">View a slideshow for the {$square->imagecount} images in this square</a></li>{/if}
    {if $viewpoint_count}<li><img src="{$static_host}/img/links/20/lookout.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a href="/gridref/{$gridref}?takenfrom">view {$viewpoint_count} image{if $viewpoint_count != 1}s{/if} taken <i>from</i> {$gridref}</a></li>{/if}
    <li><img src="{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/> <a title="search for nearby images to {$gridref}" href="/browser/#!/grid_reference+%22{$gridref}%22">Open the Browser for images in {$gridref}</a></li>
    	{if $enable_forums}
		<li><img src="{$static_host}/img/links/20/discuss.png" width="20" height="20" alt="discussion icon" align="absmiddle"/> 
		{if $discuss}
			There {if $totalcomments == 1}is 1 post{else}are {$totalcomments} posts{/if} in a 
			<a href="/discuss/index.php?gridref={$gridref}">discussion about {$gridref}</a>
		{else}
			{if $user->registered} 
				<a href="/discuss/index.php?gridref={$gridref}#newtopic">Start a discussion about {$gridref}</a>
			{else}
				<a href="/login.php">login</a> to start a discussion about this square</a>
			{/if}
		{/if}</li>
	{/if}
  <li>{getamap gridref=$gridrefraw text="Geograph Map Popup"}</li>
    </ul>
    
      
  {if $gridref6}
  <h4>In centisquare {$gridref6}</h4>
  <ul>
		<li><img src="{$static_host}/img/links/20/centi.png" width="20" height="20" alt="centisquare icon" align="absmiddle"/> <a href="/gridref/{$gridref}?viewcenti={$gridref6}">Image(s) taken in {$gridref6}</a></li>
    <li><a href="/gridref/{$gridref}?centi={$gridref6}">Subjects in {$gridref6} (if any)</a></li>
    </ul>
	{/if}


  <h4>Surrounding area</h4>
  <ul>
  {if strlen($gridrefraw) < 5}
		<li><img src="{$static_host}/img/links/20/hectad.png" width="20" height="20" alt="hectad icon" align="absmiddle"/> <a title="First Geographs within {$gridrefraw|escape:'html'}" href="/search.php?first={$gridrefraw|escape:'url'}">Find <b>First Geographs for hectad</b> {$gridrefraw|escape:'html'}</a></li>
	{/if}
	<li><img src="{$static_host}/img/links/20/search.png" width="20" height="20" alt="search icon" align="absmiddle"/> <a title="search for nearby images to {$gridref}" href="/search.php?q={$gridref}">Search for images near {$gridref}</a>
	{if $gridref6}
		(<a href="/search.php?q={$gridref6}">near {$gridref6}</a>)
	{/if}	
	</li>
	<li><img src="{$static_host}/img/links/20/place.png" width="20" height="20" alt="places icon" align="absmiddle"/> <a href="/finder/places.php?q={$gridref}">Places near {$gridref}</a></li>
	<li><img src="{$static_host}/img/links/20/no-photos.png" width="20" height="20" alt="no photos icon" align="absmiddle"/> <a title="Empty Squares" href="/squares.php?gridref={$gridref}&amp;type=without">View list of nearby squares without images</a></li>
  <li><img src="{$static_host}/img/links/20/no-photos.png" width="20" height="20" alt="no photos icon" align="absmiddle"/><a title="Few Squares" href="/squares.php?gridref={$gridref}&amp;type=few">Nearby squares with few images</a></li>
	<li><img src="{$static_host}/img/links/20/checksheet.png" width="20" height="20" alt="browse icon" align="absmiddle"/> <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$gridref}">View a printable check sheet for {if strlen($gridrefraw) < 5}{$gridrefraw|escape:'html'}{else}{$gridref}{/if}</a></li>
  <li><a href="/gridref/{$hectad}">View Hectad {$hectad}</a></li>
  </ul>

  
  
  </div>
  
  <div class="threecolumn">
    <h3>Mapping</h3>
    <h4>Google</h4>
    <ul>
    <li>{external title="Open in Google Maps" href="https://www.google.co.uk/maps/search/`$lat`,`$long`/" text="Google Maps"}</li>
    <li>{external title="Open in Google Earth" href="https://earth.google.com/web/search/`$lat`,`$long`/" text="Google Earth for Web"}</li>
    {if $id}
				<li>{external href="http://gokml.net/maps?q=`$self_host`/photo/`$id`.kml" text="ClassyGMaps" title="Google Maps view via ClassyGMaps"}</li>
		{else}
				<li>{external href="http://gokml.net/maps?q=`$lat`,`$long`&amp;ll=`$lat`,`$long`&amp;z=11" text="ClassyGMaps" title="Google Maps view via ClassyGMaps"}</li>
		{/if}
		</ul>
    <h4>OpenStreetMap</h4>
    <ul>
    <li>{external href="http://www.openstreetmap.org/?mlat=$lat&amp;mlon=$long&amp;zoom=14" text="OpenStreetMap"} {external href="http://www.openstreetmap.org/?mlat=$lat&amp;mlon=$long&amp;zoom=14&amp;layers=C" text="(Cycling)"} {external href="http://www.openstreetmap.org/?mlat=$lat&amp;mlon=$long&amp;zoom=14&amp;layers=T" text="(Transport)"}</li>
    <li>{external href="https://www.opencyclemap.org/?zoom=14&amp;lat=$lat&amp;lon=$long" text="OpenCycleMap"}</li>
    <li>{external href="https://opentopomap.org/&#35;map&equals;14/$lat/$long" text="OpenTopoMap"}</li>
    <li>{external href="https://www.openrailwaymap.org/?style=standard&amp;lat=$lat&amp;lon=$long&amp;zoom=14" text="OpenRailwayMap"}</li>
    <li>{external href="https://map.openseamap.org/?zoom=14&amp;lat=$lat&amp;lon=$long" text="OpenSeaMap"}</li>
    <li>{external href="https://openinframap.org/&#35;14/$lat/$long" text="OpenInfrastructureMap"}</li>
    <li>{external href="http://gk.historic.place/historische_objekte/translate/en/index-en.html?zoom=14&amp;lat=$lat&amp;lon=$long" text="Historical Objects"}</li>
    <li>{external href="https://hiking.waymarkedtrails.org/&#35;?map=14/$lat/$long" text="Waymarked Trails"} {external href="https://cycling.waymarkedtrails.org/&#35;?map=14/$lat/$long" text="(Cycling)"}</li>
    <li>{external href="https://www.opensnowmap.org/&#35;map&equals;14/$long/$lat&amp;b&equals;snowmap&amp;m&equals;false&amp;h&equals;false" text="OpenSnowMap"}</li>
    
    </ul>
    {if $square->reference_index eq 1}
    <h4>Historic mapping</h4>
    <ul>
    <li>##Broken link##{external href="http://www.old-maps.co.uk/maps.html?txtXCoord=`$square->nateastings`&amp;txtYCoord=`$square->natnorthings`" text="old-maps.co.uk"}</li>
    <li>{external href="https://maps.nls.uk/geo/find/marker/#zoom=13&lat=$lat&lon=$long&f=1&z=1&marker=$lat,$long" text="maps.nls.uk"}
			({external href="https://maps.nls.uk/geo/explore/side-by-side/#zoom=16&lat=$lat&lon=$long&layers=6&right=BingHyb" text="side by side viewer"})</li>
      <li>{external href="http://wtp2.appspot.com/wheresthepath.htm?lat=$lat&amp;lon=$long" text="Where's the path?"}</li>
    </ul>
    {/if}
    <h4>Other</h4>
    <ul>
    <li>{if $id}{external href="https://www.bing.com/maps?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1&amp;mapurl=`$self_host`/photo/`$id`.kml" text="Bing Maps" title="detailed aerial photography from bing.com"}{else}{external href="https://www.bing.com/maps?v=2&amp;cp=`$lat`~`$long`&amp;style=h&amp;lvl=14&amp;tilt=-90&amp;dir=0&amp;alt=-1000&amp;encType=1" text="Bing Maps" title="detailed aerial photography from bing.com"}{/if}</li>
    {if $square->reference_index eq 1}
				<li>{external href="http://www.nearby.org.uk/magic-opener.php?startTopic=maggb&amp;xygridref=`$square->nateastings`,`$square->natnorthings`&amp;startscale=10000" text="magic.defra.gov.uk"}{if $gridref6} ({external href="http://www.nearby.org.uk/magic-opener.php?startTopic=maggb&xygridref=`$square->nateastings`,`$square->natnorthings`&startscale=5000" text="closer"}){/if}</li> 
		<li>{external href="https://streetmap.co.uk/map?X=`$square->nateastings`&Y=`$square->natnorthings`&amp;A=Y&amp;Z=110`" text="streetmap.co.uk"}</li> 
		{/if}
    <li>{external href="https://explore.osmaps.com/?lat=$lat&amp;lon=$long&amp;zoom=14" text="OS Maps"}</li>
    </ul>
  </div>
  <div class="threecolumn">
  <h3>Local links</h3>
    <h4>Nearby information</h4>
    <ul>
    	{if $image_taken}
		{assign var="imagetakenurl" value=$image_taken|date_format:"&amp;MONTH=%m&amp;YEAR=%Y"}
		<li>{external href="http://www.weatheronline.co.uk/cgi-bin/geotarget?LAT=`$lat`&amp;LON=`$long``$imagetakenurl`" text="weatheronline.co.uk" title="weather at the time this photo was taken from weatheronline.co.uk"}</li>
    {else}
    <li>{external href="http://www.weatheronline.co.uk/cgi-bin/geotarget?LAT=`$lat`&amp;LON=`$long``$imagetakenurl`" text="weatheronline.co.uk" title="Current weather near to $gridref weatheronline.co.uk"}</li>
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

  
  {if $square->reference_index eq 1}
    <h4>Historic Databases</h4>
    <ul>
		
		<li>{external href="https://canmore.org.uk/site/search/result?LOCAT_XY_RADIUS_M=3000&LOCAT_X_COORD=`$square->nateastings`&LOCAT_Y_COORD=`$square->natnorthings`" text="canmore.org.uk" title="historic environment database via canmore.org.uk"}</li>
    <li>{external href="http://www.pastscape.org.uk/SearchResults.aspx?rational=m&mapist=os&&mapigrn=`$square->natnorthings`&mapigre=`$square->nateastings`&mapisa=1000&sort=2&recordsperpage=10" text="pastscape.org.uk" title="historic environment database via pastscape.org.uk"} (England Only)</li>
		<li>{external href="http://pastmap.org.uk/?zoom=8&lonlat=lon=`$square->nateastings`,lat=`$square->natnorthings`" text="pastmap.org.uk"} (Scotland Only)</li>
		<li>{external href="http://map.coflein.gov.uk/index.php?action=do_advanced&ngr=`$gridrefraw`&radiusm=3000&submit=Search" text="map.coflein.gov.uk" title="historic environment database via map.coflein.gov.uk"} (Wales Only)</li>
    </ul>

  {/if}
  
  <h4>Other location links</h4>
  <ul>
  {if $square->reference_index eq 1}
		<li>{external title="find local features and maps with wikimedia" href="https://tools.wmflabs.org/geohack/en/`$lat`;`$long`_region:GB_scale:25000?pagename=Geograph" text="wikimedia geohack"}</li>
		<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`" text="nearby.org.uk"}</li>
	{else}
		<li>{external title="find local features and maps with wikimedia" href="https://tools.wmflabs.org/geohack/en/`$lat`;`$long`_region:IE-D?pagename=Geograph" text="wikimedia geohack"}</li>
		<li>{external title="find local features and maps with nearby.org.uk" href="http://www.nearby.org.uk/coord.cgi?p=`$square->nateastings`+`$square->natnorthings`+OSI" text="nearby.org.uk"}</li>
	{/if}
  </ul>
  
  
</div>  
</div>


	{if $rastermap->enabled}
		<br style="clear:both"/>
		{$rastermap->getFooterTag()}
	{/if}

<!--Break to ensure footer is pushed below columns-->
<br style="clear:both"/>

{include file="_std_end.tpl"}
{/dynamic}
