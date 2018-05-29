{assign var="page_title" value="Regionalstatistik"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}" type="text/javascript"></script>

<h2>Regionalstatistik{if $regionname} von {$regionname|escape:'html'}{/if}</h2>

{if $hstats}
<p>Die Sortierung kann durch Anklicken der Spaltentitel geändert werden.</p>
<!--table border="1" cellpadding="4" cellspacing="0" class="statstable"-->
<table class="report sortable" id="regionstat" style="font-size:8pt;">
<thead>
<tr>
	<td{if $order=='shortname'} sorted="asc"{/if}>Gebiet</td>
	<td{if $order=='images_total'} sorted="desc"{/if}>Bilder</td>
	<td{if $order=='images_thisweek'} sorted="desc"{/if}>(letzte<br />Woche)</td>
	<td{if $order=='squares_total'} sorted="desc"{/if}>Quadrate</td>
	<td{if $order=='squares_submitted'} sorted="desc"{/if}>mit<br />Bildern</td>
	<td{if $order=='percent'} sorted="desc"{/if}>(%)</td>
	<td{if $order=='squares_geo'} sorted="desc"{/if}>Landquadrate</td>
	<td{if $order=='geographs_submitted'} sorted="desc"{/if}>mit<br />Geobildern</td>
	<td{if $order=='geopercent'} sorted="desc"{/if}>(%)</td>
	<td{if $order=='tenk_total'} sorted="desc"{/if}>Hectads<br />(10km&times;10km)</td>
	<td{if $order=='tenk_submitted'} sorted="desc"{/if}>mit<br />Bildern</td>
	<td sorted="none">Links</td>
{*<td>Myriads</td>
<td>Area (km<sup>2</sup>, land)</td>
<td>Geograph Centre</td>*}
</tr>
</thead><tbody>
{foreach from=$hstats item=row}
<tr>
	<td sortvalue="{$row.shortname|escape:'html'}">{if $row.prefix}<i>{$row.prefix}</i> {/if}{if $linkify}<a href="?region={$row.level}_{$row.community_id}">{else}<b>{/if}{$row.shortname|escape:'html'}{if $linkify}</a>{else}</b>{/if}</td>
	<td sortvalue="{$row.images_total}">{$row.images_total|thousends}</td>
	<td sortvalue="{$row.images_thisweek}">{$row.images_thisweek|thousends}</td>
	<td sortvalue="{$row.squares_total}">{$row.squares_total|thousends}</td>
	<td sortvalue="{$row.squares_submitted}">{$row.squares_submitted|thousends}</td>
	<td sortvalue="{$row.percent}">{$row.percent|floatformat:"%.3f"}%</td>
	<td sortvalue="{$row.squares_geo}">{$row.squares_geo|thousends}</td>
	<td sortvalue="{$row.geographs_submitted}">{$row.geographs_submitted|thousends}</td>
	<td sortvalue="{$row.geopercent}">{$row.geopercent|floatformat:"%.3f"}%</td>
	<td sortvalue="{$row.tenk_total}">{$row.tenk_total|thousends}</td>
	<td sortvalue="{$row.tenk_submitted}">{$row.tenk_submitted|thousends}</td>
	<td><a href="/search.php?region={$row.level}_{$row.community_id}&amp;orderby=submitted&amp;reverse_order_ind=1">Bildersuche</a>, <a href="/statistics/leaderboard.php?type=images&amp;region={$row.level}_{$row.community_id}">Rangliste</a></td>
{*<td>{$row.grid_submitted}/{$row.grid_total}</td>
<td>{$row.area|floatformat:"%.0f"}</td>
<td>{if $row.centergr == "unknown"}-{else}<a href="/gridref/{$row.centergr}" title="view square {$row.centergr}">{$row.centergr}</a>, {place place=$row.place}{/if}</td>*}
</tr>
{/foreach}
</tbody>
</table>
{/if}

{include file="_std_end.tpl"}