{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''"/></a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		
		<div>{if $image->comment}<textarea name="comment" style="font-size:0.9em;" rows="4" cols="70" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea>{/if}<input type="submit" name="create" value="create ticket"/></div>
		
	  </div><br style="clear:both;"/>
	  </form><br/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	
	<div style="position:relative">
	<br/><br/>
	<small style="padding:10px; background-color:#eeeeee;">Marked Images<span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)</small></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>
	
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
