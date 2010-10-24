{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2><a href="?">Canonical Category Mapping</a> :: List</h2>

	<p>{$intro}</p>
	
	<p>Click a column header to resort the table</p>
	
	<table class="report sortable" id="events">
	<thead><tr>
		<td>Canonical Category</td>
		<td>Categories</td>
		<td>Suggestors</td>
	</tr></thead>
	<tbody>

	{if $list}
	{foreach from=$list item=item}
		<tr{if $item.users < 3} style="color:gray"{/if}>
			<td>{$item.canonical|escape:"html"}</td>
			<td align="right">{$item.cats|thousends}</td>
			<td align="right">{$item.users|thousends}</td>
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>
	


<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
