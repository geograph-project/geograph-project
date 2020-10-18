{include file="_std_begin.tpl"}
<style>
{literal}
td.error
{
	color:red;
	font-weight:bold;
}

td.warning
{
	color:#cc0000;
  
}

td.verbose
{
	color:silver;
}

{/literal}
</style>

{dynamic}

<h2><a href="index.php">Admin</a> : <a href="events.php">Events</a> : Handler Log</h2>


<table class="report" style="font-size:0.7em">
<thead>
<tr><td>Completed Handlers</td></tr>
</thead>
<tbody>

{if $handlers}

	{foreach from=$handlers item=handler}
	<tr>
	<td>{$handler.class_name}</td>
	</tr>
	{/foreach}

{else}
	<tr><td><i>No handlers successfully completed yet</i></td></tr>
{/if}

</tbody>
</table>



<table class="report" style="font-size:0.7em">
<thead>
<tr><td>Time</td><td>Delta</td><td>Verbosity</td><td>PID</td><td >Entry</td></tr>
</thead>
<tbody>
{if $logs}
	{assign var="last" value=$logs.0.logtime|date_format:'%s'}
	{foreach from=$logs item=log}
		<tr>
			<td style="white-space:nowrap">{$log.logtime}</td>
			<td align=right>+{math equation="t-l" t=$log.logtime|date_format:'%s' l=$last}</td>
			<td>{$log.verbosity}</td>
			<td>{$log.pid}</td>
			<td class="{$log.verbosity}">{$log.log}</td>
		</tr>
		 {assign var="last" value=$log.logtime|date_format:'%s'}
	{/foreach}

{else}
	<tr><td colspan="3"><i>No log entries for this event</i></td></tr>
{/if}

</tbody>
</table>



    
{/dynamic}
{include file="_std_end.tpl"}
