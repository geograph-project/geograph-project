{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="?">Canonical Category Mapping</a> :: List</h2>
	
	<p>{$intro}</p>

	<p>Click a column header to re-sort the table</p>
	
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

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
