{assign var="imageurl" value=$pictureoftheday.image->_getFullpath(false,true)}
{assign var="extra_meta" value="<meta property=\"og:image\" content=\"`$imageurl`\"/>`$extra_meta`"}
{include file="_std_begin.tpl"}
{assign var="right_block" value="_block_recent.tpl"}

<div style="position:relative;background-color:white;">

<div style="position:relative;margin-left:auto;margin-right:auto;width:750px">

<div class="interestBox" style="padding:2px;text-align:center;border-radius:6px;">
The <b>Geograph<sup style="font-size:0.4em">&reg;</sup> Britain and Ireland</b> project aims to collect geographically
representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and
<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</div>

<div style="text-align:center; padding:10px">Since 2005, <b class="nowrap">{$stats.users|thousends} contributors</b> have submitted <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total squares</span></div>

	<div class="interestBox" style="height:370px;background-color:#333333; width:550px;color:white; float:left;padding:10px;overflow:hidden;border-radius: 10px;">
		<div style="position:relative;float:left; width:400px">
			<div style="position:relative;float:right;margin-right:10px">
				<a href="/stuff/daily.php" style="color:white;font-size:0.9em;text-decoration:underline gray" title="Previous Photos of the Day">view previous &gt;</a>
			</div>
			<h3 style="margin-top:0;margin-bottom:8px">{$ptitles.$ptitle}</h3>

			<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>
		</div>
		<div style="position:relative;float:left; width:150px">
			<p style="margin-top:30px;text-align:center">Click the map to start browsing photos</p>

			<div class="map" style="height:{$overview2_height}px;width:{$overview2_width}px">
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
		<br style="clear:both"/>
		<div style="float:right">
			<a rel="license" href="https://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="{$static_host}/img/80x15.png" /></a>
		</div>
		<div style="font-size:0.9em;margin-top:8px">
			<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo" style="color:white;text-decoration:underline gray">{$pictureoftheday.image->title}</a> 
			<span class="nowrap">by <a title="Profile" href="{$pictureoftheday.image->profile_link}" style="color:white;text-decoration:underline gray">{$pictureoftheday.image->realname}</a></span> 
			<span class="nowrap">for square <a href="/gridref/{$pictureoftheday.image->grid_reference}" style="color:white;text-decoration:underline gray">{$pictureoftheday.image->grid_reference}</a></span>, 
			<span class="nowrap">taken <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1" style="color:white;text-decoration:underline gray">{$pictureoftheday.image->getFormattedTakenDate()}</a></span>
		</div>
	</div>

	<div class="interestBox" style="height:370px;width:150px; float:left; font-size:0.8em;margin-left:10px;padding:10px; overflow:auto;border-radius: 10px;">
		<h3 style="margin-top:0;">What is Geographing?</h3>

		<ul style="margin-top:2px;margin-bottom:0;padding:0 0 0 1em;">
			<li style="padding-bottom:4px">It's a game - how many grid squares will you contribute?</li>
			<li style="padding-bottom:4px">It's a geography project for the people</li>
			<li style="padding-bottom:4px">It's a national photography project</li>
			<li style="padding-bottom:4px">It's a good excuse to get out more!</li>
			<li style="padding-bottom:4px">It's a free and <a href="/faq.php#opensource">open online community</a> project for all</li>
		</ul>

		<p><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how
		many grid squares you submit!</p>
	</div>

</div>

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


<div style="text-align:center;clear:both;padding:10px;">
	<b class="nowrap">{$stats.fewphotos|thousends} photographed squares</b> with <b class="nowrap">fewer than 4 photos</b>, <a href="/submit.php">add yours now</a>!
</div>


{foreach from=$collections item=item}
	{assign var="source" value=$item.source}

	<div class="interestBox" style="text-align:left; margin-left:auto;margin-right:auto;width:750px;border-radius: 10px; border:2px solid #{$colours.$source}; background-color:white; padding:0;">

		<div style="background-color: #{$colours.$source}; padding:2px;">
			<div style="float:right"> <a href="/content/featured.php">view previous &gt;</a> &nbsp; <a href="/content/">all collections &gt;</a></div>
			<b>Featured Collection</b></div>

		<div style="padding:7px">
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
			<div style="clear:left"></div>
		</div>
	</div>
{/foreach}

<div style="text-align:center;clear:both;padding:10px;">
	&middot; Geograph on <b>{external href="https://twitter.com/geograph_bi" text="Twitter"},
	{external href="https://www.facebook.com/geograph.org.uk" text="Facebook"}</b> 
	&middot; <b><a href="/news.php">Project News</a></b><br/>
</div>
{if $job}
<div class="interestBox" style="padding:3px;text-align:center;border-radius:6px;background-color:#FCDB8D;margin-bottom:5px">
	&middot; <a href="/blog/{$job.blog_id}">{$job.title|escape:'html'}</a> &middot; (<a href="/blog/?tag=job%20posting">more...</a>) &middot;
</div>
{/if}

<div style="position:relative; text-align:left; margin-left:auto;margin-right:auto;width:750px;">
	<div style="position:relative;float:left;width:200px;text-align:center">
		<p><a href="/help/donate"><img src="{$static_host}/img/donate-now-button.gif" style="vertical-align: middle;" width="190" height="48" alt="donate now" style="border-radius:14px"/></a>
		Please <a href="/help/donate">support</a><br/> the project</p>
	</div>
	<div style="position:relative;float:left;width:550px;text-align:center">
		<p>This site is archived for preservation by the <a href="/help/webarchive">UK Web Archive</a> project.</p>

		<p style="font-size:0.9em;"><span class="nowrap"><i>Geograph<sup>&reg;</sup> Britain and Ireland</i> is a project by <a href="/article/About-Geograph-page">Geograph Project Limited</a></span>, <span class="nowrap">a Charity Registered in England and Wales, no 1145621</span>. <span class="nowrap">Company no 7473967</span>. <br> The registered office is Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.</p>
	</div>
	<br style="clear:both"/>
</div>

<div style="text-align:center;clear:both;padding:10px;">
        &middot; Geograph <a href="https://schools.geograph.org.uk/">For Schools</a> 
	&middot; {external href="https://www.geograph.org.gg/" text="Geograph Channel Islands"}
	&middot; {external href="https://geo-en.hlipp.de/" text="Geograph Germany"}
        &middot;
</div>


</div>

<br style="clear:both"/>




{literal}
<script type="text/javascript">
//<![CDATA[

//sillyness for IE6!
var locked = false;

function thiswindowresize() {
	if (locked || !document.getElementById("right_block")) {
		return;
	}
  var main=document.getElementById("maincontent_block");
  var width = (main.className=="content3")?782:970;

  if (main.offsetWidth >width) {
  	document.getElementById("right_block").style.display = '';
  	document.getElementById("photo_block").style.display = 'none';
  	main.className="content3";
  } else {
   	document.getElementById("right_block").style.display = 'none';
  	document.getElementById("photo_block").style.display = '';
  	main.className="content2";
  }

	locked = true;
	setTimeout(function() { locked = false; }, 150);
}

//Stolen from JQuery!
var readyBound;
var isReady = false;
function bindReady(the_function){
    if ( readyBound ) return;
    readyBound = true;

    // Mozilla, Opera and webkit nightlies currently support this event
    if ( document.addEventListener ) {
        // Use the handy event callback
        document.addEventListener( "DOMContentLoaded", function(){
                document.removeEventListener( "DOMContentLoaded", arguments.callee, false );
                the_function();
        }, false );

    // If IE event model is used
    } else if ( document.attachEvent ) {
        // ensure firing before onload,
        // maybe late but safe also for iframes
        document.attachEvent("onreadystatechange", function(){
                if ( document.readyState === "complete" ) {
                        document.detachEvent( "onreadystatechange", arguments.callee );
                        the_function();
                }
        });

        // If IE and not an iframe
        // continually check to see if the document is ready
        if ( document.documentElement.doScroll && window == window.top ) (function(){
                if ( isReady ) return;

                try {
                        // If IE is used, use the trick by Diego Perini
                        // http://javascript.nwbox.com/IEContentLoaded/
                        document.documentElement.doScroll("left");
                } catch( error ) {
                        AttachEvent(window,'load',the_function,false);
                        return;
                }
				isReady = true;
                // and execute any waiting functions
                the_function();
        })();
    }

    // A fallback to window.onload, that will always work
    AttachEvent(window,'load',the_function,false);
}
bindReady(thiswindowresize);

AttachEvent(window,'resize',thiswindowresize,false);

//]]>
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
