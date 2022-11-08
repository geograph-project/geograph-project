{assign var="imageurl" value=$pictureoftheday.image->_getFullpath(false,true)}
{assign var="extra_meta" value="<meta property=\"og:image\" content=\"`$imageurl`\"/>`$extra_meta`"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

{literal}
<style>

div.homepage {
	max-width:800px;
	margin-left:auto;
	margin-right:auto;
}
.rightLinks {
	position:relative;float:right;
	white-space:nowrap;
}
.interestBox {
	border-radius: 6px;
}
.interestBox h3 {
	margin:0;
}
.homepageBox {
	padding:5px;text-align:center;border-radius:6px;
}
.titleBox {
	margin-bottom:8px;
	text-align:left;
}

/* ********************** */

.pictureOfTheDay {
	box-sizing:border-box;
	display:flex;
	margin-top:10px;
	gap:10px;
	align-items:stretch;
}
@media only screen and (max-width: 912px) {
	.pictureOfTheDay {
		flex-direction:column;
	}
	.homepage .pictureOfTheDay .imageContainer {
		max-width:inherit;
		order:1;
	}
	.homepage .pictureOfTheDay .mapContainer {
		max-width:inherit;
		order:2;
	}
	.homepage .pictureOfTheDay .listContainer {
		max-width:inherit;
		order:3;
	}
}

.pictureOfTheDay .imageContainer {
	text-align:center;
	max-width:393px;
	order:2;
}
.pictureOfTheDay .shadow img {
	border-radius: 6px;
	max-width:100%;
	height:auto;
}

.pictureOfTheDay .mapContainer {
	border-radius:6px;
	max-width:154px;
	order:3;
}
.pictureOfTheDay .mapContainer .map {
	margin-left:auto;
        margin-right:auto;
}
.pictureOfTheDay .mapContainer .prompt {
	padding:10px;
	text-align:center;
}
.pictureOfTheDay .listContainer {
	max-width:28%;
	order:1;
}
.pictureOfTheDay .listContainer h3 {
	margin-top:0;
}
.pictureOfTheDay .listContainer ul {
	padding:0 0 0 1em;
}
.pictureOfTheDay .listContainer li {
	padding-bottom:4px;
}

/* ********************** */

.photoCarousel {
	font-size:0.8em;
	height: 190px;
	overflow-x: scroll;
        overflow-y: hidden;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
	-webkit-overflow-scrolling: touch;
}
.photoCarousel > div {
	width:800px;
}
.photoCarousel div.shadow {
	float:left;
	width:150px;
	text-align:center;
}
#photo_block {
	display:none;
	position:relative;margin-left:auto;margin-right:auto;max-width:750px; margin-top:10px;
}

/* ********************** */

@media only screen and (max-width: 970px) {
	#right_block {
		display:none;
	}
	#photo_block {
		display:block;
	}
	.content3 { margin-right:0}

	.pictureOfTheDay .mapContainer {
		margin-top:2px;
	}
}
</style>
{/literal}

<div class="homepage">

	<div class="interestBox homepageBox">
		The <b>Geograph<sup style="font-size:0.4em">&reg;</sup> Britain and Ireland</b> project aims to collect geographically
		representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and
		<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</div>

	<div class="homepageBox">Since 2005, <b class="nowrap">{$stats.users|thousends} contributors</b> have submitted <b 
		class="nowrap">{$stats.images|thousends} images</b> covering <b span="nowrap">{$stats.squares|thousends} grid squares</b>, or <b 
		class="nowrap">{$stats.percentage}%</b> of the total squares</span></div>

	<div class="pictureOfTheDay">
		<div class="imageContainer shadow">
		        <div class="interestBox titleBox">
		                <div class="rightLinks">
		                        <a href="/stuff/daily.php" title="Previous Photos of the Day">view previous &gt;</a>
		                </div>
		                <h3>{$ptitles.$ptitle}</h3>
		        </div>

	                <a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a><br>

	                <a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->title}</a>
	                <span class="nowrap">by <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname}</a></span>
	                <span class="nowrap">for square <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></span>,
	                <span class="no-wrap">taken <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1">{$pictureoftheday.image->getFormattedTakenDate()}</a></span>
	        </div>

		<div class="mapContainer">
			<div class="interestBox titleBox">
		                <h3>Coverage Map</h3>
			</div>
                        <div class="map" style="height:{$overview2_height}px;width:{$overview2_width}px">
                                <div class="inner" style="position:relative;top:0px;left:0px;width:{$overview2_width}px;height:{$overview2_height}px;">

                                        {foreach from=$overview2 key=y item=maprow}
                                                <div>
                                                {foreach from=$maprow key=x item=mapcell}
                                                <a href="/mapbrowse.php?new=1&amp;o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
                                                alt="Clickable map" ismap="ismap" title="{$messages.$m}" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
                                                {/foreach}

                                                {if $marker}
                                                <div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Location of the Photo of the Day"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
                                                {/if}

                                                </div>
                                        {/foreach}
                                </div>
                        </div>
			<div class="prompt">Click the map to start browsing photos</div>
		</div>

		<div class="listContainer">
			<div class="interestBox titleBox">
		                <h3>What is Geographing?</h3>
			</div>

	                <ul>
	                        <li>It's a game - how many grid squares will you contribute?</li>
	                        <li>It's a geography project for the people</li>
	                        <li>It's a national photography project</li>
	                        <li>It's a good excuse to get out more!</li>
	                        <li>It's a <a href="/help/freedom">free</a> and <a href="/faq.php#opensource">open online community</a> project for all</li>
	                </ul>

	                <p><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how
	                many grid squares you submit!</p>
		</div>

	</div>
	<br>

	{if $recentcount}
	        <div id="photo_block">
	                <div class="interestBox titleBox">
	                        <div class="rightLinks">
	                                <a href="/explore/searches.php" title="Featured Selections">other selections &gt;</a>&nbsp;&nbsp;
	                                <a href="/finder/recent.php" title="Show the most recent submissions"><b>see more</b> &gt;</a>
	                        </div>
	                        <h3>Recent Photos</h3>
	                </div>

			<div class="photoCarousel"><div>
	                {foreach from=$recent item=image}
		                <div class="shadow">
		                        <div style="height:126px">
			                        <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
				        </div>

		                        <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'|truncate:80:"..."}</a>
			                <span class="nowrap">by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
				        <span class="nowrap">for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>
			        </div>
	                {/foreach}
			</div></div>
	        </div>
	{/if}

	<div class="interestBox homepageBox">
	        <b class="nowrap">{$stats.fewphotos|thousends} photographed squares</b> with <b class="nowrap">fewer than 4 photos</b>, <a href="/submit.php">add yours now</a>!
	</div>

	<div class="homepageBox">
	        &middot; Geograph on <b>{external href="https://twitter.com/geograph_bi" text="Twitter"},
	        {external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}</b>
	        &middot; <b><a href="/news.php">Project News</a></b>
		&middot; <a href="/help/donate">Donate/Support Us</a> &middot;
	</div>
	<br>

	{foreach from=$collections item=item}
		{assign var="source" value=$item.source}

		<div class="interestBox titleBox">
	                <div class="rightLinks">
				<a href="/content/featured.php">view previous &gt;</a> &nbsp; <a href="/content/">all collections &gt;</a>
			</div>
	                <h3>Featured Collection</h3>
		</div>

                <div>
                        <div class="shadow" style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
                                {if $item.image}
                                <a title="{$item.image->title|escape:'html'} by {$item.image->realname|escape:'html'} - click to view full size image" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a>
                                {/if}
                        </div>
                        {if $item.images > 2 && ($item.source == 'themed' || $item.source == 'gallery' || $item.source == 'snippet' || $item.source == 'article')}
                                <div style="position:relative;float:right;margin-right:10px">
                                        <a href="/browser/#/content_title={$item.title|escape:'url'}/content_id={$item.content_id}" title="View Images"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0" alt="view images in this collection"/></a>
                                </div>
                        {elseif $item.source == 'user' && $item.images > 2}
                                <div style="position:relative;float:right;margin-right:10px">
                                        <a href="/browser/#/realname+%22{$item.title|escape:'url'}%22" title="View Images"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0"/></a>
                                </div>
                        {/if}

                        <b><a href="{$item.url}">{$item.title|escape:'html'}</a></b><br/>
                        <small><small style="background-color:#{$colours.$source}">{$sources.$source}</small><small style="color:gray">{if $item.user_id}{if $item.source == 'themed' || $item.source == 'gallery'} started{/if} by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname|escape:'html'}" style="color:#6699CC">{$item.realname|escape:'html'}</a>{/if}{if $item.posts_count}, with {$item.posts_count} posts{/if}{if $item.words|thousends}, with {$item.words} words{/if}{if $item.images}, {$item.images|thousends} images{/if}{if $item.views} and viewed {$item.views|thousends} times{/if}.
                        {if $item.updated}Updated {$item.updated}.{/if}{if $item.created}Created {$item.created}.{/if}</small></small>
                        {if $item.extract}
                                <div title="{$item.extract|escape:'html'}" style="font-size:0.7em;">{$item.extract|escape:'html'|truncate:90:"... (<u>more</u>)"}</div>
                        {/if}
                        <br style="clear:left">
                </div>
	{/foreach}

{dynamic}{if $mobile_browser}
	<br>
	<div class="interestBox homepageBox">
		Tools for Mobile Devices:<br>
		&middot; <a href="https://m.geograph.org.uk/nearest">Nearest Image</a>
		&middot; <a href="/mapper/combined.php?mobile=1">Coverage Map</a>
		&middot; <a href="/submit-mobile.php">Submit Image</a>
		&middot; <a href="https://m.geograph.org.uk/radar/">Geograph Radar</a>
		&middot; 
	</div><br>
{/if}{/dynamic}

	<div class="homepageBox">Related Sites:
		&middot; Geograph <a href="https://schools.geograph.org.uk/">For Schools</a> 
		&middot; {external href="https://www.geograph.org.gg/" text="Geograph Channel Islands"}
		&middot; {external href="https://geo-en.hlipp.de/" text="Geograph Germany"}
	        &middot;
	</div>

	<div class="interestBox homepageBox">
		<i class="nowrap">Geograph<sup>&reg;</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page" class=nowrap>Geograph Project Limited</a>,
		a Charity Registered <span class="nowrap">in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. <br>
		The registered office is Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.
	</div>

	<div class="homepageBox" style="font-size:0.8em"><i>
		This site is archived for preservation by the <a href="/help/webarchive">UK Web Archive, Internet Archive and WikiMedia Commons</a> projects.</i>
	</div>
</div>

{literal}
<script type="application/ld+json">
{
   "@context": "http://schema.org",
   "@type": "WebSite",
   "url": "https://www.geograph.org.uk/",
   "name": "Geograph Britain and Ireland",
   "alternateName": "Geograph",
   "potentialAction": {
     "@type": "SearchAction",
     "target": "https://www.geograph.org.uk/of/{search_term}",
     "query-input": "required name=search_term"
   }
}
</script>
{/literal}

{include file="_std_end.tpl"}

