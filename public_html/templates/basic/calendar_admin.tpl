{include file="_std_begin.tpl"}

<h2>Admin Page</h2>

<hr>

<b><a href="start.php">Create a new Calendar Order &gt; &gt;</a></b>

{dynamic}
{if $list}
	<form method=post>
	<h3>Current Orders</h3>
	<table border=1 cellspacing=0 cellpadding=5>
	{foreach from=$list key=index item=calendar}
		<tr>
			<td>#{$calendar.calendar_id}.
			<td>{$calendar.title|default:'untitled calendar'}</td>
			<td>{$calendar.realname}
			<td>{$calendar.status} {if $calendar.quantity}x{$calendar.quantity}{/if}
			<td>{if $calendar.status == 'ordered'}<a href="view.php?id={$calendar.calendar_id}"><b>View</b></a>{/if}
			<td>{if $calendar.status == 'ordered'}<a href="download.php?id={$calendar.calendar_id}"><b>Download</b></a>{/if}
			<td>{if $calendar.status == 'ordered'}<input type=checkbox name="processed[{$calendar.calendar_id}]"> Mark as Processed{/if}
		</tr>
	{/foreach}
	</table>	
	<input type=submit value=Save disabled>
	</form>
{/if}
{/dynamic}


{include file="_std_end.tpl"}


