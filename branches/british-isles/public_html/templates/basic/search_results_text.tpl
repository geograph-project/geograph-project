{include file="_search_begin.tpl"}

{if $engine->resultCount}

	<ul>
	{foreach from=$engine->results item=image}
	{searchbreak image=$image ul=true}
	<li onmouseover="this.style.background='#efefef'" onmouseout="this.style.background=''" style="padding:2px">
	  {if $image->count}
	  	<div style="float:right;position:relative;width:130px;font-size:small;text-align:right">
	  		{$image->count|thousends} images in group
	  	</div>
	  {/if}
	<a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> 
	  <a title="{$image->comment|escape:"html"}" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
	 

	  <i>{$image->dist_string}</i></li>
	
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	</ul>
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
