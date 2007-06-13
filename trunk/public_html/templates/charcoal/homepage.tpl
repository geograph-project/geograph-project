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
<span id="sponsorlink"><a href="http://www.ordnancesurvey.co.uk/oswebsite/education/" title="Geograph British Isles sponsored by Ordnance Survey"><img src="/templates/charcoal/css/oslogo.gif" width="127" height="35" alt="Ordnance Survey Logo"/></a></span>
</div>
{/box}

{box colour="000" style="width:409px;float:left;margin-right:12px;"}
<div class="infobox" style="height:389px"> 
<h1>Photograph of the day</h1>

<a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>
<div class="potdtitle"><a href="/photo/{$pictureoftheday.gridimage_id}"
title="Click to see full size photo"
>{$pictureoftheday.image->title}</a> by <a title="Profile" href="/profile/{$pictureoftheday.image->user_id}">{$pictureoftheday.image->realname}</a></div>
</div>
{/box}

{box colour="333" style="width:160px;float:left;"}

<div class="infobox" style="height:389px">
<h1>Welcome</h1>
<p>The Geograph British Isles project aims to collect a 
geographically representative photograph for every square 
kilometre of the British Isles and you can be part of it.</p>

<p><a href="/help/more_pages"><img src="/templates/charcoal/css/find_out_more.gif"/></a></p>

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
<h1>Site Guide</h1>
</div>
{/box}

{box colour="f4f4f4"}
<div class="infobox_alt">
<h2>Explore...</h2>
<ul>

	<li><a href="/mapbrowse.php" title="View the coverage Map">Explore these isles with our <b>map</b></a></li>
	<li><a href="/search.php" title=""><b>Search</b> for places or features</a></li>
	<li><a href="/explore/" title="">Try browsing by <b>theme</b></a></li>
	<li><a href="/article/" title="">Read <b>articles</b> submitted by members</b></a></li>
	<li><a href="/help/sitemap">View a complete site map</a></li>
</ul>


<h2>Use and re-use our images!</h2>
<ul>

	<li>All photos are licenced for reuse under a <b>{external href="http://creativecommons.org/licenses/by-sa/2.0/" text="Creative Commons Licence"}</b>. <a href="/help/freedom" title="">Find out more</a><br/><br/></li>
	<li><a href="/kml.php" title="">Geograph with <b>Google Earth</b> and <b>Google Maps</b></a></li>
	<li><a href="/help/sitemap#software" title=""><b>Other ways</b> to can use this faboulous resource</a></li>

</ul>


<h2>Join In...</h2>
<ul>

	<li><a href="/submit.php" title="">Add <b>your own pictures</b></a></li>
	<li><a href="/article/edit.php" title="">Write an <b>article</b></a></li>
	{if $enable_forums}
	<li><a href="/discuss/" title=""><b>Discuss</b> the project on our forums</a></li>
	{/if}
	<li><a href="/help/guide" title="">view our <b>submission criteria</b></a></li>
	
</ul>


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

</div>

<div style="width:370px;float:left;">

	{box colour="333" style="margin-bottom:12px;"}
	<div class="titlebox">
	<h1>Welcome to the new look!</h1>
	</div>
	{/box}
	
	{box colour="f4f4f4"}
	<div class="infobox_alt">
	
	<h2>Excuse our dust!</h2>
	<p>We are still developing our new look which 
	we hope will make Geograph easier and more fun
	to use!</p>
	
	<p>While we make the transition, this beta site
	may show some pages with elements a little out of
	kilter, but we still welcome any <a href="/contact.php">feedback</a>.</p>
	
	<p>Kind regards</p>
	
	<p>The Geograph Team.</p>
	</div>
	
	{/box}
</div>

<br style="clear:both"/>
&nbsp;

{include file="_std_end.tpl"}
