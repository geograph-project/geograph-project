{assign var="page_title" value="Square Listing"}
{include file="_std_begin.tpl"}
{if $square->grid_reference}
<div style="float:right">Alternate Versions: <a title="show a print friendly page you can use&#13;&#10;to check off the squares you photograph&#13;&#10;while in the field" href="/mapsheet.php?t={$map_token}&amp;gridref_from={$square->grid_reference}">check sheet</a> or <a href="/gpx.php?gridref={$square->grid_reference}&amp;distance={$d}&amp;type={$type}&amp;submit=1" class="xml-gpx">GPX</a></div>
{/if}

<h2>{$searchdesc|escape:"html"}</h2>

    <form method="get" action="{$script_name}">
    <p>... within <input type="text" name="distance" value="{$d|escape:'html'}" size="2" maxlength="2"/>km 
    of <input type="text" name="gridref" value="{$square->grid_reference}" size="6" maxlength="6"/>
    <select name="type">
    	{html_options options=$types selected=$type}
    </select> photographs
   
    <input type="submit" value="Go"/></p></form>


{if $square->grid_reference}
	{if $overview}
	  <div style="float:right; text-align:center; width:{$overview_width+30}px; position:relative; margin-right:20px">
		{include file="_overview.tpl"}
	  </div>
	{/if}

<p><small>Click a column header to change the sort order, hover over a placename for the <a href="/faq.php#counties">historic county</a>.</small></p>
	
<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
<thead><tr>
<td>&nbsp;</td>
<td>Square</td>
<td>Images</td>
<td>km</td>
<td>Placename</td>

</tr></thead>
<tbody>

{foreach from=$data item=row name=loop}
	<tr><td sortvalue="{$smarty.foreach.loop.iteration}"><a href="/gridref/{$row.grid_reference}/links"><img src="http://{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/></a></td>
	<td><a href="/gridref/{$row.grid_reference}">{$row.grid_reference}</a></td>
	<td align="right">{$row.imagecount}</td>
	<td align="right">{math equation="sqrt(d)" d=$row.dist_sqd assign="d"}{$d|thousends}</td>
	{if $row.place}<td>{place place=$row.place}</td>{/if}</tr>
{foreachelse}
	<i>nothing to list</i>
{/foreach}
</tbody>
</table>
 
 <br style="clear:both"/>
 
{if $square->reference_index eq 1}
<div class="copyright">Great Britain locations based upon 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.<br/>
<br/>
and enhanced with the Gazetteer of British Place Names, &copy; Association of British Counties, used with permission.</div>
{/if}

<script src="{"/sorttable.js"|revision}"></script>
{/if}
{include file="_std_end.tpl"}