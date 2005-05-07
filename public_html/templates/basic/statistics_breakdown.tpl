{assign var="page_title" value="Statistics"}
{include file="_std_begin.tpl"}

    <form method="get" action="{$script_name}">
    <p>View breakdown of images by 
    <select name="by">
    	{html_options options=$bys selected=$by}
    </select> in <select name="ri">
    	{html_options options=$references selected=$ri}
    </select> 
    
    {dynamic}
    {if $user->registered}
	<select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">Just for {$profile->realname}</option>
		{/if}
		<option value="{$user->user_id}">Just for {$user->realname}</option>
		<option value="" {if !$u} selected{/if}>For Everyone</option>
	</select>
    {else}
	{if $u}
	<select name="u">
		<option value="{$u}" selected>Just for {$profile->realname}</option>
		<option value="">For Everyone</option>
	</select>
	{/if}
    {/if}
    {/dynamic}
    <input type="submit" value="Go"></p></form>
    

	<h3>{$h2title}</h3>
	{if $total > 0}
	<p><small>Click a column header to change the sort order.</small></p>

	<table class="report">
	<thead><tr>
	<td><a href="{$script_name}?{$link}&amp;order={$no}">{$title}</a></td>
	<td><a href="{$script_name}?{$link}&amp;order=c{$no}">Number</a></td>
	<td>Percentage</td></tr></thead>
	<tbody>

	{if $linkprefix}
		{foreach from=$breakdown item=line}
		<tr><td><a href="{$linkprefix}{$line.field|escape:url}">{$line.field|default:"<i>-unspecified-</i>"}</a></td>
		<td align=right>{$line.c}</td>
		<td align=right>{$line.per}%</td></tr>
		{/foreach}
	{else}
		{foreach from=$breakdown item=line}
		<tr><td>{$line.field}</td>
		<td align=right>{$line.c}</td>
		<td align=right>{$line.per}%</td></tr>
		{/foreach}
	{/if}
	
	<tr class="totalrow"><td>&nbsp;</td>
	<th align=right>{$total}</th>
	<th align=right>100%</th></tr>
	</tbody>
	</table>
	{else}
		<p><i>No Results to Display</i></p>
	{/if}


	<p align="center"><i>This page was last updated {$generation_time|date_format:"%H:%M"}</i>.</p>

{include file="_std_end.tpl"}
