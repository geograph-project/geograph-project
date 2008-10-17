{include file="_search_begin.tpl"}

{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	 <div style="border-top: 1px solid lightgrey; padding-top:1px;">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	  </div>
	  <div style="float:left; position:relative">
		
		{if $image->cluster} 
			<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->simple_title|escape:'html'}</b></a>
			by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
			<i>multiple</i> images for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a><br/>
			<div style="position:relative; width:600px; height:80px; overflow:auto" class="nowrap">
			{foreach from=$image->cluster item=im}
				<a title="{$im->title|escape:'html'} - click to view full size image" href="/photo/{$im->gridimage_id}">{$im->getThumbnail(120,120)|regex_replace:'/"(\d+)"/e':'"\'".($1/2)."\'"'}</a> 
	
			{/foreach}
			</div>
		{else}
			<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
			by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
			{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
			<i>{$image->dist_string}</i><br/>
			{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
			{if $image->imageclass}<small>Category: {$image->imageclass}</small>{/if}

			{if $image->comment}
			<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:90:"... (<u>more</u>)"|geographlinks}</div>
			{/if}
		{/if}
	  </div><br style="clear:both;"/>
	 </div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
