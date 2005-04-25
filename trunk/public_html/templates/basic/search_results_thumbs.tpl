
{include file="_std_begin.tpl"}

<h2>Search Results</h2>
{dynamic}
<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->islimited}
<b>{$engine->resultCount}</b> images
{else}
the following
{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) [<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]
	</p>
	<div>
	{foreach from=$engine->results item=image}
	
	  <div style="float:left;position:relative">
	  <a title="{$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a></div>

	{/foreach}
	<br style="clear:both"/>
	</div>
	<p>( Page {$engine->pagesString()})
{/if}
[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]</p>
{/dynamic}		
{include file="_std_end.tpl"}
