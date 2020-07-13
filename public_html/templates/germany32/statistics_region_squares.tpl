{assign var="page_title" value="Missing regional squares"}
{include file="_std_begin.tpl"}

{if $level < 0}
<h2>Missing squares by region</h2>

<table class="report" id="regionstat" style="font-size:8pt;">
<thead>
<tr>
	<td>Region</td>
	<td>Squares</td>
	<td>Geosquares</td>
</tr>
</thead>
<tbody>
{foreach from=$regions item=row}
<tr>
	<td>{if $row.prefix}<i>{$row.prefix}</i> {/if}<b>{$row.shortname|escape:'html'}</b></td>
	<td><a href="?region={$listlevel}_{$row.community_id}&amp;type=nophoto">{if $row.squares_mis}{$row.squares_mis}{else}&nbsp;{/if}</a></td>
	<td><a href="?region={$listlevel}_{$row.community_id}">{if $row.geosquares_mis}{$row.geosquares_mis}{else}&nbsp;{/if}</a></td>
</tr>
{/foreach}
</tbody>
</table>

{else}
<h2>{$squaretitle}{if $regionname} in {$regionname|escape:'html'}{/if}</h2>


{if count($squares)}

<table class="report"> 
<tbody>

{foreach from=$squares item=sq}
<tr><td><a title="View images for {$sq.grid_reference}" href="/gridref/{$sq.grid_reference}">{$sq.grid_reference}</a></td></tr>
{/foreach}

</tbody>
</table>

{if !$full && count($squares) == 50}
<p>This list is limited to 50 entries. <a href="/statistics/region_squares.php?region={$region}&amp;type={$type}&amp;full=1">Show complete list.</a></p>
{/if}

{/if}


{/if}



{include file="_std_end.tpl"}
