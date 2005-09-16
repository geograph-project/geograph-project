{assign var="page_title" value="process events"}
{include file="_std_begin.tpl"}
{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> :
<a title="Event Diagnostics" href="/admin/events.php">Events</a> : 
Process Events</h2>
<p>This will process any pending events - this tool is mainly for testing and
debugging, as cron should keep the event system ticking over</p>

<form method="get" action="process_events.php">

<input type="submit" name="start" value="Start"/>
</form>

{/dynamic}    
{include file="_std_end.tpl"}
