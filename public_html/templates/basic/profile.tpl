{include file="_std_begin.tpl"}
<script src="/sorttable.js"></script>
<h2><a name="top"></a>Profile for {$profile->realname|escape:'html'}</h2>


<h3>Basic information</h3>

<ul>
<li>Name: {$profile->realname|escape:'html'}</li>

<li>Nickname: 
{if $profile->nickname}
	{$profile->nickname|escape:'html'} 
{else}
	<i>n/a</i>
{/if}
</li>

<li>Website: 
{if $profile->website}
	<a href="{$profile->website|escape:'html'}">{$profile->website|escape:'html'}</a>
{else}
	<i>n/a</i>
{/if}
</li>
 	
{if $user->user_id ne $profile->user_id}
	{if $profile->public_email eq 1}
		<li>Email: {mailto address=$profile->email encode="javascript"}</li> 	
	{else}
		<li><a title="Contact {$profile->realname|escape:'html'}" href="/usermsg.php?to={$profile->user_id}">Send message to {$profile->realname|escape:'html'}</a></li> 	
	{/if}
{/if}

</ul>

{if $user->user_id eq $profile->user_id}
 <p><a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</p> 	
{/if}


 	
 	
 	{if $profile->stats.total gt 0}
<h3><a href="/statistics/breakdown.php?by=status&u={$profile->user_id}">Statistics</a></h3>
<ul>
 	  <li>Geograph points: {$profile->stats.ftf} (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>)</li>
 	  <li>{$profile->stats.total} photograph{if $profile->stats.total ne 1}s{/if} submitted</li>
 	  {if $profile->stats.pending gt 0}
 	 	  ({$profile->stats.pending} awaiting moderation)
 	  {/if}
 	  </li>
	  <li>{$profile->stats.squares} grid square{if $profile->stats.squares ne 1}s{/if} photographed</li>
 	  
        {else}
<h3>Statistics</h3>
<ul>
 	  <li>No photographs submitted</li>
 	{/if}

</ul>

{if $profile->stats.total gt 0}
	<h3><a href='/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1'>Photographs</a> <small>(recent first)</small></h3>
	<table class="report sortable" id="photolist" style="font-size:8pt;">
	<thead><tr>
		<td>Grid Ref</td>
		<td>Title</td>
		<td>Submitted</td>
		<td>Status</td>
	</tr></thead>
	<tbody>
		{foreach from=$userimages item=image}
<tr><td><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
<td>{$image->title}</td>
<td>{$image->submitted}</td>
<td>{$image->moderation_status}</td></tr>
		{/foreach}
</tbody></table>
{/if} 	

{if $troubled}
	<h3>Images with pending change requests</h3>

<table class="report sortable" id="troublelist" style="font-size:8pt;">
	<thead><tr>
		<td>Grid Ref</td>
		<td>Title</td>
		<td>Submitted</td>
		<td>Status</td>
	</tr></thead>
	<tbody>
		{foreach from=$troubled item=image}
<tr><td><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
<td>{$image->title}</td>
<td>{$image->submitted}</td>
<td>{$image->moderation_status}</td></tr>
		{/foreach}
</tbody></table>
 	
{/if} 


<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
