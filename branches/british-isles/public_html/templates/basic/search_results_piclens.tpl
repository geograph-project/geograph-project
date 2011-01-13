{if $engine->currentPage > 1}{assign var="rss_url" value="/feed/results/`$i`/`$engine->currentPage`.media"}{else}{assign var="rss_url" value="/feed/results/`$i`.media"}{/if}
{include file="_search_begin.tpl"}
{literal}
<style type="text/css">
	.mbf-item { display: none; }
</style>
{/literal}
{if $engine->resultCount}
	<script type="text/javascript" 	src="http://lite.piclens.com/current/piclens.js"></script>

	<p align="center"><a href="javascript:PicLensLite.start();">Start PicLens Slide Show <img src="http://lite.piclens.com/images/PicLensButton.png" alt="PicLens" width="16" height="12" border="0" align="absmiddle"></a></p>

	<div>
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	  <div style="float:left;position:relative; width:130px; height:160px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}<span class="mbf-item">#gallery http://{$http_host}/photo/{$image->gridimage_id}</span></a></div>
		<div style="text-align:center; width:130px; font-size:0.7em"><a href="/ecard.php?image={$image->gridimage_id}">eCard</a> <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
	  </div>

	{foreachelse}
		{if $engine->resultCount}
			<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
		{/if}
	{/foreach}
	<div style="position:relative;clear:both"/>
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	Marked Images<span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={$engine->criteria->displayclass}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>
	</div>

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
