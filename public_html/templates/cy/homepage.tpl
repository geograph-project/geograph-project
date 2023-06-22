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
	max-width:29%;
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
	height: 225px;
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

::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background-color: rgba(0, 0, 0, .5);
  -webkit-box-shadow: 0 0 1px rgba(255, 255, 255, .5);
}
::-webkit-scrollbar-track {
    border-radius: 10px;
    background-color: #ffffff;
}

/* ********************** */

@media only screen and (max-width: 970px) {
	#right_block {
		display:none;
	}
	#photo_block {
		display:block;
	}
	.content3 { margin-right:0; padding-right:10px }

	.pictureOfTheDay .mapContainer {
		margin-top:2px;
	}
}
</style>
{/literal}

<div class="homepage">

	<div class="interestBox homepageBox">
		Nod prosiect <b>Geograph<sup style="font-size:0.4em">&reg;</sup> Prydain ac Iwerddon</b>
		yw casglu lluniau a gwybodaeth ar gyfer pob cilometr sgw&acirc;r ym
		<a href="/explore/places/1/">Mhrydain Fawr</a> ac <a href="/explore/places/2/">Iwerddon</a>, a gallwch chi fod yn rhan o hynny.</div>

	<div class="homepageBox">
		Ers 2005, mae <b class="nowrap">{$stats.users|thousends} o gyfranwyr</b> wedi cyflwyno <b class="nowrap">{$stats.images|thousends} o luniau</b> <span 
		class="nowrap">ar gyfer <b class="nowrap">{$stats.squares|thousends} o sgwariau'r grid</b></span>, <span class="nowrap">sy'n <b 
		class="nowrap">{$stats.percentage}%</b> o gyfanswm y sgwariau</span></div>

	<div class="pictureOfTheDay">
		<div class="imageContainer shadow">
		        <div class="interestBox titleBox">
		                <div class="rightLinks">
		                        <a href="/stuff/daily.php" title="Previous Photos of the Day">Gweld y llun blaenorol &gt;</a>
		                </div>
		                <h3>Llun dan sylw</h3>
		        </div>

	                <a href="/photo/{$pictureoftheday.gridimage_id}" title="Cliciwch i weld delwedd maint llawn">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a><br>

	                <a href="/photo/{$pictureoftheday.gridimage_id}" title="Cliciwch i weld delwedd maint llawn">{$pictureoftheday.image->title}</a>
	                <span class="nowrap">gan <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname}</a></span>
	                <span class="nowrap">yn sgw&acirc;r <a href="/gridref/{$pictureoftheday.image->grid_reference}">{$pictureoftheday.image->grid_reference}</a></span>,
	                <span class="nowrap">wedi'i dynnu ar  <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1">{$pictureoftheday.image->getFormattedTakenDate()}</a></span>
	        </div>

		<div class="mapContainer">
			<div class="interestBox titleBox">
		                <h3>Map Cwmpas</h3>
			</div>
                        <div class="map" style="height:{$overview2_height}px;width:{$overview2_width}px">
                                <div class="inner" style="position:relative;top:0px;left:0px;width:{$overview2_width}px;height:{$overview2_height}px;">

                                        {foreach from=$overview2 key=y item=maprow}
                                                <div>
                                                {foreach from=$maprow key=x item=mapcell}
                                                <a href="/mapbrowse.php?new=1&amp;lang=cy&amp;o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
                                                alt="Clickable map" ismap="ismap" title="{$messages.$m}" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
                                                {/foreach}

                                                {if $marker}
                                                <div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Lleoliad Llun y Diwrnod"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
                                                {/if}

                                                </div>
                                        {/foreach}
                                </div>
                        </div>
			<div class="prompt">Cliciwch ar y map i bori drwy'r lluniau</div>
		</div>

		<div class="listContainer">
			<div class="interestBox titleBox">
		                <h3>Beth yw Geographio?</h3>
			</div>

	                <ul>
	                        <li>G&ecirc;m - faint o sgwariau wnewch chi eu cyfrannu?</li>
	                        <li>Prosiect daearyddiaeth i'r bobl</li>
	                        <li>Prosiect ffotograffiaeth cenedlaethol</li>
	                        <li>Esgus da i fynd i grwydro mwy!</li>
	                        <li>Prosiect <a href="/faq.php#opensource">cymunedol ar-lein</a> sy'n rhad ac am ddim ac yn agored i bawb</li>
	                </ul>

			<p>Gallwch <a title="register now" href="/register.php">gofrestru</a> am ddim felly ymunwch &acirc; ni i
			weld sawl sgw&acirc;r o'r grid gallwch chi eu cyflwyno!</p>
		</div>

	</div>
	<br>

	{if $recentcount}
	        <div id="photo_block">
	                <div class="interestBox titleBox">
	                        <div class="rightLinks">
	                                <a href="/explore/searches.php" title="Featured Selections">Dewisiadau Eraill &gt;</a>&nbsp;&nbsp;
	                                <a href="/finder/recent.php" title="Show the most recent submissions"><b>mwy...</b> &gt;</a>
	                        </div>
	                        <h3>Lluniau diweddar</h3>
	                </div>

			<div class="photoCarousel"><div>
	                {foreach from=$recent item=image}
		                <div class="shadow">
		                        <div style="height:126px">
			                        <a title="{$image->title|escape:'html'} - Cliciwch i weld delwedd maint llawn" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
				        </div>

		                        <a title="Cliciwch i weld delwedd maint llawn" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'|truncate:80:"..."}</a>
			                <span class="nowrap">gan <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a></span>
				        <span class="nowrap">yn sgw&acirc;r <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>
			        </div>
	                {/foreach}
			</div></div>
	        </div>
	{/if}

	<div class="interestBox homepageBox">
		Mae <b class="nowrap">llai na 4 llun</b> ar gyfer <b class="nowrap">{$stats.fewphotos|thousends} o'r sgwariau</b>, <a href="/submit.php">felly ewch ati i ychwanegu'ch lluniau chi</a>!
	</div>

	<div class="homepageBox">
		&middot; Geograph ar <b>{external href="https://twitter.com/geograph_bi" text="Twitter"},
		{external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}</b>
		&middot; <b><a href="/news.php">Newyddion y Prosiect</a></b>
		&middot; <a href="/help/donate" style="background-color:purple;color:white;text-decoration:none;font-size:1.1em;padding:4px;margin:5px;border-radius:4px">Cefnogwch Ni</a> &middot;
	</div>
	<br>

	{foreach from=$collections item=item}
		{assign var="source" value=$item.source}

		<div class="interestBox titleBox">
	                <div class="rightLinks">
				<a href="/content/featured.php">Gweld y casgliad blaenorol &gt;</a> &nbsp; <a href="/content/">Gweld pob casgliad &gt;</a>
			</div>
	                <h3>Casgliad dan sylw</h3>
		</div>

                <div>
                        <div class="shadow" style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
                                {if $item.image}
                                <a title="{$item.image->title|escape:'html'} gan {$item.image->realname|escape:'html'} - Cliciwch i weld delwedd maint llawn" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a>
                                {/if}
                        </div>
                        {if $item.images > 2 && ($item.source == 'themed' || $item.source == 'gallery' || $item.source == 'snippet' || $item.source == 'article')}
                                <div style="position:relative;float:right;margin-right:10px">
                                        <a href="/browser/#/content_title={$item.title|escape:'url'}/content_id={$item.content_id}" title="Lluniau un yr Casgliad"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0" alt="Lluniau un yr Casgliad" width=20 height=15></a>
                                </div>
                        {elseif $item.source == 'user' && $item.images > 2}
                                <div style="position:relative;float:right;margin-right:10px">
                                        <a href="/browser/#/realname+%22{$item.title|escape:'url'}%22" title="Lluniau gan {$item.title|escape:'html'}"><img src="{$static_host}/templates/basic/img/cameraicon.gif" border="0"/></a>
                                </div>
                        {/if}

                        <b><a href="{$item.url}">{$item.title|escape:'html'}</a></b><br/>
                        &nbsp;<span style="background-color:#{$colours.$source}">{$sources.$source}</span><span style="color:#666">{if $item.user_id}{if $item.source == 'themed' || $item.source == 'gallery'} cychwyn{/if} gan <a href="/profile/{$item.user_id}" title="{$item.realname|escape:'html'}" style="color:#6699CC">{$item.realname|escape:'html'}</a>{/if}{if $item.posts_count}, gyda {$item.posts_count} posts{/if}{if $item.words|thousends}, gyda {$item.words} gair{/if}{if $item.images}, {$item.images|thousends} lluniau{/if}{if $item.views} a gweld {$item.views|thousends} or weithiau{/if}.
                        {if $item.updated}Diweddarwyd {$item.updated}.{/if}{if $item.created}Wedi'i greu {$item.created}.{/if}</span>
                        {if $item.extract}
                                <div style="margin-top:10px;" title="{$item.extract|escape:'html'}">{$item.extract|escape:'html'|truncate:90:"... (<u>more</u>)"}</div>
                        {/if}
                        <br style="clear:left">
                </div>
	{/foreach}

{dynamic}{if $mobile_browser}
	<br>
	<div class="interestBox homepageBox">
		Swyddogaethau ar gyfer symudol:<br>
		&middot; <a href="https://m.geograph.org.uk/nearest">Llun Agosaf</a>
		&middot; <a href="/mapper/combined.php">Mapio Sylfaenol</a>
		&middot; <a href="/submit-mobile.php">Cyflwyno</a>
		&middot; <a href="https://m.geograph.org.uk/radar/">Geograph Radar</a>
		&middot; 
	</div><br>
{/if}{/dynamic}

	<div class="homepageBox">
		&middot; <a href="https://schools.geograph.org.uk/?lang=cy">Geograph i Ysgolion</a> 
		&middot; {external href="https://www.geograph.org.gg/" text="Geograph Ynysoedd y Sianel"}
		&middot; {external href="https://geo-en.hlipp.de/" text="Geograph yr Almaen"}
	        &middot;
	</div>

	<div class="interestBox homepageBox">

		<span class="nowrap">Prosiect gan <a href="/article/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">Elusen Gofrestredig 
		yng Nghymru a Lloegr</span>, <span class="nowrap">rhif 114562</span>, yw Geograph&reg; Prydain ac Iwerddon. <span class="nowrap">Rhif y cwmni: 7473967</span>. 
		<br> Y swyddfa gofrestredig yw: Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.

	</div>

	<div class="homepageBox"><i>
		Caiff y wefan hon ei harchifo a'i chadw gan brosiect <a href="/help/webarchive">Archif We y DG, Internet Archive ac WikiMedia Commons</a></i>
	</div>
</div>

{literal}
<script type="application/ld+json">
{
   "@context": "http://schema.org",
   "@type": "WebSite",
   "url": "https://www.geograph.org.uk/?lang=cy",
   "name": "Geograph",
   "alternateName": "Geograph Prydain ac Iwerddon",
   "potentialAction": {
     "@type": "SearchAction",
     "target": "https://www.geograph.org.uk/chwilio/?q={search_term}&lang=cy",
     "query-input": "required name=search_term"
   }
}
</script>
{/literal}

{include file="_std_end.tpl"}

