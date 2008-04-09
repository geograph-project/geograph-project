{assign var="page_title" value="Grid Square Discussions Search Results"}
{include file="_std_begin.tpl"}

<h2>Grid Square Discussions Search Results</h2>
{dynamic}
<p>Your search for discussions<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
<b>{$engine->resultCount}</b> results:
{if $engine->resultCount}
	<br/>( Page {$engine->pagesString()}) [<a href="/discuss/search.php?i={$i}&amp;form=simple">search again</a>]
	</p>

<p align="center"><i>perform text search for <a href="/discuss/index.php?action=search&searchForum=0&days=60&searchWhere=0&searchHow=0&searchFor=+{$engine->criteria->searchq|escape:"url"}+&go=Find">{$engine->criteria->searchq|escape:"html"}</a></i></p>

	{foreach from=$engine->results item=image}
	  <div style="clear:both">
		
	  <a title="view full size image" href="/discuss/index.php?action=vthread&amp;topic={$image.topic_id}">{$image.topic_title|escape:'html'}</a> ({$image.posts_count} Posts)
		topic started by <a title="view user profile for {$image.realname}" href="/profile/{$image.user_id}">{$image.nickname}</a> at {$image.topic_time}<br/>
	  for square <a title="view page for {$image.grid_reference}" href="/gridref/{$image.grid_reference}">{$image.grid_reference}</a> 

	  <i>{$image.dist_string}</i><br/><br/>
	  
	</div>


	{/foreach}

	<p style="clear:both">( Page {$engine->pagesString()})
{/if}
</p>
{/dynamic}		
{include file="_std_end.tpl"}
