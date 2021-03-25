{assign var="content_title" value="Welcome to Geograph Britain and Ireland"}
{assign var="imageurl" value=$pictureoftheday.image->_getFullpath(false,true)}
{assign var="extra_meta" value="<meta property=\"og:image\" content=\"`$imageurl`\"/>`$extra_meta`"}
{include file="_std_begin.tpl"}

<style>
{literal}
body {
	background-color: #e4e4fc;
}
.grid-item { width: 220px; background-color:#eee; margin:8px; border:1px solid silver; border-radius: 5px; padding:8px; 
		text-align: center; }

@media screen and (min-width: 480px) {
	.grid-item--width2 { width: 460px; }
}

.grid .left { text-align: left }

.grid sup { font-size:0.4em; }

.grid ul { text-align:left; }

.grid-item.strong {
	border:3px solid blue;
	font-size:1.1em;
}


.grid img {
	max-width:calc(95vw - 100px);
	height:inherit;
}

.grid .shadow a {
	border-bottom: none;
}
{/literal}
</style>

<div class="grid">


  <div class="grid-item grid-item--width2 strong">
	The <b>Geograph<sup>&reg;</sup> Britain and Ireland</b> project aims to collect geographically
	representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and
	<a href="/explore/places/2/">Ireland</a>, and you can be part of it.
  </div>

  <div class="grid-item">
	Since 2005, <b class="nowrap">{$stats.users|thousends} contributors</b> have submitted <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total squares</span>
  </div>



  <div class="grid-item grid-item--width2 shadow">
	<div style="position:relative;float:right">
		<a href="/stuff/daily.php" title="Previous Photos of the Day">view previous &gt;</a>
	</div>

	<h3>{$ptitles.$ptitle}</h3>

	<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>


	<div style="float:right">
		<a rel="license" href="https://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="{$static_host}/img/80x15.png" /></a>
	</div>
	<div>
		<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->title}</a> 
		<span class="nowrap">by <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname}</a></span> 
		<span class="nowrap">for square <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></span>, 
		<span class="nowrap">taken <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1">{$pictureoftheday.image->getFormattedTakenDate()}</a></span>
	</div>
  </div>


  <div class="grid-item">
	<a href="/help/donate"><img src="{$static_host}/img/donate-now-button.gif" width="190" height="48" alt="donate now"/></a>
		Please <a href="/help/donate">support</a><br/> the project
  </div>


  <div class="grid-item">
	Click the map to start browsing photos

	<div class="map" style="height:{$overview2_height}px;width:{$overview2_width}px;margin-left:auto;margin-right:auto;">
		<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview2_width}px;height:{$overview2_height}px;">

		{foreach from=$overview2 key=y item=maprow}
			<div>
				{foreach from=$maprow key=x item=mapcell}
					<a href="/mapbrowse.php?o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
					alt="Clickable map" ismap="ismap" title="{$messages.$m}" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
				{/foreach}

				{if $marker}
					<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Location of the Photo of the Day"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
				{/if}

			</div>
		{/foreach}
		</div>
	</div>
  </div>



{if $recentcount}
  <div class="grid-item grid-item--width2 shadow" style="height:460px; overflow:hidden;">

	<div style="position:relative;float:right">	
		<a href="/finder/recent.php" title="Show the most recent submissions"><b>more</b> &gt;</a>
	</div>

	<h3>Recently Submitted Images</h3>

	{foreach from=$recent item=image}
	   <div style="float:left; width:143px; height:220px;">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a><br>

		<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
		<span class="nowrap">by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
		<span class="nowrap">for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>
	   </div>
	{/foreach}

	<div style="float:left; min-width:140px; max-width: 100%">
		<a href="/explore/searches.php" title="Featured Selections">More selections &gt;</a> <br>

		<a href="/browser/#!/display=group/group=user_id/n=4/gorder=user_id%20desc" title="images by new contributors">New contributors &gt;</a> <br>
	</div>
  </div>
{/if}


  <div class="grid-item">
	<b class="nowrap">{$stats.fewphotos|thousends} photographed squares</b> with <b class="nowrap">fewer than 4 photos</b>, <a href="/submit.php">add yours now</a>!
  </div>


  <div class="grid-item">
	Geograph on <b>{external href="https://twitter.com/geograph_bi" text="Twitter"},
	{external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}</b> 
	&middot; <b><a href="/news.php">Project News</a></b><br/>
  </div>




{foreach from=$collections item=item}
	{assign var="source" value=$item.source}

	  <div class="grid-item" style=" border:2px solid #{$colours.$source};">
		<h3>Featured Collection</h3>

		<div>
			<div class="shadow" style="float:left; width:60px; height:60px; margin-right:10px; position:relative">
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
		</div>

		<a href="/content/featured.php">previous featured &gt;</a><br>
		<a href="/content/">all collections &gt;</a>
	  </div>
{/foreach}




  <div class="grid-item">
	<h3>What is Geographing?</h3>
	<ul>
		<li>It's a game - how many grid squares will you contribute?</li>
		<li>It's a geography project for the people</li>
		<li>It's a national photography project</li>
		<li>It's a good excuse to get out more!</li>
		<li>It's a free and <a href="/faq.php#opensource">open online community</a> project for all</li>
	</ul>

	<a title="register now" href="/register.php">Registration</a> is free so come and join us and see how many grid squares you submit!
  </div>



{if $job}
  <div class="grid-item">
	<a href="/blog/{$job.blog_id}">{$job.title|escape:'html'}</a> &middot; (<a href="/blog/?tag=job%20posting">more...</a>)
  </div>
{/if}




  <div class="grid-item">
	This site is archived for preservation by the <a href="https://www.webarchive.org.uk/ukwa/target/31653948">UK Web Archive</a> project.
  </div>


  <div class="grid-item">
	<span class="nowrap"><i>Geograph<sup>&reg;</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">a Charity Registered in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. <br> The registered office is Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.
  </div>

  <div class="grid-item">
        <a href="https://schools.geograph.org.uk/">Geograph for Schools</a> 
	{external href="https://www.geograph.org.gg/" text="Geograph Channel Islands"}
	{external href="https://geo-en.hlipp.de/" text="Geograph Germany"}
  </div>

</div>

{literal}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.js"></script>

<script>
var msnry;
$(function() { 
	var elem = document.querySelector('.grid');
	msnry = new Masonry( elem, {
	  // options
	  itemSelector: '.grid-item',
	  columnWidth: 240
	});

	$('.toggle').click(function() {
		setTimeout(function() {
			msnry.layout();
		}, 350);
	});
});

</script>

<script type="application/ld+json">
{
   "@context": "http://schema.org",
   "@type": "WebSite",
   "url": "https://www.geograph.org.uk/",
   "name": "Geograph",
   "alternateName": "Geograph Britain and Ireland",
   "potentialAction": {
     "@type": "SearchAction",
     "target": "https://www.geograph.org.uk/of/{search_term}",
     "query-input": "required name=search_term"
   }
}
</script>

{/literal}


{include file="_std_end.tpl"}
