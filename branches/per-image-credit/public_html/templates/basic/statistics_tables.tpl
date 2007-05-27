{assign var="page_title" value="Statistics:: $h2title"}
{include file="_std_begin.tpl"}
{if !$nosort}
<script src="/sorttable.js"></script>
{/if}

<h2>{$h2title}</h2>


{foreach from=$tables item=table}

	<h3>{$table.title}</h3>

	{if $table.headnote}
		{$table.headnote}
	{/if}

	
	{if $table.total > 0}
		{if !$nosort}
		<p><small>Click a column header to change the sort order.</small></p>
		{/if}

		<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
		<thead><tr>
		{foreach from=$table.table.0 key=name item=value}
		<td style="direction: rtl; writing-mode: tb-rl;">{$name}</td>
		{/foreach}

		</tr></thead>
		<tbody>


		{foreach from=$table.table item=row}
		<tr>
			{foreach from=$row key=name item=value}
				<td align="right">{$value}</td>
			{/foreach}
		</tr>
		{/foreach}



		</tbody>
		</table>

		{if $table.footnote}
			{$table.footnote}
		{/if}
	{else}
		<p><i>No Results to Display</i></p>
	{/if}
{/foreach}

{include file="_std_end.tpl"}
