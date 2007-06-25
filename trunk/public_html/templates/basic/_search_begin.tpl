{assign var="page_title" value="Search Results`$engine->criteria->searchdesc`"|escape:"html"}
{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">
{if $engine->resultCount}

{if strpos($engine->criteria->searchdesc,'with incomplete data') === FALSE}
	{assign var="sidebarclass" value="search"}
{else}
	{assign var="sidebarclass" value="searchtext"}
{/if}

<div style="float:right;position:relative">Sidebar: <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" target="_search" rel="nofollow">IE &amp; Firefox</a>, <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" rel="sidebar" rel="nofollow" title="Results">Opera</a></div>
{/if}

<h2>Search Results</h2>

<p>Your search for images<i>{$engine->criteria->searchdesc|escape:"html"}</i>, returns 
{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}
	<acronym title="to keep server load under control, we delay calculating the total">many</acronym> images
{else}{if $engine->islimited}
	<b>{$engine->resultCount}</b> images
{else}
	the following
{/if}{/if}: