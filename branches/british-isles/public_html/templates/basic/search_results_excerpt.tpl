{include file="_search_begin.tpl"}

{if $engine->resultCount}

	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$engine->results item=image}
	{searchbreak image=$image table=true}
		<tr>
			<td align="center" valign="top">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			</td>
			<td style="border-bottom:1px solid silver" valign="top">


	  {if $image->count}
	  	<div style="float:right;position:relative;width:130px;font-size:small;text-align:right">
	  		{$image->count|thousends} images in group
	  	</div>
	  {/if}

		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		
		{if $image->excerpt}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->excerpt}</div>
		{elseif $image->imageclass}<small>Category: {$image->imageclass}</small>
		{/if}

			</td>
		</tr>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	</table>

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
