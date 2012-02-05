{assign var="page_title" value="the Geograph Team"}
{include file="_std_begin.tpl"}

{literal}
<style>
.names {
   text-align:center;
   line-height:2.2em;
}
.name {
   white-space:nowrap;
   padding:4px;
   font-size:1.1em;
   color:#222222;
   text-decoration:none;
   margin-right:3px;
}

.name:hover {
   color:blue;
}

.name img {
	width:25px;
	height:25px;

    opacity: .5;
	filter: alpha(opacity=50);
	-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
}
.name:hover img {
    opacity: 1;
	filter: alpha(opacity=100);
	-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
}

</style>
{/literal}

<div align="center" class="tabHolder">
        <a href="/article/About-Geograph-page" class="tab">About Geograph</a>
        <span class="tabSelected">The Geograph Team</span>
        <a href="/credits/" class="tab">Contributors</a>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="http://hub.geograph.org.uk/downloads.html" class="tab">Downloads</a>
        <a href="/contact.php" class="tab">Contact Us</a>
	<a href="/article/Get-Involved">Get Involved...</a>
</div>
<div style="position:relative;" class="interestBox">
        <h2 align="center" style="margin:0">The Geograph Team</h2>
</div>

{if $team}
	<p align="center">These are some of the amazing people who help out with the running of the Geograph Project... 
		<span style="font-size:0.7em"><br/>Hover over a name to see the role(s) they do</span></p>

	<div class="names">

	{foreach from=$team item=userrow}
		{if $userrow.role ne 'Member' || $userrow.rights ne ''}
			<a href="/profile/{$userrow.user_id}" class="name" title="Nickname: {$userrow.nickname|escape:'html'|default:'none'}, Role(s): {$userrow.rights}"><img src="http://www.gravatar.com/avatar/{$userrow.md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=25&amp;s=25" align="absmiddle" alt="{$userrow.realname|escape:'html'}'s Gravatar"/> {$userrow.realname|escape:'html'}</a>
		{/if}
		{if $userrow.role eq 'Member'}
			{assign var="hist" value="1"}
		{/if}
	{/foreach}

	</div>
	<br/>

	<div class="interestBox" align="center">Want to see your name on this list? <a href="/article/Get-Involved">Get Involved</a>!</div>

	{if $hist}
	<p align="center">The following members have also helped out in various capacities previously...</p>


	<div class="names" style="font-size:0.9em">
	{foreach from=$team item=userrow}
		{if $userrow.role eq 'Member'}
			<a href="/profile/{$userrow.user_id}" class="name"{if $userrow.rights} title="Current Role(s): {$userrow.rights}"{/if}><img src="http://www.gravatar.com/avatar/{$userrow.md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=25&amp;s=25" align="absmiddle" alt="{$userrow.realname|escape:'html'}'s Gravatar"/> {$userrow.realname|escape:'html'}</a>
		{/if}
	{/foreach}
	</div>
	{/if}
{else}
  <p>There are no moderators !?!</p>
{/if}

<br/>
<div class="interestBox" align="center" style="font-size:0.7em"> -
        <span class="nowrap"><b>Founder</b> : Started the project back in 2005!</span> - 
        <span class="nowrap"><b>Developer</b> : Writes code and keeps the site running</span> -
        <span class="nowrap"><b>Company Director</b> : Makes sure the company is working to further the project</span> -
        <span class="nowrap"><b>Moderator</b> : Checks new submissions for unsuitable material and faciliates updates to images</span> -
        <span class="nowrap"><b>Complaints Resolution</b> : Liaises with landowners and other parties in case of disputes</span> -
        <span class="nowrap"><b>PoTY Coordinator</b> : Organizes the weekly photo competition</span> -
        <span class="nowrap"><b>Forum Moderator</b> : Keeps the discussion forum in check</span> -
        <span class="nowrap"><b>Documentation Writer</b> : Create pages to help site users find their way around the site</span> -
	<span class="nowrap"><b>Moderator Coordinator</b> : Central point of contact for communication between moderators</span> -
        <span class="nowrap"><b>Support Representative</b> : Answers questions submitted via <a href="/contact.php">Contact Us</a></span> -
</div>

{dynamic}
{if $user->registered && ($user->stats.squares gt 20)}
	<p align="center">If you are interested in helping out with moderation then please visit your <a href="/profile.php?edit=1">profile update page</a>, at the bottom of which there is a button to apply and get a feel for the moderation process. Please note, however, that we have a long waiting list!</p>
{/if}
{/dynamic}

{include file="_std_end.tpl"}

