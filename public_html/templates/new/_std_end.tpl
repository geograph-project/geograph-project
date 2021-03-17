								</section>

						</div>
					</div>

				<!-- Sidebar -->
					<div id="sidebar">
						<div class="inner">
								<section class="alt" style="background-color:#000066;">
									<a href="/"><img src="https://s1.geograph.org.uk/templates/basic/img/logo.gif"></a>
								</section>
							<!-- Search -->
								<section id="search" class="alt">
									<form action="/of/">
										<input type="text" name="q" id="q" placeholder="Search" />
										<label><input type=radio name=type checked onclick="this.form.action = '/of/'">Photos</label> &nbsp;
										<label><input type=radio name=type onclick="this.form.action = '/finder/places.php'">Places</label> &nbsp;
										<label><input type=radio name=type onclick="this.form.action = '/content/'">Collections</label> &nbsp;
										<label><input type=radio name=type onclick="this.form.action = '/content/documentation.php'">Website</label> &nbsp;
										<label><input type=radio name=type onclick="this.form.action = '/finder/multi2.php'">All</label> &nbsp;
									</form>
								</section>

							<!-- Menu -->
								<nav id="menu">
									<ul>

  {dynamic}
  {if $user->registered}
	<li>Logged in as <a title="Profile" href="/profile.php">{$user->realname|escape:'html'}</a></li>
	<li><a title="Log out" href="/logout.php">logout</a></li>
  {else}
	<li><a href="/">Homepage</a></li>
	<li><a title="Already registered? Login in here" href="/login.php">login</a> or <a title="Register to upload photos" href="/register.php">register</a></li>
  {/if}
  {/dynamic}


										<li>
											<span class="opener"><a href="/search.php">Photos</a></span>
											<ul>
												<li><a href="/mapper/combined.php">on Map</a></li>
												<li><a href="/explore/sample.php">Quick Explore</a></li>
												<li><a href="/search.php?form=text">Advanced Search</a></li>
												<li><a href="/browser/">Advanced Browser</a></li>
												<li><a href="/explore/">...more</a></li>
											</ul>
										</li>
										<li><a href="/content/">Collections</a></li>
										<li>
											<span class="opener">Contributors</span>
											<ul>
												<li><a href="/submit.php">Submit Photos</a></li>
												<li><a href="/profile">Your Profile</a></li>
												<li><a href="/submissions.php">Recent Submissions</a></li>
												<li><a href="/statistics/moversboard.php">Leaderboard</a></li>
											</ul>
										</li>
										<li><a href="/discuss/">Discussions</a></li>
										<li><a href="/numbers.php">Statistics</a></li>

  {dynamic}
  {if $is_mod || $is_admin || $is_tickmod}
    <li><span class="opener">Admin</span><ul>
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


										<li>
											<span class="opener">Support</span>
											<ul>
												<li><a href="/faq3.php">FAQ</a></li>
												<li><a href="/help/sitemap">Sitemap</a></li>
												<li><a href="/content/documentation.php">Help Pages</a></li>
											</ul>
										</li>
										<li><a href="/help/donate">Donate!</a></li>
									</ul>
								</nav>

							<!-- Section -->
								<section>
									<p style="background-color:white;padding:16px;vertical-align:baseline">sponsored by &nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;
									<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/">
									<img src="https://s1.geograph.org.uk/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></p>

									<header class="major">
										<h2>Above Geograph</h2>
									</header>
									<ul class="contact">
										<li class="fa-envelope-o"><a href="/contact.php">Contact Us</a></li>
										<li class="fa-home">About <a href="/article/About-Geograph-page">Geograph Project Limited</a></li>
									</ul>
								</section>

							<!-- Footer -->
								<footer id="footer">
								</footer>

						</div>
					</div>

			</div>

		<!-- Scripts -->
			<script src="/assets/js/jquery.min.js"></script>
			<script src="/assets/js/browser.min.js"></script>
			<script src="/assets/js/breakpoints.min.js"></script>
			<script src="/assets/js/util.js"></script>
			<script src="/assets/js/main.js"></script>

	</body>
</html>
