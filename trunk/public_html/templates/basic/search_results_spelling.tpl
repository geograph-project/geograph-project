{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
	{foreach from=$engine->results item=image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" target="editor" style="display:inline">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		<a name="{$image->gridimage_id}"><input type="text" name="title" size="80" value="{$image->title|escape:'html'}"/></a>
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}
		
		
		<div>{if $image->comment}<textarea name="comment" style="font-size:0.9em;" rows="4" cols="70">{$image->comment|escape:'html'}</textarea>{/if}<input type="submit" name="create" value="create ticket"/></div>

	  </div><br style="clear:both;"/>
	  </form><br/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
