{assign var="page_title" value="Statistics"}
{include file="_std_begin.tpl"}

{if !$by} 
    <h2>Overview Statistics for Geograph British Isles</h2>

<ul>
<li><b>{$users_submitted}/{$users_total}</b> of Registered Users have Submitted Images</li>
<li><b>{$users_thisweek}</b> new users in past 7 days</li>
<li>A total of <b>{$images_ftf}</b> Geograph Points have been awarded</p>
</ul>

<table border="1" cellpadding="4" cellspacing="0" class="statstable">
<thead><tr>
	<th>&nbsp;</th>
	<th>Great Britain</th>
	<th>Ireland</th>
	<th><i>Total</i></th>
</tr></thead>
<tbody><tr>
	<th>Images Submitted</th>
	<td><b>{$images_total_1}</b></td>
	<td><b>{$images_total_2}</b></td>
	<td>{$images_total_1+$images_total_2}</td>
</tr>
<tr>
	<th>&nbsp;[in past 7 days]</th>
	<td><b>{$images_thisweek_1}</b></td>
	<td><b>{$images_thisweek_2}</b></td>
	<td>{$images_thisweek_1+$images_thisweek_2}</td>
</tr>
<tr>
	<th>1km<sup>2</sup> Grid Squares</th>
	<td><b>{$squares_submitted_1}</b><br/>/{$squares_total_1}</td>
	<td><b>{$squares_submitted_2}</b><br/>/{$squares_total_2}</td>
	<td valign="top">{$squares_submitted_1+$squares_submitted_2}<br/>/{$squares_total_1+$squares_total_2}</td>
</tr>
<tr>
	<th>&nbsp;[with geograph(s)]</th>
	<td><b>{$geographs_submitted_1}</b></td>
	<td><b>{$geographs_submitted_2}</b></td>
	<td>{$geographs_submitted_1+$geographs_submitted_2}</td>
</tr>
<tr>
	<th>100km<sup>2</sup> Grid Squares</th>
	<td><b>{$grid_submitted_1}</b><br/>/{$grid_total_1}</td>
	<td><b>{$grid_submitted_2}</b><br/>/{$grid_total_2}</td>
	<td>{$grid_submitted_1+$grid_submitted_2}<br/>/{$grid_total_1+$grid_total_2}</td>
</tr></tbody>
</table>

    
    <h3>More Statistics</h3>
    
{/if}
    <form method="get" action="{$script_name}">
    <p>View breakdown of images by 
    <select name="by">
    	{html_options options=$bys selected=$by}
    </select> for <select name="ri">
    	{html_options options=$references selected=$ri}
    </select> 
    
    {if $user->registered}
	<select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">Just for Selected User</option>
		{/if}
		<option value="{$user->user_id}">Just for {$user->realname}</option>
		<option value="" {if !$u} selected{/if}>For Everyone</option>
	</select>
    {else}
	{if $u}
	<select name="u">
		<option value="{$u}">Just for Selected User</option>
		<option value="">For Everyone</option>
	</select>
	{/if}
    {/if}
    <input type="submit" value="Go"></p></form>
    

    
{if $by}
	{if $total > 0}
	<p><small>Click a column header to change the sort order.</small></p>

	<table class="report">
	<thead><tr>
	<td><a href="{$script_name}?{$link}&amp;order={$no}">{$title}</a></td>
	<td><a href="{$script_name}?{$link}&amp;order=c{$no}">Number</a></td>
	<td>Percentage</td></tr></thead>
	<tbody>

	{foreach from=$breakdown item=line}
	<tr><td>{$line.field}</td>
	<td align=right>{$line.c}</td>
	<td align=right>{$line.per}%</td></tr>
	{/foreach}
	
	<tr class="totalrow"><td>&nbsp;</td>
	<th align=right>{$total}</th>
	<th align=right>100%</th></tr>
	</tbody>
	</table>
	{else}
		<p><i>No Results to Display</i></p>
	{/if}
{else} 
    <p>Here's a graph of photo submissions since we began...<br/>
    <img src="/img/submission_graph.png" width="480" height="161"/>
    </p> 
{/if}
	<p align="center"><i>This page was last updated {$gentime}</i>.</p>

{include file="_std_end.tpl"}
