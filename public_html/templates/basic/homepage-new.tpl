{include file="_std_begin.tpl"}

<div style="text-align:center;font-size:0.9em;padding-bottom:3px;border-bottom:1px solid silver;margin-bottom:8px">Don't like the new layout? <a href="/">Switch Back</a>. Please also consider <a href="https://spreadsheets.google.com/viewform?formkey=dE16ckhUQ0Fjd2ZvMlVyUVNFc1l0UWc6MQ">giving us feedback</a> - <b>Thank You</b>!</div>

<h2 style="text-align:center">Welcome to Geograph Britain and Ireland</h2>

<div style="position:relative;background-color:white;">

<div style="position:relative;margin-left:auto;margin-right:auto;width:740px">

<div style="background-color:#eeeeee;padding:2px; text-align:center">
The Geograph Britain and Ireland project aims to collect geographically
representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and
<a href="/explore/places/2/">Ireland</a>, and you can be part of it.</div>
<div style="text-align:right;font-size:0.7em">Looking for <a href="/help/geograph_british_isles">Geograph British Isles</a>?</div>

<div style="font-size:0.8em; text-align:center; padding:10px"><b class="nowrap">{$stats.users|thousends} users</b> have contributed <b class="nowrap">{$stats.images|thousends} images</b> <span  class="nowrap">covering <b class="nowrap">{$stats.squares|thousends} grid squares</b>, or <b class="nowrap">{$stats.percentage}%</b> of the total</span></div>


	<div class="interestBox" style="height:389px;background-color:#333333; width:550px;color:white; float:left;padding:5px;overflow:hidden;border-radius: 6px;">
		<div style="position:relative;float:left; width:400px">
			<h3 style="margin-top:0">Photograph of the day <small>[<a href="/results/2087426" style="color:cyan">previous</a>]</small></h3>

			<a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>
		</div>
		<div style="position:relative;float:left; width:150px">
			<p style="margin-top:0">Click the map to start browsing photos of the British Isles</p>

			<div class="map" style="height:{$overview_height}px;width:{$overview_width}px">
				<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

					{foreach from=$overview key=y item=maprow}
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
		<div style="float:left">
			<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/2.0/80x15.png" /></a>
		</div>
		<div>
			&nbsp; <a href="/photo/{$pictureoftheday.gridimage_id}" title="Click to see full size photo" style="color:cyan">{$pictureoftheday.image->title}</a> by <a title="Profile" href="{$pictureoftheday.image->profile_link}" style="color:cyan">{$pictureoftheday.image->realname}</a>, <span class="nowrap">taken {$pictureoftheday.image->imagetaken|date_format:"%e %b, %Y"}</span>
		</div>
	</div>
   
	<div class="interestBox" style="height:389px;background-color:#333333; width:150px;color:white; float:left; font-size:0.8em;margin-left:20px;padding:5px; overflow:auto;border-radius: 6px;">
		<h3 style="margin-top:0;">What is Geographing?</h3>

		<ul style="margin-top:2px;margin-bottom:0;padding:0 0 0 1em;">
			<li style="padding-bottom:8px">It's a game - how many grid squares will you contribute?</li>
			<li style="padding-bottom:8px">It's a geography project for the people</li>
			<li style="padding-bottom:8px">It's a national photography project</li>
			<li style="padding-bottom:8px">It's a good excuse to get out more!</li>
			<li style="padding-bottom:8px">It's a free and <a href="/faq.php#opensource" style="color:cyan">open online community</a> project for all</li>
		</ul>


		<p><a title="register now" href="/register.php" style="color:cyan">Registration</a> is free so come and join us and see how
		many grid squares you submit!</p>

	</div>

</div>

<br style="clear:both"/>
{if $recentcount}
    <div style="position:relative;margin-left:auto;margin-right:auto;width:750px; font-size:0.9em">

		<h3>Recent Photos <small>[<a href="/search.php?i=1522" title="Show the most recent submissions">see more</a>]</small></h3>

		{foreach from=$recent item=image}

		  <div style="text-align:center;padding-bottom:1em;width:150px;float:left">
		  <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>

		  <div>
		  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
		  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
		  for <span class="nowrap">square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></span>

		  </div>

		  </div>


		{/foreach}
	</div>
{/if}

{if $news2}
	<div style="clear:both; position:relative;margin-left:auto;margin-right:auto;width:750px;font-size:0.9em">
		<h3>Latest News {if $rss_url}<a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a> <small>[<a href="/discuss/index.php?&amp;action=vtopic&amp;forum=1&amp;sortBy=1">see more</a>]</small>{/if}</h3>
		{foreach from=$news2 item=newsitem}
			<div style="position:relative;width:233px;float:left; border-left: 2px solid black; padding-left:5px;margin-left:5px">
				<h4 style="margin-top: 0px;">{$newsitem.topic_title}</h4>
				<div style="font-size:0.8em">{$newsitem.post_text}</div>
				<div style="margin-top:8px;border-top:1px solid gray">
				Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> <span class="nowrap">on {$newsitem.topic_time|date_format:"%a, %e %b"}</span>
				<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
				</div>
			</div>
		{/foreach}

	</div>
{/if}

<br style="clear:both"/>


<div class="interestBox" style="text-align:center;margin:30px"><b>Looking for more? Try the <a href="/map/">Maps</a>, <a href="/explore/">Explore</a>, <a href="/numbers.php">Statistics</a>, <a href="/content/">Collections</a> or even <a href="/help/more_pages">More pages</a>.</b></div>


<p align="center">
        This site is archived for preservation by the <a href="http://www.webarchive.org.uk/ukwa/target/31653948/source/geograph">UK Web Archive</a> project.
</div>

<p align="center"><small><i>Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967. Registered office: 26 Cloister Road, Acton, London W3 0DE.</i></small></p>




{include file="_std_end.tpl"}
