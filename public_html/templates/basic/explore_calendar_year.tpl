{include file="_std_begin.tpl"}

<h2>Geograph Calendar {$year}</h2>

<form action="{$script_name}">
<p>Date: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-whole year-" all_extra="onchange='this.form.submit()'"}<noscript><input type="submit" value="Update"/></noscript></p> 
</form>

<p>Click the month name for more detail. Key: <span style="font-family:arial;font-size:0.7em;color:green;">Geograph Images</span>.</p>

{foreach from=$months key=name item=weeks name=loop}
<div style="position:relative;float:left;width=340px;padding:10px;height:280px;">

<h2><a href="{$script_name}?Year={$year}&amp;Month={$smarty.foreach.loop.iteration}">{$name}</a></h2>

<table class="report" bordercolor="#eeeeee" border="1" cellspacing="0" cellpadding="1" style="position:relative">
<thead><tr>{foreach from=$days item=day}
<td>{$day}</td>
{/foreach}</tr></thead>
<tbody>

{foreach from=$weeks item=week}
<tr>
	{foreach from=$week item=day}
		{if $day.number}
		<td bgcolor="#{$day.image->images|colerize}" valign="top">
			<div style="font-size:0.8em;font-weight:bold;">{$day.number}</div>

			{if $day.image}
				<div align="center" style="font-family:arial;font-size:0.7em;color:green;">{$day.image->images-$day.image->supps}</div>
			{/if}
		{else}
			<td>&nbsp;
		{/if}
		</td>
	{/foreach}
</tr>
{/foreach}

</tbody>
</table>

</div>
{/foreach}
<br style="clear:both"/>
<ul>
	<li><a href="/leaderboard.php?when={$year}&amp;date=taken">User leaderboard for {$year}</a> {if $year >= 2005}(<a href="/leaderboard.php?when={$year}">Submitted</a>){/if}</li>
</ul>
{include file="_std_end.tpl"}
