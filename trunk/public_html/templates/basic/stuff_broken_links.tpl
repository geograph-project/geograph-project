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

<form action"{$script_name}" method="post">

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Image</td>
		<td>Link &amp; Reported Error</td>
		<td>Code</td>
		<td>Checked</td>
		<td>Retry</td>
	</tr>
</thead>
<tbody>
	{foreach from=$table item=item name="i"}
		{assign var="HTTP_Status" value=$item.HTTP_Status}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
		<td sortvalue="{$item.gridimage_id}" align="right">
			{if $item.images > 1}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.ids|replace:',':'<br/>'}</a>
			{else}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.gridimage_id}</a>
			{/if}
		</td>
		<td sortvalue="{$item.url|escape:'html'}">{external href=$item.url text=$item.url|replace:"http://":""|truncate:90|regex_replace:'/^([\w\.-]+)/':'<b>$1</b><small>'}<br/>
		<b>{$item.HTTP_Status}</b> {$codes.$HTTP_Status}</small>
		{if $item.HTTP_Location}
			<small>... Server reports {external href=$item.HTTP_Location text="new location"}
			{if strpos($item.HTTP_Location,'404')}
				[<b>Which <i>appears</i> to be an Error Page</b>]
			{/if}
			</small>
		{/if}
		
		</td>
		<td>{$item.HTTP_Status}</td>
		<td sortvalue="{$item.last_checked}" style="font-size:0.8em">{$item.last_checked|date_format:"%e %b %Y"}</td>
		<td style="font-size:0.8em" sortvalue="{$smarty.foreach.i.iteration}">
			
			{if $item.HTTP_Status != 200 && $item.failure_count < 4} 
				<input type="checkbox" name="retry[]" value="{$item.url|escape:'html'}"/>
			{/if}
		</td>
	</tr>
	{/foreach}
</tbody>
</table>

<p>
If you have checked some links and beleive them to be ok despite the result of our recent automatic check, please tick the relevent links above and click the following button: <input type="submit" name="Submit" value="Schedule a Recheck"/><br/>
(Note even after submitting this form it may take some time for your rescheduled checks to be processed and disappear from this page)</p>

</form>
    
{include file="_std_end.tpl"}
