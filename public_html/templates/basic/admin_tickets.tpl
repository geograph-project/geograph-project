{assign var="page_title" value="Trouble Tickets"}
{include file="_std_begin.tpl"}

<script src="/sorttable.js"></script>

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Trouble Tickets</h2>
{dynamic}

<h3>New Tickets</h3>

{if $newtickets}

<p>These tickets haven't seen any moderator activity yet...go on, sort one out</p>
<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	<td>Photographer</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Suggested by</td>
	<td>Submitted</td>
</tr></thead>
<tbody>

{foreach from=$newtickets item=ticket}
<tr>
<td>{$ticket.submitter}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title}</a></td>
<td>{$ticket.notes}</td>
<td>{$ticket.suggester}</td>
<td>{$ticket.suggested}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no new tickets</p>
{/if}


<h3>Open Tickets</h3>

{if $opentickets}

<p>These tickets have seen some moderator activity - make sure they get closed eventually!</p>
<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Moderator</td>
	<td>Photographer</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Suggested by</td>
	<td>Updated</td>
</tr></thead>
<tbody>

{foreach from=$opentickets item=ticket}
<tr>
<td>{$ticket.moderator}</td>
<td>{$ticket.submitter}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title}</a></td>
<td>{$ticket.notes}</td>
<td>{$ticket.suggester}</td>
<td>{$ticket.updated}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no open tickets! Well done!</p>
{/if}



{/dynamic}    
{include file="_std_end.tpl"}
