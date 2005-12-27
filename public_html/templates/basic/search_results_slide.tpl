{assign var="page_title" value="Search Results`$engine->criteria->searchdesc`"}
{include file="_std_begin.tpl"}
<script src="/slideshow.js"></script>

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->pageOneOnly}
	<i>many</i> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>

	<form><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/><input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/> 
	<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
	<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/><input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p></form>

	{foreach from=$engine->results item=image name=results}
	 <div id="result{$smarty.foreach.results.iteration}"{if !$smarty.foreach.results.first} style="display:none;"{/if} class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
	 <div style="float:right; position:relative;">
	 {$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>
	  	<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>, 
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i class="nowrap">{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		</div>
		<div class="img-shadow" style="clear:both"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'src=':"name=image`$smarty.foreach.results.iteration` lowsrc="}</a></div>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	{if $engine->results}
	
		<div id="marker_start" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the beginning of this page of results.
		{if $engine->currentPage > 1}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage-1}">&lt; &lt; previous page</a>
		{/if}</div>
		<div id="marker_end" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the end of this page of results.
		{if $engine->numberOfPages > $engine->currentPage}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage+1}">next page &gt; &gt;</a>
		{/if}</div>
<script>//<![CDATA[
var resultcount = {$engine->numberofimages};
setTimeout("document.images['image1'].src = document.images['image1'].lowsrc",300);
setTimeout("document.images['image2'].src = document.images['image2'].lowsrc",600);
{dynamic}
var delayinsec = {$user->slideshow_delay}+0;
{/dynamic}
 //]]></script>
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
{/if}

{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
<p align=right>{if $engine->islimited}<a title="Breakdown for images{$engine->criteria->searchdesc}" href="/statistics/breakdown.php?i={$i}">Statistics</a> {/if}<a title="Google Earth Feed for images{$engine->criteria->searchdesc}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-kml">KML</a> <a title="RSS Feed for images{$engine->criteria->searchdesc}" href="/syndicator.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-rss">RSS</a></p>	
	
{include file="_std_end.tpl"}
