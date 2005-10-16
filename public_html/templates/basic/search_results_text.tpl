
{include file="_std_begin.tpl"}

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->pageOneOnly}
	<i>many</i> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
{if $nofirstmatch}
<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
{/if}
	<ul>
	{foreach from=$engine->results item=image}
	<li>
	<a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> 
	  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
	  by <a title="view user profile" href="/profile.php?u={$image->user_id}">{$image->realname}</a>
	 

	  <i>{$image->dist_string}</i></li>
	
	{/foreach}
	</ul>
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
{else}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
{/if}

{if $engine->criteria->searchclass != 'Special'}
[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
<p align=right><a title="Google Earth Feed for images{$engine->criteria->searchdesc}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-kml">KML</a> <a title="RSS Feed for images{$engine->criteria->searchdesc}" href="/syndicator.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-rss">RSS</a></p>	
	
{include file="_std_end.tpl"}
