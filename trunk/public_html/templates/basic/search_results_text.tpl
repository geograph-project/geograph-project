
{include file="_std_begin.tpl"}

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->islimited}
<b>{$engine->resultCount}</b> images
{else}
the following
{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) [<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]
	</p>
	<ul>
	{foreach from=$engine->results item=image}
	<li>
	<a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> 
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>
	 

	  <i>{$image->dist_string}</i></li>
	
	{/foreach}
	</ul>
	<p>( Page {$engine->pagesString()})
{/if}
[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]</p>
		
{include file="_std_end.tpl"}
