{assign var="page_title" value="Sitemap"}
{assign var="meta_description" value="Very long page linking to all the main areas of Geograph. "}
{include file="_std_begin.tpl"}

 <h2>Geograph Sitemap</h2>
 
 <p>Quick links to nealy all Geograph Webpages</p>
 
 <div style="float:right;padding:5px;background:#dddddd;position:relative; font-size:0.8em;margin-left:20px;top:-30px">
 <h3>Help and Info...</h3>
 <ul style="margin-top:0;padding:0 0 0 1em;">
 <li><a href="/help/guide">Geograph Guide</a></li>
 <li><a href="http://www.geograph.org.uk/article/Geograph-or-supplemental">Moderation Guide</a></li>
 <li><a href="http://www.geograph.org.uk/article/Which-Square">Locating your Image</a></li>
 <li><a href="/help/style">Submission Style Guide</a><br/><br/></li>
 <li><a href="/help/changes">Change Request System</a><br/><br/></li>
 <li><a href="/help/squares">Square Definitions</a><br/><br/></li>
 <li><a href="/help/search">Text Search Help</a></li>
 <li><a href="/help/stats_faq">Statistics FAQ</a><br/><br/></li>
 {if $enable_forums}
 <li><a href="/help/bbcodes">Forum BBcodes Help</a><br/><br/></li>
 {/if}
 <li><a href="/help/freedom">Geograph Freedom</a></li>
 <li><a href="/help/terms">Terms &amp; Conditions</a><br/><br/></li>
 <li><a href="/contact.php">Contact Us</a><br/><br/></li>
 <li><a href="/admin/team.php">The Team</a> &amp; <a href="/help/credits">Credits</a><br/><br/></li>
 <li>... <b>more in the <a href="/article/?cat_word=Geograph">Articles Section</a></b></li>
 </ul></div>
 
 
<h3>Getting started...</h3>
<ul>
	<li><a title="Browse by Map" href="/mapbrowse.php">browse images on a <b>map</b></a></li>
	<li><a title="Find photographs" href="/search.php"><b>search images</b> taken by other members</a></li>
	<li><a title="Submit a photograph" href="/submit.php"><b>upload</b> your own <b>pictures</b></a></li>
	<li><a title="Content" href="/content/">read <b>content</b> submitted by members</a> <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></li>
</ul>
 
<h3>Exploring in more depth...</h3>
<ul>
	<li><a title="Browse by Grid Reference" href="/browse.php">browse by <b>grid square</b></a></li>
	<li><a title="Statistical Breakdown" href="/statistics.php"><b>view statistics</b> of images submitted</a> (<a href="#stats">more below</a>)</li>
	<li><a title="Explore Images" href="/explore/"><b>explore</b> geograph images</a></li>
	<li><a title="Photo Gallery" href="/gallery/">view some <b>themed galleries</b></a></li>
	<li><a title="List of all images" href="/sitemap/geograph.html">view the <b>full list</b> of images</a></li>
	<!--li><a href="/help/imagine">Through The Square Window...</a></li-->
	<li><a href="/explore/searches.php">A list of <b>featured searches</b></a> <a title="RSS Feed Featured Searches" href="/explore/searches.rss.php" class="xml-rss">RSS</a></li>
</ul>

{if $enable_forums}
<h3><a name="social"></a>Interacting with other members...</h3>
<ul>
	<li><a title="Discussion forums" href="/discuss/"><b>discuss the site</b> on our forums</a></li>
	<li><a title="Articles" href="/article/">submit your own <b>article</b></a> <a title="RSS Feed for Geograph Articles" href="/article/feed/recent.rss" class="xml-rss">RSS</a></li>
	<li><a title="Galleries" href="/discuss/?action=vtopic&forum={$forum_gallery}">create <b>submitted galleries</b></a> <a title="RSS Feed for Geograph Galleries" href="/discuss/syndicator.php?forum={$forum_gallery}" class="xml-rss">RSS</a></li>
</ul>
{/if}
 
<h3><a name="software"></a>Interacting with other software...</h3>
<ul>
	<li><a title="Google Earth Export" href="/kml.php">view images in <b>Google Earth</b> and <b>Google Maps</b></a> <a title="Google Earth Feed" href="/feed/recent.kml" class="xml-kml">KML</a></li>
	<li><a title="Geograph Feed" href="/faq.php#rss">get <b>RSS feeds</b> of images</a> <a title="RSS Feed of Recent Images" href="/feed/recent.rss" rel="RSS" class="xml-rss">RSS</a></li>
	<li><a title="Memory Map Export" href="/memorymap.php">view squares in <b>Memory Map</b></a></li>
	<li><a title="GPX File Export" href="/gpx.php">download squares in <b>GPX Format</b></a></li>
	<li><a title="GeoRSS Geograph Feed" href="/feed/recent.rss">latest images in <b>GeoRSS</b> format</a> <a title="RSS Feed of Recent Images" href="/feed/recent.rss" rel="RSS" class="xml-geo">GeoRSS</a></li>
	<li><a title="Developer API" href="/help/api">developer information on the <b>Geograph API</b></a></li>
</ul>

<h3>Site Features...</h3>
<ul>
	<li><a href="/register.php">Register as a User</a></li>
	<li><a href="/login.php">Login</a> / <a href="/logout.php">Logout</a></li>
	<li><a href="/forgotten.php">Forgotten Password</a></li>
	<li><a href="/contact.php">Contact Us</a></li>
	<li><a href="/profile.php">Your Profile</a> (for registered users only)</li>
	<li><a href="/tickets.php">Review Your Recent Change Suggestions</a></li>
</ul>

<h3><a name="users"></a>Contributors...</h3>
<ul>
	<li><a href="/credits/"><b>Contributor Listing</b></a></li>
	<li><a href="/statistics/moversboard.php"><b>Weekly</b> Leaderboard</a><ul>

<li><form action="/statistics/moversboard.php" style="display:inline">
Refine:
<select name="type">
<option value="points">Points</option> 
<option value="geosquares">GeoSquares</option>
<option value="geographs">Geographs</option>
<option value="squares">Squares</option>
<option value="images">Images</option>
<option value="depth">Depth Score</option>
<option value="myriads">Myriads</option>
<option value="hectads">Hectads</option>
<option value="spread">Spread</option>
<option value="antispread">AntiSpread</option>
<option value="days">Days</option>
<option value="classes">Categories</option>
<option value="additional">Additional Geographs</option>
<option value="supps">Supplementals</option>
<option value="centi">Centisquares</option>
<option value="clen">Description Length</option>
</select>
 <input type="submit" value="Go"/>
</form></li></ul></li>

	<li><a href="/statistics/leaderboard.php"><b>All Time</b> Leaderboard</a><ul>

<li><form action="/statistics/leaderboard.php" style="display:inline">
Refine:
<select name="type">
<option value="points">Points</option> 
<option value="geosquares">GeoSquares</option>
<option value="geographs">Geographs</option>
<option value="squares">Squares</option>
<option value="images">Images</option>
<option value="depth">Depth Score</option>
<option value="myriads">Myriads</option>
<option value="hectads">Hectads</option>
<option value="spread">Spread</option>
<option value="antispread">AntiSpread</option>
<option value="days">Days</option>
<option value="classes">Categories</option>
<option value="additional">Additional Geographs</option>
<option value="supps">Supplementals</option>
<option value="centi">Centisquares</option>
<option value="clen">Description Length</option>
</select>

<select name="date">
<option value="submitted">Submitted</option> 
<option value="taken">Taken</option>
</select> during

 {html_select_date display_days=false prefix="when" time="0000-00-00" start_year="-100" reverse_years=true  month_empty="" year_empty=""} <input type="submit" value="Go"/>
</form></li></ul></li>

	<li><a href="/statistics/monthlyleader.php">By Month</b> Leaderboard</a></li>
	<li><a href="/statistics.php?by=user&amp;ri=1"><b>Great Britain Contributor</b> List</a></li>
	<li><a href="/statistics.php?by=user&amp;ri=2"><b>Ireland Contributor</b> List</a></li>
	<li><a href="/statistics/first2square.php"><b>Numerical Squares</b> Leaderboard</a> (see <a href="http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=2&topic=1235&page=0#19">forum</a>)</li>
	<li><a href="/statistics/busyday.php?users=1">Most <b>Taken in a Day</b> Leaderboard</a></li>
	<li><a href="/statistics/leaderhectad.php"><b>Hectad</b> Leaderboard for <b>First Geographs</b></a></li>
	<li><a href="/statistics/leaderallhectad.php"><b>Hectad</b> Leaderboard all Images</a></li>
</ul>


<h3><a name="stats"></a>More Statistics...</h3>
<ul>

   <li>Mostly Geographed: <ul>
	   <li><a href="/statistics/most_geographed.php">Grid Squares</a></li>
	   <li><a href="/statistics/most_geographed.php">10<small>km</small> x 10<small>km</small></a> Squares (Hectads)</li>
	   <li><a href="/statistics/most_geographed_myriad.php">100km x 100km</a> Squares (Myriads)</li>
	</ul>
	</li>
   <li>Fully Geographed: <ul>
	   <li><a href="/statistics/fully_geographed.php">10<small>km</small> x 10<small>km</small> Squares</a> (includes Large Mosaic!)</b></li>
	</ul>
	</li>


	<li>Breakdown by: <ul>
	   <li><a href="/statistics/breakdown.php?by=category">Category</a>,
	   <a href="/statistics/breakdown.php?by=status">Classification</a>,
	   <a href="/statistics/breakdown.php?by=takenyear">Date Taken</a>,
	   <a href="/statistics/breakdown.php?by=user">Contributor</a>,
	   <a href="/statistics/coverage_by_county.php">County</a>,
	   <a href="/statistics/coverage_by_country.php">Country</a>, <br/>
	   <a href="/statistics/breakdown.php?by=gridsq">100km x 100km</a> (Myriads),
	   <a href="/statistics/breakdown.php?by=gridsq">10km x 10km</a> (Hectads) Squares,
	   </li>
	</ul>
	</li>
	
	<li>Past Activity: <ul>
	   <li><a href="/statistics/overtime.php">Photo Submission Activity Breakdown</a></li>
	   <li><a href="/statistics/overtime.php?date=taken">Photo Taken Activity Breakdown</a></li>
	   <li><a href="/statistics/overtime_forum.php">Forum Posting Breakdown</a></li>
	   <li><a href="/statistics/overtime_tickets.php">Change Suggestions</a></li>
	   <li><a href="/statistics/busyday.php">Most taken in a day</a></li>
	   <li><a href="/statistics/busyday.php?date=submitted">Most submitted in a day</a></li>
	</ul>
	</li>
	
	<li>Technical Database Stats: <ul>
	   <li><a href="/statistics/totals.php">Database Statisitics</a></li>
	   <li>Estimate for completion of Milestones<ul>
	   	<li><a href="/statistics/estimate.php?ri=1">Great Britain</a> or <a href="/statistics/estimate.php?ri=2">Ireland</a></li>
	   	</ul></li>
	   <li><a href="/statistics/forum_image_breakdown.php">Breakdown of Thumbnails used in Forum Topics</a></li>
	</ul>
	</li>
</ul>

<p><i><a href="/statistics.php#more">Further Stats Links...</a></i></p>

<h3>Explore Images...</h3>

<ul>
   <li><a href="/explore/calendar.php">Geograph <b>Calendar</b></a>, view images by date taken.<br/><br/></li>

   <li><b>Centre Points</b>: (really just arbitrary lists of Grid References!)<ul>
	   <li><a href="/explore/cities.php">Cities and Large Towns</a></li>
        </ul>
    </li>
</ul>

<p><i><a href="/explore/">Further Exploration Links...</a></i></p>


<h3>Tools...</h3>

<ul>
	<li><a href="/latlong.php">Lat/Long Entry Form</a></li>
	<!--<li><a href="/stuff/conversion.php">Coordinate Conversion</a></li>-->
</ul>


<h3>Other relevant Sites...</h3>

<ul>
	<li><a href="http://geograph.sf.net/">Developers Homepage</a></li>
</ul>

<h3>Developers...</h3>

<ul>
	<li>Paul Dixon's {external href="http://blog.dixo.net/category/geograph/" text="Blog" title="read Geograph related posts on Paul's blog"}</li>
	<li>Barry Hunters {external href="http://www.nearby.org.uk/blog/category/geograph/" text="Blog" title="read Geograph related posts on Barry's blog"}, and <i>unofficial</i> {external href="http://www.nearby.org.uk/geograph/" text="GeographTools"} extensions</li>
	<li>David Morris's {external href="http://www.brassedoff.net/wp/index.php?s=geograph" text="Blog" title="read Geograph related posts on Davids blog"}</li>
	
	
</ul>

    
{include file="_std_end.tpl"}
