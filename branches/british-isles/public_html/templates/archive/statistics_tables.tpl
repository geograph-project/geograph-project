{assign var="page_title" value="Statistics:: $h2title"}
{include file="_std_begin.tpl"}
{if !$nosort}
<script src="{"/sorttable.js"|revision}"></script>
{/if}

{if $filter}
    <form method="get" action="{$script_name}">
    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    {if $i}
    	<input type="checkbox" name="i" value="{$i}" checked="checked" 
    	id="i"/><label for="i">Limited to <a href="/search.php?i={$i}">Search</a></label>
    {else}
    {dynamic}
    {if $filter > 1}
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
    {/if}
    {/dynamic}
    {/if}
    {foreach from=$extra key=name item=value}
    	<input type="hidden" name="{$name}" value="{$value}"/>
    {/foreach}
    <input type="submit" value="Go"/></p></form>
 {/if} 
 
<h2>{$h2title}</h2>

{if $headnote}
	{$headnote}
{/if}
	
{foreach from=$tables key=tableindex item=table}

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
