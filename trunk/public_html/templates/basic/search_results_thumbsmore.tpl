{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"html"}">Submit Yours Now</a>]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
	<div>
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	  <div style="float:left;position:relative; width:130px; height:160px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
		<div style="text-align:center; width:130px; font-size:0.7em"><a href="/ecard.php?image={$image->gridimage_id}">eCard</a> <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
	  </div>

	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	<div style="position:relative;clear:both"/>
	<br/><br/>
	<small style="padding:10px; background-color:#eeeeee;">Marked Images<span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={$engine->criteria->displayclass}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)</small></div>
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
