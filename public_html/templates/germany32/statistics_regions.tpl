{assign var="page_title" value="Regional Statistics"}
{include file="_std_begin.tpl"}

<h2>Regional Statistics{if $regionname} of {$regionname|escape:'html'}{/if}</h2>

{if $hstats}
<table border="1" cellpadding="4" cellspacing="0" class="statstable">
<thead>
<tr><th>Region</th><th>Images (last week)</th><th>Squares</th><th>With geographs</th><th>Hectads</th>{*<th>Myriads</th><th>Area (km<sup>2</sup>, land)</th><th>Geograph Centre</th>*}</tr>
</thead><tbody>
{foreach from=$hstats item=row}
<tr><td>{if $linkify}<a href="?region={$row.level}_{$row.community_id}">{/if}{$row.name|escape:'html'}{if $linkify}</a>{/if}</td><td>{$row.images_total|thousends} ({$row.images_thisweek|thousends})</td><td>{$row.squares_submitted|thousends} / {$row.squares_total|thousends} ({$row.percent|floatformat:"%.3f"}%)</td><td>{$row.geographs_submitted|thousends}</td><td>{$row.tenk_submitted|thousends} / {$row.tenk_total|thousends}</td>{*<td>{$row.grid_submitted}/{$row.grid_total}</td><td>{$row.area|floatformat:"%.0f"}</td><td>{if $row.centergr == "unknown"}-{else}<a href="/gridref/{$row.centergr}" title="view square {$row.centergr}">{$row.centergr}</a>, {place place=$row.place}{/if}</td>*}</tr>
{/foreach}
</tbody>
</table>
{/if}

{include file="_std_end.tpl"}
