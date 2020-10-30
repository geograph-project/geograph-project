{include file="_search_begin.tpl"}

{if $engine->resultCount}

	<div class="shadow">
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
	  <div style="float:left;position:relative; width:130px; height:160px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
		<div style="text-align:center; width:130px; font-size:0.7em"><a href="/ecard.php?image={$image->gridimage_id}">eCard</a> <a href="/editimage.php?id={$image->gridimage_id}">Edit</a> [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]{if $image->count}
		<br/>{$image->count|thousends} images in group
		{/if}</div>

	  </div>

	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}

	</div>

	{include file="_search_marked_footer.tpl"}

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}

        <script src="/preview.js.php"></script>
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
