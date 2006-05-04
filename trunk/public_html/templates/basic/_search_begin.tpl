{assign var="page_title" value="Search Results`$engine->criteria->searchdesc`"}
{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">
{if $engine->resultCount}
<div style="float:right;position:relative"><a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=search" target="_search">Open in Sidebar</a></div>
{/if}

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}: