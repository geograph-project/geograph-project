{assign var="page_title" value="Statistics:: $h2title"}
{include file="_std_begin.tpl"}
<script src="/sorttable.js"></script>

    <form method="get" action="{$script_name}">
    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    {if $i}
    	<input type="checkbox" name="i" value="{$i}" checked="checked" 
    	id="i"/><label for="i">Limited to <a href="/search.php?i={$i}">Search</a></label>
    {else}
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
    {/if}
    <input type="submit" value="Go"></p></form>
    

	<h3>{$h2title}</h3>
{if $total > 0}
	<p><small>Click a column header to change the sort order.</small></p>

	<table class="report sortable" id="reportlist">
	<thead><tr>
	{foreach from=$table.0 key=name item=value}
	<td>{$name}</td>
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
{else}
	<p><i>No Results to Display</i></p>
{/if}

{include file="_std_end.tpl"}
