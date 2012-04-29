</div>
</div>
<div id="nav_block" class="no_print">
 <div class="nav">
  <ul>
    <li style="font-size:1.42em"><a accesskey="1" title="Return to the Home Page" href="/">Home</a></li>
    <li>View<ul>
     <li><a title="Find and locate images" href="/search.php">Search</a></li>
     <li><a title="View map of all submissions" href="/mapbrowse.php">Maps</a></li>
     <li><a title="Explore images by theme" href="/explore/">Explore</a></li>
     <li><a title="Submitted Pages, Galleries and Articles" href="/content/">Collections</a></li>
    </ul></li>
    <li>Interact<ul>
     <li><a title="Geographical games to play" href="/games/">Games</a></li>
     <li><a title="Activities on the site" href="/activities/">Activities</a> </li>
     <li><a title="Discussion Forum" href="/discuss/">Discussions</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Geograph Blog" href="/blog/">Blog</a></li>
     <li><a title="Find out about local Events" href="/events/">Events</a></li>
     {/if}{/dynamic}
    </ul></li>
    <li>Contributors<ul>
     <li><a title="Submit your photos" href="/submit.php">Submit</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Your most recent submissions" href="/submissions.php">Recent Uploads</a></li>
     {/if}{/dynamic}
     <li><a title="Interesting facts and figures" href="/numbers.php">Statistics</a></li>
     <li><a title="Contributor leaderboards" href="/statistics/moversboard.php">Leaderboards</a></li>
    </ul></li>
    <li>General<ul>
     <li><a title="Frequently Asked Questions" href="/faq3.php?l=0">FAQ</a></li>
     <li><a title="Info, Guides and Tutorials" href="/content/documentation.php">Information</a></li>
     <li><a title="View a list of all pages" href="/help/sitemap">Sitemap</a></li>
     <li><a accesskey="9" title="Contact the Geograph Team" href="/contact.php">Contact Us</a></li>
    </ul></li>
  {dynamic}
  {if $is_mod || $is_admin || $is_tickmod}
    <li>Admin<ul>
     <li><a title="Admin Tools" href="/admin/">Admin Index</a></li>
     {if $is_mod}
     	<li><a title="Moderation new photo submissions" href="/admin/moderation.php">Moderation</a></li>
     {/if}
     {if $is_tickmod}
     	<li><a title="Trouble Tickets" href="/admin/suggestions.php">Suggestions</a></li>
     {/if}
     <li><a title="Finish Moderation for this session" href="/admin/moderation.php?abandon=1">Finish</a></li>
    </ul></li>
  {/if}
  {/dynamic}
  </ul>
<div class="sponsor">sponsored by <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="http://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="http://{$static_host}/templates/basic/img/sponsor_small.gif" width="125" height="31" alt="Ordnance Survey"/></a></div>
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
    <p style="color:#777788;">Hosting supported by
    {external title="click to visit the CatN website - home of vCluster hosting" href="http://catn.com/" text="CatN"}
    </p>
  </div>
</div>
{dynamic}{pagefooter}{/dynamic}
</body>
</html>
