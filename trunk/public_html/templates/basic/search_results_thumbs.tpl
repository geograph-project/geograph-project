
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
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
	</p>
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
	<div>
	{foreach from=$engine->results item=image}
	
	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>

	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}">continue to results &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	<br style="clear:both"/>
	</div>
	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq}, <a href="/submit.php?gridreference={$engine->criteria->searchq}">Submit Yours Now</a>]</p>
	{/if}
{/if}

{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
<p align=right><a title="Google Earth Feed for images{$engine->criteria->searchdesc}" href="/kml.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-kml">KML</a> <a title="RSS Feed for images{$engine->criteria->searchdesc}" href="/syndicator.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}" class="xml-rss">RSS</a></p>	
	
{include file="_std_end.tpl"}
