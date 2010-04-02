{assign var="page_title" value="Moderators"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Moderators</h2>
{dynamic}

<h3>Moderators{if $q}/Users{/if}</h3>

{if $message}
	<p class="error">{$message|escape:'html'}</p>
{else}
	<p>Click Grant to make a user a moderator, you can also check the verification moderations using the Verify link.</p>
{/if}

<form method="get" action="{$script_name}">
<label for="show_role">Show assigned role</label>: <select name="show_role" id="show_role">
    	{html_options options=$roles selected=$show_role}
    </select>
 <input type="submit" value="Go"/></p></form>
 
{if $moderators}

<table class="report sortable" id="newtickets" style="font-size:8pt;">
<thead><tr>
	<td>Name/Profile</td>
	<td>Nickname</td>
	<td>Role</td>
	<td>Signup Date</td>
	<td>Last Verification</td>
	{if $stats}
		<td>Images Submitted</td>
		<td>Images Moderated</td>
		<td>Suggestions Moderated</td>
		<td>Forum Posts</td>
	{/if}
	<td>Moderator Actions (see below)</td>
	<td>Suggestion Actions (see below)</td>
</tr></thead>
<tbody>

{foreach from=$moderators item=userrow}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
	<td{if strpos($userrow.rights,'admin') > 0} style="font-weight:bold"{/if}><a href="/profile/{$userrow.user_id}">{$userrow.realname}</a></td>
	<td>{$userrow.nickname}</td>
	<td>{if $userrow.role}{$userrow.role}{else}{if strpos($userrow.rights,'admin') > 0}Developer{else}{if strpos($userrow.rights,'moderator') > 0}Moderator{/if}{/if}{/if}&nbsp;<a href="javascript:assignRole({$userrow.user_id},'{$userrow.role}');">e</a></td>
	<td>{$userrow.signup_date}</td>
	<td title="{$userrow.last_log_time}">{$userrow.last_log} ({$userrow.log_count})</td>
	{if $stats}
		<td>{$userrow.photo_count}</td>
		<td>{$userrow.count}</td>
		<td>{$userrow.ticket_count}</td>
		<td>{$userrow.post_count}</td>
	{/if}
	<td>
		{if $stats != $userrow.user_id}
			<a href="/admin/moderator_admin.php?stats={$userrow.user_id}">Stats</a>
		{/if}
		{if $userrow.log_count}
			<a href="/admin/moderation.php?moderator={$userrow.user_id}&amp;verify=1">Verify</a>(<a href="/admin/moderation.php?moderator={$userrow.user_id}&amp;verify=2">Mis</a>)
		{/if}
		{if strpos($userrow.rights,'moderator') > 0}
			<a href="/admin/moderation.php?moderator={$userrow.user_id}">Review</a>
			<a href="/admin/moderator_admin.php?revoke={$userrow.user_id}">Revoke</a>
		{else}
			<a href="/admin/moderator_admin.php?grant={$userrow.user_id}">Grant</a>
		{/if}
	</td>
	<td>
		{if strpos($userrow.rights,'ticketmod') > 0}
			<a href="/admin/tickets.php?moderator={$userrow.user_id}">Review</a>
			<a href="/admin/moderator_admin.php?revoke={$userrow.user_id}&amp;right=ticketmod">Revoke</a>
		{else}
			<a href="/admin/moderator_admin.php?grant={$userrow.user_id}&amp;right=ticketmod">Grant</a>
		{/if}
	</td>
</tr>
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
{/dynamic}   

{literal}
<script type="text/javascript">
function assignRole(user_id,role) {
	name = prompt("Please amend Role as required:",role);
	if (name != null)
		location.href= "/admin/moderator_admin.php?user_id="+user_id+"&role="+escape(name);
}
</script>
{/literal}

<div class="interestBox">
<div>As soon as a user is marked as a moderator, they can make real moderations, 
admin's can use these links occasionally to spot check a particular moderator.</div><br/>

<i>Key:</i><br/>
<b>Stats</b> - View moderation statistics for this user.<br/>
<b>Verify</b> - View dummy verification moderations, done before becoming a moderator<br/>
(<b>Mis</b>) - only show mismatches.<br/>
<b>Review</b> - View recent real moderations/suggestions.<br/>
<b>Revoke</b> - Remove moderation rights (moderations already done are not affected).<br/>
<b>Grant</b> - Add moderation rights to this user. (requires the user to re-login)
</div>

 
{include file="_std_end.tpl"}
