</div>
</div>

<div id="nav_block">
  <div id="nav">
  <ul>
    <li><a title="Home Page" href="/">Home</a></li>
    <li><a title="Browse the grid" href="/browse.php">Browse</a></li>
    <li><a title="Members" href="/statistics.php">Statistics</a></li>
    <li><a title="FAQ" href="/faq.php">FAQ</a></li>
    <li><a title="Contact Us" href="/contact.php">Contact</a></li>
  </ul>
  
  {if $is_admin}
  <h3>Admin</h3>
  <ul>
     <li><a title="Admin Tools" href="/admin/">Admin</a></li>
     <li><a title="Grid Builder" href="/admin/gridbuilder.php">Grid Building</a></li>
  </ul>
  {/if}
  
  </div>
</div>

<div id="search_block">
  <div id="search">
    <form method="get" action="/search.php">
    <div id="searchform">
    <label for="searchterm">Search</label> <input id="searchterm" type="text" name="q" value="" size="10"/>
    <input id="searchbutton" type="submit" name="go" value="Find"/>
    </div>
    </form>
  </div>
  
  <div id="login">
  
  {if $user->registered}
  	  Logged in as {$user->realname}
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile.php">profile</a>
  	  <span class="sep">|</span>
  	  <a title="Log out" href="/logout.php">logout</a>
  {else}
	  You are not logged in
	  <a title="Already registered? Login in here" href="login.php">login</a>
		<span class="sep">|</span>
	  <a title="Register to upload photos" href="register.php">register</a>
  {/if}
  
  </div>
</div>



<div id="footer_block">
  <div id="footer">
    <p><a href="credits.php" title="Who built this and how?">Credits</a>
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
