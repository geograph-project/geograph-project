</div>
</div>

<div id="nav_block">
  <div id="nav">
  <ul>
    <li><a title="Home Page" href="/">Home</a></li>
    <li><a title="Browse the grid and submit photos" href="/browse.php">Browse</a></li>
    <li><a title="Submit" href="/submit.php">Submit</a></li>
    <!--
    <li><a title="Members" href="/statistics.php">Statistics</a></li>
    -->
    <li><a title="FAQ" href="/faq.php">FAQ</a></li>
    <li><a title="Contact Us" href="/contact.php">Contact</a></li>
  </ul>
  
  {if $is_admin}
  <h3>Admin</h3>
  <ul>
     <li><a title="Admin Tools" href="/admin/">Admin Index</a></li>
     <li><a title="Moderation new photo submissions" href="/admin/moderation.php">Moderation</a></li>
     <li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a></li>
     <li><a title="Server Stats" href="http://www.geograph.co.uk/logs/">Server Stats</a></li>
  </ul>
  <h3>Developers</h3>
  <ul>
     <li><a title="Grid Builder" href="/admin/gridbuilder.php">Grid Building</a></li>
     <li><a title="Hash Changer" href="/admin/hashchanger.php">Hash Changer</a></li>
  </ul>
  {/if}
  
  
  {if $recentcount}
  
  	<h3>Recent Photos</h3>
  	
  	{foreach from=$recent item=image}
  
  	  <div style="text-align:center;padding-bottom:1em;">
  	  <a title="{$image->title|escape:'html'} - click to view full size image" href="/view.php?id={$image->gridimage_id}">{$image->getThumbnail(120,80)}</a>
  	  
  	  <div>
  	  <a title="view full size image" href="/view.php?id={$image->gridimage_id}">{$image->title|escape:'html'}</a>
  	  by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>
	  for square <a title="view page for {$image->gridref}" href="/browse.php?gridref={$image->gridref}">{$image->gridref}</a>
	  
	  </div>
  	  
  	  </div>
  	  
  
  	{/foreach}
  
  {/if}
  
  
  </div>
</div>

<div id="search_block">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield"><label for="searchterm">Search</label> <input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10"/>
    <input id="searchbutton" type="submit" name="go" value="Find"/></div>
    </form>
    </div>
    
  </div>
  
  <div id="login">
  
  {if $user->registered}
  	  Logged in as {$user->realname|escape:'html'}
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile.php">profile</a>
  	  <span class="sep">|</span>
  	  <a title="Log out" href="/logout.php">logout</a>
  {else}
	  You are not logged in
	  <a title="Already registered? Login in here" href="/login.php">login</a>
		<span class="sep">|</span>
	  <a title="Register to upload photos" href="/register.php">register</a>
  {/if}
  
  </div>
</div>



<div id="footer_block">
  <div id="footer">
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
  </div>
</div>

</body>
</html>
