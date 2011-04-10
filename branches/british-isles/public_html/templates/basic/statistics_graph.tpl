{assign var="page_title" value="$h2title :: Statistics"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
.graphcell {
background-color:red;
height:15px;
}
</style>
{/literal}
<script src="{"/sorttable.js"|revision}"></script>

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

{if $prefix}
	{$prefix}
{/if}

{foreach from=$graphs item=graph}

	<h3>{$graph.title}</h3>
	{if $graph.max > 0}
		
		<table class="report" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" width="95%">

		<tbody>

		{foreach from=$graph.table item=row}
		<tr>
			<td width="90">{$row.title}</td>
			<td width="70" align="right">{$row.value|number_format}</td>
			<td><div class="graphcell" style="width:{$row.value/$graph.max*99|number_format}%;"></div></td>
		</tr>
		{/foreach}
		{if $graph.total}
		<tr>
			<th width="90">{$graph.total.title}</th>
			<th width="70" align="right">{$graph.total.value|number_format}</th>
			<th></th>
		</tr>
		{/if}

		</tbody>
		</table>


	{else}
		<p><i>No Results to Display</i></p>
	{/if}
{/foreach}

{include file="_std_end.tpl"}
