{include file="_std_begin.tpl"}

{box colour="333" style="width:160px;float:left;margin-right:15px;"}
<div class="infobox" style="height:389px">
<h1>Photo Map</h1>
<p>Click the map to start browsing photos of the British Isles</p>

<div class="map" style=height:{$overview_height}px;width:{$overview_width}px">
<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

{foreach from=$overview key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img 
	alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}
	</div>
{/foreach}
</div>
</div>


<div id="sponsor">sponsored by</div>
<a href="http://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="/templates/charcoal/css/oslogo.gif"/></a>
</div>
{/box}

{box colour="000" style="width:409px;float:left;margin-right:12px;"}
<div class="infobox" style="height:389px"> 
<h1>Picture of the day</h1>

<a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>
<div class="potdtitle"><a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->title}</a> by {$pictureoftheday.image->realname}</div>
</div>
{/box}

{box colour="333" style="width:160px;float:left;"}

<div class="infobox" style="height:389px">
<h1>Welcome</h1>
<p>The Geograph British Isles project aims to collect a 
geographically representative photograph for every square 
kilometre of the British Isles and you can be part of it.</p>

<p><a href="/faq.php"><img src="/templates/charcoal/css/find_out_more.gif"/></a></p>

<div id="photocount">{$stats.images|thousends}</div>
<div id="photocount_title">photographs</div>

<div id="call_to_action">
...but there are {$stats.fewphotos|thousends} photographed squares</b> with 
fewer than 4 photos, <a href="/submit.php">add yours now!</a>
</div>

</div>

{/box}

<br style="clear:both">


 

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
	<li><a title="Google Earth Export" href="/kml.php">view images in <b>Google Earth</b> or <b>Maps</b></a> <a title="Recent Images in Google Earth" href="/feed/recent.kml" class="xml-kml">KML</a></li>
	<li><a title="RSS Deeds" href="/faq.php#rss">get <b>RSS feeds</b> of images</a> <a title="RSS Feed of Recent Images" href="/feed/recent.rss" rel="RSS" class="xml-rss">RSS</a></li>
	<li><a title="Memory Map Export" href="/memorymap.php">view squares in <b>Memory Map</b></a></li>
	<li><a title="GPX File Export" href="/gpx.php">download squares in <b>GPX Format</b></a> <a title="GPX File of Recent Images" href="/feed/recent.gpx" rel="RSS" class="xml-gpx">GPX</a></li>
</ul>

</div>

<div style="width:35%;float:left;font-size:0.8em;position:relative">

<div style="padding:5px;background:#dddddd;position:relative">
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

<div style="margin-top:10px;padding:5px;background:#ffdddd;position:relative">
<h3>Yahoo Find of the Year</h3>
<p>Geograph has been judged as Yahoo's Find of the Year 2006 in the <a href="http://uk.promotions.yahoo.com/finds2006/travel/">"Travel"</a> category.</p>
</div>

</div>



<br style="clear:both"/>
&nbsp;

{include file="_std_end.tpl"}
