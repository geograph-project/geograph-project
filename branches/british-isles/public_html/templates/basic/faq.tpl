{assign var="page_title" value="FAQ"}
{include file="_std_begin.tpl"}
{literal}

<script>

window.onload = function () {
	if (!window.location.hash || window.location.hash.length < 1) {
		location.replace('/faq3.php?l=0');
	}
}

</script>

<div class="interestBox" style="background-color:pink">
	We have a new <a href="/faq3.php?l=0">New FAQ/Knowledgebase</a> available, please check it out!
</div>

<style type="text/css">
.helpbox { float:right;padding:5px;background:#dddddd;position:relative;font-size:0.8em;margin-left:20px;z-index:10; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
.contents A { text-decoration:none; }
.contents LI { padding-bottom:2px; }
.answers h3 { padding:10px;border-top: 1px solid lightgrey;background-color:#f9f9f9; }
.answers p { padding-left:20px; }
div:target { background-color:orange;padding-bottom:10px; }
.top { text-align:right;font-size:0.7em; }
.top A { text-decoration:none; }
</style>{/literal}
<a name="top"></a>

 <div class="helpbox">
 <h3>Useful Links</h3>
 <b>Contributing</b> photos:
 <ul>
 <li><a href="/help/guide">Geograph Guide</a></li>
 <li><a href="/article/Geograph-or-supplemental">Moderation Guide</a></li>
 <li><a href="/article/Which-Square">Locating your Image</a></li>
 <li><a href="/help/style">Submission Style Guide</a><br/><br/></li>
 <li><a href="/help/changes">Change Request System</a><br/><br/></li>
 </ul>
 <b>Browsing</b> the site:
 <ul>
 <li><a href="/help/squares">Square Definitions</a><br/><br/></li>
 <li><a href="/article/Searching-on-Geograph">Search Help</a></li>
 <li><a href="/help/stats_faq">Statistics FAQ</a><br/><br/></li>
 {if $enable_forums}
 <li><a href="/help/bbcodes">Forum BBcodes Help</a><br/><br/></li>
 {/if}
 </ul>
 The <b>Geograph Website</b>:
 <ul>
 <li><a href="/help/freedom">Geograph Freedom</a></li>
 <li><a href="/help/terms">Terms &amp; Conditions</a><br/><br/></li>
 <li><a href="/contact.php">Contact Us</a></li>
 <li><a href="/team.php">The Team</a> &amp; <a href="/help/credits">Credits</a><br/><br/></li>
 </ul>
 <b>Further Resources</b>:
 <ul>
 <li><a href="/article/?cat_word=Geograph">Geograph Articles</a></li>
 <li><a href="/help/sitemap">Sitemap</a><br/><br/></li>
 </ul>

 <a title="Info, Guides and Tutorials" href="/content/documentation.php">More...</a> or <a href="/help/more_pages">Summary Page</a><br/><br/>

 <ul>
 <li><a href="/ask.php">Ask us a question!</a><br/><br/></li>
 </ul>

 </div>


    <h2>FAQ - Frequently Asked Questions</h2>

<div style="position:relative" class="contents">
<b>Contributing</b> photos:
 <ul>
 <li><a href="#what">What is <b>Geographing</b>?</a></li>
 <li><a href="#goodgeograph">What makes a good <b>Geograph</b>?</a></li>
 <li><a href="#points">How do I get a Geograph <b>point</b> for my image?</a></li>
 <li><a href="#supplemental">What is a <b>Supplemental</b> image?</a></li>
 <li><a href="#multiple">Do you accept <b>multiple images</b> per square?</a></li>
 <li><a href="#findsquares">How do I <b>find which squares need photographing</b>?</a></li>
 <li><a href="#resize">Do I need to <b>resize</b> my photos? Are there size limits?</a></li>
 <li><a href="#commercial">Why must I agree to allow <b>commercial use</b> of my image?</a></li>
 <li><a href="#legal">What are my <b>legal rights</b> when taking photographs?</a></li>
 <li><a href="#update">I made a <b>mistake</b> on my submission, how do I <b>change</b> it?</a></li>
 <li><a href="#tpoint">What an earth is a <b>TPoint</b>?</a></li>
 <li><a href="/help/stats_faq"><b>More about statistics we track</b></a></li>
 </ul>
<b>Browsing</b> the site:
 <ul>
 <li><a href="#use">I would be interested in <b>re-using</b> Geograph content, is that possible?</a></li>
 <li><a href="#thumbsup">I've seen little thumbs-up symbols <img src="{$static_host}/img/thumbs.png" width="20" height="20" /> around the site, what are they?</a></li>
 <li><a href="#change">I <b>disagree</b> with the location or title of an image - what can I do?</a></li>
 <li><a href="#concern">I'm <b>concerned</b> about a photo or comment I have seen on the site.</a></li>
 <li><a href="#counties"><b>Counties</b>, I'm confused, which do you display?</a></li>
 <li><a href="#geographism">What do all these <b>strange words</b> mean?</a></li>
 </ul>
The <b>Geograph website</b>:
 <ul>
 <li><a href="#pages">There's a page I once saw on Geograph, and I <b>can't find</b> it again!</a></li>
 <li><a href="#rss">Do you have an <b>RSS</b> feed?</a></li>
 <li><a href="#opensource"><b>Open source? Creative Commons?</b> What's that all about?</a></li>
 <li><a href="#built"><b>Who</b> built this marvellous site?</a></li>
 </ul>
<b>Issues</b> in using the Geograph site:
 <ul>
 <li><a href="#aol">I use AOL, and the images look terrible!</a></li>
 <li class="last"><a href="#missing">Many thumbnails seem to be missing, any idea of the cause?</a></li>
 </ul>
 <hr style="clear:both"/>
 You may also find answers to questions not answered here in the Help pages mentioned on the right, or the <a href="/article/?cat_word=Geograph" style="text-decoration:underline;">Geograph website</a> section of Articles, otherwise:
 <ul>
 <li><a href="#question"><b>I have a further question</b>, what should I do?</a></li>
 </ul>

</div>
<br/><br/><br/>

<div style="position:relative" class="answers">

<div id="what">
<a name="what"></a>
<h3>What is Geographing?</h3>

	<ul>
	<li>It's a game - how many grid squares will you contribute?</li>
	<li>It's a geography project for the people</li>
	<li>It's a national photography project</li>
	<li>It's a good excuse to get out more!</li>
	<li>It's a free and <a href="#opensource">open online community</a> project for all</li>
	</ul>

	<p>See the original <a title="guide to geographing" href="/help/guide">guide to good Geographing</a>, <a title="Geograph Quickstart Guide" href="/article/Geograph-Quickstart-Guide"><b>contributors' quickstart guide</b></a>, and the <a href="/article/Geograph-Introductory-letter">welcome letter</a>.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="goodgeograph">
<a name="goodgeograph"></a>
<h3>What makes a good Geograph image?</h3>
	<ul>
	<li>You must clearly show at close range one of the main geographical features within the square<br/><br/></li>
	<li>You should include a short description relating the image to the map square<br/><br/></li>
	<li>Photographing a subject that could be useful to a child in interpreting a map<br/><br/></li>
	</ul>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="points">
<a name="points"></a>
<h3>How do I get a Geograph point for my image?</h3>
	<p>If you're the first to submit a &quot;Geograph&quot; for the grid square
	you'll get a "First Geograph" point added to your profile and the warm glow that comes
	with it.</p>

	<p>We welcome many Geograph images per square, so even if you don't get the point, you are still making a valuable contribution to the project.</p>

	<blockquote>

		<p>In addition we now award "Second Visitor" points (and Third and Fourth!) - which are given to the first Geograph the <i>second contributor</i> adds to a square. The third contributor similarly gets a "Third" point for their first Geograph to the square. </p>

		<p>So a single square can have a First, Second, Third and Fourth Visitor point, but a contributor can only get one of those per square.</p>

		<p>You can earn yourself a "Personal" point by submitting a &quot;Geograph&quot; for a square that is new to you, regardless of how many contributors have been there before.</p>
	</blockquote>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="supplemental">
<a name="supplemental"></a>
<h3>What is a Supplemental image?</h3>
	<p>If an image doesn't quite fulfill the Geograph criteria above, but is still a good
	image, we'll accept it as &quot;Supplemental image&quot; - no Geograph points are awarded,
	but the image will still appear on the selected grid square. A square that just contains
	Supplemental images is still open to be claimed as a Geograph though!</p>

	<p><img src="/templates/basic/img/icon_alert.gif" alt="Note" width="25" height="22" align="absmiddle"/> Follow this link for a <a title="geograph and supplemental guide" href="/article/Geograph-or-supplemental">more in depth discussion</a> of the finer points of moderation.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="multiple">
<a name="multiple"></a>
<h3>Do you accept multiple images per square?</h3>
	<p>Certainly - the points system is there to encourage people to make that extra effort
	to capture squares we don't have photos for yet, but we welcome additional
	Geograph or Supplemental images, perhaps showing a different subject, or a different time of year.
	You could be gaining yourself a personal point too.</p>

	<p>Everyone sees things differently - feel free to give us your take on any square. Some squares have
	been done in considerable <a href="/statistics/most_geographed.php" title="squares with good overage">detail</a>, helping to more fully document and add <a href="/moversboard.php?type=depth" title="Depth Weekly Leaderboard">depth</a> to a square. In particular, watch out for things others may have missed - the coverage maps can help with this.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="findsquares">
<a name="findsquares"></a>
<h3>How do I find which squares need photographing?</h3>
	<p>If you are looking for squares to obtain a point, try the <a href="/mapbrowse.php">coverage maps</a>, and look for green squares; also accessible from that page are various printable checksheets for easy reference in print form. More technical users might enjoy <a href="/gpx.php">GPX</a> downloads. </p>

	<p>Many of the squares have been captured but only have a few photos; check out the <a href="/mapbrowse.php?depth=1">depth map</a>, from which you can find under-represented squares. In the same vein we have a number of maps to show the distribution of photos within a square, usually on a centisquare grid, which divides a grid square into 100 squares, each 100m by 100m.</p>

	<p>We have also recently introduced a new map, &quot;<a href="/mapbrowse.php?recent=1">Recent Only</a>&quot; this shows recent photos. Help us keep the coverage current by photographing squares without any recent photos (orange or green).</p>

	<p>Also look out for <img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged icon"/> icons around the site, click them to take you to the links page for the location. From that page you can access textual lists of squares in need of photos (as well as direct links to <a href="/article/Mapping-on-Geograph">many of the maps on the site).</a>)</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="resize">
<a name="resize"></a>
<h3>Do I need to resize my photos? Are there size limits?</h3>
	<p>You can upload images of any dimensions, portrait or landscape, but the file size needs to be under 8 megabytes.
	 We do resize them so their longest dimension is 640 pixels on the main photo page.
	<i>Optionally</i>, you can also release larger versions of various sizes for downloading and re-use. <a href="/article/Larger-Uploads-Information">Larger Uploads Information</a>.</p>

	<p>Ideally images shouldn't have a longest dimension of fewer than 480 pixels. While we might accept such images if they hold particular interest, we would really prefer a larger image.</p>

	<p>See also <a href="/article/Larger-Uploads-Information#panorama-tip">Tip for uploading Panoramas</a></p>

	<p>We do record the EXIF headers from your original image, so it is
	advantageous to upload your original camera image or use image editing software that maintains the EXIF data if you want this information
	to be kept (but we don't currently make use of the data).</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="change">
<a name="change"></a>
<h3>I disagree with the location or title of an image - what can I do?</h3>
	<p>Our <a href="/help/changes">"Change Suggestions"</a> allow any registered user
	to suggest a change of grid reference, title/comment or other
	information - simply view the full size image and click the "report a problem" link.
	</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="legal">
<a name="legal"></a>
<h3>What are my legal rights when taking photographs?</h3>
	<p>Let's preface this by stating <b>We Are Not Lawyers</b>, and if you have any doubts
	about your right to take pictures, then you're probably better off not submitting it to us.
	However, there is a
	{external href="http://www.sirimo.co.uk/ukpr.php" text="useful guide"}
	available which outlines your rights in the UK fairly concisely.
	</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="update">
<a name="update"></a>
<h3>I made a mistake on my submission, how do I change it?</h3>
	<p>In the grey bar near the centre of the photo page,
	is a link &quot;Suggest a change to this image&quot; - click that and fill out the form.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="tpoint">
<a name="tpoint"></a>
<h3>What an earth is a TPoint</h3>
	<p>TPoint or 'Time-gap Point' is a new kind of point.
	A contributor can gain a TPoint by submitting a contemporary photo to a square that hasn't had a photo for 5 years. The aim is to increase the date range of available photos per square.</p>
	<p>Squares available for a recent photo are shown in orange on the <a href="/map/?recent=1">Recent Only coverage map</a>, or purple dots on the 'TPoint Availability' layer on the <a href="/mapper/">Draggable OS</a> map.</p>
	<p>&middot; Read more about the various points on the <a href="/help/stats_faq">Statistics FAQ</a></p>
</div>
<div class="top"><a href="#top">back to top</a></div>


<div id="pages">
<a name="pages"></a>
<h3>There's a page I once saw on Geograph, and I <b>can't find</b> it again!</h3>
	<p>Have a look at <a href="/help/more_pages">More Pages</a> and our <a href="/help/sitemap">Sitemap</a>, between them they should give access to many a page</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="counties">
<a name="counties"></a>
<h3>Counties, I'm confused, which do you display?</h3>
	<p>We use county information to aid recognition of place names on photo pages and other areas of the site, like helping to disambiguate search terms (e.g. a search for "Gillingham")</p>

	<p>For Ireland, it's simple; we just use the traditional counties. Great Britain isn't so easy, which has seen three <i>major</i> county structures;</p>

	<dl class="picinfo">
		<dt>Ceremonial counties (sometimes known as Geographic)</dt>
		<dd>These were introduced in 1974 primarily as a way to define areas for county councils. Although these are possibly what most people recognize as counties, a suitable dataset to allow us to use these counties would be too costly for us to bear. So we must compromise a little...</dd>

		<dt>Administrative counties (also known as district/unitary authorities)</dt>
		<dd>These are the modern 'counties' in use by the current government (since 1997). This is the best dataset we have available, so we display it prominently in the gazetteer line on photo pages. It is also useful for identifying the council responsible for the area. However for large towns/cities, for example Sheffield, which are in their own authority (i.e. the 'county' of Sheffield), we attempt to be clever and display the historic county instead.</dd>

		<dt>Historic counties</dt>
		<dd>These are the counties that have evolved over many hundreds of years and were in active use until 1974. We use this data as a fallback - where we've opted to display the administrative country on a photo page, you can often find the historic county by hovering over this title.</dd>
	</dl>

	<p>For a more in depth explanation, see {external href="http://www.abcounties.co.uk/" text="abcounties.org.uk"}. <span class="nowrap">(However beware that the site doesn't use the exact same terms.)</span></p>

	<p>To see lists of counties in each structure see the <a href="/explore/#counties">Explore Section</a>.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="geographism">
<a name="geographism"></a>
<h3>What do all these strange words mean?</h3>
	<p>Well, if it's a 'geographism' - a term developed during use on this site, then see <a href="/article/Geographisms">this glossary article</a> we have started compiling. There is also a wide range of sites dealing with acronyms, and abbreviations, listed on {external href="http://www.dmoz.org/Reference/Dictionaries/By_Subject/Computers/Internet_Terms_and_Acronyms/" text="DMOZ here"}.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="rss">
<a name="rss"></a>
<h3>Do you have an RSS feed?</h3>
	<p>RSS, or Really Simple Syndication, allows you to obtain an up-to-date listing of
	the latest Geograph submissions for integration into another website or RSS reader. For more information,
	try this {external href="http://en.wikipedia.org/wiki/RSS_(protocol)" text="Wikipedia article on RSS"}.</p>

	<div style="background-color:#eeeeee; padding:10px; float:right">The feeds are also available in
	<ul>
	<li><a title="Geograph RSS 0.91 feed" href="{$self_host}/syndicator.php?format=RSS0.91">RSS0.91</a></li>
	<li><a title="Geograph RSS 1.0 feed" href="{$self_host}/syndicator.php?format=RSS1.0"><b>RSS1.0</b></a></li>
	<li><a title="Geograph RSS 2.0 feed" href="{$self_host}/syndicator.php?format=RSS2.0">RSS2.0</a></li>
	<li><a title="Geograph OPML feed" href="{$self_host}/feed/recent.opml">OPML</a></li>
	<li><a title="Geograph HTML feed" href="{$self_host}/feed/recent.html">HTML</a></li>
	<li><a title="Geograph JavaScript feed" href="{$self_host}/feed/recent.js">JavaScript</a></li>
	<li><a class="xml-kml" title="Geograph KML (Google Earth) feed" href="{$self_host}/feed/recent.kml">KML</a></li>
	<li>simple <a class="xml-geo" title="Geograph GeoRSS feed" href="{$self_host}/feed/recent.rss">GeoRSS</a></li>
	<li><a title="Geograph GeoRSS and PhotoRSS feed" href="{$self_host}/feed/recent.geophotorss">GeoPhotoRSS</a></li>
	<li>and <a title="Geograph GPX feed" href="{$self_host}/feed/recent.gpx">GPX1.0</a></li>
	formats.</ul></div>

	<p>We provide an GeoRSS (RSS1.0) feed at
	<a title="Geograph RSS feed" href="{$self_host}/feed/recent.rss">{$self_host}/feed/recent.rss</a>
	which contains links to the latest 20 moderated images. </p>

	<div>We have recently added RSS feeds to other parts of the site:
		<ul>
			<li>You will find an <a class="xml-rss">RSS</a> button at the bottom of <a href="/search.php" title="photograph search">search results</a>, useful to keep updated on local images.</li>
			{if $enable_forums}<li>Registered users can access an RSS feed of the latest topics in the Discussion Forum, and even subscribe to an individual topic, just look for the <a class="xml-rss">RSS</a> button!</li>
			<li>the Grid Square Discussions even supports <a class="xml-geo" title="Geograph Grid Square Discussions" href="{$self_host}/discuss/syndicator.php?forum=5&amp;format=GeoRSS">GeoRSS</a>.</li>{/if}
			<li>Get an <a class="xml-rss" href="/article/feed/recent.rss">RSS</a> feed of recently updated <a href="/article/">Articles</a>.</li>
			<li>The newer <a href="/content/">Content</a> section has an <a class="xml-rss" href="/content/feed/recent.rss">RSS</a> feed.</li>
			<li>Find out about <a href="/events/">organized meets</a> by following the <a class="xml-rss" href="/events/feed.rss">RSS</a> feed.</li>
		</ul>
	 (They also accept the format parameter like the main feed.)</div>

	<p>If you use the {external title="Firefox Web Browser" href="http://www.mozilla.org/products/firefox/" text="Firefox"} web browser,
	you should be able use our feeds as "live bookmarks" - simply
	click the orange button in the address bar (or in the status bar on older versions).</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="thumbsup">
<a name="#thumbsup"></a>
<h3>I've seen little thumbs-up symbols <img src="{$static_host}/img/thumbs.png" width="20" height="20" /> around the site, what are they?</h3>
<p>Simply click them if you like the image and/or description (separate icon for each). </p>

<p>We don't know what use we will make of the data, but note that there are number of things we won't do. We won't disclose who is voting (all anonymous),
 we won't be using it to produce leaderboards, and we won't be disclosing which images that have few/no votes. The general idea is to simply find great content worth showcasing.</p>

<p><a href="/help/voting">See this page</a> for a bit more information, and the general principles behind voting on Geograph.</p>

</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="use">
<a name="use"></a>
<h3>I would be interested in using Geograph content, is that possible?</h3>
	<div style="float:right"><a href="http://creativecommons.org/licenses/by-sa/2.0/"><img src="{$static_host}/img/cc_deed.jpg" width="226" height="226" alt="Creative Commons Licence Deed"/></a></div>

	<p><b>All images are licensed for re-use under a <a href="#opensource">Creative Commons Licence</a></b>, see
	licence details by viewing a full size image. Also look for the &quot;<i>Find out how to re-use this image?</i>&quot; link under each image on the main photo page, which outlines easy ways to re-use the image.</p>

	<p><b>Are you a developer?</b></p>
	<blockquote style="font-size:0.9em;margin-left:15px">
		<p>... maybe looking for <a href="/article/Ways-to-view-Geograph-Images">ways to access images outside the website</a>?</p>

		<p>Please get in <a title="Contact Us" href="/contact.php">contact</a> if you have an idea for re-using images,
		beyond what's available via the RSS feeds above. In all likelihood we can provide a <a href="/help/api">feed</a>
		to suit your requirements.</p>

		<p>The entire archive will be available for download via bittorrent - see
		{external title="Geograph Archive Torrents" href="http://torrents.geograph.org.uk" text="http://torrents.geograph.org.uk"}
		for details.</p>
	</blockquote>

	<p>We also have a Google Gadget: * {external href="http://www.google.com/ig/add?moduleurl=http%3A%2F%2Fwww.geograph.org.uk%2Fstuff%2Fggadget0.xml" target="_blank" text="Add to my iGoogle page"} * {external href="http://www.google.com/ig/creator?url=http%3A%2F%2Fwww.geograph.org.uk%2Fstuff%2Fggadget0.xml" text="Add to any webpage"} *</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="opensource">
<a name="opensource"></a>
<h3>Open source? Creative Commons? What's that all about?</h3>

	<div class="interestBox" style="float:right;width:200px">
		<b>Find out about <a href="/article/Geograph-for-Developers">getting involved as a developer</a></b>.
	</div>

	<p>Putting this together requires many people to donate their
	time or resources, and we wanted to be sure that we created a resource
	free from commercial exploitation in future. To that end, the site software
	is available for re-use under the terms of the GNU Public Licence (GPL).</p>

	<p>In addition, we require all submitters to adopt a
	{external href="http://creativecommons.org/licenses/by-sa/2.0/" text="Creative Commons Attribution-ShareAlike"}
	licence on their photographic submissions. While our volunteer photographers
	keep copyright on their photos, they also grant the use of their photographs
	in return for attribution (take a look at a <a title="View a typical photograph" href="/photo/14">typical
	submission</a> for more details).</p>

	<p>In a nutshell, we wanted to build a true community project that won't
	leave a nasty taste in the mouth by getting sold for shedloads of cash and
	taken away from the people who contributed. These licence terms ensure that the
	site and content can never be "taken away" from you. See <a href="/help/freedom">Freedom - The Geograph Manifesto</a>
	</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="commercial">
<a name="commercial"></a>
<h3>Why must I agree to allow commercial use of my image?</h3>

<p>Running this site costs money, particularly over time as the storage requirements
are quite large. While we are confident we can meet those costs with sponsorship, granting
commercial use allows anyone who runs the archive in the distant future to explore
other options for generating funds, such as sales of montage posters. </p>

<p>Granting everyone those same rights actually protects the site community from
exploitation (see previous FAQ entry), but do bear in mind that we only retain a
screen-quality version of your image, and that under the terms of the Creative Commons
Licence, you must be credited for any use of your image. </p>

</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="built">
<a name="built"></a>
<h3>Who built this marvellous site?</h3>
	<p>Please see the <a href="/help/credits" title="Credits Page">Credits Page</a> for
	information on all the <a href="/team.php">people</a> who make this site possible.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="concern">
<a name="concern"></a>
<h3>I'm concerned about a photo or comment I have seen on the site.</h3>
       <p>Please <a title="Contact Us" href="contact.php">Contact Us</a>, we'll do our best to deal with your concerns promptly.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="question">
<a name="question"></a>
<h3 style="padding-top:0px;">I have a further question, what should I do?</h3>
	<p>Please <a title="Contact Us" href="contact.php">Contact Us</a>{if $enable_forums}, or drop in on our friendly Discussion Forum.{/if}</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="aol">
<a name="aol"></a>
<h3>I use AOL, and the images look terrible!</h3>
	<p>AOL's default settings for graphics is to show &quot;Compressed Graphics Only&quot;.
	This means AOL is selectively <i>re-compressing</i> images before you see them on your
	screen. This loses a lot of image quality.</p>

	<p>You should reset the preferences under &quot;My AOL/Preferences/WWW&quot; to display
	&quot;Uncompressed Graphics&quot;. You will pay only a small penalty in download time to see
	this and other photo-oriented sites the way everyone else can see them.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

<div id="missing">
<a name="missing"></a>
<h3>Many thumbnails seem to be missing, any idea of the cause?</h3>
	<p>Some firewall programs, in particular Norton Internet Security, block images that are the same size as some common advertisements. Unfortunately many thumbnails and some map images happen to be this exact size.</p>

	<p>So if you use such a program you might like to try turning it off temporarily and trying again, and if that gets our images back, then have a look for the option to disable this, arguably, flawed method of security.</p>
</div>
<div class="top"><a href="#top">back to top</a></div>

</div>

{include file="_std_end.tpl"}
