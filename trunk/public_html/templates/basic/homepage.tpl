{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Welcome to Geograph British Isles</h2>

<div style="font-size:0.7em; text-align:center; border: 1px solid silver; padding:5px">Quick Statistics: <b class="nowrap">{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b>, covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, and been awarded <b class="nowrap">{$stats.points|thousends} points</b>. <a href="/help/stats_faq">FAQ</a><br/>

Recently completed hectads: 
{foreach from=$hectads key=id item=obj}
<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">more...</a><br/>
<b class="nowrap">{$stats.fewphotos|thousends} squares still with less than 4 photos</b>, add yours now!
</div><br/>

<div style="width:60%;float:left;padding-right:5px;position:relative">
<p>The Geograph British Isles project aims to collect geographically
representative photographs and information for every square kilometre of the UK and Eire,
and you can be part of it.</p>



<h3>Getting started...</h3>
<ul>
	<li><a title="Browse by Map" href="/mapbrowse.php">browse images on a <b>map</b></a></li>
	<li><a title="Submit a photograph" href="/submit.php"><b>upload</b> your own <b>pictures</b></a></li>
	<li><a title="Discussion forums" href="/discuss/"><b>discuss the site</b> on our forums</a></li>
</ul>

<h3>Exploring in more depth...</h3>
<ul>
	<li><a title="Find photographs" href="/search.php"><b>search images</b> taken by other members</a></li>
	<li><a title="Statistical Breakdown" href="/statistics.php"><b>view statistics</b> of images submitted</a></li>
	<li><a title="Explore Images" href="/explore/"><b>explore</b> geograph images</a></li>
	<li><a title="List of all pages" href="/help/sitemap">view the <b>full list</b> of pages</a></li>
</ul>
    

<h3>Interacting with other software...</h3>
<ul>
	<li><a title="Google Earth Export" href="/kml.php">view images in <b>Google Earth</b> or <b>Maps</b></a> <a title="Recent Images in Google Earth" href="/syndicator.php?format=KML" class="xml-kml">KML</a></li>
	<li><a title="RSS Deeds" href="/faq.php#rss">get <b>RSS feeds</b> of images</a> <a title="RSS Feed of Recent Images" href="/syndicator.php" rel="RSS" class="xml-rss">RSS</a></li>
	<li><a title="Memory Map Export" href="/memorymap.php">view squares in <b>Memory Map</b></a></li>
	<li><a title="GPX File Export" href="/gpx.php">download squares in <b>GPX Format</b></a> <a title="GPX File of Recent Images" href="/syndicator.php?format=GPX" rel="RSS" class="xml-gpx">GPX</a></li>
</ul>

</div>

<div style="width:35%;float:left;font-size:0.8em;padding:5px;background:#dddddd;position:relative">
<h3 style="margin-bottom:0;">What is Geographing?</h3>
<ul style="margin-top:0;margin-left:0;padding:0 0 0 1em;">
<li>It's a game - how many grid squares will you contribute?</li>
<li>It's a geography project for the people</li>
<li>It's a national photography project</li>
<li>It's a good excuse to get out more!</li>
<li>It's a free and open online community project for all</li>
</ul>


<h3 style="margin-bottom:0;">How do I get started?</h3>


<p style="margin-top:0;"><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how 
many grid squares you can claim first! 

Read the <a title="Frequently Asked Questions" href="/faq.php">FAQ</a>, then get submitting -
we hope you'll enjoy being a part of this great project
</p>



</div>

<br style="clear:both"/>
&nbsp;





{include file="_std_end.tpl"}
