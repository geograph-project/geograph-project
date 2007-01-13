{assign var="page_title" value="the Geograph Team"}
{include file="_std_begin.tpl"}

<h3>the Geograph Team</h3>

{if $team}

<table class="report">
<thead><tr>
	<td>Name</td>
	<td>Nickname</td>
	<td>Role</td>
	<td>Profile/Contact</td>
</tr></thead>
<tbody>

{foreach from=$team item=userrow}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}"{if strpos($userrow.rights,'admin') > 0} style="font-weight:bold"{/if}>
	<td>{$userrow.realname}</td>
	<td>{$userrow.nickname}</td>
	<td>{if $userrow.role}{$userrow.role}{else}{if strpos($userrow.rights,'admin') > 0}Developer{else}Moderator{/if}{/if}</td>
	<td><a href="/profile.php?u={$userrow.user_id}">Profile</a>/<a href="/usermsg.php?to={$userrow.user_id}">Contact</a></td>
</tr>
{/foreach}
</tbody>
</table>
{else}
  <p>There are no moderators !?!</p>
{/if}

{dynamic}
{if $user->registered}
	<p>If you are interested in helping out with moderation then please visit your <a href="/profile.php?edit=1">profile update page</a>, there you will find a button to get a feel for the moderation process. Please note that we however have a long waiting list!</p>
{/if}
{/dynamic}
 
{include file="_std_end.tpl"}
