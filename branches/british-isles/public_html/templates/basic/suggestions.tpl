{assign var="page_title" value="Change Request Suggestions"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>

{dynamic}
<h2>Change Request Suggestions for {$user->realname}</h2>

{if $own}
	<p><b>Own images</b> / <a href="/suggestions.php?others">On others</a></p>
{else}
	<p><a href="/suggestions.php">Own images</a> / <b>On others</b></p>
{/if}	

<p>Click a column header to resort a list</p>

<h3>New Suggestions</h3>

{if $newtickets}

<p>These suggestions haven't seen any activity yet... </p>
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
  <p>There are no new suggestions</p>
{/if}


<h3>Open Suggestions</h3>

{if $opentickets}

<p>These suggestions have seen some activity...</p>

{if !$own}
<p><small>The 'Comments' column shows the number of replies to the suggestion, will might be <s>striked</s> if those replies are not visible.</small></p>
{/if}

<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Moderator?</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Comments</td>
	<td style="width:150px">Updated</td>
</tr></thead>
<tbody>

{foreach from=$opentickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td>{$ticket.moderator}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title|default:'Untitled'}</a></td>
<td>{if $ticket.type == 'minor'}(minor) {/if}{$ticket.notes}</td>
<td>{$ticket.comments}</td>
<td style="width:150px">{$ticket.updated}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>You have no open suggestions.</p>
{/if}

<h3>Closed Suggestions</h3>

{if $closedtickets}

<p>These suggestions have been closed in the last 30 days...</p>
<table class="report sortable" id="opentickets" style="font-size:8pt;">
<thead><tr>
	<td>Moderator</td>
	<td>Title</td>
	<td>Problem</td>
	<td>Comments</td>
	<td style="width:150px">Updated</td>
</tr></thead>
<tbody>

{foreach from=$closedtickets item=ticket}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td>{$ticket.moderator}</td>
<td><a href="/editimage.php?id={$ticket.gridimage_id}">{$ticket.title|default:'Untitled'}</a></td>
<td>{$ticket.notes}</td>
<td>{$ticket.comments}</td>
<td style="width:150px">{$ticket.updated}</td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>You have no closed suggestions in the last 30 days.</p>
{/if}


{/dynamic}    
{include file="_std_end.tpl"}
