{assign var="maincontentclass" value="content2"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}


<h2 style="text-align:center">Welcome to Geograph Britain and Ireland</h2>

<div style="position:relative;background-color:white;">

<div style="position:relative;margin-left:auto;margin-right:auto;width:750px">

<div  class="interestBox" style="padding:2px; text-align:center;border-radius: 6px;">
The Geograph Britain and Ireland project aims to collect geographically
representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and
<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</div>

<div style="font-size:0.8em; text-align:center; padding:10px"><b class="nowrap">{$stats.users|thousends} members</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total</span></div>


	<div class="interestBox" style="height:370px;background-color:#333333; width:550px;color:white; float:left;padding:10px;overflow:hidden;border-radius: 10px;">
		<div style="position:relative;float:left; width:400px">
			<div style="position:relative;float:right;margin-right:10px">
				<a href="/results/2087426" style="color:white;font-size:0.9em;text-decoration:none;border-bottom:1px solid gray">view previous &gt;</a>
			</div>
			<h3 style="margin-top:0;margin-bottom:8px;font-size:0.95em">Photograph of the day</h3>

			<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(393,300)|replace:'/>':' style="border:1px solid black"/>'}</a>
		</div>
		<div style="position:relative;float:left; width:150px">
			<p style="margin-top:30px;text-align:center;font-size:0.8em">Click the map to start browsing photos of the <span class="nowrap">British Isles</span></p>

			<div class="map" style="height:{$overview_height}px;width:{$overview_width}px">
				<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

					{foreach from=$overview2 key=y item=maprow}
						<div>
						{foreach from=$maprow key=x item=mapcell}
						<a href="/mapbrowse.php?o={$overview_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img
						alt="Clickable map" ismap="ismap" title="{$messages.$m}" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
						{/foreach}

						{if $marker}
						<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Location of the Photo of the Day"><img src="http://{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
						{/if}

						</div>
					{/foreach}
				</div>
			</div>
		</div>
		<br style="clear:both"/>
		<div style="float:right">
			<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
		</div>
		<div style="font-size:0.9em;margin-top:8px">
			&nbsp; <a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo" style="color:white;text-decoration:none;border-bottom:1px solid gray">{$pictureoftheday.image->title}</a> <span class="nowrap">by <a title="Profile" href="{$pictureoftheday.image->profile_link}" style="color:white;text-decoration:none;border-bottom:1px solid gray">{$pictureoftheday.image->realname}</a></span>, <span class="nowrap">taken <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1" style="color:white;text-decoration:none;border-bottom:1px solid gray">{$pictureoftheday.image->imagetaken|date_format:"%e %b, %Y"}</a></span>
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
				<a href="/thumbed-weekly.php" title="Popular images">popular images &gt;</a> &nbsp;&nbsp;&nbsp;
				<a href="/search.php?i=1522" title="Show the most recent submissions"><b>see more</b> &gt;</a>
			</div>
			<h3 style="margin:0">Recent Photos</h3>
		</div>

		{foreach from=$recent item=image}

		<div style="text-align:center;padding-bottom:1em;width:150px;float:left;font-size:0.8em;">
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

<div style="text-align:center;clear:both;font-size:0.8em;padding:10px;">
	<b class="nowrap">{$stats.fewphotos|thousends} photographed squares</b> with <b class="nowrap">fewer than 4 photos</b>, <a href="/submit.php">add yours now</a>!
</div>
{if $news2}
	<div style="clear:both; position:relative;margin-left:auto;margin-right:auto;width:750px;font-size:0.9em">
		<div class="interestBox" style="border-radius: 6px;margin-bottom:8px;">
			<div style="position:relative;float:right">
				<a href="/discuss/index.php?&amp;action=vtopic&amp;forum=1&amp;sortBy=1"><b>read more</b> &gt;</a>
			</div>
			<h3 style="margin:0">Latest News {if $rss_url}&nbsp;&nbsp;&nbsp;<a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a>{/if} <a rel="alternate" type="application/rss+xml" title="Twitter Feed" href="http://twitter.com/statuses/user_timeline/251137848.rss" class="xml-rss">Twitter RSS Feed</a></h3>
		</div>

		{if $feed}
			<div style="position:relative;width:233px;float:left; border-left: 2px solid silver; padding-left:5px;margin-left:5px">
				{foreach from=$feed item=item}
					<div style="font-size:0.8em;text-align:justify">{$item.text}</div>
					<div style="margin-bottom:8px;border-bottom:1px solid gray;color:silver;text-align:right">{$item.time|date_format:"%a, %e %b"}</div>
				{/foreach}
				More at {external href="http://twitter.com/geograph_bi" text="twitter.com/geograph_bi"}
			</div>
		{/if}

		{foreach from=$news2 item=newsitem}
			<div style="position:relative;width:233px;float:left; border-left: 2px solid silver; padding-left:5px;margin-left:5px">
				<h4 style="margin-top: 0px;font-size:1.2em">{$newsitem.topic_title}</h4>
				<div style="font-size:0.8em;text-align:justify">{$newsitem.post_text}</div>
				<div style="margin-top:8px;border-top:1px solid gray">
				Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> <span class="nowrap">on {$newsitem.topic_time|date_format:"%a, %e %b"}</span>
				<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
				</div>
			</div>
		{/foreach}

	</div>
{/if}

<br style="clear:both"/>

<div class="interestBox" style="text-align:center;margin:30px; margin-left:auto;margin-right:auto;width:750px;border-radius: 10px;"><b>Looking for more?</b> Try the <a href="/map/">Maps</a>, <a href="/explore/">Explore</a>, <a href="/numbers.php">Statistics</a>, <a href="/content/">Collections</a> or even <a href="/help/more_pages">more pages</a>.</div>
<div style="text-align:center">Looking for <a href="/help/geograph_british_isles">Geograph British Isles</a>?</div>

<p align="center">
	This site is archived for preservation by the <a href="http://www.webarchive.org.uk/ukwa/target/31653948/source/geograph">UK Web Archive</a> project.
</div>

<p align="center"><small><i>Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967. Registered office: 26 Cloister Road, Acton, London W3 0DE.</i></small></p>


<script>
{literal}

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

//sillyness for IE6!
function thiswindowresize_wrapper() {
	if (typeof document.readyState == "undefined") {
		thiswindowresize();
	}
	else {
		if (document.readyState == "complete") {
			thiswindowresize();
		}
		else {
			document.onreadystatechange = function() {
				if (document.readyState == "complete") {
					document.onreadystatechange = null;
					thiswindowresize();
				}
			}
		}
	}
}


 AttachEvent(window,'load',thiswindowresize_wrapper,false);
 AttachEvent(window,'resize',thiswindowresize,false);
 AttachEvent(window,'documentready',thiswindowresize,false);

{/literal}
</script>


{include file="_std_end.tpl"}
