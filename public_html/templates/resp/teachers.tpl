{assign var="page_title" value="Geograph for Teachers"}
{assign var="meta_description" value="Links to various resources on the Geograph website, of particular interest to Geography and History teachers."}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.breakoutbox {
	float:right; 
	position:relative; 
	background-color:#000066; 
	color:white; 
	padding:10px; 
	width:250px;
}

.breakoutbox A {
	color:white; 
}
</style>{/literal}

<h2>Geograph for Teachers</h2>

<div class="interestBox" style="margin:20px"><b>Are you involved in education?</b> We have a new <a href="/help/education_feedback">feedback form</a>, please take a moment to fill it out.</div>

{if $images}
	<div style="float:right;width:40%;max-width:450px; max-height:580px; overflow-y:auto" class=shadow>
		{foreach from=$images item=image}
			<div style="float:left; position:relative; width:213px; height:160px; padding:3px; text-align:center;">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true,$src)}</a>
			</div>
		{/foreach}
		<br style="clear:both;"/>
		<a href="/curated/sample.php">view more images...</a>
	</div>
{/if}

 
<p>This page is intended as a quick start guide for using Geograph in the classroom. Please get in <a href="/contact.php">touch</a> if you have an idea for something to add here.</p>

<p>Probably mostly of relevance to Geography and maybe History lessons, but there are also plenty of other uses such as inspiration for creative writing.</p>
 

<h3>Understanding the Map</h3>

<ul class=touchPadding>
	<li><a href="/article/Ordnance-Survey-Map-Symbols">Illustrated OS Map Symbols</a>,
			part of a <a href="/content/?order=updated&q=os+maps&in=title&p=1&scope%5B%5D=article">Series of articles about OS Maps</a></li>
	<li><a href="/mapper/">View Geograph Images on a draggable OS map</a></li>
</ul>


<h3>Activities &amp; Resources <sup>[<a href="/activities/">more...</a>]</sup></h3>

<ul class=touchPadding>
	<li><a href="/games/">Geograph Games</a> - fun games involving images and maps</li>
	<li><a href="/help/imagine">Imagine Slideshow</a> - imagine the landscape behind the map</li>
	<li><a href="/activities/compare.php">Compare-a-Pair</a> - compare and contrast two similar pictures</li>
	<li><a href="/content/">Compilations</a> - user contributed collections of images on various themes</li>
</ul>


<h3>View Images in Google Earth</h3>
	
<ul class=touchPadding>
	<li>Options listed <a href="/kml.php">here</a></li>
	
	<li>See also <a href="/article/Ways-to-view-Geograph-Images">this page</a> for other software</li>
	
</ul>

{if $enable_forums}

<h3>Discussion Forum</h3>

<ul>
	<li>We have a <a href="/discuss/index.php?&action=vtopic&amp;forum=8">dedicated area</a> in our forum for educators. Let us know what you would like to see here!</li> 
</ul>
{/if}

		<br style="clear:both;"/>

{include file="_std_end.tpl"}
