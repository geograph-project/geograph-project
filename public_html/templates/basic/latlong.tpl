{assign var="page_title" value="WGS84 Lat/Long to Grid Reference"}
{include file="_std_begin.tpl"}
{dynamic}
	 <h3>WGS84 Lat/Long to Grid Reference Conversion</h3> 
	 
{if !$e && !$n}
	<p>This page will convert Latitude and Longitude (assuming WGS84 datum) into Rectlinear coordinates as suitable for use on this site. (Handles Both Great Britain and Irish Grids)</p> 
{/if}

	<div style="float:left;position:relative;width:200px;height:200px;margin-left:10px;margin-right:10px;text-align:center;background:#dddddd;">
	<form action="{$script_name}"> 
	<h4>Decimal Degrees</h4>
	<table cellpadding="3" cellspacing="0"> 

	  <tr> 
		 <td align="right">lat</td> 
		 <td><input type="text" name="lat" size="15" value="{$lat}"/></td> 
	  </tr> 
	  <tr> 
		 <td align="right">long</td> 
		 <td><input type="text" name="long" size="15" value="{$long}"/></td> 
	  </tr>
	</table>
	<p align="center"><input type="submit" name="From" value="convert"/></p>
	</form>
	</div>
	
	<div style="float:left;position:relative;width:450px;height:200px;margin-left:10px;margin-right:10px;text-align:center;background:#dddddd;">
	<form action="{$script_name}"> 

	<h4>Degrees, Minutes and Seconds</h4>
	<table border="0" align="center" cellspacing="0" cellpadding="3"> 
		  <tr> 
			 <td>lat</td> 
			 <td align="center">
				<input type="radio" name="ns" value="N"{if $nl == 'N'} checked="checked"{/if}/>N<br/><input
				type="radio" name="ns" value="S"{if $nl == 'S'} checked="checked"{/if}/>S</td> 
			 <td>deg<input type="text" size="8" name="lat" value="{$yd}"/>
				min<input type="text" size="8" name="latm" value="{$ym}"/> sec<input type="text" size="8"
				name="lats" value="{$ys}"/> </td> 
		  </tr> 
		  <tr> 
			 <td>long</td> 
			 <td align="center">
				<input type="radio" name="ew" value="W"{if $el == 'W'} checked="checked"{/if}/>W&nbsp;<input
				type="radio" name="ew" value="E"{if $el == 'E'} checked="checked"{/if}/>E</td> 
			 <td>deg<input type="text" size="8" name="long" value="{$xd}"/>
				min<input type="text" size="8" name="longm" value="{$xm}"/> sec<input type="text" size="8"
				name="longs" value="{$xs}"/> </td> 
		  </tr> 
	</table>
	<p align="center"><input type="submit" name="From" value="convert"/></p>
	</form>
	</div>
	<br style="clear:both"/>
	<div style="text-align:center;margin-top:10px;padding-top:0px;padding-bottom:10px;margin-left:10px;margin-right:10px;width:670px;padding-top:10px;background:#dddddd;">
	<form action="{$script_name}" style="display:inline"> 
	or paste in the string from <b>the old style</b> Multimap.com: <br/>
		<input type="text" name="multimap" size=40/>
		<input type="submit" name="From" value="convert"/><br/>
	<small>eg: "<b>Lat</b>: 54:32:40N (54.5445) <b>Lon</b>: 6:49:22W (-6.8228)"</small>
	</form></div>
	<div style="text-align:center;margin-top:10px;padding-top:0px;padding-bottom:10px;margin-left:10px;margin-right:10px;width:670px;padding-top:10px;background:#dddddd;">
	<form action="{$script_name}" style="display:inline"> 
	<h4>German Grid</h4>
		East: <input type="text" name="gke" size="10" />
		North: <input type="text" name="gkn" size="10" />
		<input type="submit" name="From" value="convert" />
	</form></div>

	{if $errormgs}
		<hr>
		<p>{$errormgs}{if $lat || $long}, 
		however {external href="http://www.nearby.org.uk/coord.cgi?p=`$lat`+`$long`" title="More information about this location" text="nearby.org.uk"} may understand it.
		{else}.{/if}
		</p>
	{/if}
	{if $e || $n}

		<hr>

{if $overview}
<div style="float:right; width:{$overview_width+30}px; position:relative">

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
		<div style="font-family:verdana, arial, sans serif;">
		<p>[WGS84: {$latdm} {$longdm}]</p>
		<p><b>Datum</b>: {if $datum == "osgb36"}Ordnance Survey Great Britain 1936{/if}
			{if $datum == "irish"}Irish Grid OSNI/OSI{/if}</p>
		<p><b>Easting/Northing:</b> {$e|string_format:"%d"},{$n|string_format:"%d"}{if $datum == "irish"} OSI{/if}
		<!--small><br/><b>Exact: Easting</b>: {$e} <b>Northing:</b>{$n}</small--></p>
		
		<p><b>Grid Reference</b>: {$gridref}</p>
		</div>
		 {if $place.distance}
		 <div style="color:silver">&nbsp;{if $place.distance > 3}{$place.distance-0.01} km from{else}near to{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && $place.adm1_name != $place.reference_name}, {$place.adm1_name}{/if}, {$place.reference_name}</i></small></div>{/if}

		
		<p>Where to next?</p>
		<ul>
		<li><a href="/gridref/{$gridref4}"><b>Browse</b> Pictures of {$gridref4}</a></li>
		<li><a href="/search.php?q={$gridref}"><b>Search</b> for Pictures around this location</a></li>
		<li>{if $map_token}<a href="/mapbrowse.php?t={$map_token}">Geograph <b>Map</b> around this location</a>{/if}<ul>
		{if $datum == "osgb36"}<li><a href="/mapper/?t={$map_token}&amp;gridref_from={$gridref}">Open the <span style="color:red">New!</span> <b>Draggable Map</b></a></li>{/if}
			<li>{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}</li>
		</ul></li>
		<li><a href="/submit.php?gridreference={$gridref}"><b>Submit</b> a Picture for {$gridref4} (using {$gridref} as the picture location)</a></li>
		<li><a href="/gpx.php?gridref={$gridref}">download <b>GPX</b> for this area</a><br/><br/></li>
		
		<li>{external href="http://www.nearby.org.uk/coord.cgi?p=`$e`,`$n` `$datum`" title="More info from nearby.org.uk" text="More information about this location from nearby.org.uk"}</li>
		</ul>
	{else}
		<p>See also {external href="http://www.nearby.org.uk/coord-entry.html" text="nearby.org.uk"} which offers a similar converter.</p>
	{/if}

{/dynamic}    
{include file="_std_end.tpl"}
