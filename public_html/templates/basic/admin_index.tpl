{assign var="page_title" value="Geograph Admin"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent li { padding-bottom:10px;}
#maincontent b {
	background-color:#eeeeee;
	padding:4px;
	padding-top:0px;
}
</style>{/literal}
{dynamic}

<h2>Administrative Tasks</h2>

{$status_message}

<ul>

{if $names_pending}
	<li><a href="/games/approve.php">Game Usernames</a> (<a href="/games/approve.rss.php" class="xml-rss">RSS</a>)<br/>
	<b>[{$names_pending} pending]</b></li>
{/if}

{if $is_mod} 
	<li><a href="/admin/moderation.php">Moderate</a> new photo submissions (<span><a href="/admin/moderation.php?review=1" style="color:gray">Review</a>)</span><br/>
	<b>[{$images_pending.count} pending, {$images_pending_available} available to moderate, oldest is {$images_pending.age/3600|thousends} hours]</b></li>

<li style=color:red>Note: If find a broken or missing image, please in the first instance report it via <a href="/stuff/image_report_form.php">Image Report Form</a>. (this form can be posted publically for anyone to use, not just for moderators)</li>


	{if $ci_pending}
		<li>There are <b>{$ci_pending}</b> <a href="http://www.geograph.org.gg/admin/">pending images on Geograph Channel Islands</a> (note this message is cached for an hour, so may be slightly outdated) </li>
	{/if}
{/if}

{if $is_tickmod} 
	<li><a title="Trouble Tickets" href="/admin/suggestions.php">Change Suggestions</a> - 
	   Deal with image problems<br/> <b>[{$tickets_new} new, {$tickets_yours} <a href="/admin/suggestions.php?type=open&amp;theme=tmod">open by you</a>]</b></li>
{/if}



{if $is_mod} 
	<li>{external href="https://company.geograph.org.uk/support/scp/tickets.php" text="Ticket List in the Geograph Support System"}<br/>
                [now hosted externally, so have no details of open tickets]</li>

	{if $originals_new}
		<li><a href="/admin/resubmissions.php">High Resolution Uploads</a><br/>
		<b>[{$originals_new} ready to be verified]</b></li>
	{/if}

	{if $articles_ready}
		<li><a href="/article/">Articles</a><br/>
		<b>[{$articles_ready} ready to be approved]</b></li>
	{/if}

	{if $unanswered_faq}
		<li><a href="/faq-unanswered.php">Unanswered FAQ Questions</a><br/>
		<b>[{$unanswered_faq} new questions in last 2 weeks]</b></li>
	{/if}

	<li{if $gridsquares_sea_test > 0} style="color:lightgrey">
	<b>Map-fixing in Progress</b> - please come back later.<br/>
	{else}>{/if}

	<form method="get" action="http://{$http_host}/admin/mapfixer.php" style="display:inline">

	<a title="Map Fixer" href="http://{$http_host}/admin/mapfixer.php">Map Fixer</a>: <label for="gridref">Grid Reference:</label>
	<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}"/>
	<span class="formerror">{$gridref_error}</span>
	<input type="submit" name="show" value="Check"/> or <a href="/mapfixer.php">add to queue</a><br/>
	<small>allows the land percentage
	for each 1km grid squares to be updated, which allows "square is all at sea" to be 
	corrected</small><br/>
	{if $gridsquares_sea.1 || $gridsquares_sea.2}<b>[GB:{$gridsquares_sea.1},I:{$gridsquares_sea.2} in queue]</b>{/if}
	</form>
	</li>

	<li><a href="/admin/geotrips.php">GeoTrips Admin</a></li>

</ul>
<h3>Tools</h3>
<ul>
	<li><a href="/admin/recent.php">Recently Moderated Images</a> - just a simply listing of images you most recently moderated</li>

	<li><a title="Picture of the day" href="/admin/pictureoftheday.php">Picture of the Day</a> - 
	   choose daily picture selections
	   {if $pics_no_vote > 0}
		<br/><small><b>there are {$pics_no_vote} images waiting to be rated, please <a href="/search.php?i=5761957&amp;temp_displayclass=vote">Vote now</a> or <a href="/search.php?i=5761957&amp;temp_displayclass=blackvote">as GeoRiver</a> (only shows unrated)</b> - don't disclose this url</small>
	   {elseif $pics_pending < 5}
		<br/><small><b>There are only {$pics_pending} images waiting to be displayed as picture of the day, please consider adding some!</b></small>
	   {else}
		(<b><a href="/search.php?i=5761957&amp;temp_displayclass=vote">Vote now!</a></b> - don't disclose this url)
	   {/if}</li>

	   <li>
		<form method="post" action="pictureoftheday.php">
			Quick Add: 
			<label for="addimage">Image number</label>
			<input type="text" name="addimage" size="8" id="addimage" value="" title="enter the id, or even just the photo page URL"/>
			<input type="button" value="Preview" onclick="window.open('/?potd='+this.form.addimage.value);">
			<input type="submit" name="add" value="Add"/>
		</form>
	   </li>

{else}
</ul>
<h3>Tools</h3>
<ul>
{/if}

<li>Stats: <br/>
   <a href="/statistics/admin_turnaround.php">Turn Around</a> (<a href="/statistics/admin_turnaround.php?u={$user->user_id}">You</a>) - 
   rough estimate at moderation times <br/>
   <a title="Web Stats" href="/statistics/pulse.php">Geograph Pulse</a> - 
   up to the minute general site status</li>


{if $is_mod && 0} 
<li>

<div class="interestBox" style="border:1px solid red;padding:3px">
	Please don't correct 2 'firsts' in a square with this tool (or otherwise) - we will correct them automatically shortly.
</div> 


<form method="get" action="/search.php" style="display:inline">
Remoderate a Square: <label for="gridref">Grid Reference:</label>
<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}"/>
<span class="formerror">{$gridref_error}</span>
<input type="submit" name="do" value="Moderate"/>
<input type="hidden" name="distance" value="1"/>
<input type="hidden" name="orderby" value="submitted"/>
<input type="hidden" name="displayclass" value="moremod"/>
<input type="hidden" name="resultsperpage" value="100"/>
</form></li>
{/if}
</ul>

{if $is_director}
<br/><br/>
<h2>Director Tools - use with care</h2>
<ul>

<li><a title="Moderators" href="/admin/moderator_admin.php">Moderator Admin</a> - 
   grant/revoke moderator rights to users</li>

<li><a href="/admin/discuss_reports.php">Forum Reports</a> - 
   reported inapprirate threads</li>

<li><a href="/admin/viewbounces.php">View Email Bounce/Complains</a> - 
   issues detected by Amazon system</li>

<li><a title="API Keys" href="/admin/apikeys.php">API Keys</a> - 
   setup who has access to the API</li>

</ul>
{/if}

{if $is_admin}
<br/><br/>
<h2>Admin Tools - use with care</h2>
<ul>

<li><a title="Category Consolidation" href="/admin/categories.php">Category Consolidation</a> - 
   organise the user submitted categories</li>

</ul>
<h3>Statistics</h3>
<ul>  

<li><a href="/admin/status.php">Server Status</a></li>

<li><a href="/admin/memcache.php">Memcache Status</a></li>

<li><a title="Web Stats" href="/statistics/pulse.php">Geograph Pulse</a> - 
   upto the minute general site status</li>

<li><a title="Forum Stats" href="/discuss/?action=stats">Forum Stats</a> - 
   view forum activity stats</li>

<li><a title="Events" href="events.php">Event Diagnostics</a> - see what the event system is doing</li>

</ul>
<h3>Developer Tools</h3>
<ul>

<li><a title="Custom Search" href="/search.php?form=advanced&Special=1">Create Custom Search</a> - create a one off special search (sql required)</li>

<li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a> is a simple tool for checking
the internal land/sea map</li>

</ul>
{/if}
    
{/dynamic}

{include file="_std_end.tpl"}
