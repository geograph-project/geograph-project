{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>
<script src="/js/sitemap.js"></script>


{if $overview}
  <div style="float:right; width:{$overview_width+30}px; position:relative">
  {include file="_overview.tpl"}
  </div>
{/if}



<h2><a name="top"></a><img src="http://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50" align="absmiddle" alt="{$profile->realname|escape:'html'}'s Gravatar" style="padding-right:10px"/>Profile for {$profile->realname|escape:'html'}</h2>

{if $profile->role && $profile->role ne 'Member'}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: {$profile->role}</div>
{elseif strpos($profile->rights,'admin') > 0}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Developer</div>
{elseif strpos($profile->rights,'moderator') > 0}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Moderator</div>	
{/if}

<ul>
	<li><b>Name</b>: {$profile->realname|escape:'html'}</li>

	{if $profile->nickname}
		<li><b>Nickname</b>:{$profile->nickname|escape:'html'}</li>
	{/if}

	{if $profile->website}
		<li><b>Website</b>:{external href=$profile->website}</li>
	{/if}
 
	{if $profile->deceased_date}
		<li><b>Member</b>:  {$profile->signup_date|date_format:"%B %Y"} - {$profile->deceased_date|date_format:"%B %Y"}</li>
	{else}
		{if $profile->grid_reference}
			<li><b>Home grid reference</b>: 
			<a href="/gridref/{$profile->grid_reference|escape:'html'}">{$profile->grid_reference|escape:'html'}</a>
		{/if}
	
		<li><b>Member since</b>: 
			{$profile->signup_date|date_format:"%B %Y"}
		</li>
	{/if}
</ul>

{if $profile->about_yourself && $profile->public_about}
	<div class="caption" style="background-color:#dddddd; padding:10px;">
	{if !$profile->deceased_date}
	<h2 style="margin-top:0px;margin-bottom:0px">About Me</h2>
	{/if}
	{$profile->about_yourself|TruncateWithExpand:'(<small>this is a preview only</small>) <big>Click here to <b>Read More</b></big>...'|nl2br|GeographLinks:true}</div>
{/if}

	<br/><br/>



{if $profile->stats.images gt 0}
 	<div style="background-color:#dddddd; padding:10px;">

		{if $profile->deceased_date}
		<h3 style="margin-top:0px;margin-bottom:0px">Statistics</h3>
		{else}
		<h3 style="margin-top:0px;margin-bottom:0px">My Statistics</h3>
		{/if}
		<ul>
			{if $profile->stats.points}
				<li><b>{$profile->stats.points}</b> Geograph points <sup>(see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>)</sup>
					{if $user->user_id eq $profile->user_id && $profile->stats.points_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Overall Rank: <b>{$profile->stats.points_rank|ordinal}</b> {if $profile->stats.points_rank > 1}({$profile->stats.points_rise} more needed to rise rank){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			{if $profile->stats.geosquares}
				<li><b>{$profile->stats.geosquares}</b> Personal points (gridsquare{if $profile->stats.geosquares ne 1}s{/if} <i>geographed</i>)
					{if $user->user_id eq $profile->user_id && $profile->stats.geo_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Overall Rank: <b>{$profile->stats.geo_rank|ordinal}</b> {if $profile->stats.geo_rank > 1}({$profile->stats.geo_rise} more needed to rise rank){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			{if $profile->stats.geographs}
				<li><b>{$profile->stats.geographs}</b> Geograph{if $profile->stats.geographs ne 1}s{/if}
				{if $profile->stats.geographs != $profile->stats.images}
					and <b>{$profile->stats.images-$profile->stats.geographs}</b> Supplemental
				{/if}
				</li>
			{/if}
			<li><b>{$profile->stats.images}</b> Photograph{if $profile->stats.images ne 1}s{/if}
				{if $profile->stats.squares gt 1}
					<ul style="font-size:0.8em;margin-bottom:2px">
					<li><b>{$profile->stats.squares}</b> gridsquare{if $profile->stats.squares ne 1}s{/if},
					giving a depth score of <b>{$profile->stats.depth|string_format:"%.2f"}</b> <sup>(see <a title="Statistics - Frequently Asked Questions" href="/help/stats_faq">FAQ</a>)</sup>
					</li>
					{if $profile->stats.hectads > 1}
						<li>in <b>{$profile->stats.hectads}</b> different hectads and <b>{$profile->stats.myriads}</b> Myriads<sup><a href="/help/squares">?</a></sup>{if $profile->stats.days > 3}, taken on <b>{$profile->stats.days}</b> different days{/if}</li>
					{/if}
					</ul>
				{/if}
			</li>
			{if $profile->stats.content}
				<li style="margin-top:10px"><b>{$profile->stats.content}</b> items of <a href="/content/?user_id={$profile->user_id}" title="view content submitted by {$profile->realname|escape:'html'}">Collections submitted</a>
					{if $user->user_id eq $profile->user_id}
						[<a href="/article/?user_id={$profile->user_id}">Article List</a>]
					{/if}
				</li>
			{/if}
		</ul>
		<div style="float:right;font-size:0.8em; color:gray; margin-top:-20px">Last updated: {$profile->stats.updated|date_format:"%H:%M"}</div>
	</div>
{else}
	<h3>My Statistics</h3>
	<ul>
		  <li>No photographs submitted {if $userimages}(statistics can take a few hours to appear if you have only recently begun submitting){/if}</li>
	</ul>
{/if}

{if $userimages}
	<h3 style="margin-bottom:0px">Photographs</h3>
	
	<p style="font-size:0.7em">Click column headers to sort in a different order</p>
	
	{if $limit}
		<p>This page shows the latest {$limit} images.</p>
	{/if}
	<br style="clear:both"/>
	<table class="report sortable" id="photolist" style="font-size:8pt;">
	<thead><tr>
		<td><img title="Any grid square discussions?" src="/templates/basic/img/discuss.gif" width="10" height="10"> ?</td>
		<td>Grid Ref</td>
		<td>Title</td>
		<td sorted="desc">Submitted</td>
		<td>Classification</td>
	</tr></thead>
	<tbody>
	{foreach from=$userimages item=image}
		<tr>
		<td sortvalue="{$image->last_post}">{if $image->topic_id}<a title="View discussion - last updated {$image->last_post|date_format:"%a, %e %b %Y at %H:%M"}" href="/discuss/index.php?action=vthread&amp;forum={$image->forum_id}&amp;topic={$image->topic_id}" ><img src="/templates/basic/img/discuss.gif" width="10" height="10" alt="discussion indicator"></a>{/if}</td>
		<td sortvalue="{$image->grid_reference}"><a title="view full size image" href="{if $image->gridimage_id < 1498791}javascript:void(loadimage({$image->gridimage_id}));{else}/photo/{$image->gridimage_id}{/if}">{$image->grid_reference}</a></td>
		<td>{$image->title}</td>
		<td sortvalue="{$image->gridimage_id}" class="nowrap">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
		<td class="nowrap">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if} {if $image->ftf}(first){/if}</td>
		</tr>
	{/foreach}
	</tbody></table>


{/if}


<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
