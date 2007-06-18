{include file="_std_begin.tpl"}
{dynamic}

<h2><a href="index.php">Admin</a> :: Event Diagnostics</h2>

<h3>Event Stats</h3>
<ul>
<li>{$count_pending} events pending</li>
<li>{$count_inprogress} events in progress</li>
<li>{$count_recent} events processed in past hour</li>
{if $count_recent}<li>Events in past hour took an average of {$stat_recent} seconds to complete</li>{/if}

</ul>

<form method="get" action="/_scripts/process_events.php">
<input type="submit" name="start" value="Process Events (for debugging use only)"/>

Test mode <input type="radio" name="testmode" value="1" id="testmode_on"/>
<label for="testmode_on">on</label>
<input type="radio" checked="checked" name="testmode" value="0" id="testmode_off"/>
<label for="testmode_off">off</label>
</form>



<h3>List Events</h3>

<form method="get" action="events.php">
<input type="hidden" name="count" value="{$count}"/>
<input type="hidden" name="offset" value="{$offset}"/>

<label for="event_name">Event Name</label><input type="text" id="search_name" name="search_name" value="{$search_name|escape:'html'}"/><br/>

<label for="search_start">Date Range from </label><input size="16" type="text" id="search_start" name="search_start" value="{$search_start|escape:'html'}"/>
to <input size="16" type="text" id="search_end" name="search_end" value="{$search_end|escape:'html'}"/><br/>

<label for="status">Status </label>
<select id="status" name="status">
{html_options options=$statuses selected=$status}
</select>


<input type="submit" name="list" value="Show"/>

<br/>
<br/>

<table class="report">
<thead>
<tr><td>Event</td><td>Param</td><td>Posted</td><td>Status</td></tr>
</thead>
<tbody>

{foreach from=$events item=event}
<tr>
<td><a href="events.php?showlogs={$event.event_id}" title="show logs">{$event.event_name}</a>{if $event.instances gt 2} (x{$event.instances}){/if}</td>
<td>{$event.event_param}</td>
<td>{$event.posted}</td>
<td>{$event.status}</td>
</tr>
{/foreach}

</tbody>
</table>

{if $pages gt 2}
<input type="submit" name="prev" value="&lt; Prev"/>
Page {$page} of {$pages}
<input type="submit" name="next" value="Next &gt;"/>
{/if}

</form>




<h3>Inject New Event</h3>
<form method="post" action="events.php">
<label for="event_name">Event Name</label><input type="text" id="event_name" name="event_name" value="{$event_name|escape:'html'}"/><br/>
<label for="event_param">Event Param</label><input type="text" id="event_param" name="event_param" value="{$event_param|escape:'html'}"/><br/>
<label for="event_priority">Event Priority</label><input type="text" id="event_priority" name="event_priority" size="3" value="{$event_priority|escape:'html'}"/><br/>
<input type="submit" name="fire" value="Fire!"/>
{if $event_fired} Event fired!{/if}
</form>

    
{/dynamic}
{include file="_std_end.tpl"}
