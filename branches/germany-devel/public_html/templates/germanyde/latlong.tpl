{assign var="page_title" value="WGS84 Länge/Breite oder Gauß-Krüger nach MGRS"}
{include file="_std_begin.tpl"}
{dynamic}
	<h3>Koordinatenkonverter von Länge/Breite (WGS84) oder Gauß-Krüger nach MGRS</h3>
	 
{if !$e && !$n}
	<p>Auf dieser Seite können Geographische Koordinaten (Länge/Breite auf dem WGS84-Ellipsoid) oder Gauß-Krüger-Koordinaten in die MGRS-Koordinaten umgewandelt werden,
	die auf Geograph Deutschland eingesetzt werden.</p> 
{/if}

	<div style="float:left;position:relative;width:200px;height:200px;margin-left:10px;margin-right:10px;text-align:center;background:#dddddd;">
	<form action="{$script_name}"> 
	<h4>Kommazahlen</h4>
	<table cellpadding="3" cellspacing="0"> 

	  <tr> 
		 <td align="right">Breite</td> 
		 <td><input type="text" name="lat" size="15" value="{$lat}"/></td> 
	  </tr> 
	  <tr> 
		 <td align="right">Länge</td> 
		 <td><input type="text" name="long" size="15" value="{$long}"/></td> 
	  </tr>
	</table>
	<p align="center"><input type="submit" name="From" value="umwandeln"/></p>
	</form>
	</div>
	
	<div style="float:left;position:relative;width:450px;height:200px;margin-left:10px;margin-right:10px;text-align:center;background:#dddddd;">
	<form action="{$script_name}"> 

	<h4>Grad, Minuten und Sekunden</h4>
	<table border="0" align="center" cellspacing="0" cellpadding="3"> 
		  <tr> 
			 <td>Breite</td> 
			 <td align="center">
				<input type="radio" name="ns" value="N"{if $nl == 'N'} checked="checked"{/if}/>N<br/><input
				type="radio" name="ns" value="S"{if $nl == 'S'} checked="checked"{/if}/>S</td> 
			 <td><input type="text" size="8" name="lat" value="{$yd}"/>°
				<input type="text" size="8" name="latm" value="{$ym}"/>' <input type="text" size="8"
				name="lats" value="{$ys}"/>" </td> 
		  </tr> 
		  <tr> 
			 <td>Länge</td> 
			 <td align="center">
				<input type="radio" name="ew" value="W"{if $el == 'W'} checked="checked"{/if}/>W&nbsp;<input
				type="radio" name="ew" value="E"{if $el == 'E'} checked="checked"{/if}/>O</td> 
			 <td><input type="text" size="8" name="long" value="{$xd}"/>°
				<input type="text" size="8" name="longm" value="{$xm}"/>' <input type="text" size="8"
				name="longs" value="{$xs}"/>" </td> 
		  </tr> 
	</table>
	<p align="center"><input type="submit" name="From" value="umwandeln"/></p>
	</form>
	</div>
	<br style="clear:both"/>
	<div style="text-align:center;margin-top:10px;padding-top:0px;padding-bottom:10px;margin-left:10px;margin-right:10px;width:670px;padding-top:10px;background:#dddddd;">
	<form action="{$script_name}" style="display:inline"> 
	Koordinaten aus anderer Quelle wie Multimap.com als freien Text übernehmen:<br/>
		<input type="text" name="multimap" size=40/>
		<input type="submit" name="From" value="umwandeln"/><br/>
	<small>Z.B.: "<b>Lat</b>: 54:32:40N (54.5445) <b>Lon</b>: 6:49:22W (-6.8228)"</small>
	</form></div>
	<div style="text-align:center;margin-top:10px;padding-top:0px;padding-bottom:10px;margin-left:10px;margin-right:10px;width:670px;padding-top:10px;background:#dddddd;">
	<form action="{$script_name}" style="display:inline"> 
	<h4>Gauß-Krüger</h4>
		Ost: <input type="text" name="gke" size="10" />
		Nord: <input type="text" name="gkn" size="10" />
		<input type="submit" name="From" value="umwandeln" />
	</form></div>

	{if $errormgs}
		<hr>
		<p>{$errormgs}</p>
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
	alt="Karte" ismap="ismap" title="Anklicken um herauszuzoomen" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
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
			{if $datum == "irish"}Irish Grid OSNI/OSI{/if}
			{if $datum == "german31"}MGRS (Zone 31){/if}
			{if $datum == "german32"}MGRS (Zone 32){/if}
			{if $datum == "german33"}MGRS (Zone 33){/if}</p>
		<p><b>Ostwert/Nordwert:</b> {$e|string_format:"%d"},{$n|string_format:"%d"}{if $datum == "irish"} OSI{/if}
		<!--small><br/><b>Exact: Easting</b>: {$e} <b>Northing:</b>{$n}</small--></p>
		
		<p><b>MGRS</b>: {$gridref}</p>
		</div>
		 {if $place.distance}
		 <div style="color:silver">&nbsp;{if $place.distance > 3}{$place.distance-0.01} km entfernt von{else}in der Nähe von{/if} <b>{$place.full_name}</b><small><i>{if $place.adm1_name && $place.adm1_name != $place.reference_name}, {$place.adm1_name}{/if}, {$place.reference_name}</i></small></div>{/if}

		
		<p>Was nun?</p>
		<ul>
		<li><a href="/gridref/{$gridref4}">Bilder in {$gridref4} <b>betrachten</b></a></li>
		<li><a href="/search.php?q={$gridref}">Bilder um diesen Ort <b>suchen</b></a></li>
		<li>{if $map_token}<a href="/mapbrowse.php?t={$map_token}">Geograph-<b>Karte</b> um diesen Ort</a>{/if}<ul>
			<li>{external href="http://www.multimap.com/maps/?zoom=15&countryCode=GB&lat=`$lat`&lon=`$long`&dp=904|#map=`$lat`,`$long`|15|4&dp=925&bd=useful_information||United%20Kingdom" text="multimap.com"}</li>
		</ul></li>
		<li><a href="/submit.php?gridreference={$gridref}">Ein Bild für {$gridref4} <b>einreichen</b> (an Position {$gridref})</a></li>
		<li><a href="/gpx.php?gridref={$gridref}"><b>GPX</b>-Datei für das Gebiet runterladen</a><br/><br/></li>
		
		</ul>
	{/if}

{/dynamic}    
{include file="_std_end.tpl"}
