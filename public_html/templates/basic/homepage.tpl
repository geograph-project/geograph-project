{assign var="page_title" value="Geograph British Isles"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}

<h2>Welcome to Geograph British Isles</h2>

<div style="width:60%;float:left;padding-right:5px;position:relative">
<p>The Geograph British Isles project aims to collect a geographically
representative photograph for every square kilometre of the 
<acronym title="Great Britain, Ireland and smaller adjacent islands">British Isles</acronym>
and you can be part of it.</p>

	<div class="map" style="height:{$overview_width+20}px;width:{$overview_width+20}px">
	<div class="cnr"></div>
	<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
	<div class="cnr"></div>


	<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

	<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview_width}px;height:{$overview_height}px;">

	{foreach from=$overview key=y item=maprow}
		<div>
		{foreach from=$maprow key=x item=mapcell}
		<a href="/mapbrowse.php?i={$x}&j={$y}&center="><img 
		ismap="ismap" title="Click to pan main map" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
		{/foreach}
		</div>
	{/foreach}
	</div>

	<div class="side" style="height:{$overview_height}px;">&nbsp;</div>

	<div class="cnr"></div>
	<div class="side" style="width:{$overview_width}px;">&nbsp;</div>
	<div class="cnr"></div>


	</div>

<p>Already you can...</p>
<ul>

    <li><a title="Browse photographs" href="/browse.php">browse images taken by other members</a></li>
    <li><a title="Submit a photograph" href="/submit.php">upload pictures and information</a></li>
    <li><a title="Discussion forums" href="/discuss/">discuss the site on our forums <b>(NEW!)</b></a></li>
</ul>

<p>Look out for more cool features coming soon!</p>
</div>

<div style="width:35%;float:left;font-size:0.8em;padding:5px;background:#dddddd;position:relative">
<h3 style="margin-bottom:0;">What is Geographing?</h3>
<ul style="margin-top:0;padding:0 0 0 1em;">
<li>It's a game - how many grid squares will you contribute?</li>
<li>It's a geography project for the people</li>
<li>It's a national photography project</li>
<li>It's a good excuse to get out more!</li>
<li>It's a free and open online community project for all</li>
</ul>


<h3 style="margin-bottom:0;">How do I get started?</h3>


<p style="margin-top:0;"><a title="register now" href="/register.php">Registration</a> is free so come and join us and see how 
many grid squares you can claim first! 

Read the <a title="Frequently Asked Questions" href="/faq.php">FAQ</a>, then get submitting -
we hope you'll enjoy being a part of this great project
</p>



</div>

<br style="clear:both"/>
&nbsp;




{include file="_std_end.tpl"}
