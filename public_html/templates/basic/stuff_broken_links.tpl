{assign var="page_title" value="Broken Links"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


<h2>Broken Links</h2>

<form method="get" action="{$script_name}">
    <p>Severity: 
    <select name="l">
    	{html_options options=$levels selected=$l}
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
    <input type="submit" value="Go"/></p></form>
</form>

<p>Click a column header to reorder</p>

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Image(s)</td>
		<td>URL & Error</td>
		<td>Code</td>
		<td>Checked</td>
		<td>Info</td>
	</tr>
</thead>
<tbody>
	{foreach from=$table item=item}
		{assign var="HTTP_Status" value=$item.HTTP_Status}
	<tr>
		<td sortvalue="{$item.gridimage_id}" align="right">
			{if $item.images > 1}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.ids|replace:',':'<br/>'}</a>
			{else}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.gridimage_id}</a>
			{/if}
		</td>
		<td sortvalue="{$item.url|escape:'html'}">{external href=$item.url text=$item.url|truncate:90|regex_replace:'/\/\/([\w\.-]+)/':'//<b>$1</b>'}<small><br/>
		<b>{$item.HTTP_Status}</b> {$codes.$HTTP_Status}</small></td>
		<td>{$item.HTTP_Status}</td>
		<td sortvalue="{$item.last_checked}" style="font-size:0.8em">{$item.last_checked|date_format:"%e %b %Y"}</td>
		<td style="font-size:0.8em">
			{if $item.HTTP_Location}
				Server reports {external href=$item.HTTP_Location text="new location"}
			{/if}
		</td>
	</tr>
	{/foreach}
</tbody>
</table>

    
    
{include file="_std_end.tpl"}
