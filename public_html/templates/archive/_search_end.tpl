
{if $engine->criteria->searchclass != 'Special'}
[<a href="/search.php?i={$i}&amp;form=advanced">refine search</a>]{/if}</p>
	
{if $engine->resultCount}

	{if $engine->fullText && $engine->numberOfPages eq $engine->currentPage && $engine->criteria->sphinx.compatible && $engine->criteria->sphinx.compatible_order && $engine->resultCount > $engine->maxResults}
		<div class="interestBox" style="border:1px solid pink;">
			You have reached the last page of results, this is due to the fact that the new search engine will only return at most {$engine->maxResults|number_format} results. However your search seems to be compatible with the lagacy engine. You can <a href="/search.php?i={$i}&amp;legacy=true&amp;page={$engine->currentPage+1}">view the next page in Legacy Mode</a> to continue. <b>Note, searches will be slower.</b>
		</div>
	{/if}

{/if}

</div>

<br style="clear:both"/>

{if $statistics} 
	<a href="javascript:void(show_tree(2));" id="hide2">Expand Word Statistics</a>
	<div style="font-size:0.8em; display:none; margin-left:20px" id="show2"><b>Word Match statistics</b>
	<ul>
	{foreach from=$statistics key=word item=row}
		<li><b>{$word}</b> <small>{$row.docs} images, {$row.hits} hits</small></li>
	{/foreach}
	</ul>
	
	<p>Note, these are the raw words sent to the query engine, which are used to form the base query. There is post-filtering to make the results match your query as closely as possible which is why these terms can seem very broad.</p>
	<a href="javascript:void(hide_tree(2));">close</a></div>

{/if}



{include file="_std_end.tpl"}