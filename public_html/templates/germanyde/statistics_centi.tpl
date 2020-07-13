{assign var="page_title" value="Centisquares"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}" type="text/javascript"></script>

<h2>Centisquare coverage</h2>

{if $rows}
<!--table border="1" cellpadding="4" cellspacing="0" class="statstable"-->
<table class="report">
<thead>
<tr>
	<td>Grid square</td>
	<td>Photographed</td>
	<td>Geographed</td>
</tr>
</thead><tbody>
{foreach from=$rows item=row}
<tr>
	<td><a href="/gridref/{$row.gridref}?by=centi">{$row.gridref}</a></td>
	<td><a href="/gridref/{$row.gridref}?by=centi">{$row.csq}</a></td>
	<td><a href="/gridref/{$row.gridref}?by=centi&amp;status=geograph">{$row.csqgeo}</a></td>
</tr>
{/foreach}
</tbody>
</table>
{/if}

{include file="_std_end.tpl"}
