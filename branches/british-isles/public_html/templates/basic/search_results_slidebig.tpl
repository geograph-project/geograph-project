<html>
<head>
<title>{$engine->criteria->searchdesc|escape:"html"}</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
{dynamic}
<body style="background-color:{$maincontentclass|replace:"content_photo":""}"
{if $maincontentclass eq "content_photowhite"}
	text="#000000"
{/if}
{if $maincontentclass eq "content_photoblack"}
	text="#FFFFFF"
{/if}
{if $maincontentclass eq "content_photogray"}
	text="#CCCCCC"
{/if}
>
{/dynamic}

<div style="float:right;position:relative">Back to normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide">Slide Show</a> mode &nbsp;&nbsp;</div>


{if $engine->resultCount}

	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}

	<script src="{"/slideshow.js"|revision}"></script>

	<form><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/><input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/> 
	<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
	<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/><input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p></form>

	{foreach from=$engine->results item=image name=results}
	 <div id="result{$smarty.foreach.results.iteration}"{if !$smarty.foreach.results.first} style="display:none;"{/if} class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="position:relative">
	 <div style="float:right; position:relative;">{$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>
		<div class="caption" style="font-size:1.2em"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i class="nowrap">{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		</div>
		<div class="img-shadow" style="clear:both; position:relative;"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'src=':"name=image`$smarty.foreach.results.iteration` lowsrc="}</a></div>
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
setTimeout("document.images['image1'].src = document.images['image1'].lowsrc",300);
setTimeout("document.images['image2'].src = document.images['image2'].lowsrc",600);
{dynamic}
var delayinsec = {$user->slideshow_delay|default:5};
{/dynamic}
 //]]></script>
 	<br style="clear:both"/>
	<p>Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

<div class="interestBox" style="font-size:0.8em">| <a href="/">Geograph British Isles</a> homepage | Back to normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide">Slide Show</a> mode |</div>


</body>
</html>