{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2>Canonical Category Mapping</h2>
	
	<p>Click a column header to resort the table</p>
	
	<table class="report sortable" id="events">
	<thead><tr>
		<td sorted="asc">Main Category</td>
		<td>Canonical Category</td>

	</tr></thead>
	<tbody>


	{if $list}
	{foreach from=$list item=item}
		<tr>
			<td>{$item.imageclass|escape:"html"}</td>
			<td>{$item.canonical|escape:"html"}</td>
		</tr>
		
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>
	<tfoot>

	</tfoot>
	</table>


{include file="_std_end.tpl"}
