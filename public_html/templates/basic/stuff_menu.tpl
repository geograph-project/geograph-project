{include file="_std_begin.tpl"}

<b>Menu Length:</b> (drag the toggle)

<div id="slider" style="margin:20px"></div>


<hr/>

	<div style="position:relative;width:400px">
	<ul id="menudiv">
		<li style="font-size:1.42em"><a title="Home Page" href="/">Home</a></li>

		<li><a title="Find images" href="/search.php">Image Search</a><ul>
			<li><a title="Advanced image search" href="/search.php?form=text">Advanced</a></li>
			<li><a title="Advanced image search" href="/finder/sqim.php">By Square</a></li>
			<li><a title="Advanced image search" href="/finder/places.php">Place</a></li>
			<li><a title="Advanced image search" href="/finder/multi.php">Multi</a></li>
		</ul></li>
		<li><a title="View map of all submissions" href="/mapbrowse.php">Map</a><ul>
			<li><a title="Depth Map" href="/mapbrowse.php?depth=1">Depth</a></li>
			<li><a title="Depth Map" href="/mapbrowse.php?recent=1">Recent</a></li>
			<li><a title="Draggable Map" href="/mapper/">Draggable</a><ul>
				<li><a title="Draggable Map" href="/mapper/?centi=1">Centisquare</a>
			</ul></li>
			<li><a title="Draggable Map" href="/mapper/clusters.php">Clusters</a></li>
			<li><a title="Hectad Map" href="/hectadmap.php">Hectad Coverage</a></li>
		</ul></li>
		<li><a title="Browse" href="/browse.php">Browse</a></li>
		<li><a title="Explore Images by Theme" href="/explore/">Explore</a><ul>
			<li><a href="/featured.php">Featured Stuff</a></li>
			<li><a href="/statistics/fully_geographed.php">Mosaics</a></li>
			<li><a href="/thumbed-weekly.php">Popular Images</a></li>
			<li><a href="/explore/routes.php">Routes</a></li>
			<li><a href="/explore/places/">Places</a></li>
			<li><a href="/explore/calendar.php">Calendar</a></li>
			<li><a href="/explore/searches.php">Featured Searches</a></li>
		</ul></li>
		<li><a title="Content" href="/content/">Collections</a><ul>
			<li><a href="/article/">Articles</a></li>
			<li><a href="/gallery/">Galleries</a></li>
			{if $enable_forums && $user->registered}
				<li><a href="/discuss/index.php?action=vtopic&amp;forum=6">Themed Topics</a></li>
			{/if}
			<li><a href="/snippets.php">Shared Descriptions</a></li>
			<li><a href="/finder/contributors.php">User Profiles</a></li>
			<li><a href="/stuff/canonical.php?final=1">Categories</a></li>
		</ul></li>

		<li>Contribute<ul rel="open">
			<li><a title="Submit" href="/submit.php">Submit Photos</a><ul>
				<li><a title="Submit Version 2" href="/submit2.php">Submit v2</a></li>
				<li><a title="Submit More" href="/help/submissions">Other</a></li>
			</ul></li>
			<li><a title="Content" href="/article/Content-on-Geograph">Collections</a></li>
		</ul></li>
		{dynamic}{if $user->registered}
		<li>My Photos<ul rel="open">
			<li><a title="Profile" href="/profile.php">My Profile</a></li>
			<li><a title="Submissions" href="/submissions">My Submissions</a></li>
			<li><a title="Submissions" href="/thumbed.php">My Thumbed Images</a></li>
			<li><a href="/profile/{$user->user_id}/map" rel="nofollow">Personal Map</a></li>
			<li><a title="Advanced image search" href="/search.php?form=check">Check Submissions</a></li>
			<li><a title="Submissions" href="/export.csv.php?u={$user->user_id}&supp=1&taken=1&hits=1">CSV Export</a></li>
		</ul></li>
		{/if}{/dynamic}
		<li><a title="Activities" href="/activities/">Activities</a><ul>
			<li><a title="Play Games" href="/games/">Games</a> </li>
			<li><a title="Imagine the map in pictures" href="/help/imagine">Imagine</a></li>
		</ul></li>
		<li>Interact<ul rel="open">
			<li><a title="Discuss" href="/discuss/">Discussions</a><ul>
				<li><a title="Discuss" href="/finder/discussions.php">Search</a>
			</ul></li>
			<li><a title="Discuss" href="/blog/">Blog</a></li>
			{dynamic}{if $user->registered}
			<li><a title="Chat" href="/chat/">Chat</a> {if $irc_seen}<span style="color:gray">({$irc_seen} online)</span>{/if}</li>
			<li><a title="Find out about local Events" href="/events/">Events</a></li>
			{/if}{/dynamic}
		</ul></li>
		<li><a title="Statistics" href="/numbers.php">Statistics</a><ul>
			<li><a title="More Stats" href="/statistics.php">More Stats</a></li>
			<li><a title="Credits" href="/credits/">Contributors</a></li>
			<li><a title="Credits" href="/statistics/pulse.php">Current Stats</a></li>
			<li><a title="Leaderboard" href="/statistics/moversboard.php">Leaderboard</a></li>
		</ul></li>
		<li>Export<ul>
			<li><a title="KML" href="/kml.php">Google Earth/Maps</a></li>
			<li><a title="Memory Map Exports" href="/memorymap.php">Memory Map</a></li>
			<li><a title="GPX Downloads" href="/gpx.php">GPX</a></li>
			<li style="font-size:0.9em;"><a title="API" href="/help/api">API</a></li>
		</ul></li>
		<li>Further Info<ul rel="open">
			<li><a title="FAQ" href="/faq.php">FAQ</a></li>
			<li><a title="Info, Guides and Tutorials" href="/content/documentation.php">Information</a></li>
			<li><a title="View More Pages" href="/help/more_pages">More Pages</a><ul>
				<li><a title="View All Pages" href="/help/sitemap">Sitemap</a></li>
				<li><a title="View All Pages" href="http://www.geographs.org/links/sitemap.php?experimental=Y&amp;internal=Y&amp;site=www.geograph.org.uk">Experimental Featured</a></li>
			</ul></li>

			<li><a title="Contact Us" href="/contact.php">Contact Us</a><ul>
				<li><a title="The Geograph Team" href="/admin/team.php">The Team</a></li>
				<li><a href="/help/credits" title="Who built this and how?">Credits</a></li>
			</ul></li>
		</ul></li>
		{dynamic}
		{if	$is_mod || $is_admin || $is_tickmod}
			<li>Admin<ul rel="open">
				<li><a title="Admin Tools" href="/admin/">Admin Homepage</a></li>
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
	</div>

	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/jquery-ui.min.js"></script>

	<script type="text/javascript">{literal}

	$(document).ready(function() {
		$("#slider").slider({min:15,max:50,value:15,
			slide: function(event,ui) {

				for(q=5;q<=50;q++) {
						if (ui.value < q) {
								$('.score'+q).show();
						} else {
								$('.score'+q).hide();
						}
				}

				$("#menudiv").find("li").each(function(index) {
					var shown = 0;
					$(this).find('A:visible').each(function(index) {
						shown=shown+1;
					});
					if(shown == 0) {
						$(this).hide();
					} else {
						$(this).show();
					}
				});

			}
		});

	});


	{/literal}</script>



{include file="_std_end.tpl"}
