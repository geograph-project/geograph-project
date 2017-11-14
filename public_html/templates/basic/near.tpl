{dynamic}
{assign var="page_title" value="$gridrefraw :: Links"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent h3 { padding: 5px; margin-top:0px; background-color: black; color:white}
</style>{/literal}
{if $errormsg}
	<p>{$errormsg}</p>
{else}


<dl style="float:right; margin:0px; position:relative">
<dd style="font-family:verdana, arial, sans serif; font-size:0.8em">
{if $square->reference_index eq 1}OSGB36{elseif $square->reference_index eq 2}Irish{elseif $square->reference_index eq 3}Germany, MGRS 32{elseif $square->reference_index eq 4}Germany, MGRS 33{elseif $square->reference_index eq 5}Germany, MGRS 31{/if}: {getamap gridref=$gridrefraw text=$gridrefraw} [{$square->precision}m precision]<br/>
WGS84: <span class="geo"><abbr class="latitude" title="{$lat|string_format:"%.5f"}">{$latdm}</abbr> <abbr class="longitude" 
title="{$long|string_format:"%.5f"}">{$longdm}</abbr></span>
</dd>
</dl>

<h2><img src="http://{$static_host}/img/geotag_32.png" width="32" height="32" align="absmiddle">{$gridrefraw}</h2>

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


{foreach from=$places item=place}
	{if $place.distance}
	 	{if $place.gaz == 'OS250'}
			<h3>OS 250k Gazetteer</h3>
			
			{place place=$place}
			
			<div class="copyright">Based upon 1:250,000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office,<br/>
			&copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>
	 		<br/><br/>
	 	{elseif $place.gaz == 'OS'}
			<h3>OS 50k Gazetteer</h3>
			
			{place place=$place}
			
			<div class="copyright">Based upon 1:50,000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office,<br/>
			&copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>
	 		<br/><br/>
	 	{elseif $place.gaz == 'hist'}
	 		<h3>Historic Placenames</h3>
	 		
	 		{place place=$place}
	 		
	 		<div class="copyright">Gazetteer of British Place Names, &copy; Association of British Counties, used with permission.</div>
	 		<br/><br/>
	 	{elseif $place.gaz == 'towns'}
	 		<h3>Mapping Towns</h3>
	 		
	 		{place place=$place}
	 		<br/><br/>
	 	{elseif $place.gaz == 'geonames'}
	 		<h3>GNS Database</h3>
	 	
	 		{place place=$place}
	 		
	 		<div class="copyright">Placename/Toponymic information is based on the Geographic Names Data Base, containing official standard names approved by the United States Board on Geographic Names and maintained by the National Geospatial-Intelligence Agency. More information is available at the Products and Services link at {external href="http://www.nga.mil/" text="www.nga.mil"}</div>
	 	{/if}
		
	{/if}
{/foreach}


<br style="clear:both"/>
<br/>
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}

{/if}
{include file="_std_end.tpl"}
{/dynamic}
