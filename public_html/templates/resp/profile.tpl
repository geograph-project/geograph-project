{if $userimages && $profile->user_id != 1695}
{assign var="extra_meta" value="<link rel=\"canonical\" href=\"`$self_host`/profile/`$profile->user_id`\" />"}
{else}
{assign var="extra_meta" value="<meta name=\"robots\" content=\"noindex, nofollow\" />"}
{/if}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
{/literal}
</style>

<div style="float:right; background-color: white; margin:auto; text-align:right">
<details class="dropdown">
<summary>Navigation</summary>
<ul style="list-style-type:none; right:0;">
<li><a href="#basicdetails">Basic details</a></li>
<li><a href="#statistics">Statistics</a></li>
{if $userimages}<li><a href="#explore">Explore images</a></li>{/if}
{if $profile->about_yourself && $profile->public_about}<li><a href="#about">About me</a></li>{/if}
{if $userimages}<li><a href="#images">Photos</a></li>{/if}
</ul>
</details>
</div>

<h2 style=margin-bottom:0><a name="top"></a><img src="{if $profile->md5_email}https://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50{else}https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=30{/if}" align="absmiddle" alt="{$profile->realname|escape:'html'}'s Gravatar" {if $profile->md5_email}width=50 height=50{else}width=30 height=30{/if} style="margin-right:10px"/>Profile for {$profile->realname|escape:'html'}</h2>

<br style="clear:both"/>

{if $user->user_id eq $profile->user_id}
<div style="border-style: double; padding: 6px; border-color: grey"><img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="18" height="16" align="left" style="margin-right:10px"/>
{if $simplified}
This is your <b>private profile</b> and includes additional information and links which aren't shown on your <a href="/profile/{$user->user_id}">full public profile</a>. {if $profile->about_yourself && $profile->public_about}Your 'About me' box is hidden in your private profile, and is only visible on your public profile.{/if}
{else}
This is your <b>public profile</b> and appears as it will to site visitors. For additional links, view your <a href="/profile.php">private profile</a>.
{/if}
<br/><br/>
<a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</div><br/>
{/if}


{*-------------------------Warning messages---------------------------*}

{dynamic}
{if $credit_realname}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<img src="/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	The image you were viewing was contributed by the user below, but is specifically credited to <b>{$credit_realname|escape:'html'}</b>
	</div>
	<br/><br/>
{/if}

{if $bounce_message}
	<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
		{$bounce_message}
		If you have an alternate email address you can use, then <a href=/profile.php?edit=1>Edit your profile</a> to use it.
		Otherwise we hope to soon have a way to reenable delivery, in the meantime if you have concerns, please <a href="/contact.php">contact us</b>. 
	</div>

{elseif $profile->tickets}
	<div id="ticket_message">
		{if $profile->last_ticket_time}
			<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
			You have <b>{$profile->tickets}</b> ongoing suggestions on your images, please go to <a href="/suggestions.php">your suggestions page</a> to review them.
			<small><br/><br/><a href="javascript:void(hide_message())">I've read this, please hide</a> </small>
			</div>
			<br/><br/>
		{else}
			<div style="text-align:center">You have <b>{$profile->tickets}</b> ongoing suggestions on your images, please go to <a href="/suggestions.php">your suggestions page</a> to review them. <a href="javascript:void(hide_message())">hide this</a></div>
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

{*---------------------------Two col setup-------------------------*}


<div class="threecolsetup">



{*------------------------Basic details----------------------------*}
<div class="threecolumn">
<a name="basicdetails"></a>
<h3>Basic details</h3>



<div style="display: flex; flex-direction: row; flex-wrap: wrap">
<div>
<ul style="margin-top:0">
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

		{if $profile->role && $profile->role ne 'Member'}
			<li><b>Geograph Role</b>: {$profile->role}</li>
		{elseif strpos($profile->rights,'admin') > 0}
			<li><b>Geograph Role</b>: Developer</li>
		{elseif strpos($profile->rights,'moderator') > 0}
			<li><b>Geograph Role</b>: Moderator</li>
		{/if}
		{if strpos($profile->rights,'member') > 0}
		        <li><i>Geograph Project Limited Company Member</i>
				{if $company_link}<br>
					<a href="{$company_link|escape:'html'}" target="_blank">Follow this link to be taken to the company mini-site</a>
				{/if}
			</li>
		{/if}

	{/if}
</ul>
</div>

{if $overview}
  <div style="width:{$overview_width+30}px; margin: auto">
  {include file="_overview.tpl"}
  </div>
{/if}
</div>


</div>



{*---------------------------Statistics-------------------------*}
<div class="threecolumn">
<a name="statistics"></a>
<h3>Statistics</h3>

{if $profile->stats.images gt 0}


<h4>{$profile->stats.images|thousends}</b> <a href="#images">Photograph{if $profile->stats.images ne 1}s{/if}</a> submitted</h4>

{if $profile->stats.squares gt 1}
      <ul>
					{if $profile->stats.geographs}<li><b>{$profile->stats.geographs|thousends}</b> Geograph{if $profile->stats.geographs ne 1}s{/if}
						{if $profile->stats.geographs != $profile->stats.images} and <b>{$profile->stats.images-$profile->stats.geographs|thousends}</b> others{/if}
						</li>
					{/if}
					<li>in <b>{$profile->stats.squares|thousends}</b> grid square{if $profile->stats.squares ne 1}s{/if}{if $profile->stats.squares>3322} (<b>{math equation="x/332182*100" x=$profile->stats.squares format="%.1f"}%</b>){/if},
						<span class=nowrap>giving a depth score of <b>{$profile->stats.depth|string_format:"%.2f"}</b></span></li>
					{if $profile->stats.hectads > 1}
						<li>in <b>{$profile->stats.hectads|thousends}</b> different <span class=nowrap>hectads{if $profile->stats.hectads > 38} (<b>{math equation="x/3877*100" x=$profile->stats.hectads format="%.1f"}%</b>){/if}</span>
							<span class=nowrap>and <b>{$profile->stats.myriads}</b> myriads{if $profile->stats.myriads > 7} (<b>{math equation="x/76*100" x=$profile->stats.myriads format="%.1f"}%</b>){/if}</span>
							{if $profile->stats.days > 3}and <span class=nowrap>taken on <b>{$profile->stats.days|thousends}</b> different days</span>{/if}</li>
					{/if}
     </ul>
{/if}




<h4>Points</h4>
<ul>
{if $profile->stats.points}
				<li><b>{$profile->stats.points|thousends}</b> First Geograph points
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
					<b>{$profile->stats.seconds|thousends}</b> Second Visitor points,
				{/if}
				{if $profile->stats.thirds}
					<span class=nowrap><b>{$profile->stats.thirds|thousends}</b> Third Visitor points,</span>
				{/if}
				{if $profile->stats.fourths}
					<span class=nowrap><b>{$profile->stats.fourths|thousends}</b> Fourth Visitor points</span>
				{/if}
				</li>
{/if}
{if $profile->stats.geosquares}
				<li><b>{$profile->stats.geosquares|thousends}</b> Personal points (grid square{if $profile->stats.geosquares ne 1}s{/if} <i>geographed</i>)
					{if $user->user_id eq $profile->user_id && $profile->stats.geo_rank > 0}
						<ul style="font-size:0.8em;margin-bottom:2px">
						<li>Overall Rank: <b>{$profile->stats.geo_rank|ordinal}</b> {if $profile->stats.geo_rank > 1}({$profile->stats.geo_rise} more needed to rise rank){/if}</li>
						</ul>
					{/if}
				</li>
{/if}
{if $profile->stats.tpoints}
				<li style="padding-bottom:3px"><b>{$profile->stats.tpoints|thousends}</b> TPoints (Time-gap points <sup><a href="/help/stats_faq#tpoints" class="about" style="font-size:0.6em">About</a></sup>)</li>
{/if}
			
</ul>
      
      
{if $profile->stats.content}
      <h4>Collections</h4>
      <ul>
				<li style="margin-top:10px"><b>{$profile->stats.content|thousends}</b> items of <a href="/content/?user_id={$profile->user_id}&amp;scope=article,gallery,blog,trip" title="view content submitted by {$profile->realname|escape:'html'}">collections submitted</a>
					{if $user->user_id eq $profile->user_id && $simplified}
						[<a href="/article/?user_id={$profile->user_id}">just articles</a>]
					{/if}
				</li>
        </ul>
{/if}

{if $profile->blog.blog_id}
	<p>&middot; My latest blog entry: <a href="/blog/{$profile->blog.blog_id}">{$profile->blog.title|escape:'html'}</a> <small>({$profile->blog.created})</small></p>
{/if}

{if !$profile->deceased_date}
<div style="float:right;">Last updated: {$profile->stats.updated|date_format:"%H:%M"}</div>
{/if}


{elseif !$userimages}
	<ul>
		  <li>No photographs submitted</li>
	</ul>
{/if}




</div>




{*------------------------Explore----------------------------*}
<div class="threecolumn">

{if $userimages}
<a name="explore"></a>
<h3>Explore images</h3>

<form action="/search.php" style="text-align:center">
  <input type="hidden" name="form" value="profile"/>
  <input type="text" name="q" placeholder="enter search query" id="fq" {dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic} style="width: 400px;max-width: 70%"/>
	<input type="hidden" name="user_id" value="{$profile->user_id}"/>
	<input type="submit" value="Find"/>
</form>

<ul class="buttonbar">

<div class="buttonbar-dropdown">
  <button>View images in the search &#9660;</button>
  <div class="buttonbar-dropdown-content">
    <b>Recent submissions</b>
				<a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=full&amp;do=1">Full details</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbs&amp;do=1">Thumbnails</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=grid&amp;do=1">Thumbnails grid</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=slide&amp;do=1">Slideshow</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=map&amp;do=1">Map</a>
        <a href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;displayclass=black&amp;do=1">Georiver</a>
  </div>
</div> 

<div class="buttonbar-dropdown">
  <button>One image per... &#9660;</button>
  <div class="buttonbar-dropdown-content">
    <b>Day taken</b>
				<a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=full&amp;do=1">Full details</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=thumbs&amp;do=1">Thumbnails</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=grid&amp;do=1">Thumbnails grid</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=slide&amp;do=1">Slideshow</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=map&amp;do=1">Map</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=takendays&amp;breakby=imagetaken&amp;orderby=imagetaken&amp;displayclass=black&amp;do=1">Georiver</a>
    <b>Gridsquare</b>
				<a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=full&amp;do=1">Full details</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=thumbs&amp;do=1">Thumbnails</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=grid&amp;do=1">Thumbnails grid</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=slide&amp;do=1">Slideshow</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=map&amp;do=1">Map</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=agridsquare&amp;breakby=grid_reference&amp;orderby=sequence&amp;displayclass=black&amp;do=1">Georiver</a>
    <b>Hectad</b>
				<a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=full&amp;do=1">Full details</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=thumbs&amp;do=1">Thumbnails</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=grid&amp;do=1">Thumbnails grid</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=slide&amp;do=1">Slideshow</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=map&amp;do=1">Map</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=ahectad&amp;breakby=hectad&amp;orderby=sequence&amp;displayclass=black&amp;do=1">Georiver</a>
    <b>Myriad</b>
				<a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=full&amp;do=1">Full details</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=thumbs&amp;do=1">Thumbnails</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=thumbsmore&amp;do=1">Thumbnails + links</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=bigger&amp;do=1">Thumbnails - bigger</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=grid&amp;do=1">Thumbnails grid</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=slide&amp;do=1">Slideshow</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=map&amp;do=1">Map</a>
        <a href="/search.php?u={$profile->user_id}&amp;groupby=amyriad&amp;breakby=myriad&amp;orderby=sequence&amp;displayclass=black&amp;do=1">Georiver</a>
  </div>
</div> 


{if $user->user_id eq $profile->user_id && $simplified}
	<li><a href="/mapper/combined.php?mine=1#5/56.317/-2.769">Personalised coverage map</a></li>
{/if}

<li><a href="/browser/#!/q=user{$profile->user_id}/realname+%22{$profile->realname|escape:'url'}%22">Browser</a></li>

<li><a href="/explore/calendar.php?u={$profile->user_id}">Calendar</a></li>


{if $profile->stats.images > 2}
<div class="buttonbar-dropdown">
  <button>Detailed breakdown &#9660;</button>
  <div class="buttonbar-dropdown-content">
  <b>Statistics</b>
	<a href="/statistics/breakdown.php?by=takenyear&u={$profile->user_id}">by Date taken</a>
	<a href="/statistics/breakdown.php?by=gridsq&u={$profile->user_id}">by Myriad</a>
	<a href="/finder/bytag.php?user_id={$profile->user_id}">by Tags</a>
	<a href="/finder/groups.php?q=user{$profile->user_id}&amp;group=context_ids">by Geographical context</a>
  </div>
</div> 
{/if}

{if $user->user_id eq $profile->user_id && $simplified && $profile->stats.images gt 2}

<div class="buttonbar-dropdown">
  <button>Download a list of submissions &#9660;</button>
  <div class="buttonbar-dropdown-content">
	<a href="/export.csv.php?u={$profile->user_id}&amp;supp=1&amp;taken=1&amp;submitted=1&amp;hits=1&amp;tags=1&amp;points=1">as a CSV file (Comma separated values)</a>
	<a href="/export.excel.xml.php?u={$profile->user_id}&amp;supp=1&amp;taken=1&amp;submitted=1&amp;hits=1&amp;tags=1&amp;points=1">as an XML file for Excel 2003</a>
  </div>
</div>

<div class="buttonbar-dropdown">
  <button>Word Cloud &#9660;</button>
  <div class="buttonbar-dropdown-content">
	<a href="/stuff/make-wordle.php?u={$profile->user_id}">Image titles</a>
	<a href="/stuff/make-wordle.php?u={$profile->user_id}&amp;tags=1">Tags</a>
  </div>
</div>

	<li><a href="/myphotos.php">My photos used around the site</a></li>

	{if $profile->stats.images gt 24}
		<li><a href="/stuff/your-year.php?choose=1">Annual showcase</a></li>
	{/if}

<div class="buttonbar-dropdown">
  <button>Browse submissions in last 30 days in squares I have photographed &#9660;</button>
  <div class="buttonbar-dropdown-content">
	<a href="/browser/my_squares-redirect.php?days=30">In the Browser</a>
	<a href="/search.php?my_squares=1&amp;user_id={$profile->user_id}&amp;user_invert_ind=1&amp;submitted_startDay=30&amp;submitted_startYear">In the old search</a>
  </div>
</div>
{/if}

</ul>


<div style="text-align:center">Recent Images:
<a title="View images by {$profile->realname|escape:'html'} in Google Earth" href="/search.php?u={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;kml" class="xml-kml">KML</a> 
<a title="RSS Feed for images by {$profile->realname|escape:'html'}" href="/profile/{$profile->user_id}/feed/recent.rss" class="xml-rss">RSS</a> 
<a title="GPX file for images by {$profile->realname|escape:'html'}" href="/profile/{$profile->user_id}/feed/recent.gpx" class="xml-gpx">GPX</a>
</div>


{/if}



{*------------------------Tags----------------------------*}

{if $profile->tags}
<a name="tags"></a>
<h3>Most Used Tags</h3>

<ul class="buttonbar">
	{foreach from=$profile->tags item=item}
		{if $item.prefix != 'type'}
			<li><a title="{$item.images} images" {if $item.images > 10} style="font-weight:bold"{/if} href="/search.php?searchtext=[{if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}]&user_id={$profile->user_id}&amp;orderby=submitted&amp;reverse_order_ind=1&amp;do=1" class="taglink">{$item.tag|capitalizetag|escape:'html'}</a></li>
		{/if}
	{/foreach}
</ul>
<p align="center">View a <a href="/finder/bytag.php?user_id={$profile->user_id}">full breakdown of tags</a></p>
{/if}

</div>

</div>

<br style="clear:both">

{*------------------------About----------------------------*}

{if $profile->about_yourself && $profile->public_about && ($userimages || $user->user_id eq $profile->user_id) && !$simplified}

	{if !$profile->deceased_date}
  <a name="about"></a>
			<h3 style="color: black; font-weight:bold; text-align: center; background: silver; border-radius: 10px; padding: 2px;">About Me</h3>

		<div style="padding-left:10px">
			{$profile->about_yourself|TruncateWithExpand:'(<small>this is a preview only</small>) <big><b>Further information</b></big>...'|nl2br|GeographLinks:true}
		</div>
	{else}
		{$profile->about_yourself|TruncateWithExpand:'(<small>this is a preview only</small>) <big><b>Further information</b></big>...'|nl2br|GeographLinks:true}
	{/if}

{/if}

{*-------------------------Photographs---------------------------*}

{if $userimages}

<a name="images"></a>
<h3 style="color: black; font-weight:bold; text-align: center; background: silver; border-radius: 10px; padding: 2px;">{if $limit && $userimages[0]->submitted>date('Y')}Recent {/if}Photographs</h3>


<table class="report width700 sortable" id="photolist" style="clear:none;background-color:white;position:relative">
	<thead><tr style="position:sticky;top:0;">
		<th>Grid Ref</td>
		<th>Title</td>
		<th sorted="desc">Submitted</td>
		<th>Image Type</td>
		<th>Points</td>
		<th>Taken</td>
	</tr></thead>
	<tbody>
	{foreach from=$userimages item=image}
		<tr>
		<td data-title="Grid ref" sortvalue="{$image->grid_reference}"><a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></td>
		<td data-title="Title" sortvalue="{$image->title|escape:'html'}"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'|default:'untitled'}</a></td>
		<td data-title="Submitted" sortvalue="{$image->gridimage_id}" class="nowrap" align="right">{$image->submitted|date_format:"%a, %e %b %Y"}</td>
		<td data-title="Image type">
			{assign var="seperator" value=""}
			{if $image->tags}
				{foreach from=$image->tags item=tag}{if strpos($tag,'type:') === 0}{$seperator} {$tag|replace:'type:':''|escape:'html'}{assign var="seperator" value=","}{/if}{/foreach}
			{/if}

			{if !$seperator || $image->moderation_status eq "rejected"}
				{$seperator} {if $image->moderation_status eq "accepted"}<i style=color:gray>not yet allocated</i>{else}{$image->moderation_status|ucfirst}{/if}
			{/if}
		</td>
		<td data-title="Points">
			{if  $image->moderation_status ne "rejected"}
				{if $image->ftf eq 1}first{elseif $image->ftf eq 2}second{elseif $image->ftf eq 3}third{elseif $image->ftf eq 4}fourth{/if}
				{if $image->ftf gt 0}personal{/if}
				{if $image->points eq 'tpoint'}tpoint{/if}
			{/if}
		</td>
		<td data-title="Taken" sortvalue="{$image->imagetaken}" class="nowrap" align="right">{if strpos($image->imagetaken,'-00') eq 4}{$image->imagetaken|replace:'-00':''}{elseif strpos($image->imagetaken,'-00') eq 7}{$image->imagetaken|replace:'-00':''|cat:'-01'|date_format:"%b %Y"}{else}{$image->imagetaken|date_format:"%a, %e %b %Y"}{/if}</td>
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


{/if}





<div align="right"><a href="#top">Back to Top</a></div>

<br style="clear:both"/>

{include file="_std_end.tpl"}
