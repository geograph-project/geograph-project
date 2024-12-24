</div>
</div>

<input type=checkbox id="nav_toggle">
<label id="nav_label" for="nav_toggle">&#9776;</label>
<div id="nav_block" class="no_print">
 <div class="nav">

{if $welsh_url}
<div style="text-align:center">
	<a href="{$welsh_url}">Fersiwn Cymraeg</a>
</div>
{/if}


  <ul>
    <li style="font-size:1.42em"><a accesskey="1" title="Return to the Home Page" href="/">Home</a></li>
    <li>View<ul>
     <li><a title="Find and locate images" href="/search.php">Search</a></li>
     <li id="markedLink" style="display:none"><a title="View your current Marked Images" href="/finder/marked.php">Marked Images</a></li>
     <li><a title="View map of all submissions" href="/mapper/combined.php">Maps</a></li>
     <li><a title="Interactive browser, search and map in one" href="/browser/#!start">Browser</a></li>
     <li><a title="Explore images by theme" href="/explore/">Explore</a></li>
     <li><a title="Curated selection of images" href="/gallery.php">Showcase</a></li>
     <li><a href="/finder/recent.php">New Images</a></li>
    </ul></li>
    <li><ul>
     <li><a title="Submitted Pages, Galleries and Articles" href="/content/">Collections</a></li>
    </ul></li>
    <li>Interact<ul>
     <li><a title="Geographical games to play" href="/games/">Games</a></li>
     <li><a title="Discussion Forum" href="/discuss/">Discussions</a></li>
     <li><a title="Blog Posts by Members" href="/blog/">Blog</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Upcoming Meet/Events" href="/events/">Events</a></li>
     {/if}
    </ul></li>
    <li>Contributors<ul>
     <li><a title="Submit your photos" href="/submit.php">Submit</a></li>
     {if $user->registered && $user->stats.images}
     <li><a title="Your most recent submissions" href="/submissions.php">Recent Uploads</a></li>
	{if $user->tickets !== 0}
	     <li><a title="Active image change/suggestions" href="/suggestions.php">Suggestions</a></li>
	{/if}
     {/if}
     <li><a title="Interesting facts and figures" href="/numbers.php">Statistics</a></li>
     <li><a title="Contributor leaderboards" href="/statistics/moversboard.php">Leaderboards</a></li>
    </ul></li>
    <li>General<ul>
     <li><a title="Frequently Asked Questions" href="/faq3.php?l=0">FAQ</a></li>
     <li><a title="Info, Guides and Tutorials" href="/content/documentation.php">Help Pages</a></li>
     <li><a title="View a list of all pages" href="/help/sitemap">Sitemap</a></li>
     <li><a title="Contact the Geograph Team" href="/contact.php">Contact Us</a></li>
     <li><a title="Donate to Geograph Project" href="/help/donate">Support Us</a></li>
    </ul></li>
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
<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/"><img src="{$static_host}/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></div>
{if $square && $square->collections}
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
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b %Y"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
{/if}
{if $news}
	{foreach from=$news item=newsitem}
		<h3 class="newstitle">{$newsitem.topic_title}</h3>
		<div class="newsbody">{$newsitem.post_text}</div>
		<div class="newsfooter">
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b %Y"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
	{if $rss_url}
		<h3><a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a></div>
	{/if}
{/if}
  </div>
</div>
<input type=checkbox id="search_toggle">
<label id="search_label" for="search_toggle">&#128269;</label>
<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield">
    <input type="hidden" name="form" value="simple"/>
    {dynamic}<input id="searchterm" type="text" name="q" placeholder="enter search query" value="{$searchq|escape:'html'}" size="10" title="Enter a Postcode, Grid Reference, Placename or a text search"/>{/dynamic}
    <input id="searchbutton" type="submit" name="go" value="Find"/>
	<div id="searchoptions">
		 What to search:<ul class="touchPadding">
                                <li><label><input type=radio name=type checked onclick="this.form.action = '/of/'">Photos</label> &nbsp; <i>Enter keywords or a location/postcode to search nearby images</i>
				{if $square && $square->grid_reference}
                                <li><label><input type=radio name=type onclick="this.form.action = '/gridref/{$square->grid_reference}/'">Photos in Grid Square {$square->grid_reference}</label> &nbsp;
                                <li><label><input type=radio name=type onclick="this.form.action = '/near/{$square->grid_reference}'">Photos near {$square->grid_reference}</label> &nbsp;
				{elseif $image && $image->grid_square && $image->grid_square->grid_reference}
                                <li><label><input type=radio name=type onclick="this.form.action = '/near/{$image->grid_square->grid_reference}'">Photos near {$image->grid_square->grid_reference}</label> &nbsp;
				{/if}
                                <li><label><input type=radio name=type onclick="this.form.action = '/browse.php'">Enter a Grid Reference</label> &nbsp;
                                <li><label><input type=radio name=type onclick="this.form.action = '/finder/places.php'">Placenames</label> &nbsp;
                                <li><label><input type=radio name=type onclick="this.form.action = '/content/'">Collections</label> &nbsp;
				{dynamic}{if $user->registered}
                                  <li><label><input type=radio name=type onclick="this.form.action = '/finder/discussions.php'">Discussions</label> &nbsp;
				{/if}{/dynamic}
                                <li><label><input type=radio name=type onclick="this.form.action = '/content/documentation.php'">Website Pages</label> &nbsp;
                                <li><label><input type=radio name=type onclick="this.form.action = '/finder/multi2.php'">Everything</label> &nbsp;
		</ul>
		<hr>
		Others: <a href="/search.php?form=text">Advanced Search</a> | <a href="/browser/">Image Browser</a><br><br>
		Or: <a href="/mapper/combined.php">Coverage Map</a> | <a href="/browser/#!/display=map_dots">Searchable Map</a>
	</div>
      </div>
    </form>
    </div>
  </div>
</div>
  <div id="login" class="no_print"><span class="nowrap">
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
{if $right_block}
	{include file=$right_block}
	<div class="content3" id="footer_block">
{else}
	<div class="content2" id="footer_block">
{/if}
  <div id="footer" class="no_print">
     <p style="float:left">Page updated at {$smarty.now|date_format:"%H:%M"}</p>
   <p><a href="/help/sitemap" title="Listing of site pages">Sitemap</a>
       <span class="sep">|</span>
       <a href="/article/Use-of-Cookies-on-Geograph-Website" title="How this site uses cookies">Cookies</a>
       <span class="sep">|</span>
       <a href="/help/credits" title="Who built this and how?">Credits</a>
       <span class="sep">|</span>
       <a href="/help/terms" title="Terms and Conditions">Terms of use</a>
       <span class="sep">|</span>
       <a href="/article/Get-Involved" title="contribute to geograph">Get Involved</a>
    </p>
  </div>
</div>
{dynamic}{pagefooter}{/dynamic}
</body>
</html>
