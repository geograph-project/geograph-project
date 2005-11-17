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
 	  <li><b>{$profile->stats.ftf}</b> Geograph points (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>) {if $profile->rank > 0}Rank: <b>{$profile->rank|ordinal}</b>{/if} {if $profile->rank > 1}({$profile->to_rise_rank} more needed to get to {$profile->rank-1|ordinal}){/if}</li>
 	  <li><b>{$profile->stats.geosquares}</b> gridsquare{if $profile->stats.geosquares ne 1}s{/if} geographed</li>
 	  <li><b>{$profile->stats.total}</b> photograph{if $profile->stats.total ne 1}s{/if} submitted</li>
 	  {if $profile->stats.pending gt 0}
 	 	  ({$profile->stats.pending} awaiting moderation)
 	  {/if}
 	  <ul>
 	  	<li>Covering <b>{$profile->stats.squares}</b> gridsquare{if $profile->stats.squares ne 1}s{/if}</li>
 	  </ul>
 	  </li>

 	  
        {else}
<h3>Statistics</h3>
<ul>
 	  <li>No photographs submitted</li>
 	{/if}

</ul>

{if $profile->stats.total gt 0}
	<h3><a href='/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1'>Photographs</a></h3>
	<p>Click column headers to sort in a different order</p>
	
	{if $limit}

		<p>Showing last {$limit} images, <a href="profile.php?u={$profile->user_id}&amp;all=1" rel="nofollow">click here to see all results</a></p>
	{/if}
	
	<table class="report sortable" id="photolist" style="font-size:8pt;">
	<thead><tr>
		<td><img title="Any grid square discussions?" src="/templates/basic/img/discuss.gif" width="10" height="10"> ?</td>
		<td>Grid Ref</td>
		<td>Title</td>
		<td sorted="desc">Submitted</td>
		<td>Status</td>
			
		<td><img title="Any image problems or change requests?" src="/templates/basic/img/alert.gif" width="11" height="10"> ?</td>
	</tr></thead>
	<tbody>
		{foreach from=$userimages item=image}
<tr>
<td sortvalue="{$image->last_post}">{if $image->topic_id}<a title="View most recent discussion" href="/discuss/index.php?action=vthread&amp;forum={$image->forum_id}&amp;topic={$image->topic_id}"><img src="/templates/basic/img/discuss.gif" width="10" height="10"></a>{/if}</td>
<td sortvalue="{$image->grid_reference}"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
<td>{$image->title}</td>
<td sortvalue="{$image->gridimage_id}" class="nowrap">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
<td class="nowrap">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if} {if $image->ftf}(first){/if}</td>
<td sortvalue="{if $image->open_tickets}{$image->open_tickets}{/if}">{if $image->open_tickets}<a title="Click to view open change requests" href="/editimage.php?id={$image->gridimage_id}"><img src="/templates/basic/img/alert.gif" width="11" height="10"></a>{/if}</td>
</tr>
		{/foreach}
</tbody></table>

	{if $limit}
		<p>Showing last {$limit} images, <a href="profile.php?u={$profile->user_id}&amp;all=1" rel="nofollow">click here to see all results</a></p>
	{/if}
		
	
{/if} 	




<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
