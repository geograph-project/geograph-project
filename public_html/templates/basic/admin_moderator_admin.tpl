{assign var="page_title" value="Moderators"}
{include file="_std_begin.tpl"}

<script src="/sorttable.js"></script>

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Moderators</h2>
{dynamic}

<h3>Moderators{if $q}/Users{/if}</h3>

{if $message}
	<p class="error">{$message|escape:'html'}</p>
{/if}

{if $moderators}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	<td>Name/Profile</td>
	<td>Nickname</td>
	{if $stats}
		<td>Photos</td>
		<td>Moderations</td>
		<td>Verifications</td>
		<td>Tickets Moderated</td>
		<td>Forum Posts</td>
	{/if}
	<td>Moderator Actions (see below)</td>
	<td>Ticket Actions (see below)</td>
</tr></thead>
<tbody>

{foreach from=$moderators item=userrow}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td><a href="/profile.php?u={$userrow.user_id}">{$userrow.realname}</a></td>
	<td>{$userrow.nickname}</td>
{if $stats}
	<td>{$userrow.photo_count}</td>
	<td>{$userrow.count}</td>
	<td>{$userrow.log_count}</td>
	<td>{$userrow.ticket_count}</td>
	<td>{$userrow.post_count}</td>
{/if}
<td>{if $stats != $userrow.user_id}
	<a href="/admin/moderator_admin.php?stats={$userrow.user_id}">Stats</a>
{/if}
{if strpos($userrow.rights,'moderator') > 0}
	<a href="/admin/moderation.php?moderator={$userrow.user_id}&amp;verify=1">Verify</a>
	(<a href="/admin/moderation.php?moderator={$userrow.user_id}&amp;verify=1">Mis</a>)
	<a href="/admin/moderation.php?moderator={$userrow.user_id}">Review</a>
	<a href="/admin/moderator_admin.php?revoke={$userrow.user_id}">Revoke</a>
{else}
	<a href="/admin/moderator_admin.php?grant={$userrow.user_id}">Grant</a>
{/if}
</td>
<td>{if $stats != $userrow.user_id}
	<a href="/admin/moderator_admin.php?stats={$userrow.user_id}">Stats</a>
{/if}
{if strpos($userrow.rights,'ticketmod') > 0}
	<a href="/admin/tickets.php?moderator={$userrow.user_id}">Review</a>
	<a href="/admin/moderator_admin.php?revoke={$userrow.user_id}&amp;right=ticketmod">Revoke</a>
{else}
	<a href="/admin/moderator_admin.php?grant={$userrow.user_id}&amp;right=ticketmod">Grant</a>
{/if}
</td></tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no moderators !?!</p>
{/if}

<h3>Find User</h3>

<form action="/admin/moderator_admin.php" method="get">
	<p><label for="user_q">Search</label>*: <input type="text" id="user_q" name="q" value="{$q|escape:'html'}"/>
	<input type="submit" value="Next &gt;"/></p>
	<p style="font-size:0.7em">* Search by name, nickname or user-id</p>
</form>

<div class="interestBox">
<b>Stats</b> - View moderation statistics for this user<br/>
<b>Verify</b> - View suggested moderations<br/>
(<b>Mis</b>) - mismatches only<br/>
<b>Review</b> - View recent moderations<br/>
<b>Revoke</b> - Remove moderation rights (moderations already done are not affected)<br/>
<b>Grant</b> - Add moderation rights to this user (requires the user to re-login)
</div>

{/dynamic}    
{include file="_std_end.tpl"}
