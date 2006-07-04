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
	{external href=$profile->website}
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

{if $profile->about_yourself && $profile->public_about}
<div class="caption" style="background-color:#dddddd; padding:10px;">
<h3 style="margin-top:0px;margin-bottom:0px">About Myself</h3>
{$profile->about_yourself|nl2br|GeographLinks:true}</div>
{/if}

{if $user->user_id eq $profile->user_id}
 <p><a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</p> 	
{else}
 <br/><br/>
{/if}


 	
 	
 	{if $profile->stats.total gt 0}
 	<div style="background-color:#dddddd; padding:10px;">
<div style="float:right; position:relative; margin-top:0px; font-size:0.7em">View Breakdown by <a href="/statistics/breakdown.php?by=status&u={$profile->user_id}" rel="nofollow">Status</a>, <a href="/statistics/breakdown.php?by=takenyear&u={$profile->user_id}" rel="nofollow">Date Taken</a> or <a href="/statistics/breakdown.php?by=gridsq&u={$profile->user_id}" rel="nofollow">Myriad</a>(<a href="/help/squares" title="What is a Myriad?">?</a>).</div>
<h3 style="margin-top:0px;margin-bottom:0px">Statistics</h3>
<ul>
 	  <li><b>{$profile->stats.ftf}</b> Geograph points (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>)<ul>
 	  {if $profile->rank > 0}<li>Overall Rank: <b>{$profile->rank|ordinal}</b> {if $profile->rank > 1}({$profile->to_rise_rank} more needed to reach {$profile->rank-1|ordinal} position){/if}</li>{/if}
 	  <li><b>{$profile->stats.geosquares}</b> gridsquare{if $profile->stats.geosquares ne 1}s{/if} <i>geographed</i></li>
 	  </ul>
 	  </li>
 	  <li><b>{$profile->stats.total}</b> photograph{if $profile->stats.total ne 1}s{/if} submitted</li>
 	  {if $profile->stats.pending gt 0}
 	 	  ({$profile->stats.pending} awaiting moderation)
 	  {/if}<ul>
 	  	<li><b>{$profile->stats.squares}</b> gridsquare{if $profile->stats.squares ne 1}s{/if} <i>photographed</i>,
 	  	giving a depth score of <b>{$profile->stats.total/$profile->stats.squares|string_format:"%.2f"}</b> (see <a title="Statistics - Frequently Asked Questions" href="/help/stats_faq">FAQ</a>)
 	  	</li>
 	  </ul>
 	  </li>

 	  </div>
        {else}
<h3>Statistics</h3>
<ul>
 	  <li>No photographs submitted</li>
 	{/if}

</ul>

{if $profile->stats.total gt 0}
	<div style="float:right; position:relative; margin-top:0px; font-size:0.7em"><a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1">Find images by {$profile->realname|escape:'html'}</a> (<a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbs">Thumbnail Only</a>, <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=slide">Slide Show Mode</a>)</div>
	<h3 style="margin-bottom:0px">Photographs</h3>
	
	<p style="font-size:0.7em">Click column headers to sort in a different order</p>
	
	{if $limit}

		<p>Showing last {$limit} images, <a href="{if $profile->nickname}/user/{$profile->nickname|escape:'url'}/all{else}/profile.php?u={$profile->user_id}&amp;all=1{/if}" rel="nofollow">click here to see all results</a></p>
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
<td sortvalue="{$image->last_post}">{if $image->topic_id}<a title="View discussion - last updated {$image->last_post|date_format:"%a, %e %b %Y at %H:%M"}" href="/discuss/index.php?action=vthread&amp;forum={$image->forum_id}&amp;topic={$image->topic_id}" ><img src="/templates/basic/img/discuss.gif" width="10" height="10" alt="discussion indicator"></a>{/if}</td>
<td sortvalue="{$image->grid_reference}"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
<td>{$image->title}</td>
<td sortvalue="{$image->gridimage_id}" class="nowrap">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
<td class="nowrap">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if} {if $image->ftf}(first){/if}</td>
<td sortvalue="{if $image->open_tickets}{$image->open_tickets}{/if}">{if $image->open_tickets}<a title="Click to view open change requests" href="/editimage.php?id={$image->gridimage_id}"><img src="/templates/basic/img/alert.gif" width="11" height="10"></a>{/if}</td>
</tr>
		{/foreach}
</tbody></table>

	{if $limit}
		<p>Showing last {$limit} images, <a href="{if $profile->nickname}/user/{$profile->nickname|escape:'url'}/all{else}/profile.php?u={$profile->user_id}&amp;all=1{/if}" rel="nofollow">click here to see all results</a></p>
	{/if}
		
	
{/if} 	




<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
