{include file="_std_begin.tpl"}

<h2>Welcome to Geograph Ireland</h2>

<div style="width:60%;float:left;padding-right:5px;position:relative">
<p>The Geograph project aims to collect geographically
representative photographs and information for every square kilometre of 
<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</p>

<h3>Getting started...</h3>
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
	<li><a title="Photo Gallery" href="/discuss/?action=vtopic&amp;forum=11">view some <b>themed galleries</b></a></li>
</ul>
    

<h3>Interacting with other software...</h3>
<ul>
	<li><a title="Google Earth Export" href="/kml.php">view images in <b>Google Earth</b> or <b>Maps</b></a> <a title="Recent Images in Google Earth" href="/feed/recent.kml" class="xml-kml">KML</a></li>
	<li><a title="RSS Feeds" href="/faq.php#rss">get <b>RSS feeds</b> of images</a> <a title="RSS Feed of Recent Images" href="/feed/results/5165.rss" rel="RSS" class="xml-rss">RSS</a></li>
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
<li>It's a free and <a href="/faq.php#opensource">open online community</a> project for all</li>
</ul>


<h3 style="margin-bottom:0;">How do I get started?</h3>


<p style="margin-top:0;"><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how 
many grid squares you can claim first! 

Read the <a title="Frequently Asked Questions" href="/faq.php">FAQ</a>, then get submitting -
we hope you'll enjoy being a part of this great project
</p>

</div>

<div style="margin-top:10px;padding:5px;background:yellow;position:relative">
	<p>We are still developing geograph.ie, and tailoring it to the Geograph Ireland project.</p>
	
	<p>While we make the transition, this beta site
	may show some pages with elements a little out of
	kilter, but we still welcome any <a href="/contact.php">feedback</a>.</p>
</div>
<br/>

{if $overview}
	<h3>Overview Map</h3>
	<div class="map" style="margin-left:20px;border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

	<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

	{foreach from=$overview key=y item=maprow}
	        <div>
	        {foreach from=$maprow key=x item=mapcell}
	        <a href="/mapbrowse.php?new=1&amp;o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
	        alt="Clickable map" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	        {/foreach}
	        </div>
	{/foreach}
	</div>
	</div>
{/if}


&middot; Geograph on: {external href="https://twitter.com/geograph_bi" text="Twitter"}, {external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}<br/>
<br>
<a href="/help/donate" style="background-color:purple;color:white;text-decoration:none;font-size:1.1em;padding:4px;margin:5px;border-radius:4px">Donate/Support Us</a>
</div>


<br style="clear:both"/>
&nbsp;

<div style="text-align:center; border: 1px solid silver; padding:5px">Within Ireland... <b class="nowrap">{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total</span>.<br/>

Recently completed hectads: 
{foreach from=$hectads key=id item=obj}
<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php?ri=2" title="Completed 10km x 10km squares">more...</a><br/>

<span class="nowrap"><b>{$stats.fewphotos|thousends} photographed squares</b> with <b>fewer than 4 photos</b></span>, add yours now!

</div><br/>


<br style="clear:both"/>

{if $recentcount}
        <div style="position:relative;margin-left:auto;margin-right:auto;width:750px; margin-top:10px" id="photo_block">
                <div class="interestBox" style="border-radius: 6px;margin-bottom:8px">
                        <div style="position:relative;float:right">
                                <a href="/explore/searches.php" title="Featured Selections">other selections &gt;</a>&nbsp;&nbsp;
                                <a href="/finder/recent.php" title="Show the most recent submissions"><b>see more</b> &gt;</a>
                        </div>
                        <h3 style="margin:0">Recent Photos</h3>
                </div>

                {foreach from=$recent item=image}

                <div class="shadow" style="text-align:center;padding-bottom:1em;width:150px;float:left;font-size:0.8em;">
                        <div style="height:126px">
                                <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
                        </div>

                        <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
                        <span class="nowrap">by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
                        <span class="nowrap">for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>

                </div>

                {/foreach}
                <br style="clear:both"/>
        </div>
{/if}



<div class="interestBox">
        <b>Geograph Ireland</b> is a currently sub-project of {external href="https://www.geograph.org.uk/" text="Geograph Britain and Ireland"}, the two sites share a common database. Photos submitted to one will be available on the other.
</div>

{literal}
<script type="application/ld+json">
{
   "@context": "http://schema.org",
   "@type": "WebSite",
   "name": "Geograph Ireland",
   "alternateName": "Geograph",
   "url": "https://www.geograph.ie/",
   "potentialAction": {
     "@type": "SearchAction",
     "target": "https://www.geograph.ie/of/{search_term}",
     "query-input": "required name=search_term"
   }
}
</script>

{/literal}

{include file="_std_end.tpl"}
