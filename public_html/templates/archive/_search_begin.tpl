{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">

<h2>Search Results</h2>


<p>Your search for images<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount|number_format}</b> images
{else}
	the following
{/if}{/if}:

{if $engine->fullText && $engine->numberOfPages eq $engine->currentPage && $engine->criteria->sphinx.compatible && $engine->criteria->sphinx.compatible_order && $engine->resultCount > $engine->maxResults}
	<div class="interestBox" style="border:1px solid pink;">
		You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However your search seems to be compatible with the lagacy engine. You can <a href="/search.php?i={$i}&amp;legacy=true&amp;page={$engine->currentPage+1}">view the next page in Legacy Mode</a> to continue. <b>Note, searches will be slower.</b>
	</div>
	
{elseif $engine->fullText && (!$engine->criteria->sphinx.compatible || $engine->criteria->sphinx.no_legacy)}


{elseif strlen($engine->criteria->searchtext) && $engine->criteria->sphinx.impossible}
	<div style="padding:2px;border:1px solid gray; font-size:0.7em;text-align:center">You have dropped back into <a href="/help/search_new">legacy search mode</a>, the search options you have selected are not supported in the new search,<br/> you can try simplifing the choosen options to change mode.
	
	{if $engine->criteria->sphinx.no_legacy}
	<br/><br/>
		<b>However legacy is not able to support this query</b> - please <a href="/contact.php">let us know</a>.
	{elseif strpos($engine->criteria->searchtext,' ')}
	<br/><br/>
		Note: <b>The <a href="/help/search_new">text matching method</a> is different</b>. So the results might not be what you expect.
	{/if}
	</div>
{elseif $legacy && $engine->criteria->sphinx.no_legacy}
	<div style="padding:2px;border:1px solid red; text-align:center">
		This query is not supported in Legacy Mode, try in the <a href="/search.php?i={$i}"> new interface</a>
	</div>
{/if}

{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) {if $engine->criteria->searchclass != 'Special'}[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}
{/if}


	</p>

	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for <a href="/gridref/{$engine->criteria->searchq|escape:"html"}">{$engine->criteria->searchq|escape:"html"}</a>, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}
