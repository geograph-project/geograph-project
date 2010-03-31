{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}


<h2>Welcome to Geograph Channel Islands</h2>

<div style="position:relative;background-color:white;">

<div style="background-color:#eeeeee;padding:2px; text-align:center">
The Geograph Channel Islands project aims to collect geographically
representative photographs and information for every square kilometre of <a href="/explore/places/6/">Jersey, Guernsey, Alderney, Sark, Herm &
Jethou</a>, and you can be part of it.</div>


<br style="clear:both"/>

<div style="margin-top:10px;padding:5px;position:relative;text-align:center">
	<h3 style="margin-top:0;margin-bottom:4px;text-align:center">What is Geographing?</h3>
	
	&middot; It's a game - how many grid squares will you contribute? &middot;<br/>
	&middot; It's a geography project for the people &middot;<br/>
	&middot; It's a national photography project &middot;<br/>
	&middot; It's a good excuse to get out more! &middot;<br/>
	&middot; It's a free and <a href="/faq.php#opensource">open online community</a> project for all &middot;<br/>
	
</div>
<br style="clear:both"/>
<div style="font-size:0.8em; text-align:center; border: 1px solid silver; padding:5px"><b class="nowrap">{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage|number_format:2}%</b> of the total</span>.<br/>

{if count($hectads)}
Recently completed hectads: 
{foreach from=$hectads key=id item=obj}
<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">more...</a><br/>
{/if}

<b class="nowrap">{$stats.fewphotos|thousends} photographed squares</b> with <b class="nowrap">fewer than 4 photos</b>, add yours now!

</div><br style="clear:both"/>


<div style="width:350px;float:left;position:relative">

<h3>Quick Links...</h3>
<ul>
	<li><a title="Browse by Map" href="/mapbrowse.php">browse images on a <b>map</b></a></li>
	<li><a title="Submit a photograph" href="/submit.php"><b>upload</b> your own <b>pictures</b></a></li>
	<li><a title="Find photographs" href="/search.php"><b>search images</b> taken by other members</a></li>
	<li><a title="Discussion forums" href="/discuss/"><b>discuss the site</b> on our forums</a></li>
</ul>

<h3>Exploring in more depth...</h3>
<ul>
	<li><a title="Statistical Breakdown" href="/statistics.php"><b>view statistics</b> of images submitted</a></li>
	<li><a title="Explore Images" href="/explore/"><b>explore</b> geograph images</a></li>
	<li><a title="Submitted Content" href="/content/">view <b>content and articles</b></a> <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></li>
	<li><a title="List of all pages" href="/help/sitemap">view the <b>full list</b> of pages</a></li>
	<li><a title="Features Searches" href="/explore/searches.php">browse <b>featured collections</b></a> <a title="RSS Feed Featured Searches" href="/explore/searches.rss.php" class="xml-rss">RSS</a></li>
</ul>

</div>

<div style="width:300px;float:left;position:relative">

<p><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how 
many grid squares you submit! 

Read the <a title="Frequently Asked Questions" href="/faq.php">FAQ</a>, then get submitting -
we hope you'll enjoy being a part of this great project
</p>


<h3>View Images elsewhere...</h3>

<a title="Recent Images in Google Earth" href="/feed/recent.kml" class="xml-kml">KML</a> (for <a title="Google Earth Export" href="/kml.php"><b>Google Earth</b> and <b>Maps</b></a>)<br/>

<a title="RSS Feed of Recent Images" href="/feed/recent.rss" rel="RSS" class="xml-rss">RSS</a> (<a title="RSS Feeds" href="/faq.php#rss">more <b>RSS feeds</b> of images</a>)<br/>

<a title="GPX File of Recent Images" href="/feed/recent.gpx" rel="RSS" class="xml-gpx">GPX</a> (<a title="GPX File Export" href="/gpx.php">more GPX options</a>)<br/>

or <a title="Memory Map Export" href="/memorymap.php">in <b>Memory Map</b></a><br/><br/>

and <a href="http://www.geograph.org.uk/article/Ways-to-view-Geograph-Images">even more ways to view images</a>
</div>

<br style="clear:both"/>
&nbsp;

</div>
{include file="_std_end.tpl"}
