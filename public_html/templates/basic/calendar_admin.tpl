{include file="_std_begin.tpl"}

<h2>Calendar Admin Page</h2>

<hr>
<p>Orders marked as 'Paid' are ready to be processed. 

{dynamic}
{if $list}
	<form method=post>
	<h3>Current Orders</h3>
	<table border=1 cellspacing=0 cellpadding=5>
	{foreach from=$list key=index item=calendar}
		{if $calendar.ordered < '1000'}
		<tr style=color:silver>
		{else}
		<tr>
		{/if}
			<td>#{$calendar.calendar_id}.
			<td>{$calendar.title|default:'untitled calendar'}</td>
			<td>{$calendar.realname}
			<td>{$calendar.status} {if $calendar.quantity}x{$calendar.quantity}{/if}
			<td><b>{$calendar.user_id}{$calendar.alpha}</b>
			<td>{if $calendar.status != 'new'}<a href="view.php?id={$calendar.calendar_id}"><b>View</b></a>{/if}
			<td>{if $calendar.status == 'paid' || $calendar.status == 'ordered'}

				<input type=checkbox name="processed[{$calendar.calendar_id}]" id="processed{$calendar.calendar_id}">
				<b><label for="processed{$calendar.calendar_id}"> Mark as Processed</label></b>
			{elseif $calendar.status == 'processed'}
				Processed
			{/if}
		</tr>
	{/foreach}
	{if $total}
		<tr>
		<td colspan=3>
		<td>Total Paid: {$total}
		<td colspan=3>
	{/if}
	</table>	
	<input type=submit value="Save Progress"> 
	</form>
{/if}
{/dynamic}

{literal}
<style>
input:checked + label {
        font-weight:bold;
}
</style>
{/literal}

{include file="_std_end.tpl"}


