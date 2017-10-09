</div>
</div>
<div id="nav_block" class="no_print">
 <div class="nav">
  <ul id="treemenu1" class="treeview">
    <li style="font-size:1.42em"><a accesskey="1" title="Home Page" href="/">Home</a></li>
    <li>View<ul rel="open">
     <li><a title="Find images" href="/search.php">Search</a><ul>
      <li><a title="Advanced image search" href="/search.php?form=text">Advanced</a></li>
      <li><a title="Advanced image search" href="/finder/">More</a></li>
     </ul></li>
     <li><a title="View map of all submissions" href="/mapper/coverage.php#zoom=7&lat=53.52506&lon=-8.092&layers=FFT000000000000BFT">Maps</a><ul>
      <li><a title="Depth Map" href="/mapbrowse.php?depth=1">Depth</a></li>
      <li><a title="Other Map" href="/help/maps">...more</a></li>
     </ul></li>
     <li><a title="Interactive browser, search and map in one" href="/browser/#!/country+(%22Northern+Ireland%22+%7C+%22Republic+of+Ireland%22)">Browser</a></li>
     <li><a title="Explore Images by Theme" href="/explore/">Explore</a><ul>
      <li><a href="/statistics/fully_geographed.php">Mosaics</a></li>
      <li><a href="/explore/routes.php">Routes</a></li>
      <li><a href="/explore/places/2/">Places</a></li>
      <li><a href="/explore/calendar.php">Calendar</a></li>
      <li><a href="/explore/searches.php">Featured</a></li>
      <li><a href="/gallery.php">Showcase</a></li>
     </ul></li>
     <li><a title="Content" href="/content/">Collections</a><ul>
      <li><a href="/article/">Articles</a></li>
      <li><a href="/gallery/">Galleries</a></li>
      <li><a href="http://www.geograph.org.uk/geotrips/">Geo-Trips</a></li>
     </ul></li>
     <li><a title="Activities" href="/activities/">Activities</a><ul>
      <li><a title="Play Games" href="/games/">Games</a> </li>
      <li><a title="Imagine the map in pictures" href="/help/imagine">Imagine</a></li>
     </ul></li>
    </ul></li>
    <li>Contribute<ul rel="open">
     <li><b><a title="Submit" href="/submit.php">Submit</a></b><ul>
      <li><a title="Submit Version 2" href="/submit2.php">Submit v2</a></li>
      <li><a title="Multi-Uploader Submission" href="/submit-multi.php">Multi-Submit</a></li>
      <li><a title="Recent Submissions" href="/submissions.php">Recent Uploads</a></li>
      <li><a title="Your Profile" href="/profile.php">Your Profile</a></li>
     </ul></li>
     <li><a title="Statistics" href="/numbers.php">Statistics</a><ul>
      <li><a title="More Stats" href="/statistics.php">More Stats</a></li>
      <li><a title="Credits" href="/credits/">Contributors</a></li>
     </ul></li>
     <li><a title="Leaderboard" href="/statistics/moversboard.php">Leaderboard</a></li>
     <li><a title="Content" href="/article/Content-on-Geograph">Content</a></li>
    </ul></li>
    <li>Interact<ul rel="open">
     <li><a title="Discuss" href="/discuss/">Discussions</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Geograph Blog" href="/blog/">Blog</a></li>
     <li><a title="Chat" href="/chat/">Chat</a> {if $irc_seen}<span style="color:gray">({$irc_seen} online)</span>{/if}</li>
     <li><a title="Find out about local Events" href="/events/">Events</a></li>
     {/if}{/dynamic}
    </ul></li>
    <li>Export<ul>
     <li><a title="KML" href="/kml.php">Google Earth/Maps</a></li>
     <li><a title="Memory Map Exports" href="/memorymap.php">Memory Map</a></li>
     <li><a title="GPX Downloads" href="/gpx.php">GPX</a></li>
     <li style="font-size:0.9em;"><a title="API" href="/help/api">API</a></li>
    </ul></li>
    <li>Further Info<ul rel="open">
     <li><a title="Recent Geograph Announcements" href="/news.php">Latest News</a></li>
     <li><a title="FAQ" href="/faq3.php?l=0">FAQ</a><ul>
      <li><a title="Geograph Documents" href="/content/documentation.php">Project Info</a></li>
     </ul></li>
     <li><a title="View All Pages" href="/help/sitemap">Sitemap</a><ul>
      <li><a title="View More Pages" href="/help/more_pages">More Pages</a><ul>
     </ul></li>

     <li><a accesskey="9" title="Contact Us" href="/contact.php">Contact Us</a><ul>
      <li><a title="The Geograph Team" href="/admin/team.php">The Team</a></li>
      <li><a href="/help/credits" title="Who built this and how?">Credits</a></li>
     </ul></li>
    </ul></li>
  {dynamic}
  {if $is_mod || $is_admin || $is_tickmod}
    <li>Admin<ul rel="open">
     <li><a title="Admin Tools" href="/admin/">Admin Home</a></li>
     {if $is_mod}
     	<li><a title="Moderation new photo submissions" href="/admin/moderation.php">Moderation</a></li>
     {/if}
     {if $is_tickmod}
     	<li><a title="Change Suggestions" href="/admin/suggestions.php">Suggestions</a></li>
     {/if}
     <li><a title="Finish Moderation for this session" href="/admin/moderation.php?abandon=1">Finish</a></li>
    </ul></li>
  {/if}
  {/dynamic}
  </ul>
<div class="sponsor">sponsored by <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/"><img src="{$static_host}/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></div>
{if $image && $image->collections}
	<h3 class="newstitle">This photo is linked from:</h3>
	{assign var="lasttype" value="0"}
	{foreach from=$image->collections item=item}
		{if $lasttype != $item.type}
			<div class="newsheader">{$item.type|regex_replace:"/y$/":'ie'}s</div>
		{/if}{assign var="lasttype" value=$item.type}
		<div class="collection">&middot; <a href="{$item.url}" title="{$item.type|escape:'html'}">{$item.title|escape:'html'}</a></div>
	{/foreach}
{elseif $square && $square->collections}
	<h3 class="newstitle">Collections:</h3>
	{assign var="lasttype" value="0"}
	{foreach from=$square->collections item=item}
		{if $lasttype != $item.type}
			<div class="newsheader">{$item.type|regex_replace:"/y$/":'ie'}s</div>
		{/if}{assign var="lasttype" value=$item.type}
		<div class="collection">&middot; <a href="{$item.url}" title="{$item.type|escape:'html'}"{if $item.type == $square->grid_reference} style="font-weight:bold"{/if}>{$item.title|escape:'html'}</a></div>
	{/foreach}
{/if}
{if $discuss}
	{foreach from=$discuss item=newsitem}
		<h3 class="newstitle">{$newsitem.topic_title}</h3>
		<div class="newsbody">{$newsitem.post_text}</div>
		<div class="newsfooter">
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
{/if}
{if $news}
	{foreach from=$news item=newsitem}
		<h3 class="newstitle">{$newsitem.topic_title}</h3>
		<div class="newsbody">{$newsitem.post_text}</div>
		<div class="newsfooter">
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
	{if $rss_url}
		<h3><a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a></div>
	{/if}
{/if}
  </div>
</div>
<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield">
    <input type="hidden" name="form" value="simple"/>
    {dynamic}<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10" title="Enter a Postcode, Grid Reference, Placename or a text search" onfocus="search_focus(this)" onblur="search_blur(this)"/>{/dynamic}
    <input id="searchbutton" type="submit" name="go" value="Find"/></div>
    </form>
    </div>
  </div>
  <div id="login"><span class="nowrap">
  {dynamic}
  {if $user->registered}
  	  Logged in as {$user->realname|escape:'html'}
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile.php">profile</a>
  	  <span class="sep">|</span></span>
  	  <a title="Log out" href="/logout.php">logout</a>
  {else}
	  You are not logged in
	  <a title="Already registered? Login in here" href="/login.php">login</a>
		<span class="sep">|</span></span>
	  <a title="Register to upload photos" href="/register.php">register</a>
  {/if}
  {/dynamic}
  </div>
</div>
{if $right_block}
	{include file=$right_block}
	<div class="content3" id="footer_block">
{else}
	<div class="content2" id="footer_block">
{/if}
  <div id="footer" class="no_print">
     <p style="color:#AAAABB;float:left">Page updated at {$smarty.now|date_format:"%H:%M"}</p>
   <p><a href="/help/sitemap" title="Listing of site pages">Sitemap</a>
       <span class="sep">|</span>
       <a href="/help/credits" title="Who built this and how?">Credits</a>
       <span class="sep">|</span>
       <a href="/help/terms" title="Terms and Conditions">Terms of use</a>
       <span class="sep">|</span>
       <a href="/article/Get-Involved" title="contribute to geograph">Get Involved</a>
    </p>
    <p style="color:#777788;">Website supported by
    {external title="click here to visit the Livetodot website" href="http://www.livetodot.com/hosting/" text="Hosting from Livetodot" nofollow="true"}
    </p>
  </div>
</div>
{dynamic}{pagefooter}{/dynamic}
</body>
</html>
