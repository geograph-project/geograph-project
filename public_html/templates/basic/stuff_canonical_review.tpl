{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}

<h2><a href="?">Canonical Category Mapping</a> :: Review</h2>

<p>This is a list of your recent suggestions, can use the Try again link to suggest a new canonical category.</p>

{dynamic}

	
	<table class="report sortable" id="events">
	<thead><tr>
		<td>Main category</td>
		<td>Canonical category</td>

	</tr></thead>
	<tbody>


	{if $list}
	{foreach from=$list item=item}
		<tr>
			<td>{$item.imageclass|escape:"html"}</td>
			<td>{$item.canonical|escape:"html"}</td>
			<td><a href="?mode=random&amp;category={$item.imageclass|escape:"url"}">Try again</a></td>
		</tr>
	{/foreach}
	{else}
		<tr><td colspan="2">- nothing to show -</td></tr>
	{/if}

	</tbody>

	</table>
	

{/dynamic}

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
