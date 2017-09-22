{if $userimages && $profile->user_id != 1695}
{assign var="extra_meta" value="<link rel=\"canonical\" href=\"`$self_host`/profile/`$profile->user_id`\" />"}
{else}
{assign var="extra_meta" value="<meta name=\"robots\" content=\"noindex, nofollow\" />"}
{/if}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

{dynamic}
{if $credit_realname}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	The image you were viewing was contributed by the user below, but is specifically credited to <b>{$credit_realname|escape:'html'}</b>
	</div>
	<br/><br/>
{/if}

{if $profile->tickets}
	<div id="ticket_message">
		{if $profile->last_ticket_time}
			<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
			You have <b>{$profile->tickets}</b> ongoing suggestions on your images, please go to <a href="/suggestions.php">your suggestions page</a> to review them.
			<small><br/><br/><a href="javascript:void(hide_message())">I've read this, please hide</a> </small>
			</div>
			<br/><br/>
		{else}
			<div style="text-align:center;color:gray">You have <b>{$profile->tickets}</b> ongoing suggestions on your images, please go to <a href="/suggestions.php">your suggestions page</a> to review them. <a href="javascript:void(hide_message())">hide this</a></div>
		{/if}
	</div>
	<script type="text/javascript">{literal}
	function hide_message() {
		document.getElementById('ticket_message').style.display= 'none';
		pic1= new Image();
		pic1.src="/profile.php?hide_message";
	}
	{/literal}</script>
{/if}
{/dynamic}

{if $overview}
  <div style="float:right; width:{$overview_width+30}px; position:relative">
  {include file="_overview.tpl"}
  </div>
{/if}



<h2><a name="top"></a><img src="{if $profile->md5_email}https://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50{else}https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=30{/if}" align="absmiddle" alt="{$profile->realname|escape:'html'}'s Gravatar" style="padding-right:10px"/>Profile for {$profile->realname|escape:'html'}</h2>

{if $profile->role && $profile->role ne 'Member'}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: {$profile->role}</div>
{elseif strpos($profile->rights,'admin') > 0}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Developer</div>
{elseif strpos($profile->rights,'moderator') > 0}
	<div style="margin-top:0px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 4px;"><b>Geograph Role</b>: Moderator</div>
{/if}
{if strpos($profile->rights,'member') > 0}
        <div style="margin-top:-1px;border-top:1px solid red; border-bottom:1px solid red; color:purple; padding: 3px; font-size:0.9em">Geograph Project Limited Company Member</div>
{/if}


<ul>
	<li><b>Name</b>: {$profile->realname|escape:'html'}</li>

	{if $profile->nickname}
		<li><b>Nickname</b>: {$profile->nickname|escape:'html'}</li>
	{/if}

	{if $profile->website && !$profile->hasPerm('suspicious',true)}
		{if $userimages}
			<li><b>Website</b>: {external href=$profile->website}</li>
		{else}
			<li><b>Website</b>: {$profile->website|escape:'html'|replace:'http://':''|replace:'.':' [dot] '}</li>
		{/if}
	{/if}

        {if $profile->google_profile}
		<li><b>Google Profile</b>: <a rel="me" href="{$profile->google_profile|escape:'html'}?rel=author">{$profile->google_profile|escape:'html'}</a></li>
	{/if}

 	{if $profile->hasPerm('dormant',true)}
 		<!--<li><i>We do not hold contact details for this user.</i></li>-->
 	{elseif $user->user_id ne $profile->user_id}
		{if $profile->public_email eq 1}
			<li><b>Email</b>: {mailto address=$profile->email encode="javascript"}</li>
		{/if}
		<li><a title="Contact {$profile->realname|escape:'html'}" href="/usermsg.php?to={$profile->user_id}">Send message{if !$profile->deceased_date} to {$profile->realname|escape:'html'}{/if}</a></li>
	{elseif $simplified}
		<li><b>Email</b>: {mailto address=$profile->email encode="javascript"}
		{if $profile->public_email ne 1} <em>(not displayed to other users)</em>{/if}
		</li>
	{/if}

	{if $profile->deceased_date}
		<li><b>Site Member</b>:  {$profile->signup_date|date_format:"%B %Y"} - {$profile->deceased_date|replace:'-00':'-01'|date_format:"%B %Y"}</li>
	{elseif strlen($profile->rights) > 1}
		{if $profile->grid_reference}
			<li><b>Home grid reference</b>:
			<a href="/gridref/{$profile->grid_reference|escape:'html'}">{$profile->grid_reference|escape:'html'}</a>
		{/if}

		<li><b>Site Member since</b>:
			{$profile->signup_date|date_format:"%B %Y"}
		</li>
	{/if}
</ul>

{if $simplified}
	<p><img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/> This is a simplified view of your own profile, you can also view your <a href="/profile/{$user->user_id}">full public profile</a>.</p>
{else}

{if $profile->blog.blog_id}
	<p>&middot; My latest blog entry: <a href="/blog/{$profile->blog.blog_id}">{$profile->blog.title|escape:'html'}</a> <small>({$profile->blog.created})</small></p>
{/if}

{if $profile->about_yourself && $profile->public_about && ($userimages || $user->user_id eq $profile->user_id)}
	<div class="caption" style="clear:both">
	{if !$profile->deceased_date}
		<div class="interestBox" style="border-radius: 10px;">
			<h2 style="margin-top:0px;margin-bottom:0px">About Me</h2>
		</div>
		<div style="padding-left:10px">
			{$profile->about_yourself|TruncateWithExpand:'(<small>this is a preview only</small>) <big><b>Further information</b></big>...'|nl2br|GeographLinks:true}
		</div>
	{else}
		{$profile->about_yourself|TruncateWithExpand:'(<small>this is a preview only</small>) <big><b>Further information</b></big>...'|nl2br|GeographLinks:true}
	{/if}
	</div>
{/if}
{/if}

{if $user->user_id eq $profile->user_id && $simplified}
	<p><a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</p>
{else}
	<br/><br/>
{/if}

{if $profile->stats.images gt 0}
 	<div class="interestBox" style="clear:both;border-radius: 10px;">
 		{if $profile->stats.images > 2}
		<div style="float:right; position:relative; margin-top:0px; font-size:0.7em">View Breakdown by <a href="/statistics/breakdown.php?by=takenyear&u={$profile->user_id}" rel="nofollow">Date Taken</a> or <a href="/statistics/breakdown.php?by=gridsq&u={$profile->user_id}" rel="nofollow">Myriad</a><sup><a href="/help/squares" title="What is a Myriad?" class="about" style="font-size:0.7em">?</a></sup>.</div>
		{/if}
		{if $profile->deceased_date}
		<h3 style="margin-top:0px;margin-bottom:0px">Statistics</h3>
		{else}
		<h3 style="margin-top:0px;margin-bottom:0px">My Statistics <a href="/help/stats_faq" class="about" style="font-size:0.7em">About</a></h3>
		{/if}
	</div>
	{if !$profile->deceased_date}
		<div style="float:right;font-size:0.8em; color:gray;">Last updated: {$profile->stats.updated|date_format:"%H:%M"}</div>
	{/if}
	<div>
 		<ul>
			{if $profile->stats.points}
				<li><b>{$profile->stats.points}</b> First Geograph points
					{if $user->user_id eq $profile->user_id && $profile->stats.points_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Overall Rank: <b>{$profile->stats.points_rank|ordinal}</b> {if $profile->stats.points_rank > 1}({$profile->stats.points_rise} more needed to rise rank){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			{if $profile->stats.seconds || $profile->stats.thirds || $profile->stats.fourths}
				<li style="padding-bottom:3px">
				{if $profile->stats.seconds}
					<b>{$profile->stats.seconds}</b> Second Visitor points,
				{/if}
				{if $profile->stats.thirds}
					<b>{$profile->stats.thirds}</b> Third Visitor points,
				{/if}
				{if $profile->stats.fourths}
					<b>{$profile->stats.fourths}</b> Fourth Visitor points
				{/if}
				</li>
			{/if}
			{if $profile->stats.geosquares}
				<li><b>{$profile->stats.geosquares}</b> Personal points (grid square{if $profile->stats.geosquares ne 1}s{/if} <i>geographed</i>)
					{if $user->user_id eq $profile->user_id && $profile->stats.geo_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Overall Rank: <b>{$profile->stats.geo_rank|ordinal}</b> {if $profile->stats.geo_rank > 1}({$profile->stats.geo_rise} more needed to rise rank){/if}</li>
						</ul>
					{/if}
				</li>
			{/if}
			{if $profile->stats.tpoints}
				<li style="padding-bottom:3px"><b>{$profile->stats.tpoints}</b> TPoints (Time-gap points <sup><a href="/help/stats_faq#tpoints" class="about" style="font-size:0.6em">About</a></sup>)</li>
			{/if}
			<li><b>{$profile->stats.images}</b> Photograph{if $profile->stats.images ne 1}s{/if}
				{if $profile->stats.squares gt 1}
					<ul style="font-size:0.8em;margin-bottom:2px">
					{if $profile->stats.geographs}
						<li><b>{$profile->stats.geographs}</b> Geograph{if $profile->stats.geographs ne 1}s{/if}
						{if $profile->stats.geographs != $profile->stats.images}
							and <b>{$profile->stats.images-$profile->stats.geographs}</b> others
						{/if}
						</li>
					{/if}
					<li><b>{$profile->stats.squares}</b> grid square{if $profile->stats.squares ne 1}s{/if},
					giving a depth score of <b>{$profile->stats.depth|string_format:"%.2f"}</b>
					</li>
					{if $profile->stats.hectads > 1}
						<li>in <b>{$profile->stats.hectads}</b> different hectads and <b>{$profile->stats.myriads}</b> myriads{if $profile->stats.days > 3}, taken on <b>{$profile->stats.days}</b> different days{/if}</li>
					{/if}
					</ul>
				{/if}
			</li>
			{if $profile->stats.content}
				<li style="margin-top:10px"><b>{$profile->stats.content}</b> items of <a href="/content/?user_id={$profile->user_id}" title="view content submitted by {$profile->realname|escape:'html'}">Collections submitted</a>
					{if $user->user_id eq $profile->user_id}
						[<a href="/article/?user_id={$profile->user_id}">Article list</a>]
					{/if}
				</li>
			{/if}
		</ul>
	</div>
{elseif !$userimages}
	<h3>My Statistics</h3>
	<ul>
		  <li>No photographs submitted</li>
	</ul>
{/if}

{if $userimages}
	<div class="interestBox" style="border-radius: 10px;">
	<div style="float:right; position:relative; font-size:0.7em;"><a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1">Find images by {$profile->realname|escape:'html'}</a>, <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbs">Thumbnails</a>, <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=slide">Slideshow</a>, <a href="/browser/#!/q=user{$profile->user_id}/realname+%22{$profile->realname|escape:'url'}%22">Browser</a></div>
	<h3 style="margin-top:0px;margin-bottom:0px">Photographs</h3>
	</div>
	<form action="/search.php" style="display:inline;float:right">
	<input type="hidden" name="form" value="profile"/>
	<label for="fq">Search</label>: <input type="text" name="q" id="fq" size="20"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
	<input type="hidden" name="user_id" value="{$profile->user_id}"/>
	<input type="submit" value="Find"/>
	</form>

	<p style="font-size:0.7em">Click column headers to sort in a different order</p>


{if $profile->tags}
	<div style="clear:both;position:relative;width:1010px">
	<div style="float:left;position:relative;width:790px">
{/if}

	<table class="report sortable" id="photolist" style="font-size:8pt;clear:none;background-color:white">
	<thead><tr>
		<td><img title="Any grid square discussions?" src="{$static_host}/templates/basic/img/discuss.gif" width="10" height="10"> ?</td>
		<td>Grid Ref</td>
		<td>Title</td>
		<td sorted="desc">Submitted</td>
		<td>Image Type</td>
		<td>Points</td>
		<td>Taken</td>
	</tr></thead>
	<tbody>
	{foreach from=$userimages item=image}
		<tr>
		<td sortvalue="{$image->last_post}">{if $image->topic_id}<a title="View discussion - last updated {$image->last_post|date_format:"%a, %e %b %Y at %H:%M"}" href="/discuss/index.php?action=vthread&amp;forum={$image->forum_id}&amp;topic={$image->topic_id}" ><img src="{$static_host}/templates/basic/img/discuss.gif" width="10" height="10" alt="discussion indicator"></a>{/if}</td>
		<td sortvalue="{$image->grid_reference}"><a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></td>
		<td sortvalue="{$image->title|escape:'html'}"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'|default:'untitled'}</a></td>
		<td sortvalue="{$image->gridimage_id}" class="nowrap" align="right">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
		<td class="nowrap">
			{assign var="seperator" value=""}
			{if $image->tags}
				{foreach from=$image->tags item=tag}{if strpos($tag,'type:') === 0}{$seperator} {$tag|replace:'type:':''|escape:'html'}{assign var="seperator" value=","}{/if}{/foreach}
			{/if}

			{if !$seperator || $image->moderation_status eq "rejected"}
				{$seperator} {if $image->moderation_status eq "accepted"}<i style=color:gray>not yet allocated</i>{else}{$image->moderation_status|ucfirst}{/if}
			{/if}
		</td>
		<td>
			{if  $image->moderation_status ne "rejected"}
				{if $image->ftf eq 1}first{elseif $image->ftf eq 2}second{elseif $image->ftf eq 3}third{elseif $image->ftf eq 4}fourth{/if}
				{if $image->ftf gt 0}personal{/if}
				{if $image->points eq 'tpoint'}tpoint{/if}
			{/if}
		</td>
		<td sortvalue="{$image->imagetaken}" class="nowrap" align="right">{if strpos($image->imagetaken,'-00') eq 4}{$image->imagetaken|replace:'-00':''}{elseif strpos($image->imagetaken,'-00') eq 7}{$image->imagetaken|replace:'-00':''|cat:'-01'|date_format:"%b %Y"}{else}{$image->imagetaken|date_format:"%a, %e %b %Y"}{/if}</td>
		</tr>
	{/foreach}
	</tbody></table>

	{if $limit}
		<p>This page shows the latest {$limit} images, more are available <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=text&amp;resultsperpage=100&amp;page=2">via the search interface</a></p>
	{/if}
	{if $profile->stats.images gt 100 && $limit == 100}
		{dynamic}
		{if $user->user_id eq $profile->user_id}
			<form method="get" action="/profile/{$profile->user_id}/more"><input type="submit" value="Show Longer Profile Page"/></form>
		{/if}
		{/dynamic}
	{/if}

</div>

{if $profile->tags}
	<div id="most_used_tags" style="float:left;width:200px;background-color:white;font-size:0.7em;line-height:1.4em; text-align:center;margin:10px;{if count($tags) > 100 && $results} height:150px;overflow:auto{/if}">
	<b><a href="/finder/bytag.php?user_id={$profile->user_id}">Most Used Tags</a></b>:<br/><br/>
	{foreach from=$profile->tags item=item}
		<span class="tag"><a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="/search.php?searchtext=[{if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}]&user_id={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" class="taglink">{$item.tag|capitalizetag|escape:'html'}</a></span> <br/>
	{/foreach}
	</div>

	<br style="clear:both"/>
	</div>
{/if}

	{if !$profile->deceased_date}
		<h3 style="margin-bottom:0px">Explore My Images</h3>
	{/if}
	<ul>

		<li><b>Maps</b>: {if $profile->stats.images gt 10}<a href="/profile/{$profile->user_id}/map" rel="nofollow">Personalised Geograph map</a> or {/if} photos on <a href="/mapper/quick.php?q=user{$profile->user_id}">Quick Interactive Map</a></li>
		<li><b>Browser</b>: <a href="/browser/#!/q=user{$profile->user_id}/realname+%22{$profile->realname|escape:'url'}%22">View images in the Browser</a></li>
		<li><b>Recent Images</b>: <a title="View images by {$profile->realname|escape:'html'} in Google Earth" href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;kml">as KML</a> or <a title="RSS Feed for images by {$profile->realname|escape:'html'}" href="/profile/{$profile->user_id}/feed/recent.rss" class="xml-rss">RSS</a> or <a title="GPX file for images by {$profile->realname|escape:'html'}" href="/profile/{$profile->user_id}/feed/recent.gpx" class="xml-gpx">GPX</a></li>
		{if $profile->stats.images gt 10}
			{dynamic}{if $user->registered}
				<li><b>Download</b>:
					<a title="Comma Seperated Values - file for images by {$profile->realname|escape:'html'}" href="/export.csv.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1&amp;submitted=1&amp;hits=1&amp;tags=1&amp;points=1{/if}">CSV</a>
					{if $user->user_id eq $profile->user_id},
						<a title="Excel 2003 XML - file for images by {$profile->realname|escape:'html'}" href="/export.excel.xml.php?u={$profile->user_id}&amp;supp=1{if $user->user_id eq $profile->user_id}&amp;taken=1&amp;submitted=1&amp;hits=1&amp;tags=1&amp;points=1{/if}">XML<small> for Excel <b>2003</b></small></a>
					{/if} of all images</li>
			{/if}{/dynamic}
		{/if}
		{if $user->user_id eq $profile->user_id}
			<li><b>Wordle</b>: {external href="`$self_host`/stuff/make-wordle.php?u=`$profile->user_id`" text="View words from image titles as a <i>Wordle</i>"} or
				{external href="`$self_host`/stuff/make-wordle.php?u=`$profile->user_id`&amp;tags=1" text="View your tags"}</li>
			<li><b>Change Requests</b>: <a href="/suggestions.php" rel="nofollow">View recent suggestions</a></li>
			{if !$enable_forums}
				<li><b>Submissions</b>: <a href="/submissions.php" rel="nofollow">Edit my recent submissions</a></li>
			{/if}
			<li><b>Uses</b>: <a href="/myphotos.php">Use of my photos around the site</a></li>
			<li><b>2013</b>: <a href="/stuff/your-year.php?choose=1">Your Pictures by Year</a></li>
		{/if}
	</ul>
	{if $user->user_id eq $profile->user_id}
		<ul>
		<li><a href="/browser/my_squares-redirect.php?days=30">Browse submissions in last 30 days in squares I have photographed</a>
		   (<a href="/search.php?my_squares=1&amp;user_id={$profile->user_id}&amp;user_invert_ind=1&amp;submitted_startDay=30&amp;submitted_startYear">Old Search</a>)</li>
		</ul>
	{/if}
{/if}


<div align="right"><a href="#top">Back to Top</a></div>

{include file="_std_end.tpl"}
