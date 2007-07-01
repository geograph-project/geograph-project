{include file="_std_begin.tpl"}
<script src="/sorttable.js"></script>

{if $overview}
  <div style="float:right; width:{$overview_width+30}px; position:relative">
  {include file="_overview.tpl"}
  </div>
{/if}

<h2><a name="top"></a>Profile for {$profile->realname|escape:'html'}</h2>

{if $profile->role}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: {$profile->role}</div>
{else}
	{if strpos($profile->rights,'admin') > 0}
		<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Developer</div>
	{else}
		{if strpos($profile->rights,'moderator') > 0}
			<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Moderator</div>
		{/if}
	{/if}
{/if}

<ul>
	<li><b>Name</b>: {$profile->realname|escape:'html'}</li>

	<li><b>Nickname</b>: 
		{if $profile->nickname}
			{$profile->nickname|escape:'html'} 
		{else}
			<i>n/a</i>
		{/if}
	</li>

	<li><b>Website</b>: 
		{if $profile->website}
			{external href=$profile->website}
		{else}
			<i>n/a</i>
		{/if}
	</li>
 
	{if $user->user_id ne $profile->user_id}
		{if $profile->public_email eq 1}
			<li><b>Email</b>: {mailto address=$profile->email encode="javascript"}</li>
		{/if}
		<li><a title="Contact {$profile->realname|escape:'html'}" href="/usermsg.php?to={$profile->user_id}">Send message to {$profile->realname|escape:'html'}</a></li>
	{else}
		<li><b>Email</b>: {mailto address=$profile->email encode="javascript"}
		{if $profile->public_email ne 1} <em>(not displayed to other users)</em>{/if}
		</li>
	{/if}

	{if $profile->grid_reference}
		<li><b>Home grid reference</b>: 
		<a href="/gridref/{$profile->grid_reference|escape:'html'}">{$profile->grid_reference|escape:'html'}</a>
	{/if}
</ul>

{if $profile->about_yourself && $profile->public_about}
	<div class="caption" style="background-color:#dddddd; padding:10px;">
	<h3 style="margin-top:0px;margin-bottom:0px">More about me</h3>
	{$profile->about_yourself|nl2br|GeographLinks:true}</div>
{/if}

{if $user->user_id eq $profile->user_id}
	<p><a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</p> 	
{else}
	<br/><br/>
{/if}


{if $profile->stats.total gt 0}
 	<div style="background-color:#dddddd; padding:10px;">
		<div style="float:right; position:relative; margin-top:0px; font-size:0.7em">View Breakdown by <a href="/statistics/breakdown.php?by=status&u={$profile->user_id}" rel="nofollow">Classification</a>, <a href="/statistics/breakdown.php?by=takenyear&u={$profile->user_id}" rel="nofollow">Date Taken</a> or <a href="/statistics/breakdown.php?by=gridsq&u={$profile->user_id}" rel="nofollow">Myriad</a>(<a href="/help/squares" title="What is a Myriad?">?</a>).</div>
		<h3 style="margin-top:0px;margin-bottom:0px">My Statistics</h3>
		<ul>
			<li><b>{$profile->stats.ftf}</b> Geograph points (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>)<ul>
			{if $user->user_id eq $profile->user_id && $profile->rank > 0}
				<li>Overall Rank: <b>{$profile->rank|ordinal}</b> {if $profile->rank > 1}({$profile->to_rise_rank} more needed to reach {$profile->rank-1|ordinal} position){/if}</li>
			{/if}
			<li><b>{$profile->stats.geosquares}</b> gridsquare{if $profile->stats.geosquares ne 1}s{/if} <i>geographed</i></li>
			</ul></li>
			<li><b>{$profile->stats.total}</b> photograph{if $profile->stats.total ne 1}s{/if} submitted
				{if $profile->stats.pending gt 0}
					({$profile->stats.pending} awaiting moderation)
				{/if}
				{if $profile->stats.squares gt 0}<ul>
					<li><b>{$profile->stats.squares}</b> gridsquare{if $profile->stats.squares ne 1}s{/if} <i>photographed</i>,
					giving a depth score of <b>{math equation="(t-p)/s" assign="depth" t=$profile->stats.total p=$profile->stats.pending s=$profile->stats.squares}{$depth|string_format:"%.2f"}</b> (see <a title="Statistics - Frequently Asked Questions" href="/help/stats_faq">FAQ</a>)
					</li>
				</ul>{/if}
			</li>
		</ul>
	</div>
{else}
	<h3>My Statistics</h3>
	<ul>
		  <li>No photographs submitted</li>
	</ul>
{/if}

{if $profile->stats.total gt 0}
	<div style="float:right; position:relative; margin-top:0px; font-size:0.7em"><a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1">Find images by {$profile->realname|escape:'html'}</a> (<a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbs">Thumbnail Only</a>, <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=slide">Slide Show Mode</a>)</div>
	<h3 style="margin-bottom:0px">Photographs</h3>
	
	<p style="font-size:0.7em">Click column headers to sort in a different order</p>
	
	{if $limit}
		<p>Showing the latest {$limit} images, see <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=text&amp;resultsperpage=100">More</a></p>
	{/if}
	
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
		<td sortvalue="{$image->grid_reference}"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->grid_reference}</a></td>
		<td>{$image->title}</td>
		<td sortvalue="{$image->gridimage_id}" class="nowrap">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
		<td class="nowrap">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if} {if $image->ftf}(first){/if}</td>
		</tr>
	{/foreach}
	</tbody></table>

	{if $limit}
		<p>Showing the latest {$limit} images, see <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=text&amp;resultsperpage=100">More</a></p>
	{/if}


	<h3 style="margin-bottom:0px">Explore My Images</h3>

	<ul>
		<li><b>Maps</b>: {if $profile->stats.total gt 10}<a href="/profile/{$profile->user_id}/map">Personalised Geograph Map</a> or {/if} Recent Photos on <a href="http://maps.google.co.uk/maps?q=http://{$http_host}/profile/{$profile->user_id}/feed/recent.kml&ie=UTF8&om=1">Google Maps</a></li>

		<li><b>Recent Images</b>: <a title="RSS Feed for images by {$profile->realname}" href="/profile/{$profile->user_id}/feed/recent.georss" class="xml-rss">RSS</a> or <a title="GPX file for images by {$profile->realname}" href="/profile/{$profile->user_id}/feed/recent.gpx" class="xml-gpx">GPX</a></li>
		{if $profile->stats.total gt 10}
			{dynamic}{if $user->registered}
				<li><b>Download</b>: 
					<a title="Comma Seperated Values - file for images by {$profile->realname}" href="/export.csv.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1{/if}">CSV</a>
					{if $user->user_id eq $profile->user_id},
						<a title="Excel 2003 XML - file for images by {$profile->realname}" href="/export.excel.xml.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1{/if}">XML<small> for Excel <b>2003</b></small></a>
					{/if} of all images</li>
			{/if}{/dynamic}
		{/if}
		{dynamic}
			{if $user->user_id eq $profile->user_id}
				<li><b>Change Requests</b>: <a href="/tickets.php">View Recent Tickets</a></li>
			{/if}
		{/dynamic}
	</ul>
{/if}


<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
