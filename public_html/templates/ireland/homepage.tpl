{include file="_std_begin.tpl"}
<style>
{literal}
.ie-homepage-grid {
  display: grid;
    grid-template-areas: 
    'ie-homepage-header'
    'ie-homepage-what'
    'ie-homepage-map'
    'ie-homepage-recent'
    'ie-homepage-start'
    'ie-homepage-mobiletools'
    'ie-homepage-stats'
    'ie-homepage-support'
    'ie-homepage-projects';
  grid-template-rows: max-content max-content max-content max-content max-content max-content max-content max-content max-content;
  grid-template-columns: 1fr;
  grid-row-gap: 10px;
  grid-column-gap: 10px;
  margin: auto;
  padding: 2px;
  max-width: 100%;
  h3 {
    text-align: center;
  }
}
.ie-homepage-grid > div {
  background-color: #F6F9FB;
  border-radius: 10px;
  padding: 5px;
  min-width: 0px;
}
@media all and (min-width: 850px) {
  .ie-homepage-grid {
  grid-template-areas:
    'ie-homepage-header ie-homepage-header ie-homepage-header'
    'ie-homepage-what ie-homepage-map ie-homepage-start'
    'ie-homepage-recent ie-homepage-recent ie-homepage-recent'
    'ie-homepage-support ie-homepage-stats ie-homepage-projects';
  grid-template-rows: max-content max-content max-content max-content;  
  grid-template-columns: 2fr 3fr 2fr;
  }
}
div.ie-homepage-header {
  grid-area: ie-homepage-header;
  background-color: #E9EFF4;
  text-align: center;
}
div.ie-homepage-what {
  grid-area: ie-homepage-what;
}
div.ie-homepage-map {
  grid-area: ie-homepage-map;
}
div.ie-homepage-start {
  grid-area: ie-homepage-start;
}
div.ie-homepage-recent {
  grid-area: ie-homepage-recent;
  background-color: #E9EFF4;
}
div.ie-homepage-stats {
  grid-area: ie-homepage-stats;
}
div.ie-homepage-support {
  grid-area: ie-homepage-support;
  text-align: center;
}
div.ie-homepage-projects {
  grid-area: ie-homepage-projects;
}
div.ie-homepage-mobiletools {
  grid-area: ie-homepage-mobiletools;
  background-color: #e4e4fc;
}
@media all and (min-width: 850px) {
  div.ie-homepage-mobiletools {
    display: none;
  }
}

.ie-homepage-map-grid {
  display: grid;
  grid-template-areas:
    'ie-homepage-map-left'
    'ie-homepage-map-right';
  grid-template-rows: max-content max-content;  
  grid-template-columns: 1fr;
  grid-row-gap: 2px;
  grid-column-gap: 10px;
  margin: auto;
}
@media all and (min-width: 400px) {
  .ie-homepage-map-grid {
    display: grid;
    grid-template-areas:
      'ie-homepage-map-left ie-homepage-map-right';
    grid-template-rows: max-content;  
    grid-template-columns: max-content 1fr;
  }
}
.ie-homepage-map-left {
  grid-area: ie-homepage-map-left;
  text-align: center;
  font-size: 0.875em;
  margin: auto;
}
.ie-homepage-map-right {
  grid-area: ie-homepage-map-right;
  text-align: left;
}
.ie-homepage-recent-flex-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-around;
}
@media all and (max-width: 850px) {
  .ie-homepage-recent-flex-container {
    flex-wrap: nowrap;
    overflow-y: auto;
  }
}
.ie-homepage-recent-flex-photo {
  display: flex;
  flex-wrap: wrap;
  text-align:center;
  padding-bottom:1em;
  width:150px;
  font-size:0.8em;
}
.ie-homepage-recent-attribution
{
  text-align: center;
  width: 100%
}

.ie-homepage-thirdsgrid {
  display: grid;
  grid-template-areas:
    'ie-homepage-left ie-homepage-center ie-homepage-right';
  grid-template-rows: max-content;  
  grid-template-columns: 1fr 1fr 1fr;
  grid-row-gap: 2px;
  grid-column-gap: 5px;
  margin: auto;
  h3 {
    text-align: center;
  }
}
@media all and (max-width: 850px) {
  .ie-homepage-thirdsgrid {
  grid-template-areas: 
    'ie-homepage-left'
    'ie-homepage-center'
    'ie-homepage-right';
  grid-template-rows: max-content max-content max-content;
  grid-template-columns: 1fr;
  padding: 2px;
  }
}
.ie-homepage-left {
  grid-area: ie-homepage-left;
  text-align: left;
  background-color: #F6F9FB;
}
.ie-homepage-center {
  grid-area: ie-homepage-center;
  text-align: left;
  background-color: #F6F9FB;
}
.ie-homepage-right {
  grid-area: ie-homepage-right;
  text-align: left;
  background-color: yellow;
}
{/literal}
</style>



<div class="ie-homepage-grid">

<div class="ie-homepage-header">
<h2>Welcome to Geograph Ireland</h2>
<p>The Geograph project aims to collect geographically
representative photographs and information for every square kilometre of 
<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</p>
</div>


<div class="ie-homepage-what">

<h3 style="margin-bottom:0;">What is Geographing?</h3>
<ul style="margin-top:0;margin-left:0;padding:0 0 0 1em;">
<li>It's a game - how many grid squares will you contribute?</li>
<li>It's a geography project for the people</li>
<li>It's a national photography project</li>
<li>It's a good excuse to get out more!</li>
<li>It's a free and <a href="/faq.php#opensource">open online community</a> project for all</li>
</ul>

</div>


<div class="ie-homepage-map">
<h3>Irish Grid</h3>
<div class="ie-homepage-map-grid">
<div class="ie-homepage-map-left">

{if $overview}
	<div class="map" style="border:2px solid black; height:{$overview_height}px;width:{$overview_width}px">

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
  <p>Click map to explore</p>
{/if}

</div>
<div class="ie-homepage-map-right">

<p>The Irish Grid System provides Ireland with a coordinate system. The country is split into 1 &#x00d7; 1 km grid squares, and the Geograph Project aims to capture geographically representative images for every square.</p>
<!--<p>The grid is formed from 25 100 &#x00d7; 100 km areas known as myriads which are labelled with a letter, with land in 18 of these. Each myriad is then divided into 100 10 &#x00d7; 10 km blocks known as hectads which use a numeric coodinate system from 0 to 9 with eastings, then northings, for example <a href="/gridref/h47">H47</a> covers Omagh. These hectads are then divided futher into the 1 km grid squares, which can then be divided further into centisquares (100 &#x00d7; 100 m). This subdivision can be continued into incresingly smaller blocks by adding additional digits.</p>

<p>Stuff to explain Irish grid, myriads, hectads, gridsquares</p>-->

</div>

</div>
</div>


<div class="ie-homepage-start">

<h3>Start exploring</h3>

<ul>
  <li><a title="Explore places in the directory" href="/stuff/viewgaz3.php">Explore Ireland using our places directory</a></li>
  <li><a title="Visit the showcase gallery" href="/gallery.php">Visit our showcase gallery</a></li>
  <li><a title="Find photographs using the search" href="/search.php">Search for photographs</a></li>
	<li><a title="Browse by Map" href="/mapbrowse.php">Browse images on our map</a></li>
</ul>



<h3>Join our project</h3>

<p><a title="register now" href="/register.php">Registration</a> is free so come and join us. <a title="Submit a photograph" href="/submit.php">Submit your photos</a> and see how 
many grid squares you can contribute your photos to. Or join in the <a title="Discussion forums" href="/discuss/">discussions on our forums</a>.</p>


</div>


<div class="ie-homepage-recent">
{if $recentcount}
<h3>Recent Photos</h3>
        <div id="photo_block" class="ie-homepage-recent-flex-container">
                {foreach from=$recent item=image}
                <div class="ie-homepage-recent-flex-photo">
                        <div class="shadow" style="height:126px; width:100%;">
                                <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
                        </div>
                        <div class="ie-homepage-recent-attribution">
                        <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
                        <span class="nowrap">by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
                        <span class="nowrap">for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>
                        </div>
                </div>

                {/foreach}                
        </div>
                <div style="text-align: right;">
                        <div>
                                <a href="/explore/searches.php" title="Featured Selections">other selections &gt;</a>&nbsp;&nbsp;
                                <a href="/finder/recent.php" title="Show the most recent submissions"><b>see more</b> &gt;</a>
                        </div>
                </div>

{/if}
</div>


<div class="ie-homepage-stats">
<h3>Statistics</h3>
Since 2005, <b>{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span>covering <b class="nowrap">{$stats.squares|thousends} grid squares</b> in Ireland, or <b>{$stats.percentage}%</b> of the total</span>.<br/>

Recently completed hectads: 
{foreach from=$hectads key=id item=obj}
<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad}</a>,
{/foreach}
<a href="/statistics/fully_geographed.php?ri=2" title="Completed 10km x 10km squares">more...</a><br/>

<span><b>{$stats.fewphotos|thousends} photographed squares</b> with <b>fewer than 4 photos</b></span>, add yours now!


</div>


<div class="ie-homepage-support">
<h3>Support Geograph</h3>
<p>The Geograph Project relies on donations to fund the website.</p>

<a href="/help/donate" style="background-color:purple;color:white;text-decoration:none;font-size:1.1em;padding:4px;margin:5px;border-radius:4px">Donate/Support Us</a><br/>

</div>


<div class="ie-homepage-projects">
<h3>Geograph Britain and Ireland</h3>
<p><b>Geograph Ireland</b> is a currently sub-project of {external href="https://www.geograph.org.uk/" text="Geograph Britain and Ireland"}, the two sites share a common database. Photos submitted to one will be available on the other.</p>
<p>Geograph on: {external href="https://twitter.com/geograph_bi" text="Twitter"}, {external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}</p>
</div>

<div class="ie-homepage-mobiletools">
<h3>Tools for mobile devices</h3>

<ul class="buttonbar">
  <li><a href="https://m.geograph.org.uk/nearest">Nearest image</a></li>
  <li><a href="/mapper/combined.php">Coverage map</a></li>
  <li><a href="/submit-mobile.php">Submit image</a></li>
  <li><a href="https://m.geograph.org.uk/radar/">Geograph radar</a></li>
  <li><a href="https://m.geograph.org.uk/s/new.php">Recreate camera</a></li>
</ul>
</div>

</div>


<br/>


<div class="ie-homepage-thirdsgrid">
<div class="ie-homepage-left">


</div>


<div class="ie-homepage-center">



</div>


<div class="ie-homepage-right">



</div>

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
