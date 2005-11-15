
<script src="/sorttable.js"></script>

<h2>Trouble Tickets</h2>
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
<td><a href="/editimage.php?id={$ticket.gridimage_id}" target="main">{$ticket.title|default:'Untitled'}</a></td>
<td>{$ticket.notes}</td>
<td>{$ticket.suggester}</td>
<td>{$ticket.suggested}</td>
<td>{$ticket.submitter}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no new tickets</p>
{/if}
{/dynamic}    
