
{include file="_std_begin.tpl"}

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->islimited}
<b>{$engine->resultCount}</b>
{else}
the following
{/if} images:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) [<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]
	</p>

	{foreach from=$engine->results item=image}
	  <!-- //todo switch to css layout -->
	  <table width="100%">
	  <tr><td align="center" width="120">
	  <a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
	</td><td>
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a> <br/>
	  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>

	  <i>{$image->dist_string}</i>
	</td></tr></table>


	{/foreach}

	<p>( Page {$engine->pagesString()})
{/if}
[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]</p>
		
{include file="_std_end.tpl"}
