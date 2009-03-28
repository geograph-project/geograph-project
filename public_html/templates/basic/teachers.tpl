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
 
<p>This page is intended as a quick start guide for using Geograph in the Classroom, please get in <a href="/contact.php">contact</a> if you have an idea for something to add here.</p>

<p>Probably mostly of interest for Geography and maybe History lessens, but also finds plenty of other uses such as inspiration for creative writing.</p>
 

<h3>Understanding the Map</h3>

<ul>
	<li><a href="/article/Ordnance-Survey-Map-Symbols">Illustrated OS Map Symbols</a></li>
	<li><a href="/mapper/">View Geograph Images on a draggable OS map</a></li>
</ul>


<h3>Activities &amp; Resources <sup>[<a href="/activities/">more...</a>]</sup></h3>

<ul>
	<li><a href="/games/">Geograph Games</a> - fun games involving images and maps</li>
	<li><a href="/help/imagine">Imagine Slideshow</a> - imagine the landscape behind the map</li>
	<li><a href="/activities/compare.php">Compare-a-Pair</a> - compare and contrast two similar pictures</li>
	<li><a href="/content/">Compiliations</a> - user contributed collections of images on various themes</li>
</ul>


<h3>View Images in Google Earth</h3>
	
<ul>
	<li>The quickest method is using the <a href="/kml-superlayer.php">Superlayer</a> - will open directly in GE</li>
	<li>Many more options listed <a href="/kml.php">here</a></li>
	
	<li>See also <a href="/article/Ways-to-view-Geograph-Images">this page</a> for other software</li>
	
</ul>

{if $enable_forums && $forum_teaching >= 0}

<h3>Discussion Forum</h3>

<ul>
	<li>We have a <a href="/discuss/index.php?&action=vtopic&amp;forum={$forum_teaching}">dedicated area</a> in our forum for educators. Let us know what you would like to see here!</li> 
</ul>
{/if}

{include file="_std_end.tpl"}
