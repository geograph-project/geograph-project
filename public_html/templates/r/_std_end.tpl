		</section>

		    <form action="/of/">
      <nav>
	<div id="logo"><a href="/"><img src="https://s1.geograph.org.uk/templates/basic/img/logo.gif"></a></div>

        <label for="drop" class="toggle">Menu &#9660;</label>
        <input type="checkbox" id="drop" />
            <ul class="menu">
                <li id="navhome"><a href="/">Home</a></li>

                <li>
                    <label for="drop-0" class="toggle">Search &#9660;</label>
                    <a href="/of/">Search</a>
                    <input type="checkbox" id="drop-0"/>
                    <ul>
				<li id="navsearch"><input type="search" name="q" id="q" placeholder="Search" /><input type='submit' value=" Go ";>
				<br>what to search:
				<li><label><input type=radio name=type checked onclick="this.form.action = '/of/'">Photos</label> &nbsp;
				<li><label><input type=radio name=type onclick="this.form.action = '/finder/places.php'">Places</label> &nbsp;
				<li><label><input type=radio name=type onclick="this.form.action = '/content/'">Collections</label> &nbsp;
				<li><label><input type=radio name=type onclick="this.form.action = '/content/documentation.php'">Website</label> &nbsp;
				<li><label><input type=radio name=type onclick="this.form.action = '/finder/multi2.php'">All</label> &nbsp;
                    </ul>
                </li>

                <li>
                    <label for="drop-1" class="toggle">Photos &#9660;</label>
                    <a href="/of/">Photos</a>
                    <input type="checkbox" id="drop-1"/>
                    <ul>
				<li><a href="/mapper/combined.php">Map</a></li>
				<li><a href="/explore/sample.php">Quick Explore</a></li>
				<li><a href="/search.php?form=text">Advanced Search</a></li>
				<li><a href="/browser/">Advanced Browser</a></li>
				<li><a href="/explore/">...more</a></li>

                	    <li>
                       
	                    <label for="drop-11" class="toggle">Statistics +</label>
	                    <a href="/numbers.php">Statistics</a>         
                	    <input type="checkbox" id="drop-11"/>

        	            <ul>
				<li><a href="/numbers.php">Statistics</a></li>
        	            </ul>
	                    </li>
                    </ul> 

                </li>

                <li>
                    <label for="drop-2" class="toggle">Collections &#9660;</label>
                    <a href="/content/">Collections</a>
                    <input type="checkbox" id="drop-2"/>
                    <ul>
	                        <li><a href="/content/">All Collections</a></li>
				<li><a href="/article/">Articles</a></li>
				<li><a href="/geotrips/">Trips</a></li>
				<li><a href="/blog/">Blog</a></li>
				<li><a href="/article/Content-on-Geograph">Contribute</a></li>                       
                    </ul>
                </li>

                <li>
                    <label for="drop-3" class="toggle">Contributors &#9660;</label>
                    <a href="#">Contributors</a>
                    <input type="checkbox" id="drop-3"/>
                    <ul>
				<li><a href="/article/Geograph-Introductory-letter">Welcome</a></li>
				<li><a href="/help/freedom">Freedom</a></li>
				<li><a href="/submit.php">Submit Photos</a></li>
				<li><a href="/profile.php">Your Profile</a></li>
				<li><a href="/submissions.php">Recent Submissions</a></li>
				<li><a href="/statistics/moversboard.php">Leaderboard</a></li>                       
                    </ul>
                </li>

                <li>
                    <label for="drop-4" class="toggle">Website &#9660;</label>
                    <a href="/content/documentation.php">Website</a>
                    <input type="checkbox" id="drop-4"/>
                    <ul>
				<li><a href="/discuss/">Discussions</a></li>
				<li><a href="/faq3.php">FAQ</a></li>
				<li><a href="/help/sitemap">Sitemap</a></li>
				<li><a href="/content/documentation.php">Help Pages</a></li>

		                <li><a href="/contact.php">Contact</a></li>
                		<li><a href="/article/About-Geograph-page">About</a></li>
                    </ul>
                </li>

            </ul>
        </nav>
		    </form>

<div id="login">
  {dynamic}
  {if $user->registered}

     <a title="" href="/submit.php">Submit Image</a> &middot;
     <a title="" href="/submissions.php">Submissions</a> &middot;

  {if $is_mod || $is_admin || $is_tickmod}
     <a title="Admin Tools" href="/admin/">Admin</a> &middot;
  {/if}

	Logged in as <a title="Profile" href="/profile.php">{$user->realname|escape:'html'}</a>
	<a title="Log out" href="/logout.php">logout</a></li>
  {else}
	<a title="Already registered? Login in here" href="/login.php">login</a> or <a title="Register to upload photos" href="/register.php">register</a>
  {/if}
  {/dynamic}
</div>

<div id="footer">

   <div id="footer_right">
   Project sponsored by <a href="https://www.ordnancesurvey.co.uk/education/" title="Geograph Britain and Ireland sponsored by Ordnance Survey"><img src="{$static_host}/img/os-logo-pw64.png" width="64" height="50" alt="Ordnance Survey Logo" align="right" style="margin-left:7px"/></a></div>

        <div id="footer_left"><a href="/help/sitemap" title="Listing of site pages">Sitemap</a>
       <span class="sep">|</span>
       <a href="/help/credits" title="Who built this and how?">Credits</a>
       <span class="sep">|</span>
       <a href="/help/terms" title="Terms and Conditions">Terms of use</a>

       <br/>

       <span class="unimportant">Page updated at {$smarty.now|date_format:"%H:%M"}</span>
    </div>

    <br style="clear:both"/>

</div>
{dynamic}{pagefooter}{/dynamic}

	</body>
</html>
