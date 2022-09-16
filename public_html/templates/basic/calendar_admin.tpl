{include file="_std_begin.tpl"}

<h2>Calendar Admin Page {$year}</h2>

<hr>
<p>Orders marked as 'Paid' are ready to be processed. 

{dynamic}
{if $list}
	<form method=get>
		<input type=checkbox name=paid value=1 {if $paid} checked{/if} id=paid onclick=this.form.submit()> <label for=paid>Only show Paid orders</label>
	</form>
	<form method=post>
	<h3>Current Orders</h3>
	<table border=1 cellspacing=0 cellpadding=5>
	<tr>
		<th>id
		<th>title
		<th>user
		<th>status
		<th>custom
		<th>bestof
		<th>ref
		<th colspan=2>
	</tr>
	{foreach from=$list key=index item=calendar}
		{if $calendar.ordered < '1000'}
		<tr style=color:silver>
		{else}
		<tr>
		{/if}
			<td>#{$calendar.calendar_id}.
			<td>{$calendar.title|default:'untitled calendar'}</td>
			<td>{$calendar.realname}
			<td>{$calendar.status}</td>
			<td align=right>{if $calendar.quantity}{$calendar.quantity}{/if}</td>
			<td align=right>{if $calendar.best_quantity}{$calendar.best_quantity}{/if}</td>
			<td><b>{$calendar.user_id}{$calendar.alpha}</b>
			<td>{if $calendar.status != 'new' && $calendar.quantity}<a href="view.php?id={$calendar.calendar_id}" {if $calendar.status != 'paid'} style=color:silver{/if}><b>View</b></a>{/if}
			<td>{if ($calendar.status == 'paid' || $calendar.status == 'ordered') && $calendar.quantity}

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
		<td>Total Paid:</td>
		<td align=right>{$total}
		<td align=right>{$best}
		<td colspan=3>Total Processed: {$processed}/{$orders}
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


