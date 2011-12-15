{assign var="page_title" value="the Geograph Team"}
{include file="_std_begin.tpl"}

<div align="center" class="tabHolder">
        <a href="/article/About-Geograph-page" class="tab">About Geograph</a>
        <span class="tabSelected">The Geograph Team</span>
        <a href="/credits/" class="tab">Contributors</a>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="http://hub.geograph.org.uk/downloads.html" class="tab">Downloads</a>
        <a href="/contact.php" class="tab">Contact Us</a>
</div>
<div style="position:relative;" class="interestBox">
        <h2 align="center" style="margin:0">The Geograph Team</h2>
</div>

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

<p>The following members have also helped out in various capacities previously...</p>

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
<div class="interestBox">... see also the <a href="/credits/">Contributor Credits</a> and <a href="/help/credits">Website Credits</a></div>

{dynamic}
{if $user->registered && ($user->stats.squares gt 20)}
	<p>If you are interested in helping out with moderation then please visit your <a href="/profile.php?edit=1">profile update page</a>, at the bottom of which there is a button to apply and get a feel for the moderation process. Please note, however, that we have a long waiting list!</p>
{/if}
{/dynamic}
 
{include file="_std_end.tpl"}
