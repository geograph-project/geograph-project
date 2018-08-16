<html>
<head>
<title>Photos{$engine->criteria->searchdesc|escape:"html"}</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
{dynamic}
<body {if $maincontentclass}class="{$maincontentclass}" style="margin:2;border:0"{/if}>
{/dynamic}

<div style="float:right;position:relative">To normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide" target="_top">Slide Show</a> mode &nbsp;&nbsp;</div>
<style>
{literal}
form.buttons input {
	margin:8px;
	font-size:1.3em;
}
{/literal}
</style>

{if $engine->resultCount}

	<div class="interestBox" style="display:inline">{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}{elseif $engine->islimited}<b>{$engine->resultCount|number_format}</b> {/if} Images<b>{$engine->criteria->searchdesc|escape:"html"}</b> | Page {$engine->pagesString()}</div>

	<script src="{"/slideshow.js"|revision}"></script>

	<form class="buttons"><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/><input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/>
	<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
	<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/><input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p></form>

	{foreach from=$engine->results item=image name=results}
	 <div id="result{$smarty.foreach.results.iteration}"{if !$smarty.foreach.results.first} style="display:none;"{/if} class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="position:relative">
	 <div style="float:right; position:relative;">{$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>
		<div class="caption" style="font-size:1.2em"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		<small>{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i class="nowrap">{$image->dist_string}</i></small><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		</div>
		<div class="shadow" style="clear:both; position:relative;"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'src=':"name=image`$smarty.foreach.results.iteration` data-src="}</a></div>
		{if $image->comment}
		  <div class="caption" style="font-size:1.2em">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
  		{/if}
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	{if $engine->results}

		<div id="marker_start" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the beginning of this page of results.
		{if $engine->currentPage > 1}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage-1}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}">&lt; &lt; previous page</a>
		{/if}</div>
		<div id="marker_end" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the end of this page of results.
		{if $engine->numberOfPages > $engine->currentPage}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage+1}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}">next page &gt; &gt;</a>
		{/if}</div>
<script>//<![CDATA[
var resultcount = {$engine->numberofimages};
var hasnextpage = {if $engine->numberOfPages > $engine->currentPage}1{else}0{/if};
setTimeout("document.images['image1'].src = document.images['image1'].getAttribute('data-src')",300);
setTimeout("document.images['image2'].src = document.images['image2'].getAttribute('data-src')",600);
{dynamic}
var delayinsec = {$user->slideshow_delay|default:5};
{/dynamic}
{literal}
if (window.location.hash == '#autonext') {
	setTimeout("auto_slide_go(1)",500);
}
{/literal}
 //]]></script>
 	<br style="clear:both"/>
	<div style="font-size:0.9em">&nbsp;Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})</div>
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

<div style="font-size:0.8em;border-top:1px solid silver;margin-top:2px;padding-top:2px">| <a href="/">Geograph Britain and Ireland</a> homepage | Back to normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide">Slide Show</a> mode | {if $engine->criteria->searchclass != 'Special'}<a href="/search.php?i={$i}&amp;form=advanced">Refine this search</a> |{/if} </div>


</body>
</html>
