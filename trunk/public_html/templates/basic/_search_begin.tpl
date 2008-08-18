{assign var="page_title" value="Search Results`$engine->criteria->searchdesc`"|escape:"html"}
{include file="_std_begin.tpl"}

<div style="padding:10px;" class="searchresults">
{if $engine->resultCount}

{if strpos($engine->criteria->searchdesc,'with incomplete data') === FALSE}
	{assign var="sidebarclass" value="search"}
{else}
	{assign var="sidebarclass" value="searchtext"}
{/if}

<div style="float:right;position:relative; font-size:0.9em">Sidebar for:
<a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" target="_search" rel="nofollow">IE &amp; Firefox</a>, <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass={$sidebarclass}" rel="sidebar" rel="nofollow" title="Results">Opera</a>.
Display: 
<form action="/search.php" method="get" style="display:inline">
<input type="hidden" name="i" value="{$i}"/>
{if $engine->currentPage > 1}<input type="hidden" name="page" value="{$engine->currentPage}"/>{/if}
<select name="displayclass" size="1" onchange="this.form.submit()" style="font-size:0.9em"> 
	{html_options options=$displayclasses selected=$engine->criteria->displayclass}
</select>
<noscript>
<input type="submit" value="Update"/>
</noscript>
</form>

</div>
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

{if $engine->fullText}
	<div style="padding:20px;border:1px solid pink"><b>Not seeing the results you expect?</b> This search is powered by the new <a href="/help/search_new">experimental Full-Text search index</a>, which is less precise than the legacy search, but often results in quicker and more relevent results. You can access the <a href="/search.php?i={$i}&amp;legacy=true">old search here</a>.</div>
	
	{if $engine->criteria->isallsearch}
		<p>Note: The new engine defaults to searching the whole entry (not just the title like in legacy), so + has no effect, to search just in the title prefix the word with "title:"</p>
	{/if}
	{if $engine->criteria->changeindefault}
		<p>Note: The new engine defaults to searching the whole entry (not just the title like in legacy), to search just in the title prefix the word with "title:"</p>
	{/if}
{else $engine->criteria->searchtext && $engine->criteria->sphinx.impossible}
	<div style="padding:2px;border:1px solid gray; font-size:0.7em;text-align:center">You have dropped back into <a href="/help/search_new">legacy search mode</a>, the search options you have selected are not supported in the new search,<br/> you can try simplifing the choosen options to change mode.</div>
{/if}