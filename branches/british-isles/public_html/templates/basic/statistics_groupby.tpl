{assign var="page_title" value="Statistics:: $h2title"}
{include file="_std_begin.tpl"}
{if !$nosort}
<script src="{"/sorttable.js"|revision}"></script>
{/if}

{if $filter}
    <form method="get" action="{$script_name}">
    
    <table cellspacing=0 cellpadding=2 border=1>
    	<tr>
    		<th align="right">Field</th>
    		<th>Group By</th>
    		<th>Count Distinct</th>
    		<th>Filter</th>
    	</tr>
    {foreach from=$options key=name item=row}
    	<tr>
    		<th align="right">{$row.name}</th>
    		<td align="center">{if $row.groupby}<input type="radio" name="groupby" value="{$name}"{if $groupby == $name} checked{/if}/>{elseif $row.groupby === '0'}coming soon{/if}</td>
    		<td align="center">{if $row.distinct}<input type="radio" name="distinct" value="{$name}"{if $distinct == $name} checked{/if}/>{/if}</td>
    		<td>{if $row.filter}<input type="text" name="filter[{$name}]" value="{$row.filtervalue}" size="6"/> {$row.filter}{elseif $row.filter === false}coming soon{/if}</td>
    	</tr>
    {/foreach}
    	<tr>
    		<th align="right">None</th>
    		<td>&nbsp;</td>
    		<td align="center"><input type="radio" name="distinct" value=""{if $distinct == ''} checked{/if}/></td>
    		<td>&nbsp;</td>
    	</tr>
    </table>

    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    <input type="submit" value="Go"/></p></form>
 {/if}  

	{if $headnote}
		{$headnote}
	{/if}

	<h3>{$h2title}</h3>
	
{if $total > 0}
	{if !$nosort}
	<p><small>Click a column header to change the sort order.</small></p>
	{/if}
	
	<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
	<thead><tr>
	{foreach from=$table.0 key=name item=value}
	<td style="direction: rtl; writing-mode: tb-rl;">{$name}</td>
	{/foreach}

	</tr></thead>
	<tbody>


	{foreach from=$table item=row}
	<tr>
		{foreach from=$row key=name item=value}
			<td align="right">{$value}</td>
		{/foreach}
	</tr>
	{/foreach}
	
	

	</tbody>
	</table>
	
	{if $footnote}
		{$footnote}
	{/if}

	<div class="interestBox">&middot;
	<a href="{$script_name}?{foreach from=$extra key=name item=value}{$name}={$value}&amp;{/foreach}{if $ri}ri={$ri}&amp;{/if}{if $i}i={$i}&amp;{/if}{dynamic}{if $u}u={$u}&amp;{/if}{/dynamic}output=csv">Download this table as a CSV File</a></div>

{else}
	<p><i>No Results to Display</i></p>
{/if}

{include file="_std_end.tpl"}
