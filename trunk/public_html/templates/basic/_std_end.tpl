</div>
</div>

<div id="nav_block">
 <div class="nav">
  <ul>
    <li><a accesskey="1" title="Home Page" href="/">Home</a></li>
    <li><a title="Find images" href="/search.php">Search</a> / <a title="Explore Images by Theme" href="/explore/">Explore</a></li>
    <li><a title="View map of all submissions" href="/mapbrowse.php">Map Viewer</a></li>
    <li><a title="Submit" href="/submit.php">Submit</a></li>
    <li><a title="Discuss" href="/discuss/">Discuss</a></li>
    <li><a title="Statistics" href="/statistics.php">Statistics</a></li>
    <li><a title="Leaderboard" href="/moversboard.php">Leaderboard</a></li>
    <li><a title="FAQ" href="/faq.php">FAQ</a></li>
    <li><a accesskey="9" title="Contact Us" href="/contact.php">Contact</a></li>
  </ul>
  
  {dynamic}
  {if $is_mod}
  <h3>Admin</h3>
  <ul>
     <li><a title="Admin Tools" href="/admin/">Admin Index</a></li>
     <li><a title="Moderation new photo submissions" href="/admin/moderation.php">Moderation</a></li>
     <li><a title="Trouble Tickets" href="/admin/tickets.php">Tickets</a> (<a title="Trouble Tickets" href="/admin/tickets.php?sidebar=1" target="_search">S</a>)</li>
  </ul>
  {/if}
  {/dynamic}

<div style="text-align:center; padding-top:15px; border-top: 2px solid black; margin-top: 15px;">sponsored by <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="http://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="/templates/basic/img/sponsor_small.gif" width="125" height="31" alt="Ordnance Survey" style="padding:4px;"/></a></div>

   
{if $discuss}

{foreach from=$discuss item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile.php?u={$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>

{/foreach}

{/if}
 

{if $news}

{foreach from=$news item=newsitem}
<h3 class="newstitle" style="padding-top:15px; border-top: 2px solid black; margin-top: 15px;">{$newsitem.topic_title}</h3>
<div class="newsbody">{$newsitem.post_text}</div>
<div class="newsfooter">
Posted by <a href="/profile.php?u={$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b"}
<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
</div>

{/foreach}

{/if}

  </div>
</div>

<div id="search_block">
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
  	  <a title="Profile" href="{if $user->nickname}/user/{$user->nickname|escape:'url'}/{else}/profile.php?u={$user->user_id}{/if}">profile</a>
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
  <div id="footer">
     <p style="color:#AAAABB;float:left">Page updated at {$smarty.now|date_format:"%H:%M"}</p>
   <p><a href="/help/credits" title="Who built this and how?">Credits</a>
       <span class="sep">|</span>
       <a href="/help/terms" title="Terms and Conditions">Terms of use</a>
       <span class="sep">|</span>
       <a href="http://validator.w3.org/check/referer" title="check our xhtml standards compliance">XHTML</a>
       <span class="sep">|</span>
       <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://{$http_host}/templates/basic/css/basic.css" title="check our css standards compliance">CSS</a>
       <span class="sep">|</span>
       <a href="http://bobby.watchfire.com/bobby/bobbyServlet?URL=http%3A%2F%2F{$http_host}%2F&amp;output=Submit&amp;gl=sec508" title="check our accessibility standards compliance">Accessibility</a>
    </p>
    <p style="color:#777788;">Hosting supported by 
    {external title="click to visit the Fubra website" href="http://www.fubra.com/" text="Fubra"}
    </p>
  </div>
</div>

</body>
</html>
