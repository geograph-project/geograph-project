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

	{if $marker}
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Location of the Photo of the Day"><img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
	{/if}

	</div>
{/foreach}
</div>
</div>


<div id="sponsor">sponsored by</div>
<span id="sponsorlink"><a href="http://www.ordnancesurvey.co.uk/oswebsite/education/" title="Geograph British Isles sponsored by Ordnance Survey"><img src="http://{$static_host}/templates/charcoal/css/oslogo.gif" width="127" height="35" alt="Ordnance Survey Logo"/></a></span>
</div>
{/box}

{box colour="000" style="width:409px;float:left;margin-right:12px;"}
<div class="infobox" style="height:389px"> 
<h1>Photograph of the day</h1>

<a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>

<div style="float:left">
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
</div>
<div class="potdtitle"><a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->title}</a> by <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname}</a></div>
</div>
{/box}

{box colour="333" style="width:160px;float:left;"}

<div class="infobox" style="height:389px">
<h1>Welcome</h1>
<p>The Geograph British Isles project aims to collect a 
geographically representative photograph for every square 
kilometre of the British Isles and you can be part of it.</p>

<p><a href="/help/more_pages"><img src="http://{$static_host}/templates/charcoal/css/find_out_more.gif"/></a></p>

<div id="photocount">{$stats.images|thousends}</div>
<div id="photocount_title">photographs</div>

<div id="call_to_action">
...but there are {$stats.fewphotos|thousends} photographed squares</b> with 
fewer than 4 photos, <a href="/submit.php">add yours now!</a>
</div>

</div>

{/box}

<br style="clear:both"/>
<br style="clear:both"/>

<div style="width:370px;float:left;margin-right:14px;">

{box colour="333" style="margin-bottom:12px;"}
<div class="titlebox">
<b>Are you a teacher/student?</b> <small><a href="/help/education_feedback" style="color:white">We value your opinion</a></small>.
</div>
{/box}



{box colour="f4f4f4"}
<div class="infobox_alt">
<h2>Explore...</h2>
<ul>

	<li><a href="/mapbrowse.php" title="View the coverage Map">Explore these isles with our <b>map</b></a></li>
	<li><a href="/search.php" title="Image Search"><b>Search</b> for places or features</a></li>
	<li><a href="/explore/" title="Themed Exploring">Try browsing by <b>theme</b></a></li>
	<li><a href="/content/" title="Submitted Content">Read <b>content</b> submitted by members</b></a></li>
	<li><a href="/help/sitemap">View a complete site map</a></li>
</ul>


<h2>Use and re-use our images!</h2>
<ul>
	<li><a href="/kml.php" title="KML Exports">Geograph with <b>Google Earth</b> and <b>Google Maps</b></a></li>
	<li><a href="/help/sitemap#software" title="Geograph Page List"><b>Other ways</b> to use this faboulous resource</a></li>
	<li><a href="/activities/" title="Activites">View images in our <b>Activities section</b></a></li>
	<li><a href="/teachers/" title="Education Area">Geograph for <b>teachers</b></a><br/><br/></li>
	
	<li>All our photos are licenced for reuse under a <b>{external href="http://creativecommons.org/licenses/by-sa/2.0/" text="Creative Commons Licence"}</b>. <a href="/help/freedom" title="">Find out more</a></li>
</ul>


<h2>Join In...</h2>
<ul>

	<li><a href="/games/" title="educational games">try out some <b>games</b> using our images and maps</a></li>
	<li><a href="/submit.php" title="">Add <b>your own pictures</b></a></li>
	<li><a href="/article/edit.php" title="">Write an <b>article</b></a></li>
	{if $enable_forums}
	<li><a href="/discuss/" title=""><b>Discuss</b> the project on our forums</a></li>
	{/if}
	<li><a href="/help/guide" title="">view our <b>submission criteria</b></a></li>

</ul>

</div>
{/box}

</div>

<div style="width:370px;float:left;">

{box colour="333" style="margin-bottom:12px;"}
<div class="titlebox">
<h1>Site Guide</h1>
</div>
{/box}

	{box colour="f4f4f4"}
	<div class="infobox_alt">



	<h2>Statistics Junkie?</h2>
	<ul>

		<li><a href="/numbers.php" title="">View a <b>summary</b></a></li>
		<li><a href="/statistics.php" title="">More <b>in-depth Statistics</b></a></li>
		<li><a href="/help/sitemap#stats" title="">Further Statistics</a></li>
		<li><a href="/statistics/moversboard.php" title="">View the current <b>leaderboard</b></a></li>
	</ul>


	<h2>Need Help?</h2>
	<ul>

		<li><a href="/faq.php" title="">View our Frequently Asked Questions</a></li>
		<li><a href="/help/credits" title="">Who runs the site</a></li>
		<li><a href="/contact.php" title="">Contact Us</a></li>

	</ul>
	</div>
	{/box}
	
	
	<br/><br/><br/>
	{box colour="f4f4f4"}
	<div class="infobox_alt" style="font-size:0.7em; text-align:center;">
	<b class="nowrap">{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total</span>.<br/>
	
	Recently completed hectads: 
	{foreach from=$hectads key=id item=obj}
	<a title="View Mosaic for {$obj.hectad_ref}, completed {$obj.completed}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad_ref}</a>,
	{/foreach}
	<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">more...</a><br/>
	
	<span class="nowrap"><b>{$stats.fewphotos|thousends} photographed squares</b> with <b>fewer than 4 photos</b></span>, <a href="/submit.php">add yours now</a>!
	
	</div>
	{/box}
	
	
</div>

<br style="clear:both"/>
&nbsp;

{include file="_std_end.tpl"}
