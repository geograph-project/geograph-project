{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<script src="{"/slideshow.js"|revision}"></script>

	<form><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/><input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/>
	<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
	<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/><input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p></form>

	{foreach from=$engine->results item=image name=results}
		{if $image->rastermap->enabled}
			<table align="center" id="mapA{$smarty.foreach.results.iteration}" style="position:relative;{if !$smarty.foreach.results.first}display:none;{/if}"><tr><td>
				<div id="mapB{$smarty.foreach.results.iteration}" class="rastermap" style="zoom:2.0; width:{$image->rastermap->width}px;position:relative;cursor:hand;float:none" onclick="show_slide_part2(cs);">
				{$image->rastermap->getImageTag()|replace:'name="tile" src':"name=\"mapC`$smarty.foreach.results.iteration`\" lowsrc"}
				</div><br/>
				<small>(click the map to reveal the image)</small>
			</td></tr></table>
		{/if}

		<div id="result{$smarty.foreach.results.iteration}" class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="display:none;position:relative">
			<table align="center" style="position:relative;"><tr>
				{if $image->rastermap->enabled}
					<td valign="top">
						<div style="float:left; position:relative; width:137px; height:137px;border:1px solid gray; padding: 10px;">
							<div style="position:absolute; top:-48px;left:-48px; clip: rect(56px 197px 197px 56px); overflow: hidden; width:199px; height:199px;">
							{$image->rastermap->getImageTag()|replace:'name="tile" src':"name=\"mapD`$smarty.foreach.results.iteration`\" lowsrc"}
							</div>
						</div>
					</td>
				{/if}
				<td>
					<div style="float:right; position:relative;">{$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>
					<div class="caption" style="clear:none"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
						{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
						<i class="nowrap">{$image->dist_string}</i><br/>
						{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
						{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
					</div>
					<div class="img-shadow" style="clear:right; position:relative;"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'src=':"name=image`$smarty.foreach.results.iteration` lowsrc="}</a></div>
					{if $image->comment}
						<div class="caption">{$image->comment|escape:'html'|geographlinks}</div>
					{/if}
				</td>
			</tr></table>
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
{literal}
 AttachEvent(window,'load',function() {
 document.images['image1'].src = document.images['image1'].lowsrc;
 setTimeout("document.images['image2'].src = document.images['image2'].lowsrc",300);
 document.images['mapC1'].src = document.images['mapC1'].lowsrc;
 document.images['mapD1'].src = document.images['mapD1'].lowsrc;;
 setTimeout("document.images['mapC2'].src = document.images['mapC2'].lowsrc",300);
 setTimeout("document.images['mapD2'].src = document.images['mapD2'].lowsrc",300);
 },false);
if (window.location.hash == '#autonext') {
	setTimeout("auto_slide_go(1)",500);
}
{/literal}
{dynamic}
var mapdelayinsec = 7;
var delayinsec = {$user->slideshow_delay|default:5} + mapdelayinsec;
{/dynamic}
 //]]></script>
 	<br style="clear:both"/>
	<p>Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
