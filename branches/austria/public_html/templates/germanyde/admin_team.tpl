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
{if $userrow.role ne 'Member'}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}"{if strpos($userrow.rights,'admin') > 0} style="font-weight:bold"{/if}>
	<td>{$userrow.realname}</td>
	<td>{$userrow.nickname}</td>
	<td>{if $userrow.role}{$userrow.role}{else}{if strpos($userrow.rights,'admin') > 0}Developer{else}Moderator{/if}{/if}</td>
	<td><a href="/profile/{$userrow.user_id}">Profile</a>/<a href="/usermsg.php?to={$userrow.user_id}">Contact</a></td>
</tr>
{/if}
{/foreach}
</tbody>
</table>

<p>The following members have also helped out in various capacities previouslly...</p>

<table class="report">
<thead><tr>
	<td>Name</td>
	<td>Nickname</td>
	<td>Profile/Contact</td>
</tr></thead>
<tbody>

{foreach from=$team item=userrow}
{if $userrow.role eq 'Member'}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}"{if strpos($userrow.rights,'admin') > 0} style="font-weight:bold"{/if}>
	<td>{$userrow.realname}</td>
	<td>{$userrow.nickname}</td>
	<td><a href="/profile/{$userrow.user_id}">Profile</a>/<a href="/usermsg.php?to={$userrow.user_id}">Contact</a></td>
</tr>
{/if}
{/foreach}
</tbody>
</table>

{else}
  <p>There are no moderators !?!</p>
{/if}

<br/>
<div class="interestBox">... see also the <a href="/help/credits">Contributor Credits</a> and <a href="/help/credits">Website Credits</a></div>

{dynamic}
{if $user->registered && ($user->stats.squares gt 20)}
	<p>If you are interested in helping out with moderation then please visit your <a href="/profile.php?edit=1">profile update page</a>, there you will find a button to get a feel for the moderation process. Please note that we however have a long waiting list!</p>
{/if}
{/dynamic}
 
{include file="_std_end.tpl"}
