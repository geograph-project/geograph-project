{assign var="page_title" value="WGS84 Lat/Long to Grid Reference"}
{include file="_std_begin.tpl"}
{dynamic}
<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
{/literal}
</style>
	 <h2>WGS84 Lat/Long to Grid Reference Conversion</h2> 
	 
	<p>This page will convert latitude and longitude (assuming WGS84 datum) into rectilinear coordinates suitable for use on this site (handles both Great Britain and Irish grids).</p>
  <p>See also {external href="http://www.nearby.org.uk/coord-entry.html" text="nearby.org.uk"} which offers a similar converter.</p>

<div class="threecolsetup">
	<div class="threecolumn">
 
	<h3>Decimal Degrees</h3>
	<table border="0" cellpadding="2" cellspacing="0"> 
	  <tr>
    <form action="{$script_name}"> 
		 <td align="right">lat</td> 
		 <td><input type="text" name="lat" size="15" value="{$lat|escape:'html'}"/></td> 
     <td rowspan="2"><input type="submit" name="From" value="convert"/></td>
    </tr>
    <tr>
		 <td align="right">long</td> 
		 <td><input type="text" name="long" size="15" value="{$long|escape:'html'}"/></td>
     </form>
	  </tr>
	</table> 
 
 
 
  </div>
  <div class="threecolumn">
 
 <h3>Degrees, Minutes and Seconds</h3>
	<table border="0" cellspacing="0" cellpadding="2"> 
  <form action="{$script_name}"> 
		  <tr>
			 <td rowspan="3">lat</td>
       <td class=nowrap>deg</td>
       <td><input type="text" size="6" name="lat" value="{$yd|escape:'html'}"/></td>
       <td align="center" rowspan="3"><input type="radio" name="ns" value="N"{if $nl == 'N'} checked="checked"{/if}/>N
       <br/>
       <input type="radio" name="ns" value="S"{if $nl == 'S'} checked="checked"{/if}/>S</td>
       <td rowspan="6" align="left"><input type="submit" name="From" value="convert"/></td>
      </tr>
      <tr>
       <td class=nowrap>min</td>
       <td><input type="text" size="6" name="latm" value="{$ym|escape:'html'}"/></td>
      </tr>
      <tr>
       <td class=nowrap>sec</td>
       <td><input type="text" size="6" name="lats" value="{$ys|escape:'html'}"/></td>
       </tr>
		  <tr>
			 <td rowspan="3">long</td>
       <td class=nowrap>deg</td>
       <td><input type="text" size="6" name="long" value="{$xd|escape:'html'}"/></td>
       <td align="center" rowspan="3"><input type="radio" name="ew" value="W"{if $el == 'W'} checked="checked"{/if}/>W
       <span class=nowrap><input type="radio" name="ew" value="E"{if $el == 'E'} checked="checked"{/if}/>E</span></td>
      </tr>
      <tr>
       <td class=nowrap>min</td>
       <td><input type="text" size="6" name="longm" value="{$xm|escape:'html'}"/></td>
      </tr>
      <tr>
       <td class=nowrap>sec</td>
       <td><input type="text" size="6" name="longs" value="{$xs|escape:'html'}"/></td>
       </tr>
      <tr>
      <td colspan="5">Note: The individual deg/min/sec boxes can all take decimal values too. A value like '56:34.2345' (degrees with decimal minutes) can be entered with the degrees in the deg box (56) and the minutes with decimals in the min box (34.2345).</td>
      </tr>
  </form>
	</table>

 
 
 
  </div>
  <div class="threecolumn">
 
 	<h3>String</h3>
	<table border="0" cellpadding="2" cellspacing="0">
	<form action="{$script_name}" style="display:inline">
  <tr>
  <td colspan="2">Paste in a lat/long string:</td>
  </tr>
  <tr>
	<td><input type="text" name="multimap" size=30/></td>
	<td><input type="submit" name="From" value="convert"/></td>
	</tr>
  <tr>
  <td colspan="2"><small>eg: "<b>Lat</b>: 54:32:40N (54.5445) <b>Lon</b>: 6:49:22W (-6.8228)"</small></td>
  </tr>
  </form>
  </table>
 
 
 
  </div>
</div>

<br style="clear:both"/>

<div class="threecolsetup">

	{if $errormgs}
<br><br><hr><br>
  <a name="results"></a><h2>Results</h2>
		<p>{$errormgs}{if $lat || $long}, 
		however {external href="http://www.nearby.org.uk/coord.cgi?p=`$lat`+`$long`"|escape:html title="More information about this location" text="nearby.org.uk"} may understand it.
		{else}.{/if}
		</p>
	{/if}
	
{if $e || $n}

<br><br><hr><br>
<a name="results"></a><h2>Results</h2>



	<div class="threecolumn">
  
  <h3>Coordinates</h3>
  
		<p>[WGS84: {$latdm} {$longdm}]</p>
		<p><b>Datum</b>: {if $datum == "osgb36"}Ordnance Survey Great Britain 1936{/if}
			{if $datum == "irish"}Irish Grid OSNI/OSI{/if}</p>
		<p><b>Easting/Northing:</b> {$e|string_format:"%d"},{$n|string_format:"%d"}{if $datum == "irish"} OSI{/if}
		<!--small><br/><b>Exact: Easting</b>: {$e} <b>Northing:</b>{$n}</small--></p>
		
		<p><b>Grid Reference</b>: {$gridref}</p>
		
		 {if $place.distance}
		 <div style="color:silver">&nbsp;{if $place.distance > 3}{$place.distance-0.01} km from{else}near to{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && $place.adm1_name != $place.reference_name}, {$place.adm1_name}{/if}, {$place.reference_name}</i></small></div>{/if}    
  
  
  
  </div>

	<div class="threecolumn" style="text-align:center">
  {if $overview}
  <h3>Map</h3>
  <div style="display: inline-block; vertical-align: middle; text-align:center; width:{$overview_width+30}px; margin:5px;">
	{include file="_overview.tpl"}
  <div style="color:gray"><small>Tip: Click the map to open the coverage map</small></div>
	</div>
{/if}  
  
  
  
  </div>

	<div class="threecolumn">
  

  
		<h3>Where to next?</h3>
		<ul>
		<li><a href="/gridref/{$gridref4}">Browse the <b>grid reference</b> page for {$gridref4}</a></li>
		<li><a href="/search.php?q={$gridref}"><b>Search</b> for pictures around this location</a></li>
		<li><a href="/mapper/combined.php#14/{$lat}/{$long}">Open the <b>coverage map</b></a></li>
		<li>{getamap gridref=$gridref text="Open Get&hyphen;A&hyphen;Map&trade;"}</li>
		<li>{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom"|escape:html text="Open multimap.com" title="multimap includes 1:50,000 mapping for Northern Ireland"}</li>
		<li><a href="/submit.php?gridreference={$gridref}"><b>Submit</b> a picture for {$gridref4} (using {$gridref} as the picture location)</a></li>
		<li><a href="/gpx.php?gridref={$gridref}">Download a <b>GPX</b> file for this area</a></li>
		<li>{external href="http://www.nearby.org.uk/coord.cgi?p=`$e`,`$n` `$datum`" title="More info from nearby.org.uk" text="See this location at nearby.org.uk"}</li>
		</ul>

  
  </div>






	{else}
	{/if}  
</div>
<br style="clear:both"/>

{/dynamic}    
{include file="_std_end.tpl"}
