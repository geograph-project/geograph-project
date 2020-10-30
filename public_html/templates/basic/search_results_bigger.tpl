{include file="_search_begin.tpl"}

{if $engine->resultCount}
	{assign var="thumbw" value="213"}
	{assign var="thumbh" value="160"}

<style>{literal}
.gridded.med {
    display: grid;
    grid-template-columns: repeat(auto-fit, 223px);
    grid-gap: 18px;
    grid-row-gap: 20px;
}
.gridded > div {
	text-align:center;
	float:left; /* ignored in grid, but to support older browsers! */
	width:233px;
}
{/literal}</style>

	
	<div class="gridded med">
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
				<div class="shadow"><div style="height:{$thumbh+8}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
				<div class="caption"><div class="minheightprop" style="height:2.5em"></div>{if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
				
				<div class="statuscaption" style=float:right>[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>

				<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
				</div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	<br style="clear:both"/>
	</div>

	{include file="_search_marked_footer.tpl"}

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}


{include file="_search_end.tpl"}
