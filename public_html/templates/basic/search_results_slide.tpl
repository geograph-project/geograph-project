{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<script src="{"/slideshow.js"|revision}"></script>

	<form><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/><input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/>
	<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
	<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/><input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p></form>

	{foreach from=$engine->results item=image name=results}
	 <div id="result{$smarty.foreach.results.iteration}"{if !$smarty.foreach.results.first} style="display:none;"{/if} class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="position:relative">
	 <div style="float:right; position:relative;">{$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>
		<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i class="nowrap">{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		</div>

	<div class="interestBox" style="float:right;position:relative;width:20px"><span  id="hideside{$image->gridimage_id}"></span>
		<img src="http://{$static_host}/img/thumbs.png" width="20" height="20" onmouseover="show_tree('side{$image->gridimage_id}');refreshMainList({$image->gridimage_id});"/>
	</div>

	<div style="float:right;position:relative">
	<div style="position:absolute;left:-210px;top:-20px;width:220px;padding:10px;display:none;text-align:left;z-index:1000" id="showside{$image->gridimage_id}" onmouseout="hide_tree('side{$image->gridimage_id}')">
		<div class="interestBox" onmousemove="event.cancelBubble = true" onmouseout="event.cancelBubble = true">
			<img src="http://{$static_host}/img/thumbs.png" width="20" height="20" style="float:left;padding:4px"/>
			<div id="votediv{$image->gridimage_id}img"><a href="javascript:void(record_vote('img',{$image->gridimage_id},5,'img'));" title="I like this image! - click to agree">I like this image!</a></div>
			{if $image->comment}
				<div id="votediv{$image->gridimage_id}desc"><a href="javascript:void(record_vote('desc',{$image->gridimage_id},5,'desc'));" title="I like this description! - click to agree">I like this description!</a></div>
			{/if}
			<br style="clear:both"/>
			<b>Image Buckets</b><br/>
			{foreach from=$buckets item=item}
					<label id="{$image->gridimage_id}label{$item|escape:'html'}" for="{$image->gridimage_id}check{$item|escape:'html'}" style="color:gray">
					<input type=checkbox id="{$image->gridimage_id}check{$item|escape:'html'}" onclick="submitBucket({$image->gridimage_id},'{$item|escape:'html'}',this.checked?1:0,this.value);"> {$item|escape:'html'}
					{if $item == 'CloseCrop'} (was Telephoto){/if}
					</label><br/>

			{/foreach}<br/>
			<small>IMPORTANT: Please read the {newwin href="/article/Image-Buckets" title="Article about Buckets" text="Buckets Article"} before picking from this list</small>


		</div>
	</div>
	</div>

		<div class="img-shadow" style="clear:both; position:relative;"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'src=':"name=image`$smarty.foreach.results.iteration` lowsrc="}</a></div>
		{if $image->comment}
		  <div class="caption">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
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
setTimeout("document.images['image1'].src = document.images['image1'].lowsrc",300);
setTimeout("document.images['image2'].src = document.images['image2'].lowsrc",600);
{dynamic}
var delayinsec = {$user->slideshow_delay|default:5};
{/dynamic}
{literal}
if (window.location.hash == '#autonext') {
	setTimeout("auto_slide_go(1)",500);
} else if (window.location.hash == '#nonext') {
	hasnextpage = 0;
}
{/literal}
 //]]></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>

 		<div style="text-align:center;padding-top:40px"><a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slidebig">Full Page Slide-Show</a></div>
 	<br style="clear:both"/>
	<p>Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
