{assign var="page_title" value="Change Request Tickets"}
{include file="_std_begin.tpl"}

<script src="/sorttable.js"></script>

{dynamic}
<h2>Change Request Tickets for {$user->realname}</h2>

<h3>New Tickets</h3>

{if $newtickets}

<p>These tickets haven't seen any activity yet... </p>
<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	<td>Title</td>
	<td>Problem</td>
	<td style="width:150px">Submitted</td>
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title|default:'Untitled'}</a></td>
<td>{$ticket.notes}</td>
<td style="width:150px">{$ticket.suggested}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no new tickets</p>
{/if}


<h3>Open Tickets</h3>

{if $opentickets}

<p>These tickets have seen some activity...</p>
<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Moderator?</td>
	<td>Title</td>
	<td>Problem</td>
	<td style="width:150px">Updated</td>
</tr></thead>
<tbody>

{foreach from=$opentickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td>{$ticket.moderator}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}&amp;alltickets=1">{$ticket.title|default:'Untitled'}</a></td>
<td>{$ticket.notes}</td>
<td style="width:150px">{$ticket.updated}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>You have no open tickets.</p>
{/if}

<h3>Closed Tickets</h3>

{if $closedtickets}

<p>These tickets have been closed in the last 30 days...</p>
<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Moderator</td>
	<td>Title</td>
	<td>Problem</td>
	<td style="width:150px">Updated</td>
</tr></thead>
<tbody>

{foreach from=$closedtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td>{$ticket.moderator}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}&amp;alltickets=1">{$ticket.title|default:'Untitled'}</a></td>
<td>{$ticket.notes}</td>
<td style="width:150px">{$ticket.updated}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>You have no closed tickets in the last 30 days.</p>
{/if}


{/dynamic}    
{include file="_std_end.tpl"}
