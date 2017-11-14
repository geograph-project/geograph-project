{assign var="page_title" value="Photographed Squares"}
{include file="_std_begin.tpl"}

<div style="float:right">Alternate Versions: <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$square->grid_reference}">check sheet</a> or <a href="/gpx.php?gridref={$square->grid_reference}&amp;distance={$d}&amp;type={$type}&amp;submit=1" class="xml-gpx">GPX</a></div>

<h2>Photographed Squares</h2>
<h3 style="color:red">{$searchdesc|escape:"html"}</h3>

	{if $overview}
	  <div style="float:right; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
	  </div>
	{/if}

<p><small>(hover over a placename for the <a href="/faq.php#counties">historic county</a>)</small></p>

<ul>
{foreach from=$data item=row}
	<li><a href="/gridref/{$row.grid_reference}/links"><img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/></a> <a href="/gridref/{$row.grid_reference}">{$row.grid_reference}</a> <small>[{$row.imagecount}]</small> {if $row.place}{place place=$row.place}{/if}</li>
{foreachelse}
	<li><i>nothing to list</i></li>
{/foreach}
</ul>
 
 <br style="clear:both"/>
 
{if $square->reference_index eq 1}
<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
and enhanced with the Gazetteer of British Place Names, &copy; Association of British Counties, used with permission.</div>
{/if}

{include file="_std_end.tpl"}