{if $smarty.server.REQUEST_METHOD == 'GET' && $smarty_template != 'view.tpl' && $smarty_template != 'editimage.tpl'}
<div id="hidefeed" style="text-align:center"><a href="javascript:void(show_tree('feed'));">&middot; Give Feedback &middot;</a></div>
<div id="showfeed" class="interestBox" style="display:none"><form method="post" action="/stuff/feedback.php">
<label for="feedback_comments">What do you like, dislike or otherwise want to comment on about <b>this</b> page:</label><br/>
<input type="text" name="comments" size="80" id="feedback_comments"/><input type="submit" name="submit" value="send"/>
{dynamic}{if $user->registered}<br/>
<small>(<input type="checkbox" name="nonanon"/> <i>Tick here to include your name with this comment, so we can then reply</i>)</small>
{else}<br/>
<i><small>If you want a reply please use the <a href="/contact.php">Contact Us</a> page. We are unable to reply to comments left here.</small></i>
{/if}{/dynamic}
<input type="hidden" name="template" value="{$smarty_template}"/>
<input type="hidden" name="referring_page" value="{$smarty.server.HTTP_REFERER}"/>
    <div style="display:none">
    <br /><br />
    <label for="name">Leave Blank!</label><br/>   
	<input size="40" id="name" name="name" value=""/>
    </div>
</form></div>{/if}
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
     <li><a title="Discussion Forum" href="/discuss/">Discuss</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Chat with other members" href="/chat/">Chat</a> {if $irc_seen}<span style="color:gray">({$irc_seen} online)</span>{/if}</li>
     <li><a title="Find out about local meetups" href="/events/">Events</a></li>
     {/if}{/dynamic}
    </ul></li>
    <li>Contributors<ul>
     <li><a title="Submit your photos" href="/submit.php">Submit</a></li>
     <li><a title="Interesting facts and figures" href="/numbers.php">Statistics</a></li>
     <li><a title="Contributor leaderboards" href="/statistics/moversboard.php">Leaderboards</a></li>
    </ul></li>
    <li>Further Info<ul>
     <li><a title="Frequently Asked Questions" href="/faq.php">FAQ</a></li>
     <li><a title="Information documents" href="/content/?docs&amp;order=views">Guides, Tutorials</a></li>
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
     	<li><a title="Trouble Tickets" href="/admin/tickets.php">Tickets</a> (<a href="/admin/tickets.php?sidebar=1" target="_search" title="Open in Sidebar, IE and Firefox Only">S</a>)</li>
     {/if}
     <li><a title="Finish Moderation for this session" href="/admin/moderation.php?abandon=1">Finish</a></li>
    </ul></li>
  {/if}
  {/dynamic}
  </ul> 
<div style="text-align:center; padding-top:15px; border-top: 2px solid black; margin-top: 15px;">sponsored by <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="http://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="http://{$static_host}/templates/basic/img/sponsor_small.gif" width="125" height="31" alt="Ordnance Survey" style="padding:4px;"/></a></div>
{if $discuss}
{foreach from=$discuss item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>
{/foreach}
{/if}
{if $collections}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">Collections for this image: <sup style="color:red">new!</sup></h3>
{foreach from=$collections item=item}
<div class="newsbody">&middot; <a href="{$item.url}" title="{$item.type|escape:'html'}">{$item.title|escape:'html'}</a></div>
{/foreach}
{/if}
{if $news}
{foreach from=$news item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>
{/foreach}
{if $rss_url}
<div style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">
<a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a>
</div>
{/if}
{/if}
  </div>
</div>
<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield"><label for="searchterm">Search</label> 
    <input type="hidden" name="form" value="simple"/>
    {dynamic}<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10" title="Enter a Postcode, Grid Reference, Placename or a text search"/>{/dynamic}
    <input id="searchbutton" type="submit" name="go" value="Find"/></div>
    </form>
    </div>
  </div>
  <div id="login">
  {dynamic}
  {if $user->registered}
  	  Logged in as {$user->realname|escape:'html'}
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile/{$user->user_id}">profile</a>
  	  <span class="sep">|</span>
  	  <a title="Log out" href="/logout.php">logout</a>
  {else}
	  You are not logged in
	  <a title="Already registered? Login in here" href="/login.php">login</a>
		<span class="sep">|</span>
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
       <a href="http://validator.w3.org/check/referer" title="check our xhtml standards compliance">XHTML</a>
       <span class="sep">|</span>
       <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://{$static_host}/templates/basic/css/basic.css" title="check our css standards compliance">CSS</a>
    </p>
    <p style="color:#777788;">Hosting supported by 
    {external title="click to visit the Fubra website" href="http://www.fubra.com/" text="Fubra"}
    </p>
  </div>
</div>
</body>
</html>
