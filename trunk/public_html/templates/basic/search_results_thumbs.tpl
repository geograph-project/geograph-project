
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
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
	<div>
	{foreach from=$engine->results item=image}
	
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a></div>
	  </div>

	{/foreach}
	<br style="clear:both"/>
	</div>
	<p>( Page {$engine->pagesString()})
{else}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
{/if}
[<a href="search.php?i={$i}&amp;form=advanced">refine search</a>]</p>
{/dynamic}		
{include file="_std_end.tpl"}
